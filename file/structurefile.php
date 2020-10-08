<?php
    namespace whatwhat\file;

    class StructureFile extends File{
        static public $tablesDirectory = 'wwtables/';
        static public $viewsDirectory = 'wwviews/';

        public function get(){
            $this->checkFile($this->path);
            if($this->isArrayFile()) return include($this->path);
            else{
                trigger_error('File '.$this->path.' seems not to return a valid php array, ignoring.', E_USER_WARNING);
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

        public function writeView($database, $view, $definition){
            $content = "<?php\n\treturn array('database' => '"
                        .$database."',\n\t\t'view' => '".$view."',\n\t\t'definition' => '"
                                                        .str_replace("'", "\'", $definition)."');";
            $this->write($content);
        }

        public static function setTablesDirectory($dir){
            self::$tablesDirectory = $dir;
        }

        static public function getLatestMigFile($migrationDirectory, $dbname){
            $migFiles = Directory::scandir($migrationDirectory);
            if($migFiles !== false){
                $mostRecent['year'] =  0;
                $mostRecent['month'] = 0;
                $mostRecent['day'] = 0;
                $mostRecent['hour'] = 0;
                $mostRecent['minute'] = 0;
                $mostRecent['second'] = 0;
                $lendb = strlen($dbname);
                foreach($migFiles as $migFile){
                    $date = substr($migFile, $lendb, strlen($migFile) - ($lendb+4));
                    $data = explode('_', $date);
                    $greatDate = $data[0];
                    $littleDate = $data[1];
                    $data1 = explode('-', $greatDate);
                    $data2 = explode('-', $littleDate);
                    $year = $data1[0];
                    $month = $data1[1];
                    $day = $data1[2];
                    $hour = $data2[0];
                    $minute = $data2[1];
                    $second = $data2[2];
                    if($mostRecent['year'] < $year
                        || ($mostRecent['year'] == $year && $mostRecent['month'] < $month)
                        || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] < $day)
                        || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                                && $mostRecent['hour'] < $hour)
                        || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                                && $mostRecent['hour'] == $hour && $mostRecent['minute'] < $minute)
                        || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                                && $mostRecent['hour'] == $hour && $mostRecent['minute'] == $minute && $mostRecent['second'] < $second)){
                        $mostRecent['year'] = $year;
                        $mostRecent['month'] = $month;
                        $mostRecent['day'] = $day;
                        $mostRecent['hour'] = $hour;
                        $mostRecent['minute'] = $minute;
                        $mostRecent['second'] = $second;
                    }
                }
                if($mostRecent['year'] == 0) die('No migration file found in '.$migrationDirectory);
                return $migrationDirectory.$dbname
                            .$mostRecent['year']."-".$mostRecent['month']."-".$mostRecent['day']
                            ."_".$mostRecent['hour']."-".$mostRecent['minute']."-".$mostRecent['second'].".mig";
            }else{
                die('No migration directory found here '.$migrationDirectory);
            }
        }
    }