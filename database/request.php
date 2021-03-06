<?php
    namespace whatwhat\database;

    class Request{
        protected $cmd;
        protected $db;
        protected $stmt;
        protected $bindCount;
        protected $binds;

        public function __construct($dbtag){
            if(strpos($dbtag, ':') === false || count($data = explode(':', $dbtag)) != 2)
                            throw new \Exception('Database wrongly defined. Expecting "envname:databasename"');
            $this->bindCount = 0;
            $this->binds = array();
            $this->db = new Connection($data[0], $data[1]);
        }

        public function bindexec(){
            $this->setStmt();
            if(!empty($this->binds)){
                foreach($this->binds as $param => $value){
                    if(is_bool($value)){
                        $this->stmt->bindValue(':'.$param, $value, \PDO::PARAM_BOOL);
                    }elseif(is_numeric($value) && is_integer($value)){
                        $this->stmt->bindValue(':'.$param, $value, \PDO::PARAM_INT);
                    }else{
                        $this->stmt->bindValue(':'.$param, $value, \PDO::PARAM_STR);
                    }
                }
            }
            $this->stmt->execute();
            $this->bindCount = 0;
            $this->binds = array();
        }
        
        protected function setStmt(){
            foreach(array_keys($this->binds) as $key){
                if(strpos($key, '__table') === 0){
                    $this->cmd = str_replace($key, $this->binds[$key], $this->cmd);
                    unset($this->binds[$key]);
                }
            }
            $this->stmt = $this->db->getRessource()->prepare($this->cmd);
        }

        protected function clause($where = null){
            if(!empty($where)){
                $this->cmd .= " where ";
                $i = 0;
                $tempBinds = array();
                foreach($where as $key => $value){
                    $this->cmd .= (($i == 0) ? '' : ' and ').$key." = :".$key.strval($this->bindCount);
                    $tempBinds[$key.strval($this->bindCount)] = $value;
                    $this->bindCount++;
                    $i++;
                }
                $this->addBinds($tempBinds);
            }
        }

        public function addBinds($addedBinds){
            paramcheck($addedBinds, 'array');
            $this->binds = array_merge($addedBinds, $this->binds);
        }

        public function getResults(){
            $this->bindexec();
            $return = array();
            while(($row = $this->stmt->fetchObject()) !== false){
                array_push($return, $row);
            }
            return $return;
        }

        public function getAll($table, $where = null){
            $this->setCmd('select * from '.$table);
            $this->clause($where);
            return $this->getResults();
        }

        public function getBindCount(){
            return $this->bindCount;
        }

        public function setBindCount($nb){
            $this->bindCount = $nb;
        }

        public function getdbType(){
            return $this->db->getdbType();
        }

        public function getdbName(){
            return $this->db->getdbName();
        }

        public function getEnv(){
            return $this->db->getEnv();
        }

        public function setCmd($cmd){
            $this->cmd = $cmd;
        }

        public function moreCmd($addedCmd){
            $this->cmd .= $addedCmd;
        }

        public function getStmt(){
            return $this->stmt;
        }
    }