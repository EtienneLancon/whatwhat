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
        private $dumpedTables;

        public function migrate(){
            $this->pullNewStructure();
            $this->saveOldStructure();
            $this->createNewStructureCmd(true);
            $this->dumpTables();
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
            
            $this->targetDbExec($this->cmd);
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
                    if($execute && !empty($cmd)) $this->targetDbExec($cmd);
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

        public function dumpTables(){
            if(!empty($this->dumpedTables)){
                foreach($this->dumpedTables as $dumped){
                    if($this->targetDb->tableExists($dumped)){
                        $this->targetDbExec("delete from ".$dumped);
                        $this->dumpData($dumped);
                    }else echo ln()."Table ".$dumped." not found in target database.";
                }
            }else{
                echo ln()."No dump ordered.";
            }
        }

        private function dumpData($table){
            $columndataList = $this->targetDb->collectColumnList($table);
            $fieldsstr = '';
            $fields = array();
            foreach($columndataList as $columndata){
                if(!$columndata->wwautoincrement){
                    $fieldsstr .= $columndata->wwfield.",";
                    $fields[] = $columndata->wwfield;
                }
            }
            $fieldsstr = substr($fieldsstr, 0, strlen($fieldsstr)-1);
            $requestheader = "INSERT INTO __table\n(".$fieldsstr.")\nVALUES";
            $this->targetDb->getRequest()->addBinds(array('__table' => $table));
            
            $this->sourceDb->getRequest()->setCmd('select count(*) as nbRows from '.$table);
            $nbRows = $this->sourceDb->getRequest()->getResults()[0]->nbRows;

            for($i = 0; $i < $nbRows; $i += 1000){
                $cmd = "SELECT ".$fieldsstr." FROM ".$table." LIMIT 1000 OFFSET ".$i;
                $this->sourceDbExec($cmd);
                $requestbody = '';
                $j = 0;
                while(($row = $this->sourceDb->getRequest()->getStmt()->fetchObject()) !== false){
                    $tmp = "\n(";
                    $binds = array();
                    foreach($fields as $field){
                        $name = "param".strval($j);
                        $tmp .= ":".$name.",";
                        $binds[$name] = $row->$field;
                        $j++;
                    }
                    $this->targetDb->getRequest()->addBinds($binds);
                    $tmp = substr($tmp, 0, strlen($tmp)-1);
                    $requestbody .= $tmp."),";
                }
                $requestbody = substr($requestbody, 0, strlen($requestbody)-1);
                $request = $requestheader.$requestbody;
                $this->targetDb->getRequest()->setCmd($request);
                $this->targetDb->getRequest()->bindexec();
            }            
        }

        private function writeMigration(){
            Directory::isdir(self::$migrationDirectory);
            $migFile = new StructureFile(self::$migrationDirectory
                                        .$this->targetDb->getdbName().$this->targetDb->getDate().'.mig');
            $migFile->write($this->cmd);
        }

        private function targetDbExec($cmd){
            $this->targetDb->getRequest()->setCmd($cmd);
            $this->targetDb->getRequest()->bindexec();
        }

        private function sourceDbExec($cmd){
            $this->sourceDb->getRequest()->setCmd($cmd);
            $this->sourceDb->getRequest()->bindexec();
        }

        private function pullNewStructure(){
            $this->sourceDb->createModels();
            $this->sourceDb->createViews();
        }

        private function saveOldStructure(){
            $this->targetDb->createModels();
            $this->targetDb->createViews();
        }

        public function setSourceDb($dbtag){
            $this->sourceDb = new DbImage($dbtag, 'source');
        }

        public function setTargetDb($dbtag){            
            $this->targetDb = new DbImage($dbtag, 'target');
        }

        public function setDumpedTables($dumpedTables){
            paramcheck($dumpedTables, 'array');
            $this->dumpedTables = $dumpedTables;
        }
    }