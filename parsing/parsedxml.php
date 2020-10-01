<?php
    namespace whatwhat\parsing;
    //ParsedXml a pour but de récupérer des données dans un fichier XML selon le context passé
    //le context exprime les cibles de la façon array("foo" => "path in XML", "bar" => "path in XML")
    class ParsedXml extends ParsedFile{
        private $returnedData;
        private $conditionok = false;
        
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
            $currentpath = preg_replace("#\n#", '', $nodePath);
            if($this->context['condition']['path'] == $currentpath){
                if($this->context['condition']['value'] == $node->nodeValue) $this->conditionok = true;
                else $this->conditionok = false;
            }
            foreach($this->context['lookfor'] as $data => $path){
                if($this->conditionok && $currentpath == $path){
                    $this->returnedData[$data] = $node->nodeValue;
                }
            }
        }
    }