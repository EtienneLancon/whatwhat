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

        static public function createView($model){
            return "CREATE OR REPLACE VIEW ".$model['view']
            ." AS\n".str_replace("'", "\'", $model['definition']).";\n\n";
        }
    }