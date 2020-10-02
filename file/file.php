<?php
    namespace whatwhat\file;

    class File{
        protected $path;
        protected $mode;
        protected $ressource;

        public function __construct($path){
            paramcheck($path, 'string');
            $this->path = $path;
        }

        protected function open($mode){
            $this->mode = $mode;
            if(($this->ressource = fopen($this->path, $this->mode)) === false){
                throw new \Exception("Unable to open file ".$this->path);
            }
        }

        static public function checkFile($path){
            if(!is_file($path)) throw new \Exception("Can't locate file ".$path);
        }

        public function display(){
            $this->checkFile($this->path);
            $file = file($this->path);
            $output = "";
            foreach($file as $line){
                $output .= ln().$line; 
            }
            echo $output;
        }

        public function write($input){
            $this->open('w');
            if(fwrite($this->ressource, $input) === false) throw new \Exception('An error occure writing in file '.$this->path);
            fclose($this->ressource);
        }

        public function getExt(){
            return pathinfo($this->path, PATHINFO_EXTENSION);
        }

        public function getName(){
            return pathInfo($this->path, PATHINFO_FILENAME);
        }

        public function rename($newName){
            $this->path = $newName;
        }
    }