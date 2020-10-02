<?php
    namespace whatwhat\database;
    use whatwhat\file\StructureFile;
    use whatwhat\file\Directory;

    class Migration{
        static public $migrationDirectory = 'wwmigrations/';
        static public $saveDirectory = 'wwsave/';
        private $request;
        private $sourceDb;
        private $targetDb;
        private $cmd = '';

        public function migrate(){
            $this->pullNewStructure();
            $this->saveOldStructure();

            $oldTables = $this->targetDb->getTableList();
            foreach($this->sourceDb->getTableList() as $newTable){
                $f = new StructureFile(StructureFile::$tablesDirectory.$newTable.'.php');
                $newModel = $f->get();
                if(($existingTable = array_search($newTable, $oldTables)) === false){
                    $this->cmd .= SqlGenerator::createTable($newModel);
                }else{
                    // REWRITE SqlGenerator::alterTable();
                }
            }
            
            foreach($this->sourceDb->getViewList() as $newView){
                $f = new StructureFile(StructureFile::$viewsDirectory.$newView.'.php');
                $newModel = $f->get();
                $this->cmd .= SqlGenerator::createView($newModel);
            }
            $this->writeMigration();
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

        private function writeMigration(){
            Directory::isdir(self::$migrationDirectory);
            $migFile = new StructureFile(self::$migrationDirectory
                                        .$this->targetDb->getdbName().$this->targetDb->getDate().'.mig');
            $migFile->write($this->cmd);
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

        public function pullNewStructure(){
            $this->sourceDb->createModels();

            $this->sourceDb->createViews();
        }

        public function saveOldStructure(){
            $this->targetDb->createModels();

            $this->targetDb->createViews();
        }

        public function setSourceDb($dbtag){
            $this->sourceDb = new DbImage($dbtag, 'new');
        }

        public function setTargetDb($dbtag){            
            $this->targetDb = new DbImage($dbtag, 'old');
        }
    }