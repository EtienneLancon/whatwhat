<?php
    namespace whatwhat\database;

    class SqlGenerator{

        static public function createTable($model){
            $pk = null;
            $cmd = "CREATE TABLE ".$model['table']." (\n\t";
            foreach($model['fields'] as $field => $desc){
                $cmd .= $field." ".$desc['type'];
                if(array_key_exists('length', $desc)){
                    $cmd .= " (".$desc['length'].")";
                }
                if($desc['nullable'] === false) $cmd .= " NOT NULL";
                if(array_key_exists('autoincrement', $desc) && $desc['autoincrement'] === true){
                    $cmd .= " AUTO_INCREMENT";
                }
                $cmd .= ", \n\t";
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

        static public function alterTable($model, $existingTable){
            $onlyInNewModel = $model;
            foreach($existingTable as &$existingColumn){
                $existingColumn->foundInModel = false;
                if($existingColumn->wwtable == $onlyInNewModel['table']){
                    foreach($onlyInNewModel['fields'] as $fieldname => &$fielddata){
                        if($fieldname == $existingColumn->wwfield){
                            $existingColumn->foundInModel = true;
                            unset($onlyInNewModel['fields'][$fieldname]);
                        }
                    }
                    if(!$existingColumn->foundInModel){     //only in old table.
                        echo $existingColumn->wwfield." n'est pas dans le nouveau.\n";
                    }
                }else throw new \Exception("<br/>Error during treatment : new and existing table mismatch. "
                                                .$existingColumn->wwtable." --- ".$model['table']);
            }
            foreach($onlyInNewModel as $fieldname => $field){     //only in new table.
                echo $fieldname." n'est pas dans l'existant.\n";
            }
        }

        static public function createView($model){
            return "CREATE OR REPLACE VIEW ".$model['view']
            ." AS\n".str_replace("\'", "'", $model['definition']).";\n\n";
        }
    }