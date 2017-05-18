<?php

class Peptide {

    private $_sequence;
    private $_group_acc;
    //private $_group_acc = array();
    private $_phos = array();
    private $_area;
    private $_ion_score;
    private $_mz;
    private $_rt;
    private $_first_scan;
    private $_spectrum_file;
    private $_analysis_name;
    private $_sample_id;
    private $_length;
    private $_position;
    private $_first_p_aa;
    private $_last_p_aa;
    private $_p_aa_count;

    public function __construct($peptide_data, $parent_sequence, $analysis_name, $sample_id, $phos_threshold) {
        $this->_sequence = strtoupper(trim((string) $peptide_data[0]));
        $this->_sequence = preg_replace('/\n/', "", $this->_sequence);
        $this->_sequence = preg_replace('/\s*/', "", $this->_sequence);

        $this->_group_acc = $peptide_data[1];

        if (!((float) $peptide_data[3] > 0)) {
            $this->_area = 0;
        } else {
            $this->_area = (float) $peptide_data[3];
        }

        $this->_ion_score = $peptide_data[4];
        $this->_mz = $peptide_data[5];
        $this->_rt = $peptide_data[6];
        $this->_first_scan = $peptide_data[7];
        $this->_spectrum_file = $peptide_data[8];
        $this->_analysis_name = $analysis_name;
        $this->_sample_id = $sample_id;

        $this->_length = strlen($peptide_data[0]);

        $align = array();
        $phospho_extract = array();
        $sum = 0;

        $pattern = "/" . $this->_sequence . "/";
        if (preg_match($pattern, $parent_sequence, $align, PREG_OFFSET_CAPTURE)) {
            $this->_position = $align[0][1] + 1;
        }

        if (strlen($peptide_data[2]) > 0) {
            if (!empty($peptide_data[2]) && $peptide_data[2] != "Too many isoforms" && "Too many NL-allowing PTMs") {

                $phospho = str_getcsv($peptide_data[2], ";");
                foreach ($phospho as $item) {
                    $preg_result = preg_match('/(\d+)\):\s*(\d+\.\d+)/', $item, $phospho_extract);
                    if ($preg_result == 1) {
                        unset($phospho_extract[0]);
                        $phospho_extract = array_values($phospho_extract);
                        if ($phospho_extract[1] > 0) {
                            $this->_phos[$phospho_extract[0]] = $phospho_extract[1];
                        }
                    }
                }
            }
        }

        $paa = array();

        if (!empty($this->_phos)) {
            foreach ($this->_phos as $key => $item) {
                if ($item >= $phos_threshold) {
                    $paa[] = $key;
                }
            }

            ksort($paa);
            reset($paa);
            $this->_first_p_aa = current($paa) + $this->_position - 1;
            end($paa);
            $this->_last_p_aa = current($paa) + $this->_position - 1;
        } else {
            $this->_first_p_aa = NULL;
            $this->_last_p_aa = NULL;
        }



        foreach ($this->_phos as $item) {
            $sum += $item;
        }
        $this->_p_aa_count = round($sum / 100);
    }

    public function isPhospho() {
        if (!empty($this->_phos)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function isStandard($standards) {
        if (in_array($this->_group_acc, $standards)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function compareMzRt($peptide2, $rt_diff, $mz_diff) {
        if (abs($peptide2->getRt() - $this->_rt) > $rt_diff || abs($peptide2->getMz() - $this->_mz) > $mz_diff) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function compareSequence($peptide2) {
        if ($this->_sequence == $peptide2->getSequence()) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function comparePosition(Peptide $peptide1, Peptide $peptide2) {
        if ($peptide1->getPosition() == $peptide2->getPosition()) {
            return 0;
        } else if ($peptide1->getPosition() > $peptide2->getPosition()) {
            return -1;
        } else {
            return 1;
        }
    }

    public function compareClusterPeptide(Peptide $peptide1, Peptide $peptide2) {
        if ($peptide1->getRt() > $peptide2->getRt()) {
            return 1;
        } else if ($peptide1->getRt() < $peptide2->getRt()) {
            return -1;
        } else {
            if ($peptide1->getArea() > $peptide2->getArea()) {
                return 1;
            } else if ($peptide1->getArea() < $peptide2->getArea()) {
                return -1;
            } else {
                return 0;
            }
        }
    }

    public function isSimilarClusterPeptide($peptide2, $mz_diff, $rt_diff) {
        if ($this->getSampleId() == $peptide2->getSampleId() && $this->compareSequence($peptide2) && $this->compareMzRt($peptide2, $rt_diff, $mz_diff)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function reportPhosphoPeptide() {
        if ($this->isPhospho()) {
            return $this->__toString();
        } else {
            return FALSE;
        }
    }

    public function __toString() {
        $sequence = $this->_sequence;
        $phosphorylations = array();
        foreach ($this->_phos as $key => $phos) {
            $phosphorylations[] = $sequence[$key - 1] . ((int) $key + $this->_position - 1);
            $sequence[$key - 1] = strtolower($sequence[$key - 1]);
        }

        $string = $sequence . "\t";

        foreach ($phosphorylations as $phospho) {
            $string .= $phospho . ", ";
        }

        $string = substr($string, 0, -2);
        $string .= "\t";

        $string .= $this->_mz . "\t" . $this->_rt . "\t" . $this->_ion_score . "\t" . $this->_area . "\t" . $this->_p_aa_count . "\n";
        return $string;
    }

    public function getSequence() {
        return $this->_sequence;
    }

    public function setSequence($sequence) {
        $this->_sequence = $sequence;
    }

    public function getFirstPAa() {
        return $this->_first_p_aa;
    }

    public function getLastPAa() {
        return $this->_last_p_aa;
    }

    public function setFirstPAa($first_p_aa) {
        $this->_first_p_aa = $first_p_aa;
    }

    public function setLastPAa($last_p_aa) {
        $this->_last_p_aa = $last_p_aa;
    }

    public function getGroupAcc() {
        return $this->_group_acc;
    }

    public function setGroupAcc($group_acc) {
        $this->_group_acc = $group_acc;
    }

    public function getPhos() {
        return $this->_phos;
    }

    public function setPhos($phos) {
        $this->_phos = $phos;
    }

    public function getArea() {
        return $this->_area;
    }

    public function setArea($area) {
        $this->_area = $area;
    }

    public function getIonScore() {
        return $this->_ion_score;
    }

    public function setIonScore($ion_score) {
        $this->_ion_score = $ion_score;
    }

    public function getMz() {
        return $this->_mz;
    }

    public function setMz($mz) {
        $this->_mz = $mz;
    }

    public function getRt() {
        return $this->_rt;
    }

    public function setRt($rt) {
        $this->_rt = $rt;
    }

    public function getFirstScan() {
        return $this->_first_scan;
    }

    public function setFirstScan($first_scan) {
        $this->_first_scan = $first_scan;
    }

    public function getSpectrumFile() {
        return $this->_spectrum_file;
    }

    public function setSpectrumFile($spectrum_file) {
        $this->_spectrum_file = $spectrum_file;
    }

    public function getLength() {
        return $this->_length;
    }

    public function setLength($length) {
        $this->_length = $length;
    }

    public function getPosition() {
        return $this->_position;
    }

    public function setPosition($position) {
        $this->_position = $position;
    }

    public function getAnalysisName() {
        return $this->_analysis_name;
    }

    public function setAnalysisName($analysis_name) {
        $this->_analysis_name = $analysis_name;
    }

    public function getSampleId() {
        return $this->_sample_id;
    }

    public function setSampleId($sample_id) {
        $this->_sample_id = $sample_id;
    }

    public function getPAaCount() {
        return $this->_p_aa_count;
    }

    public function setPAaCount($p_aa_count) {
        $this->_p_aa_count = $p_aa_count;
    }
    
    
}

?>
