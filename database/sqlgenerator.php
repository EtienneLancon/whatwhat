<?php
    namespace whatwhat\database;

    class SqlGenerator{

        static public function createTable($model){
            $pk = null;
            $cmd = "CREATE TABLE ".$model['table']." (\n\t";
            foreach($model['fields'] as $field => $desc){
                $cmd .= self::writeColumn($field, $desc).", \n\t";
                if(array_key_exists('primary', $desc) && $desc['primary'] === true){
                    $pk = $field;
                }
            }
            if(!empty($pk)){
                $cmd .= "PRIMARY KEY (".$pk.")";
            }else $cmd = substr($cmd, 0, strlen($cmd)-4);
            $cmd .= ");\n\n";
            if(isset($model['indexes'])){
                foreach($model['indexes'] as $index => $columns){
                    $cmd .= "CREATE INDEX ".$index."\nON ".$model['table']." (";
                    $i = 0;
                    foreach($columns as $column){
                        $cmd .= ($i == 0) ? $column : ', '.$column;
                        $i++;
                    }
                    $cmd .= ");\n\n";
                }
            }
            return $cmd;
        }

        static public function alterTable($dbtype, $model, $existingTable){
            var_dump($model);
            var_dump($existingTable);
            exit;
            $onlyInNewModel = $model;
            $droppedColumns = array();
            $addedColumns = array();
            $modifiedColumns = array();
            
            foreach($existingTable as &$existingColumn){
                $existingColumn->foundInModel = false;
                if($existingColumn->wwtable == $onlyInNewModel['table']){
                    foreach($onlyInNewModel['fields'] as $fieldname => &$fielddata){
                        if($fieldname == $existingColumn->wwfield){ //field in both old in new table, look for changes
                            $existingColumn->foundInModel = true;
                            unset($onlyInNewModel['fields'][$fieldname]);
                            
                            $modified = array();
                            foreach($fielddata as $data => $value){
                                if($value != $existingColumn->{'ww'.$data}){
                                    $modified[$data] = $value;
                                }
                            }
                            if(!empty($modified)) $modifiedColumns[] = self::writeColumn($fieldname, $modified);
                        }
                    }
                    if(!$existingColumn->foundInModel){     //only in old table.
                        $droppedColumns[] = $existingColumn->wwfield;
                    }
                }else throw new \Exception(ln()."Error during treatment : new and existing table mismatch. "
                                                .$model['table']." --- ".$existingColumn->wwtable);
            }

            foreach($onlyInNewModel['fields'] as $field => $desc){     //only in new table.
                $addedColumns[] = self::writeColumn($field, $desc);
            }
            

            return $dbtype->alterTable($model['table'], $addedColumns, $droppedColumns, $modifiedColumns);
        }

        static private function writeColumn($field, $desc){
            $cmd = $field." ".$desc['type'];
            if(array_key_exists('length', $desc)){
                $cmd .= " (".$desc['length'].")";
            }
            if($desc['nullable'] === false) $cmd .= " NOT NULL";
            if(array_key_exists('autoincrement', $desc) && $desc['autoincrement'] === true){
                $cmd .= " AUTO_INCREMENT";
            }
            return $cmd;
        }

        static public function createView($model){
            return "CREATE OR REPLACE VIEW ".$model['view']
            ." AS\n".str_replace("\'", "'", $model['definition']).";\n\n";
        }
    }