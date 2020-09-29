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

        static public function alterTable($model, $existingTable){
            $onlyInNewModel = $model;
            $cmd = '';
            $tmp = '';
            $tmpCmdModify;
            $first = true;
            foreach($existingTable as &$existingColumn){
                $existingColumn->foundInModel = false;
                if($existingColumn->wwtable == $onlyInNewModel['table']){
                    foreach($onlyInNewModel['fields'] as $fieldname => &$fielddata){
                        if($fieldname == $existingColumn->wwfield){ //field in both old in new table, look for changes
                            $existingColumn->foundInModel = true;
                            unset($onlyInNewModel['fields'][$fieldname]);
                            var_dump($fielddata);
                            var_dump($existingColumn);
                            $hasmodified = false;
                            foreach($fielddata as $data => $value){
                                if($value == $existingColumn->{'ww'.$data}){
                                    if(!$hasmodified){
                                        //$tmpCmdModify .= 'ALTER TABLE '.$model['table'];
                                        //$hasmodified = true;
                                    }
                                }
                            }
                            //exit;
                        }
                    }
                    if(!$existingColumn->foundInModel){     //only in old table.
                        $tmp .= "\n\t\tCOLUMN ".$existingColumn->wwfield.(($first) ? "," : "");
                    }
                }else throw new \Exception(ln()."Error during treatment : new and existing table mismatch. "
                                                .$existingColumn->wwtable." --- ".$model['table']);
            }

            if(strlen($tmp) > 0) $cmd = 'ALTER TABLE '.$model['table']."\n\tDROP ".substr($tmp, 0, strlen($tmp)-1).";\n\n";

            $first = true;
            $tmp = '';
            foreach($onlyInNewModel['fields'] as $field => $desc){     //only in new table.
                $tmp .= (($first) ? '' : "\n\t, ").self::writeColumn($field, $desc);
                $first = false;
            }

            if(strlen($tmp) > 0) $cmd .= 'ALTER TABLE '.$model['table']."\n\tADD ".$tmp.";\n\n";
            

            return $cmd;
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