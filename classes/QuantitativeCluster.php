<?php

class QuantitativeCluster {

    private $_start;
    private $_end;
    private $_length;
    private $_peptides = array();
    private $_samples_area = array();
    private $_analysis_area = array();
    private $_normalized_analysis_area = array();

    function __construct(Peptide $peptide) {
        $this->_start = $peptide->getFirstPAa();
        $this->_end = $peptide->getLastPAa();
        $this->_length = $this->_end - $this->_start + 1;
        $this->_peptides[] = $peptide;
    }

    public function compareClusterPosition($peptide) {
        if (($this->_start >= $peptide->getFirstPAa() && $this->_start <= $peptide->getLastPAa()) || ($this->_end >= $peptide->getFirstPAa() && $this->_end <= $peptide->getLastPAa())) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function addPeptide($peptide) {
        $this->_peptides[] = $peptide;
    }

    public function findSimilarPeptide($peptide, $mz_diff, $rt_diff) {
        $return_rt_peptide = NULL;
        $return_mz_peptide = NULL;
        $small_rt_diff = PHP_INT_MAX;
        $small_mz_diff = PHP_INT_MAX;

        foreach ($this->_peptides as $cluster_peptide) {
            if ($peptide->getSampleId() == $cluster_peptide->getSampleId()) {
                $rt_difference = abs($peptide->getRt() - $cluster_peptide->getRt());
                $mz_difference = abs($peptide->getMz() - $cluster_peptide->getMz());
                if ($rt_difference < $small_rt_diff && $rt_difference < $rt_diff) {
                    $small_rt_diff = $rt_difference;
                    $return_rt_peptide = $cluster_peptide;
                }
                if ($mz_difference < $small_mz_diff && $mz_difference < $mz_diff) {
                    $small_mz_diff = $mz_difference;
                    $return_mz_peptide = $cluster_peptide;
                }
            }
        }
        echo "rt :";
        print_r($return_mz_peptide);
        echo "mz :";
        print_r($return_rt_peptide);

        if ($small_mz_diff / ($mz_diff / 100) < $small_rt_diff / ($rt_diff / 100)) {
            return $return_mz_peptide;
        } else {
            return $return_rt_peptide;
        }
    }

    public function switchPeptide($peptide_old, $peptide_new) {
        unset($this->_peptides[array_search($peptide_old, $this->_peptides)]);
        $this->_peptides[] = $peptide_new;
    }

    public function sortPeptides() {
        usort($this->_peptides, 'Peptide::comparePosition');
        return TRUE;
    }

    public function update() {
        $this->_start = PHP_INT_MAX;
        $this->_end = 0;

        foreach ($this->_peptides as $pep) {
            if ($this->_start > $pep->getFirstPAa()) {
                $this->_start = $pep->getFirstPAa();
            } if ($this->_end < $pep->getLastPAa()) {
                $this->_end = $pep->getLastPAa();
            }
        }

        $this->_length = $this->_end - $this->_start + 1;

        if ($this->_start == 10000 && $this->_end == 0) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function computeSamplesArea($standards, $id) {
        foreach ($this->_peptides as $peptide) {
            $standard_norm_error = $standards[$peptide->getSampleId()];
            if (array_key_exists($peptide->getSampleId(), $this->_samples_area)) {
                $this->_samples_area[$peptide->getSampleId()] += $peptide->getArea() / $standard_norm_error;
            } else {
                $this->_samples_area[$peptide->getSampleId()] = $peptide->getArea() / $standard_norm_error;
            }
        }

        $max_sample_no = max(array_keys($standards));
        
        
        for ($i = 0; $i < $id; $i++) {
            if (!array_key_exists($i, $this->_samples_area) && array_key_exists($i, $standards)) {
                $this->_samples_area[$i] = (float) 1 / $standards[$i];
            }
        }

        return $this->_samples_area;
    }

    public function computeAnalysisArea($unmodif_peptides, $analysis_sample_assignment) {
        foreach ($analysis_sample_assignment as $key1 => $assign) {
            foreach ($this->_samples_area as $key2 => $sample_area) {
                if (in_array($key2, $assign)) {
                    if (!isset($this->_analysis_area[$key1])) {
                        $this->_analysis_area[$key1] = $sample_area / $unmodif_peptides[$key1];
                    } else {
                        $this->_analysis_area[$key1] += $sample_area / $unmodif_peptides[$key1];
                    }
                }
            }
        }

        return $this->_analysis_area;
    }
    
    public function normalizeAnalysisArea() {
        foreach($this->_analysis_area as $key => $area) {
            $this->_normalized_analysis_area[$key] = $area/max($this->_analysis_area);
        }

        return $this->_normalized_analysis_area;
    }

    public function sameCluster(QuantitativeCluster $cluster2) {
        if (($this->_start >= $cluster2->_start && $this->_start <= $cluster2->_end) || ($this->_end >= $cluster2->_start && $this->_end <= $cluster2->_end)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function mergeCluster(QuantitativeCluster $cluster, $rt_diff, $mz_diff) {
        $return = 0;
        foreach ($cluster->getPeptides() as $peptide1) {
            $result = $this->addPeptide($peptide1, $rt_diff, $mz_diff);
            $this->update();
            if ($result > 0) {
                $return++;
            }
        }
        return $return;
    }

    public function refineCluster($mz_diff, $rt_diff) {
        foreach ($this->_peptides as $key1 => &$peptide1) {
            foreach ($this->_peptides as $key2 => &$peptide2) {
                if ($peptide1->isSimilarClusterPeptide($peptide2, $mz_diff, $rt_diff) && $peptide1 != $peptide2) {
                    if ($peptide1->getArea() >= $peptide2->getArea()) {
                        unset($this->_peptides[$key2]);
                    } else {
                        unset($this->_peptides[$key1]);
                        continue 2;
                    }
                }
            }
        }
    }

    public function sortClusterPeptides() {
        usort($this->_peptides, "Peptide::compareClusterPeptide");
    }

    public function __toString() {
        return $this->_start . "\t" . $this->_length . "\t" . $this->_sequence . "<br />" . $this->_peptides . "<br /><br />";
    }

    public function getSequence() {
        return $this->_sequence;
    }

    public function setSequence($sequence) {
        $this->_sequence = $sequence;
    }

    public function getLength() {
        return $this->_length;
    }

    public function setLength($length) {
        $this->_length = $length;
    }

    public function getStart() {
        return $this->_start;
    }

    public function getEnd() {
        return $this->_end;
    }

    public function setStart($start) {
        $this->_start = $start;
    }

    public function setEnd($end) {
        $this->_end = $end;
    }

    public function getPeptides() {
        return $this->_peptides;
    }

    public function setPeptides($peptides) {
        $this->_peptides = $peptides;
    }

    public function getSamplesArea() {
        return $this->_samples_area;
    }

    public function setSamplesArea($samples_area) {
        $this->_samples_area = $samples_area;
    }

    public function getAnalysisArea() {
        return $this->_analysis_area;
    }

    public function setAnalysisArea($analysis_area) {
        $this->_analysis_area = $analysis_area;
    }

    public function getNormalizedAnalysisArea() {
        return $this->_normalized_analysis_area;
    }

    public function setNormalizedAnalysisArea($normalized_analysis_area) {
        $this->_normalized_analysis_area = $normalized_analysis_area;
    }


    
}

?>
