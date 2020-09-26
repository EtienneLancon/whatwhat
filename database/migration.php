<?php
    namespace whatwhat\database;
    use whatwhat\file\StructureFile;
    use whatwhat\file\Directory;

    class Migration{
        static private $migrationDirectory = 'wwmigrations';
        private $dbname;

        public function __construct($dbname){
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
            $this->makeMigration();
        }

        private function makeSql($model, $type){
            $cmd = '';
            if($type == 'table'){
                $pk = null;
                $cmd .= "CREATE TABLE ".$model['table']." (\n\t";
                foreach($model['fields'] as $field => $desc){
                    $cmd .= $field." ".$desc['type'];
                    if(array_key_exists('length', $desc)){
                        $cmd .= " (".$desc['length'].")";
                    }
                    if($desc['nullable'] === false) $cmd .= " NOT NULL";
                    if($desc['autoincrement'] === true) $cmd .= " AUTO_INCREMENT";
                    $cmd .= ", \n\t";
                    if($desc['primary']){
                        $pk = $field;
                    }
                }
                if(!empty($pk)){
                    $cmd .= "PRIMARY KEY (".$pk.")";
                }else $cmd = substr($cmd, 0, strlen($cmd)-4);
                $cmd .= ");\n\n";
            }elseif($type == 'view'){
                $cmd .= "CREATE OR REPLACE VIEW ".$this->getdbName().".".$model['view']
                            ." AS\n".$model['definition'].";\n\n";
            }else{
                throw new \Exception('Bound unknown object type');
            }
            return $cmd;
        }

        private function writeMigration(){
            Directory::isdir(self::$migrationDirectory);
            $migFile = new StructureFile(self::$migrationDirectory.'/'.$this->getdbName().date('Y-d-n_H-i-s').'.mig');
            $migFile->write($this->cmd);
        }

        public function fileMigration($file){
            $cmd = file_get_contents($file);
            $this->setCmd($cmd);
            $this->getResults();
        }

        public function cmdMigration($cmd){
            $this->setCmd($cmd);
            $this->getResults();
        }

        public function collect(){
            $request = new Request($this->dbname);
            $request->setCmd(ConnectionType::getTableRequest($request->getdbType()));
            $request->addBinds(array('dbName' => $this->dbname));
            $columnList = $request->getResults();
            if(empty($columnList)) echo "<br/><b>No columns found.</b>";

            $this->createModels($columnList);

            $request->setCmd(ConnectionType::getViewRequest($request->getdbType()));
            $viewList = $request->getResults();
            if(empty($viewList)) echo "<br/><b>No views found.</b>";

            $this->createViews($viewList);
        }

        private function createModels($columnList){
            $previousTable = null;
            $fields = array();
            foreach($columnList as $column){
                if($previousTable != $column->wwtable){
                    if(!is_null($previousTable)){
                        Directory::isdir(StructureFile::$tablesDirectory);
                        $f = new StructureFile(StructureFile::$tablesDirectory.'/'.$previousTable.'.php');
                        $f->writeModel($this->dbname, $previousTable, $fields);
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
            $f = new StructureFile(StructureFile::$tablesDirectory.'/'.$previousTable.'.php');
            $f->writeModel($this->dbname, $previousTable, $fields);
        }

        public function createViews($viewList){
            foreach($viewList as $view){
                Directory::isdir(StructureFile::$viewsDirectory);
                $f = new StructureFile(StructureFile::$viewsDirectory.'/'.$view->wwview.'.php');
                $f->writeView($this->dbname, $view->wwview, $view->wwdefinition);
            }
        }

        public static function setMigrationDirectory($dir){
            self::$migrationDirectory = $dir;
        }

        private function tableExists($table){

        }
    }