<?php
    namespace whatwhat\database;

    class Request{
        protected $cmd;
        protected $db;
        protected $stmt;
        protected $bindCount;
        protected $binds;

        public function __construct($dbname){
            paramcheck($dbname, 'string');
            $this->bindCount = 0;
            $this->binds = array();
            $this->db = new Connection($dbname);
        }

        protected function bindexec(){
            if(!empty($this->binds)){
                foreach($this->binds as $param => $value){
                    if(strpos($param, '__table') === 0) throw new \Exception('A table binding must be done before statement is setted');
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
        }
        
        protected function setStmt(){
            foreach(array_keys($this->binds) as &$key){
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
            $this->binds = array_merge($addedBinds, $this->binds);
        }

        public function getResults(){
            $this->setStmt();
            $this->bindexec();
            $return = array();
            var_dump($this->stmt);
            while(($row = $this->stmt->fetchObject()) !== false){
                echo "toto";
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

        public function setCmd($cmd){
            $this->cmd = $cmd;
        }
    }