<?php
    namespace whatwhat\database;
    use whatwhat\file\TableFile;

    class Migration extends Request{
        private $current;
        static private $migrationDirectory = 'wwmigrations';

        public function migrate(){
            if(is_dir(TableFile::$tablesDirectory) || mkdir(TableFile::$tablesDirectory)){
                $tables = wwscandir(TableFile::$tablesDirectory);
                foreach($tables as $table){
                    $this->current = $table;
                    $f = new TableFile(TableFile::$tablesDirectory.'/'.$table);
                    $model = $f->get();
                    if(!empty($model)) $this->sqlCreateTable($model);
                }
                $this->writeMigration();
            }else throw new \Exception("Can't find ".TableFile::$tablesDirectory." directory.");
        }

        private function sqlCreateTable($model){
            $pk = null;
            $this->cmd .= "CREATE TABLE ".$model['table']." (\n\t";
            foreach($model['fields'] as $field => $desc){
                $this->cmd .= $field." ".$desc['type'];
                if(array_key_exists('length', $desc)){
                    $this->cmd .= " (".$desc['length'].")";
                }
                if($desc['nullable'] === false) $this->cmd .= " NOT NULL";
                if($desc['autoincrement'] === true) $this->cmd .= " AUTO_INCREMENT";
                $this->cmd .= ", \n\t";
                if($desc['primary']){
                    $pk = $field;
                }
            }
            if(!empty($pk)){
                $this->cmd .= "PRIMARY KEY (".$pk.")";
            }else $this->cmd = substr($this->cmd, 0, strlen($this->cmd)-4);
            $this->cmd .= ");\n\n";
        }

        private function writeMigration(){
            if(is_dir(self::$migrationDirectory) || mkdir(self::$migrationDirectory)){
                $migFile = new TableFile(self::$migrationDirectory.'/'.$this->current);
                $date = date('Y-d-n_H-i-s');
                $migFile->rename(self::$migrationDirectory.'/'.$this->getdbName().$date.'.mig');
                $migFile->write($this->cmd);
            }else throw new \Exception("Can't find ".self::$migrationDirectory." directory.");
        }

        public function makeMigration($file = false){
            if($file !== false){
                $this->cmd = file_get_contents($file);
            }
            $this->setCmd($this->cmd);
            $this->getResults();
        }

        public function collect(){
            switch($this->getdbType()){
                case 'mysql':
                    $this->cmd = "SELECT TABLE_NAME as wwtable, COLUMN_NAME as wwfield,
                                    CASE WHEN IS_NULLABLE = 'YES' THEN 1 ELSE 0 END as wwnullable, DATA_TYPE as wwtype,
                                    CHARACTER_MAXIMUM_LENGTH as wwlength,
                                    CASE WHEN COLUMN_KEY = 'PRI' THEN 1 ELSE 0 END as wwprimary,
                                    CASE WHEN EXTRA = 'auto_increment' THEN 1 ELSE 0 END as wwautoincrement,
                                    COLUMN_DEFAULT as wwdefault
                                    FROM INFORMATION_SCHEMA.COLUMNS
                                    where TABLE_SCHEMA = :dbName";
                    break;
            }
            $this->setCmd($this->cmd);
            $this->binds = array('dbName' => $this->getdbName());
            $columnList = $this->getResults();

            $this->createModels($columnList);
        }

        private function createModels($columnList){
            $previousTable = null;
            $fields = array();
            foreach($columnList as $column){
                if($previousTable != $column->wwtable){
                    if(!is_null($previousTable)){
                        is_dir(TableFile::$tablesDirectory) or mkdir(TableFile::$tablesDirectory);
                        $f = new TableFile(TableFile::$tablesDirectory.'/'.$previousTable.'.php');
                        $f->writeModel($this->getdbName(), $previousTable, $fields);
                    }
                    $fields = array();
                    $previousTable = $column->wwtable;
                }
                $fields[$column->wwfield]['nullable'] = $column->wwnullable;
                $fields[$column->wwfield]['type'] = $column->wwtype;
                $fields[$column->wwfield]['length'] = $column->wwlength;
                $fields[$column->wwfield]['primary'] = $column->wwprimary;
                $fields[$column->wwfield]['autoincrement'] = $column->wwautoincrement;
                $fields[$column->wwfield]['default'] = wwnull($column->wwdefault);
            }
            $f = new TableFile(TableFile::$tablesDirectory.'/'.$previousTable.'.php');
            $f->writeModel($this->getdbName(), $previousTable, $fields);
        }
    }