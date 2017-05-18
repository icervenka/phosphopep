<?php

class Analysis {

    private $_name;
    private $_protein_samples = array();
    private $_standards_acc = array();
    private $_unmodif_peptides = array();
    private $_phos_summary = array();
    private $_quality_count;
    private $_quantity_count;

    public function __construct($name) {
        $this->_name = $name;

        for ($i = 782; $i <= 806; $i++) {
            $this->_standards_acc[] = $i;
        }
    }

    public function __toString() {
        $string = "";
        $string .= $this->_name . "<br />";
        $string .= $this->_mz_diff . " " . $this->_rt_diff . "<br />";
        foreach ($this->_protein_samples as $sample) {
            $string .= $sample . "<br >";
        }
        return $string;
    }

    public function addProteinSample(Sample $prot) {
        $this->_protein_samples[] = $prot;
        return TRUE;
    }

    public function addUnmodifPeptide(Peptide $peptide) {
        if (preg_match("/S/", $peptide->getSequence()) || preg_match("/T/", $peptide->getSequence()) || preg_match("/Y/", $peptide->getSequence()) || $peptide->getArea() <= 0) {
            return FALSE;
        } else {
            if (empty($this->_unmodif_peptides)) {
                $this->_unmodif_peptides[] = $peptide;
            } else {
                $count = 0;
                foreach ($this->_unmodif_peptides as $key => $pep) {
                    if ($peptide->getSequence() == $pep->getSequence()) {
                        if ($peptide->getArea() >= $pep->getArea()) {
                            unset($this->_unmodif_peptides[$key]);
                            $this->_unmodif_peptides[] = $peptide;
                            break;
                        }
                    } else {
                        $count++;
                    }
                }
                if ($count == count($this->_unmodif_peptides)) {
                    $this->_unmodif_peptides[] = $peptide;
                }
            }

            uasort($this->_unmodif_peptides, function($a, $b) {
                return ($a->getArea() > $b->getArea()) ? -1 : 1;
            });
            return TRUE;
        }
    }

    public function generatePhosSummary($type) {
        foreach ($this->_protein_samples as $sample) {
            //if($sample->getType() == $type) {
                foreach ($sample->getPhosphorylatedAa() as $key => $phos_aa_array) {
                    if (empty($this->_phos_summary[$key]['max_prob'])) {
                        $this->_phos_summary[$key]['max_prob'] = max($phos_aa_array);
                    } else {
                        if ($this->_phos_summary[$key]['max_prob'] < max($phos_aa_array)) {

                            $this->_phos_summary[$key]['max_prob'] = max($phos_aa_array);
                        }
                    }

                    $phos_aa_count = count($phos_aa_array);

                    if (empty($this->_phos_summary[$key]['tot_prob'])) {
                        $this->_phos_summary[$key]['tot_prob'] = $phos_aa_count;
                    } else {
                        $this->_phos_summary[$key]['tot_prob'] += $phos_aa_count;
                    }
                }
            //}
        }

        ksort($this->_phos_summary);

        return TRUE;
    }

    function removeCorruptPeptides() {
        $count = 0;
        foreach ($this->_unmodif_peptides as $key => $peptide) {
            if ($peptide->getPosition() == NULL) {
                unset($this->_unmodif_peptides[$key]);
                $count++;
            }
        }

        return $count;
    }
    
    public function getName() {
        return $this->_name;
    }

    public function setName($name) {
        $this->_name = $name;
    }

    public function getProteinSamples() {
        return $this->_protein_samples;
    }

    public function setProteinSamples($protein_samples) {
        $this->_protein_samples = $protein_samples;
    }

    public function getUnmodifPeptides() {
        return $this->_unmodif_peptides;
    }

    public function setUnmodifPeptides($unmodif_peptides) {
        $this->_unmodif_peptides = $unmodif_peptides;
    }

    public function getStandardsAcc() {
        return $this->_standards_acc;
    }

    public function setStandardsAcc($standards) {
        $this->_standards_acc = $standards;
    }

    public function getPhosSummary() {
        return $this->_phos_summary;
    }

    public function setPhosSummary($phos_summary) {
        $this->_phos_summary = $phos_summary;
    }

}

?>
