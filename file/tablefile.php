<?php
    namespace whatwhat\file;

    class TableFile extends File{
        static public $tablesDirectory = 'wwtables';

        public function get(){
            $this->checkFile();
            if($this->isArrayFile()) return include($this->path);
            else{
                trigger_error('File '.$this->path.' seems not to return a valid php array, ignoring.', E_USER_NOTICE);
                return array();
            }
        }

        private function isArrayFile(){
            if($this->getExt() != 'php') return false;
            $f = file_get_contents($this->path);
            if(preg_match("#<?php[\n\s\t]*return[\n\s\t]*array#", $f)) return true;
            else return false;
        }

        public function writeModel($database, $table, $fields){
            if(is_file($this->path)){
                echo '<br/>File '.$this->path.' already exists. Ignoring. Use update() method to update your models from database.';
            }else{
                $content = "<?php\n\treturn array('database' => '".$database."',\n\t\t'table' => '".$table."',\n\t\t'fields' => array(";
                foreach($fields as $name => $desc){
                    $content .= "\n\t\t\t'".$name."' => array (\n\t\t\t\t'type' => '".$desc['type']."'";
                    $content .= ",\n\t\t\t\t'nullable' => ".(($desc['nullable']) ? "true" : "false");
                    if(strpos($desc['type'], 'int') !== false) $content .= ",\n\t\t\t\t'primary' => ".(($desc['primary']) ? "true" : "false");
                    if(strpos($desc['type'], 'int') !== false) $content .= ",\n\t\t\t\t'autoincrement' => ".(($desc['autoincrement']) ? "true" : "false");
                    if(!is_null($desc['length']))$content .= ",\n\t\t\t\t'length' => ".$desc['length'];
                    if(!is_null($desc['default'])){
                        $content .= ",\n\t\t\t\t'default' => ".((strpos($desc['type'], 'int') !== false) ? "'".$desc['default']."'" : $desc['default']);
                    }
                    $content .= "),";
                }
                $content = substr($content, 0, strlen($content) - 2).")));";
                $this->write($content);
            }
        }
    }