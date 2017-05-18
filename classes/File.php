<?php

class File {
    
    public function __construct($filename, $path) {
        $this->_filename = $filename;
        $this->_path = $path;
    }
    
    public function export() {
        return array($this->_filename => $this->_path);
    }
    
    public function getFilename() {
        return $this->_filename;
    }
    
    public function getPath() {
        return $this->_path;
    }
    
}

class QualityFile extends File{
    
    public function __construct($filename, $path) {
        parent::__construct($filename, $path);
    }
}

class QuantityFile extends File{
    
    public function __construct($filename, $path) {
        parent::__construct($filename, $path);
    }
}

class FileContainer {
    
    private $_name;
    private $_files = array();
    
    public function __construct($name) {
        $this->_name = $name;
    }
    
    public function addFile(File $file) {
        $this->_files[] = $file;
    }
     
    public function export() {
       foreach($this->_files as $key => $file) {
           $return_array[get_class($file).$key] = $file->export();
       }
       
       return $return_array;
    }
    
    public function getName() {
        return $this->_name;
    }

    public function getFiles() {
        return $this->_files;
    }

    public function setName($name) {
        $this->_name = $name;
    }

    public function setFiles($files) {
        $this->_files = $files;
    }


}



?>

