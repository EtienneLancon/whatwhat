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
            $this->createNewStructureCmd(true);
            $this->writeMigration();
        }

        public function prepare(){
            $this->pullNewStructure();
            $this->saveOldStructure();
            $this->createNewStructureCmd();
            $this->writeMigration();
        }

        public function exec($file = null){
            if(!is_null($file)) File::checkFile($file);
            else{
                $file = StructureFile::getLatestMigFile(self::$migrationDirectory
                                            , $this->targetDb->getRequest()->getdbName());
            }
            $this->cmd = file_get_contents($file);
            
            $this->makeMigration();
        }

        public function reverse($dir = null){
            if(is_null($this->targetDb)) throw new \Exception('Must set target database first.');

            $this->setSourceDb($this->targetDb->getdbTag());
            if(is_null($dir)) $dir = Directory::getLatestSaveDir(self::$saveDirectory
                                            , $this->sourceDb->getRequest()->getdbName());
            
            $tablesaves = Directory::scandir($dir."/wwtables/");
            $viewsaves = Directory::scandir($dir."/wwviews/");

            if(!empty($tablesaves)){
                foreach(Directory::scandir(StructureFile::$tablesDirectory) as $tablefile){
                    unlink(StructureFile::$tablesDirectory.$tablefile);
                }
                foreach($tablesaves as $tablesave){
                    copy($dir."/wwtables/".$tablesave, StructureFile::$tablesDirectory.$tablesave);
                    $this->sourceDb->pushInTableList(substr($tablesave, 0, strlen($tablesave)-4));
                }
            }

            if(!empty($viewsaves)){
                foreach(Directory::scandir(StructureFile::$viewsDirectory) as $viewfile){
                    unlink(StructureFile::$viewsDirectory.$viewfile);
                }
                foreach($viewsaves as $viewsave){
                    copy($dir."/wwviews/".$viewsave, StructureFile::$viewsDirectory.$viewsave);
                    $this->sourceDb->pushInViewList(substr($viewsave, 0, strlen($viewsave)-4));
                }
            }
            
            $this->saveOldStructure();
            $this->createNewStructureCmd(true);
            $this->writeMigration();
        }

        private function createNewStructureCmd($execute = false){
            $oldTables = $this->targetDb->getTableList();
            $newTables = $this->sourceDb->getTableList();
            
            foreach($newTables as $newTable){
                $newModelFile = new StructureFile(StructureFile::$tablesDirectory.$newTable.'.php');
                $newModel = $newModelFile->get();
                if(array_search($newTable, $oldTables) === false){
                    $cmd = SqlGenerator::createTable($newModel);
                    $this->cmd .= $cmd;
                    if($execute && !empty($cmd)) $this->targetDbExec($cmd);
                }else{
                    $oldModelFile = new StructureFile(self::$saveDirectory.$this->targetDb->getdbName()
                                        .$this->targetDb->getDate().'/'.StructureFile::$tablesDirectory.$newTable.'.php');
                    $oldModel = $oldModelFile->get();
                    $cmd = SqlGenerator::alterTable($this->targetDb->getRequest()->getdbType()
                                                            , $newModel, $oldModel); //DO INDEXES
                    $this->cmd .= $cmd;
                    if($execute && !empty($cmd)) $this->targetDbExec($cmd);

                    $cmd = SqlGenerator::diffIndexes($newModel, $oldModel);
                    $this->cmd .= $cmd;
                }
            }

            foreach($oldTables as $oldTable){
                if(array_search($oldTable, $newTables) === false){
                    $cmd = SqlGenerator::dropTable($oldTable);
                    $this->cmd .= $cmd;
                    if($execute) $this->targetDbExec($cmd);
                }
            }
            
            foreach($this->sourceDb->getViewList() as $newView){
                $f = new StructureFile(StructureFile::$viewsDirectory.$newView.'.php');
                $newModel = $f->get();
                $cmd = SqlGenerator::createView($newModel);
                $this->cmd .= $cmd;
                if($execute) $this->targetDbExec($cmd);
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

        public function makeMigration(){
            $this->targetDbExec($this->cmd);
        }

        private function targetDbExec($cmd){
            $this->targetDb->getRequest()->setCmd($cmd);
            $this->targetDb->getRequest()->bindexec();
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
            $this->sourceDb = new DbImage($dbtag, 'source');
        }

        public function setTargetDb($dbtag){            
            $this->targetDb = new DbImage($dbtag, 'target');
        }
    }