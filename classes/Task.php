<?php

Class Task {

    private $_name;
    private $_sequence;
    private $_length;
    private $_prot_group_acc;
    private $_mz_diff;
    private $_rt_diff;
    private $_ion_threshold;
    private $_phos_threshold;
    private $_quantity_count;
    private $_standards_acc = array();
    private $_phosphorylable_aa = array();
    private $_phosphorylable_aa_count = 0;

    public function __construct($name, $prot_group_acc, $sequence, $mz_diff, $rt_diff, $ion_threshold, $phos_threshold) {
        $this->_name = $name;
        $this->_name = preg_replace('/\s+/', "_", $this->_name);

        $this->_sequence = preg_replace('/sp.*/', "", $this->_sequence);
        $this->_sequence = strtoupper(trim($sequence));
        $this->_sequence = preg_replace('/[^A-Z]/', "", $this->_sequence);
        $this->_prot_group_acc = $prot_group_acc;
        
        for ($i = 782; $i <= 806; $i++) {
            $this->_standards_acc[] = $i;
        }
        
        $this->_length = strlen($this->_sequence);

        $this->_mz_diff = $mz_diff;
        $this->_rt_diff = $rt_diff;
        $this->_ion_threshold = $ion_threshold;
        $this->_phos_threshold = $phos_threshold;
        
        $this->_quantity_count = 0;

        for ($i = 0; $i < strlen($this->_sequence); $i++) {
            if ($this->_sequence[$i] == 'S' || $this->_sequence[$i] == 'T' || $this->_sequence[$i] == 'Y') {
                $this->_phosphorylable_aa_count++;
            }
        }

        for ($i = 0; $i < strlen($this->_sequence); $i++) {
            if ($this->_sequence[$i] == 'S' || $this->_sequence[$i] == 'T' || $this->_sequence[$i] == 'Y') {
                $this->_phosphorylable_aa[$i + 1] = $this->_sequence[$i];
            }
        }
    }

    public function getName() {
        return $this->_name;
    }

    public function getSequence() {
        return $this->_sequence;
    }

    public function getMzDiff() {
        return $this->_mz_diff;
    }

    public function getRtDiff() {
        return $this->_rt_diff;
    }

    public function getProtGroupAcc() {
        return $this->_prot_group_acc;
    }

    public function getPhosphorylableAa() {
        return $this->_phosphorylable_aa;
    }

    public function getPhosphorylableAaCount() {
        return $this->_phosphorylable_aa_count;
    }

    public function setName($name) {
        $this->_name = $name;
    }

    public function setSequence($sequence) {
        $this->_sequence = $sequence;
    }

    public function setMzDiff($mz_diff) {
        $this->_mz_diff = $mz_diff;
    }

    public function setRtdiff($rt_diff) {
        $this->_rt_diff = $rt_diff;
    }

    public function setProtGroupAcc($prot_group_acc) {
        $this->_prot_group_acc = $prot_group_acc;
    }

    public function setPhosphorylableAa($phosphorylable_aa) {
        $this->_phosphorylable_aa = $phosphorylable_aa;
    }

    public function setPhosphorylableAaCount($phosphorylable_aa_count) {
        $this->_phosphorylable_aa_count = $phosphorylable_aa_count;
    }

    public function getIonThreshold() {
        return $this->_ion_threshold;
    }

    public function getPhosThreshold() {
        return $this->_phos_threshold;
    }

    public function setIonThreshold($ion_threshold) {
        $this->_ion_threshold = $ion_threshold;
    }

    public function setPhosThreshold($phos_threshold) {
        $this->_phos_threshold = $phos_threshold;
    }

    public function getLength() {
        return $this->_length;
    }

    public function setLength($length) {
        $this->_length = $length;
    }
    
    public function getStandardsAcc() {
        return $this->_standards_acc;
    }

    public function setStandardsAcc($standards_acc) {
        $this->_standards_acc = $standards_acc;
    }

    public function getQuantityCount() {
        return $this->_quantity_count;
    }

    public function setQuantityCount($quantity_count) {
        $this->_quantity_count = $quantity_count;
    }

}
?>
