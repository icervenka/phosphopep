<?php

class Parser {

    private $_psms_header = array();
    private $_header_index = array();
    private $_peptide_data = array();

    public function __construct($psms_header) {       
        $this->_psms_header = array_map('trim', $psms_header);
    }


    public function parseHeaderLine($line) {
        unset($this->_header_index);
        $data = str_getcsv($line, "\t");
        foreach ($this->_psms_header as $key => $item) {
            $index = array_search(strtolower($item), array_map('strtolower', $data));
            if($index != FALSE) {
                $this->_header_index[] = $index;
            }
        }
        if(count($this->_header_index) != count($this->_psms_header)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function parseDataLine($line) {
        unset($this->_peptide_data);
        $data = str_getcsv($line, "\t");

        for ($i = 0; $i < count($data); $i++) {
            if (in_array($i, $this->_header_index)) {
                $this->_peptide_data[] = $data[$i];
            }
        }

        if (isset($this->_peptide_data)) {
            return $this->_peptide_data;
        }
    }

    public function getHeaderIndex() {
        return $this->_header_index;
    }

    public function setHeaderIndex($header_index) {
        $this->_header_index = $header_index;
    }

    public function getHeaderName() {
        return $this->_psms_header;
    }

    public function setHeaderName($header_name) {
        $this->_psms_header = $header_name;
    }

    public function getPeptideData() {
        return $this->_peptide_data;
    }

    public function setPeptideData($peptide_data) {
        $this->_peptide_data = $peptide_data;
    }
    
    public function getPsmsHeader() {
        return $this->_psms_header;
    }



}

?>
