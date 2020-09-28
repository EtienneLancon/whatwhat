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
            if($type == 'table'){
                if(!$this->tableExists($model['table'])){
                    return SqlGenerator::createTable($model);
                }else{
                    $existingTable = $this->getColumnList($model['table']);
                    return SqlGenerator::alterTable($model, $existingTable);
                }
            }elseif($type == 'view'){
                return SqlGenerator::createView($model);
            }else{
                throw new \Exception('Bound unknown object type');
            }
        }

        private function writeMigration($cmd){
            Directory::isdir(self::$migrationDirectory);
            $migFile = new StructureFile(self::$migrationDirectory.'/'.$this->dbname.date('Y-d-n_H-i-s').'.mig');
            $migFile->write($cmd);
        }

        public function fileMigration($file){
            File::checkFile($file);
            $cmd = file_get_contents($file);
            $this->cmdMigration($cmd);
        }

        public function cmdMigration($cmd){
            $request = new Request($this->dbname);
            $request->setCmd($cmd);
            $request->bindexec();
        }

        public function collect(){
            $this->createModels($this->getColumnList());

            $this->createViews($this->getViewList());
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
            $request->setCmd($request->getdbType()->getTableExistsRequest());
            $request->addBinds(array('table' => $table));
            if(empty($request->getResults())) return false;
            else return true;
        }

        private function getIndexes($table){
            $request = new Request($this->dbname);
            $request->setCmd($request->getdbType()->getIndexRequest());
            $request->addBinds(array($request->getdbType()->getIndexRequestBindName() => $table));
            $indexes = $request->getResults();
            // var_dump($table);
            // var_dump($request->getdbType()->getIndexRequestBindName());
            // var_dump($indexes);

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

        private function getColumnList($table = null){
            $request = new Request($this->dbname);
            if(!is_null($table)){
                $request->setCmd($request->getdbType()->getTableRequest());
                $request->addBinds(array('dbName' => $this->dbname, 'table' => $table));
            }else{
                $request->setCmd($request->getdbType()->getTableListRequest());
                $request->addBinds(array('dbName' => $this->dbname));
            }
            $columnList = $request->getResults();
            if(empty($columnList)) echo "<br/><b>No columns found.</b>";
            return $columnList;
        }

        private function getViewList(){
            $request = new Request($this->dbname);
            $request->setCmd($request->getdbType()->getViewListRequest());
            $viewList = $request->getResults();
            if(empty($viewList)) echo "<br/><b>No views found.</b>";
            return $viewList;
        }

        public static function setMigrationDirectory($dir){
            self::$migrationDirectory = $dir;
        }
    }