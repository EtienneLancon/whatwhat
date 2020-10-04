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
            $this->createNewStructureCmd();
            $this->writeMigration();
            $this->makeMigration();
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
            if(is_null($dir)) $dir = Directory::getLatestSaveDir(self::$saveDirectory
                                            , $this->targetDb->getRequest()->getdbName());
            var_dump($dir);
        }

        private function createNewStructureCmd(){
            $oldTables = $this->targetDb->getTableList();
            $newTables = $this->sourceDb->getTableList();
            
            foreach($newTables as $newTable){
                $newModelFile = new StructureFile(StructureFile::$tablesDirectory.$newTable.'.php');
                $newModel = $newModelFile->get();
                if(array_search($newTable, $oldTables) === false){
                    $this->cmd .= SqlGenerator::createTable($newModel);
                }else{
                    $oldModelFile = new StructureFile(self::$saveDirectory.$this->targetDb->getdbName()
                                        .$this->targetDb->getDate().'/'.StructureFile::$tablesDirectory.$newTable.'.php');
                    $oldModel = $oldModelFile->get();
                    $this->cmd .= SqlGenerator::alterTable($this->targetDb->getRequest()->getdbType()
                                                            , $newModel, $oldModel);
                }
            }
            
            foreach($this->sourceDb->getViewList() as $newView){
                $f = new StructureFile(StructureFile::$viewsDirectory.$newView.'.php');
                $newModel = $f->get();
                $this->cmd .= SqlGenerator::createView($newModel);
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
            $this->targetDb->getRequest()->setCmd($this->cmd);
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
            $this->sourceDb = new DbImage($dbtag, 'new');
        }

        public function setTargetDb($dbtag){            
            $this->targetDb = new DbImage($dbtag, 'old');
        }
    }