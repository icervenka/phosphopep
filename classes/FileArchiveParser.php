<?php

Class FileArchiveParser {
    
    static private $_path = "source/";
    private $_name;
    private $_quality_identifier;
    private $_quantity_identifier;
    
    public function __construct($name, $quality_identifier, $quantity_identifier) {
        $this->_name = $name;
        $this->_quality_identifier = $quality_identifier;
        $this->_quantity_identifier = $quantity_identifier;
        
        $zip = new ZipArchive();
        
        if($zip->open($this->_name) === TRUE) {
            $zip->extractTo(FileArchiveParser::$_path);
            $zip->close();
        } else {
            die("corrupted archive");
        }
    }

    public function createFileContainers() {
        $container = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(FileArchiveParser::$_path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        
        foreach($iterator as $file) {
            if($iterator->getDepth() > 2) {
                die("Archive format is not correct");
            } else {                      
                if($file->isDir()) {                    
                    if(isset($Temp_File_Container)) {
                        $container[] = $Temp_File_Container;
                        $Temp_File_Container = new FileContainer($file->getBasename());
                    } else {
                        $Temp_File_Container = new FileContainer($file->getBasename());
                    }
 
                } elseif($file->isFile()) { 
                    if(preg_match('/'.$this->_quality_identifier.'/', $file->getBasename())) {
                        $Temp_File_Container->addFile(new QualityFile($file->getBasename(), $file->getPathname()));
                    } elseif(preg_match('/'.$this->_quantity_identifier.'/', $file->getBasename())) {
                        $Temp_File_Container->addFile(new QuantityFile($file->getBasename(), $file->getPathname()));
                    }
                }
                
            }
        }
        $container[] = $Temp_File_Container;
        
        return $container;
        
    }
    
    public function clearFiles() {
       $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(FileArchiveParser::$_path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
       
       foreach($it as $file) {
           if($file->isDir()) {
               rmdir($file->getPathname());
           } elseif($file->isFile() || $file->isLink()) {
               unlink($file->getPathname());
           }
       }
       
    }
/*
    public function __destruct() {
       try {
           $this->clearFiles();
       } catch (Exception $e) {
           echo $e->getMessage();
       }
       
    }
     */
}
?>