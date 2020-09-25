<?php
    namespace whatwhat\file;

    class Directory{
        public static function scandir($dir, $create = false){
            if($create) self::isdir($dir);
            $no = array('.', '..', '.DS_STORE');
            return array_diff(scandir($dir), $no);
        }
        
        public static function isdir($dir){
            if(!is_dir($dir) && !mkdir($dir)) throw new \Exception("Can't find or create ".$dir." directory.");
        }
    }