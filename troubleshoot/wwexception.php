<?php
    namespace whatwhat\troubleshoot;

    class Wwexception{
        private $output;
        private $e;

        public function __construct($e){
            $this->output = $e->getMessage();
            $this->e = $e;
            $this->error_handling();
        }

        private function error_handling(){
            $stack = $this->e->getTrace();
            if(strpos($this->e->__toString(), 'Error') === 0){
                $this->output .= " in ".$this->e->getFile()." line <b>".$this->e->getLine()."</b>";
            }
            foreach($stack as $trace){
                $this->buildoutput($trace);
            }

            echo $this->output;
        }

        private function buildoutput($trace){
            $this->output .= "<br/><br/> in ".$trace['file']." on line <b>".$trace['line']."</b>, calling ";
            if(isset($trace['class'])){
                $this->output .= $trace['class'].(($trace['function'] == '__construct') ? "()" : "->");
            }
            if($trace['function'] != '__construct') $this->output .= $trace['function'];
            
            if(!empty($trace['args'])){
                $this->output .= ' with args :';
                foreach($trace['args'] as $arg => $value){
                    $this->output .= '<br/>'.$arg.' => '.$value;
                }
            }
        }
    }