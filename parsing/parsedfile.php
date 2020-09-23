<?php
    namespace whatwhat\parsing;
    //Parse each node of tagged file
    //If node does not have children, call a treatment defined in children classes
    abstract class ParsedFile extends \DOMDocument{
        protected $path; //file to parse
        protected $root; //name of root node
        protected $context; //list of targets of the treatment
        protected $nodePathArray; //path of current node, with each element a parent node until current

        public function __construct(){}

        public function __set($value, $target){
            paramcheck($target, 'string');
            $this->$target = $value;
        }

        public function __get($target){
            return $this->$target;
        }

        protected function parsing($node){
            $this->nodePathArray[] = $node->tagName;
            foreach($node->childNodes as $child){
                if($child->hasChildNodes()){
                    $this->parsing($child);
                    array_pop($this->nodePathArray);
                }else{
                    $this->treatment($child);
                }
            }
        }

        protected function treatment($node){
            //in children classes
        }
    }