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

        static public function alterTable($dbtype, $newModel, $oldModel){   
            $onlyInNewModel = $newModel;
            $droppedColumns = array();
            $addedColumns = array();
            $modifiedColumns = array();
            
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
                                }
                            }
                            if(!empty($modifiedField)) $modifiedColumns[] = self::writeColumn($newFieldName, $modifiedField);
                        }
                    }
                    if(!$oldField['foundInNew']){     //only in old table.
                        $droppedColumns[] = $oldFieldName;
                    }
                }else throw new \Exception(ln()."Error during treatment : new and existing table mismatch. "
                                                .$model['table']." --- ".$existingColumn->wwtable);
            }

            foreach($onlyInNewModel['fields'] as $field => $desc){     //only in new table.
                $addedColumns[] = self::writeColumn($field, $desc);
            }
            
            return $dbtype->alterTable($newModel['table'], $addedColumns, $droppedColumns, $modifiedColumns);
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