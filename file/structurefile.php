<?php
    namespace whatwhat\file;

    class StructureFile extends File{
        static public $tablesDirectory = 'wwtables';
        static public $viewsDirectory = 'wwviews';

        public function get(){
            $this->checkFile();
            if($this->isArrayFile()) return include($this->path);
            else{
                trigger_error('File '.$this->path.' seems not to return a valid php array, ignoring.', E_USER_NOTICE);
                return false;
            }
        }

        private function isArrayFile(){
            if($this->getExt() != 'php') return false;
            $f = file_get_contents($this->path);
            if(preg_match("#[\n\s\t]*<?php[\n\s\t]*return[\n\s\t]*array#", $f)) return true;
            else return false;
        }

        public function writeModel($database, $table, $fields, $indexes){
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
                $content = substr($content, 0, strlen($content) - 1).")";

                if(!empty($indexes)){
                    $previousIndex = null;
                    $content .= ",\n\t\t'indexes' => array(";
                    foreach($indexes as $index){
                        if($index['wwindex'] != $previousIndex){
                            if(!is_null($previousIndex)) $content = substr($content, 0, strlen($content) - 2)."),";
                            $content .= "\n\t\t\t'".$index['wwindex']."' => array(";
                            $previousIndex = $index['wwindex'];
                        }
                        $content .= "\n\t\t\t\t'".$index['wwcolumn']."', ";
                    }
                    $content = substr($content, 0, strlen($content) - 2)."))";
                }
                $content .= ");";
                $this->write($content);
            }
        }

        public function writeView($database, $view, $definition){
            if(is_file($this->path)){
                echo '<br/>File '.$this->path.' already exists. Ignoring. Use update() method to update your models from database.';
            }else{
                $content = "<?php\n\treturn array('database' => '"
                            .$database."',\n\t\t'view' => '".$view."',\n\t\t'definition' => '".$definition."');";
                $this->write($content);
            }
        }

        public static function setTablesDirectory($dir){
            self::$tablesDirectory = $dir;
        }
    }