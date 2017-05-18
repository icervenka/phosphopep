<?php

class Sample {

    private $_id;
    private $_filename;
    private $_type;
    private $_ion_threshold;
    private $_phosphorylated_aa = array();
    private $_peptides = array();
    private $_standards = array();
    private $_coverage_sequence;
    private $_coverage_percentage;

    public function __construct($id, $filename, $type, $ion_threshold) {
        $this->_id = $id;
        $this->_filename = trim($filename);
        $this->_type = $type;
        $this->_ion_threshold = $ion_threshold;
    }

    public function addPeptide(Peptide $peptide, $prot_group_acc) {
        $found = 0;
        if($peptide->getIonScore() < $this->_ion_threshold || 
           //!preg_match('/' . $prot_group_acc . '/', $peptide->getGroupAcc()) ||
           $peptide->getArea() <= 0) {
            return FALSE;
        }

        $pep_group_acc_expolode = array();
        $pep_group_acc_expolode = explode(';', $peptide->getGroupAcc());

        foreach($pep_group_acc_expolode as $single_group_acc) {
        	$found += preg_match('/' . $single_group_acc . '/', $prot_group_acc);
        }
        
        if($found = 0) {return FALSE;}

        foreach ($this->_peptides as $item) {
            if ($peptide->getFirstScan() == $item->getFirstScan()) {
                return FALSE;
            }
        }

        $this->_peptides[] = $peptide;
        return TRUE;
    }

    public function addStandard(Peptide $peptide, $standards) {
        if(in_array($peptide->getGroupAcc(), $standards)) {
            foreach ($this->_standards as $item) {
                if ($peptide->getFirstScan() == $item->getFirstScan() || $peptide->getIonScore() < $this->_ion_threshold) {
                    return FALSE;
                }
            }
            $this->_standards[] = $peptide;
            return TRUE;
        } else {
            return FALSE;
        }    
    }

    function removeCorruptPeptides() {
        $count = 0;
        foreach ($this->_peptides as $key => $peptide) {
            if ($peptide->getPosition() == NULL) {
                unset($this->_peptides[$key]);
                $count++;
            }
        }

        return $count;
    }

    public function setCoverage($sequence) {
        $count = 0;
        $this->_coverage_sequence = $sequence;
        foreach ($this->_peptides as $item) {
            for ($i = 0; $i < $item->getLength(); $i++) {
                $this->_coverage_sequence[$item->getPosition() + $i - 1] = strtolower($this->_coverage_sequence[$item->getPosition() + $i - 1]);
            }
        }

        for ($j = 0; $j < strlen($this->_coverage_sequence); $j++) {
            if (ctype_lower($this->_coverage_sequence[$j])) {
                $count++;
            }
        }

        $this->_coverage_percentage = ($count / strlen($this->_coverage_sequence)) * 100;

        return TRUE;
    }

    public function generatePhosphorylatedAa($phos_threshold) {
        foreach ($this->_peptides as $peptide) {
            foreach ($peptide->getPhos() as $key => $phos) {
                if ($phos >= $phos_threshold) {
                    $this->_phosphorylated_aa[$peptide->getPosition() + $key - 1][] = $phos;
                }
            }
        }
    }

    public function sortPeptides() {
        usort($this->_peptides, 'Peptide::comparePosition');
        return TRUE;
    }

    public function sortPhosAa() {
        ksort($this->_phosphorylated_aa);
    }

    public function __toString() {
        $string = "Id: \t" . $this->_id . "\n" . "Name: \t" . $this->_filename . "\n" . "Coverage percentage: \t" . $this->_coverage_percentage . "\n" . "Coverage sequence: \t" . $this->_coverage_sequence . "\n";
        $string .= "Phosphorylation site\tPhosphorylation probabilities\tTotal probabilities\n";

        foreach ($this->_phosphorylated_aa as $key => $phos) {
            $string .= $key . " \t";
            foreach ($phos as $aa) {
                $string .= $aa . ", ";
            }
            //$string = substr($string, 0, -2);
            $string .= "\t" . count($phos);
            $string .= "\n";
        }

        return $string;
    }

    public function getId() {
        return $this->_id;
    }

    public function setId($id) {
        $this->_id = $id;
    }

    public function getName() {
        return $this->_filename;
    }

    public function setName($name) {
        $this->_filename = $name;
    }

    public function getStandards() {
        return $this->_standards;
    }

    public function setStandards($standards) {
        $this->_standards = $standards;
    }

    public function getIonThreshold() {
        return $this->_ion_threshold;
    }

    public function setIonThreshold($ion_threshold) {
        $this->_ion_threshold = $ion_threshold;
    }

    public function getPhosphorylatedAa() {
        return $this->_phosphorylated_aa;
    }

    public function setPhosphorylatedAa($phosphorylated_aa) {
        $this->_phosphorylated_aa = $phosphorylated_aa;
    }

    public function getPeptides() {
        return $this->_peptides;
    }

    public function setPeptides($peptides) {
        $this->_peptides = $peptides;
    }

    public function getCoverageSequence() {
        return $this->_coverage_sequence;
    }

    public function setCoverageSequence($coverage_sequence) {
        $this->_coverage_sequence = $coverage_sequence;
    }

    public function getCoveragePercentage() {
        return $this->_coverage_percentage;
    }

    public function setCoveragePercentage($coverage_percentage) {
        $this->_coverage_percentage = $coverage_percentage;
    }

    public function setType($type) {
        $this->_type = $type;
    }
    
    public function getType() {
        return $this->_type;
    }
    
    public function getFilename() {
        return $this->_filename;
    }

    public function setFilename($filename) {
        $this->_filename = $filename;
    }



}

?>
