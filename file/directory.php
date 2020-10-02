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

        public static function mkpath($path){
            if(is_string($path)){
                $arraypath = explode('/', $path);
            }elseif(is_array($path)){
                $arraypath = $path;
            }else{
                paramcheck($path, array('string', 'array'));
            }

            $currentPath = '';
            foreach($arraypath as $p){
                $currentPath .= $p.'/';
                self::isdir($currentPath);
            }
        }
    }