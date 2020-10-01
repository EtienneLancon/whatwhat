<?php
    namespace whatwhat\database;

    class DbImage{
        private $tableList;
        private $viewList;
        private $indexList;
        private $request;

        public function __construct($env, $dbname){
            $this->request = new Request($env, $dbname);
        }

        public function collectColumnList($table = null){
            if(!is_null($table)){
                $this->request->setCmd($this->request->getdbType()->getTableRequest());
                $this->request->addBinds(array('dbName' => $this->getdbName(), 'table' => $table));
            }else{
                $this->request->setCmd($this->request->getdbType()->getTableListRequest());
                $this->request->addBinds(array('dbName' => $this->getdbName()));
            }
            $columnList = $this->request->getResults();
            if(empty($columnList)) echo "<br/><b>No columns found.</b>";
            return $columnList;
        }

        public function collectViewList(){
            $this->request->setCmd($this->request->getdbType()->getViewListRequest());
            $this->request->addBinds(array('dbName' => $this->getdbName()));
            $this->viewList = $this->request->getResults();
            if(empty($this->viewList)) echo "<br/><b>No views found.</b>";
            return $this->viewList;
        }

        public function tableExists($table){
            $this->request->setCmd($this->request->getdbType()->getTableExistsRequest());
            $this->request->addBinds(array('table' => $table, 'schema' => $this->request->getEnv()));
            $result = $this->request->getResults();
            if(empty($result)) return false;
            else return true;
        }

        public function getIndexes($table){
            $this->request->setCmd($this->request->getdbType()->getIndexRequest());
            $this->request->addBinds(array($this->request->getdbType()->getIndexRequestBindName() => $table));
            $indexes = $this->request->getResults();

            return $this->filterIndex($this->request->getdbType()->getIndexFilter(), $indexes);
        }

        private function filterIndex($filter, $indexes){
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

        public function setTableList($tableList){
            $this->tableList = $tableList;
        }

        public function setViewList($viewList){
            $this->viewList = $viewList;
        }

        public function setIndexList($indexList){
            $this->indexList = $indexList;
        }

        public function getTableList(){
            return $this->tableList;
        }

        public function getViewList(){
            return $this->viewList;
        }

        public function getIndexList(){
            return $this->indexList;
        }

        public function getRequest(){
            return $this->request;
        }

        public function getdbName(){
            return $this->request->getdbName();
        }
    }