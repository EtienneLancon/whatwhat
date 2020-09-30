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
            $this->output .= ln(2)."in ".$trace['file']." on line <b>".$trace['line']."</b>, calling ";
            if(isset($trace['class'])){
                $this->output .= $trace['class'].(($trace['function'] == '__construct') ? "()" : "->");
            }
            if($trace['function'] != '__construct') $this->output .= $trace['function'];
            
            if(!empty($trace['args'])){
                $this->output .= ' with args :';
                $this->traceToString($trace['args']);
            }
        }

        private function traceToString($trace){
            foreach($trace as $arg => $value){
                if(is_array($value)) $this->output .= ln().$arg.' => '."Array";
                elseif(is_object($value)) $this->output .= ln().$arg.' => '."Object ".get_class($value);
                else{
                    $this->output .= ln().$arg.' => '.$value;
                }
            }
        }
    }