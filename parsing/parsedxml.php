<?php
    namespace whatwhat\parsing;
    //ParsedXml a pour but de récupérer des données dans un fichier XML selon le context passé
    //le context exprime les cibles de la façon array("foo" => "path in XML", "bar" => "path in XML")
    class ParsedXml extends ParsedFile{
        private $returnedData;
        
        public function getData(){
            $this->go();
            return $this->returnedData;
        }

        protected function go(){
            $this->load($this->path);
            $rootNode = $this->getElementsByTagName($this->root); //see to modify it
            if(is_string($this->root) && $rootNode->length > 0){
                $this->parsing($rootNode[0]);
            }else{
                throw new \Exception("Can't locate tag ".$this->root);
            }
        }

        protected function treatment($node){
            $nodePath = implode('/', $this->nodePathArray)."\n";
            foreach($this->context as $data => $path){
                if(preg_replace("#\n#", '', $nodePath) == $path){
                    $this->returnedData[$data] = $node->nodeValue; 
                }
            }
        }
    }