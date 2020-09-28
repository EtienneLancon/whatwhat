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
            foreach($existingTable as $existingColumn){
                if($existingColumn->wwtable == $model['table']){
                    if(array_key_exists($existingColumn->wwfield, $model['fields'])){ //in new and old tables.
                        echo $existingColumn->wwfield." ca existe\n";
                    }else{                                                          //only in old table.
                        echo $existingColumn->wwfield." ca existe pas\n";
                    }
                    foreach($model['fields'] as $fieldname => &$fielddata){
                        if($fieldname == $existingColumn->wwfield){
                            $fielddata['inExisting'] = true;
                        }
                    }
                }else throw new \Exception("<br/>Error during treatment : new and existing table mismatch. "
                                                .$existingColumn->wwtable." --- ".$model['table']);
            }
            foreach($model['fields'] as $fieldname => $field){
                if(!array_key_exists('inExisting', $field)){     //only in new table.
                    echo $fieldname." n'est pas dans l'existant.\n";
                }
            }
        }

        static public function createView($model){
            return "CREATE OR REPLACE VIEW ".$model['view']
            ." AS\n".str_replace("\'", "'", $model['definition']).";\n\n";
        }
    }