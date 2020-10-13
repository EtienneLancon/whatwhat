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

        static public function alterTable($dbtype, $newModel, $oldModel){   //DO INDEXES
            $onlyInNewModel = $newModel;
            $droppedColumns = array();
            $addedColumns = array();
            $modifiedColumns = array();
            $droppedpk = '';
            $pks = array();
            
            foreach($oldModel['fields'] as $oldFieldName => &$oldField){
                $oldField['foundInNew'] = false;
                if($oldModel['table'] == $onlyInNewModel['table']){
                    foreach($onlyInNewModel['fields'] as $newFieldName => &$newField){
                        if($newFieldName == $oldFieldName){ //field in both old in new table, look for changes
                            $oldField['foundInNew'] = true;
                            unset($onlyInNewModel['fields'][$oldFieldName]);
                            
                            $modifiedField = array();
                            foreach($newField as $data => $value){
                                if($value != $oldField[$data]){
                                    $modifiedField = $newField;
                                    break;
                                }
                            }

                            if(array_key_exists('primary', $newField)
                                && $newField['primary'] == false
                                && array_key_exists('primary', $oldField)
                                && $oldField['primary'] == true) $droppedpk = $newFieldName;
                            
                            if(!empty($modifiedField)){
                                if((array_key_exists('primary', $modifiedField) !== false && $modifiedField['primary'] === true) 
                                && (array_key_exists('primary', $oldField) === false || $oldField['primary'] === false)) $pks[] = $newFieldName;
                                $modifiedColumns[$newFieldName] = self::writeColumn($newFieldName, $modifiedField);
                            }
                        }
                    }
                    if(!$oldField['foundInNew']){     //only in old table.
                        $droppedColumns[] = $oldFieldName;
                    }
                }else throw new \Exception(ln()."Error during treatment : new and existing table mismatch. "
                                                .$model['table']." --- ".$existingColumn->wwtable);
            }

            foreach($onlyInNewModel['fields'] as $field => $desc){     //only in new table.
                if(array_key_exists('primary', $desc) !== false && $desc['primary'] === true) $pks[] = $field;
                $addedColumns[$field] = self::writeColumn($field, $desc);
            }
            
            return $dbtype->alterTable($newModel['table'], $addedColumns, $droppedColumns, $modifiedColumns, $droppedpk, $pks);
        }

        static public function diffIndexes($newModel, $oldModel){
            $cmd = '';
            foreach($newModel['indexes'] as $indexname => $indexfields){
                if(array_key_exists($indexname, $oldModel['indexes']) === false){
                    $cmd .= "CREATE INDEX ".$indexname."\nON ".$newModel['table']." (";
                    foreach($indexfields as $field){
                        $cmd .= $field.",";
                    }
                    $cmd = substr($cmd, 0, strlen($cmd)-1).");\n\n";
                }else{
                    $tmp = '';
                    $modify = false;
                    foreach($indexfields as $field){
                        $tmp .= $field.",";
                        if(array_key_exists($field, $oldModel['indexes'][$indexname]) === false){
                            $modify = true;
                        }
                    }
                    if($modify){
                        $cmd .= "DELETE INDEX ".$indexname.";\n\n";
                        $cmd .= "CREATE INDEX ".$indexname."\nON ".$newModel['table']." (".$tmp;
                        $cmd = substr($cmd, 0, strlen($cmd)-1).");\n\n";
                    }
                }
            }
            foreach($oldModel['indexes'] as $indexname => $indexfields){
                if(array_key_exists($indexname, $newModel['indexes']) === false){
                    $cmd .= "DELETE INDEX ".$indexname.";\n\n";
                }
            }
            return $cmd;
        }

        static public function dropTable($tableName){
            return "DROP TABLE ".$tableName.";\n\n";
        }

        static private function writeColumn($field, $desc){
            $cmd = $field." ".$desc['type'];
            if(array_key_exists('length', $desc) && $desc['type'] != 'longtext'){
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