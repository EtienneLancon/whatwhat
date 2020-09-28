<?php
    namespace whatwhat\database;
    use whatwhat\file\StructureFile;
    use whatwhat\file\Directory;

    class Migration{
        static private $migrationDirectory = 'wwmigrations';
        private $dbname;

        public function __construct($dbname){
            paramcheck($dbname, 'string');
            $this->dbname = $dbname;
        }

        public function migrate(){
            $cmd = '';
            $tables = Directory::scandir(StructureFile::$tablesDirectory);
            foreach($tables as $table){
                $f = new StructureFile(StructureFile::$tablesDirectory.'/'.$table);
                $model = $f->get();
                if($model !== false) $cmd .= $this->makeSql($model, 'table');
            }

            $views = Directory::scandir(StructureFile::$viewsDirectory);
            foreach($views as $view){
                $f = new StructureFile(StructureFile::$viewsDirectory.'/'.$view);
                $model = $f->get();
                if($model !== false) $cmd .= $this->makeSql($model, 'view');
            }
            $this->writeMigration($cmd);
        }

        private function makeSql($model, $type){
            $cmd = '';
            if($type == 'table'){
               // if(!$this->tableExists($model['table'])){
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
              //  }
            }elseif($type == 'view'){
                $cmd = "CREATE OR REPLACE VIEW ".$this->dbname.".".$model['view']
                            ." AS\n".str_replace("'", "\'", $model['definition']).";\n\n";
            }else{
                throw new \Exception('Bound unknown object type');
            }
            return $cmd;
        }

        private function writeMigration($cmd){
            Directory::isdir(self::$migrationDirectory);
            $migFile = new StructureFile(self::$migrationDirectory.'/'.$this->dbname.date('Y-d-n_H-i-s').'.mig');
            $migFile->write($cmd);
        }

        public function fileMigration($file){
            $cmd = file_get_contents($file);
            $this->cmdMigration($cmd);
        }

        public function cmdMigration($cmd){
            $request = new Request($this->dbname);
            $request->setCmd($cmd);
            $request->getResults();
        }

        public function collect(){
            $request = new Request($this->dbname);
            $request->setCmd($request->getdbType()->getTableRequest());
            $request->addBinds(array('dbName' => $this->dbname));
            $columnList = $request->getResults();
            if(empty($columnList)) echo "<br/><b>No columns found.</b>";

            $this->createModels($columnList);

            $request->setCmd($request->getdbType()->getViewRequest());
            $viewList = $request->getResults();
            if(empty($viewList)) echo "<br/><b>No views found.</b>";

            $this->createViews($viewList);
        }

        private function createModels($columnList){
            $previousTable = null;
            $fields = array();
            Directory::isdir(StructureFile::$tablesDirectory);
            foreach($columnList as $column){
                if($previousTable != $column->wwtable){
                    if(!is_null($previousTable)){
                        $indexes = $this->getIndexes($previousTable);
                        $f = new StructureFile(StructureFile::$tablesDirectory.'/'.$previousTable.'.php');
                        $f->writeModel($this->dbname, $previousTable, $fields, $indexes);
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

            $indexes = $this->getIndexes($previousTable);
            $f = new StructureFile(StructureFile::$tablesDirectory.'/'.$previousTable.'.php');
            $f->writeModel($this->dbname, $previousTable, $fields, $indexes);
        }

        public function createViews($viewList){
            Directory::isdir(StructureFile::$viewsDirectory);
            foreach($viewList as $view){
                $f = new StructureFile(StructureFile::$viewsDirectory.'/'.$view->wwview.'.php');
                $f->writeView($this->dbname, $view->wwview, $view->wwdefinition);
            }
        }

        private function tableExists($table){
            $request = new Request($this->dbname);
            $request->setCmd('select * from '.$table.' limit 1');
            if(empty($request->getResults())) return false;
            else return true;
        }

        private function getIndexes($table){
            $request = new Request($this->dbname);
            $request->setCmd($request->getdbType()->getIndexRequest());
            $request->addBinds(array($request->getdbType()->getIndexRequestBindName() => $table));
            $indexes = $request->getResults();
            var_dump($table);
            var_dump($request->getdbType()->getIndexRequestBindName());
            var_dump($indexes);

            return $this->filterIndex($request->getdbType()->getIndexFilter(), $indexes);
        }

        public function filterIndex($filter, $indexes){
            $temp = array();
            $i = 0;
            foreach($indexes as $index){
                foreach($filter as $wwname => $value){
                    $temp[$i][$wwname] = $index->$value;
                }
                $i++;
            }
            return $temp;
        }

        public static function setMigrationDirectory($dir){
            self::$migrationDirectory = $dir;
        }
    }