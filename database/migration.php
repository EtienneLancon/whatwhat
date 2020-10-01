<?php
    namespace whatwhat\database;
    use whatwhat\file\StructureFile;
    use whatwhat\file\Directory;

    class Migration{
        static private $migrationDirectory = 'wwmigrations';
        private $request;
        private $sourceDb;
        private $targetDb;

        public function migrate(){
            $this->pullStructure();
            $this->pushStructure();
        }

        public function pushStructure(){
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
                if(!$this->targetDb->tableExists($model['table'])){
                    return SqlGenerator::createTable($model);
                }else{
                    $existingTable = $this->targetDb->collectColumnList($model['table']);
                    $existingTable['indexes'] = $this->targetDb->getIndexes($model['table']);
                    return SqlGenerator::alterTable($this->request->getdbType(), $model, $existingTable);
                }
            }elseif($type == 'view'){
                return SqlGenerator::createView($model);
            }else{
                throw new \Exception('Bound unknown object type');
            }
        }

        private function writeMigration($cmd){
            Directory::isdir(self::$migrationDirectory);
            $migFile = new StructureFile(self::$migrationDirectory.'/'.$this->targetDb->getdbName().date('Y-d-n_H-i-s').'.mig');
            $migFile->write($cmd);
        }

        public function fileMigration($file){
            File::checkFile($file);
            $cmd = file_get_contents($file);
            $this->cmdMigration($cmd);
        }

        public function cmdMigration($cmd){
            $this->request->setCmd($cmd);
            $this->request->bindexec();
        }

        public function pullStructure(){
            $this->createModels($this->sourceDb->collectColumnList());

            $this->createViews($this->sourceDb->collectViewList());
        }

        private function createModels($columnList){
            $previousTable = null;
            $fields = array();
            Directory::isdir(StructureFile::$tablesDirectory);
            foreach($columnList as $column){
                if($previousTable != $column->wwtable){
                    if(!is_null($previousTable)){
                        $indexes = $this->sourceDb->getIndexes($previousTable);
                        $f = new StructureFile(StructureFile::$tablesDirectory.'/'.$previousTable.'.php');
                        $f->writeModel($this->sourceDb->getdbName(), $previousTable, $fields, $indexes);
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

            $indexes = $this->sourceDb->getIndexes($previousTable);
            $f = new StructureFile(StructureFile::$tablesDirectory.'/'.$previousTable.'.php');
            $f->writeModel($this->sourceDb->getdbName(), $previousTable, $fields, $indexes);
        }

        public function createViews($viewList){
            Directory::isdir(StructureFile::$viewsDirectory);
            foreach($viewList as $view){
                $f = new StructureFile(StructureFile::$viewsDirectory.'/'.$view->wwview.'.php');
                $f->writeView($this->sourceDb->getdbName(), $view->wwview, $view->wwdefinition);
            }
        }

        public static function setMigrationDirectory($dir){
            self::$migrationDirectory = $dir;
        }

        public function setSourceDb($dbtag){
            paramcheck($dbtag, 'string');
            if(strpos($dbtag, ':') === false || count($data = explode(':', $dbtag)) != 2)
                    throw new \Exception('Source database wrongly defined. Expecting "envname:databasename"');
            
            $this->sourceDb = new DbImage($data[0], $data[1]);
        }

        public function setTargetDb($dbtag){
            paramcheck($dbtag, 'string');
            if(strpos($dbtag, ':') === false || count($data = explode(':', $dbtag)) != 2)
                    throw new \Exception('Target database wrongly defined. Expecting "envname:databasename"');
            
            $this->targetDb = new DbImage($data[0], $data[1]);
        }
    }