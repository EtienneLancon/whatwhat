<?php
    namespace whatwhat\database;
    use whatwhat\file\Directory;
    use whatwhat\file\StructureFile;

    class DbImage{
        private $tableList = array();
        private $viewList = array();
        private $request;
        private $date;
        private $directory;

        public function __construct($dbtag, $type){
            $this->request = new Request($dbtag);
            $this->date = date('Y-d-n_H-i-s');
            $this->directory = (($type == 'old') ? Migration::$saveDirectory
                                                .$this->getdbName().$this->date."/" : "");
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
            if(empty($columnList)) echo ln()."<b>No columns found in ".$this->request->getdbName().".</b>";
            return $columnList;
        }

        public function collectViewList(){
            $this->request->setCmd($this->request->getdbType()->getViewListRequest());
            $this->request->addBinds(array('dbName' => $this->getdbName()));
            $viewList = $this->request->getResults();
            if(empty($viewList)) echo ln()."<b>No views found in ".$this->request->getdbName().".</b>";
            return $viewList;
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

        public function createModels(){
            $columnList = $this->collectColumnList();
            Directory::mkpath($this->directory.StructureFile::$tablesDirectory);

            $previousTable = null;
            $fields = array();
            foreach($columnList as $column){
                if($previousTable != $column->wwtable){
                    if(!is_null($previousTable)){
                        $this->tableList[] = $previousTable;
                        $indexes = $this->getIndexes($previousTable);
                        $f = new StructureFile($this->directory.StructureFile::$tablesDirectory.$previousTable.'.php');
                        $f->writeModel($this->getdbName(), $previousTable, $fields, $indexes);
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

            $this->tableList[] = $previousTable;
            $indexes = $this->getIndexes($previousTable);
            $f = new StructureFile($this->directory.StructureFile::$tablesDirectory.$previousTable.'.php');
            $f->writeModel($this->getdbName(), $previousTable, $fields, $indexes);
        }

        public function createViews(){
            $viewList = $this->collectViewList();
            Directory::mkpath($this->directory.StructureFile::$viewsDirectory);
            foreach($viewList as $view){
                $this->viewList[] = $view->wwview;
                $f = new StructureFile($this->directory.StructureFile::$viewsDirectory.$view->wwview.'.php');
                $f->writeView($this->getdbName(), $view->wwview, $view->wwdefinition);
            }
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

        public function getDate(){
            return $this->date;
        }
    }