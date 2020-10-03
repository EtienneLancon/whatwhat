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
                $file = $this->getLatestMigFile();
            }
            $this->cmd = file_get_contents($file);
            $this->makeMigration();
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
                    $oldModelFile = new StructureFile(self::$saveDirectory.$this->targetDb->getDate().'/'
                                                    .StructureFile::$tablesDirectory.$newTable.'.php');
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

        private function getLatestMigFile(){
            $migFiles = Directory::scandir(self::$migrationDirectory);
            $mostRecent['year'] =  0;
            $mostRecent['month'] = 0;
            $mostRecent['day'] = 0;
            $mostRecent['hour'] = 0;
            $mostRecent['minute'] = 0;
            $mostRecent['second'] = 0;
            $lendb = strlen($this->targetDb->getRequest()->getdbName());
            foreach($migFiles as $migFile){
                $date = substr($migFile, $lendb, strlen($migFile) - ($lendb+4));
                $data = explode('_', $date);
                $greatDate = $data[0];
                $littleDate = $data[1];
                $data1 = explode('-', $greatDate);
                $data2 = explode('-', $littleDate);
                $year = $data1[0];
                $month = $data1[1];
                $day = $data1[2];
                $hour = $data2[0];
                $minute = $data2[1];
                $second = $data2[2];
                if($mostRecent['year'] < $year
                    || ($mostRecent['year'] == $year && $mostRecent['month'] < $month)
                    || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] < $day)
                    || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                            && $mostRecent['hour'] < $hour)
                    || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                            && $mostRecent['hour'] == $hour && $mostRecent['minute'] < $minute)
                    || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                            && $mostRecent['hour'] == $hour && $mostRecent['minute'] == $minute && $mostRecent['second'] < $second)){
                    $mostRecent['year'] = $year;
                    $mostRecent['month'] = $month;
                    $mostRecent['day'] = $day;
                    $mostRecent['hour'] = $hour;
                    $mostRecent['minute'] = $minute;
                    $mostRecent['second'] = $second;
                }
            }
            return self::$migrationDirectory.$this->targetDb->getRequest()->getdbName()
                        .$mostRecent['year']."-".$mostRecent['month']."-".$mostRecent['day']
                        ."_".$mostRecent['hour']."-".$mostRecent['minute']."-".$mostRecent['second'].".mig";
        }

        public function setSourceDb($dbtag){
            $this->sourceDb = new DbImage($dbtag, 'new');
        }

        public function setTargetDb($dbtag){            
            $this->targetDb = new DbImage($dbtag, 'old');
        }
    }