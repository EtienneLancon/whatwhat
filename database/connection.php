<?php
    namespace whatwhat\database;
    use whatwhat\parsing\ParsedXml;

    class Connection{
        private $dbname;
        private $alias;
        private $ressource;
        private $dbtype;
        private static $known = array();

        public function __construct($dbName){
            $this->dbname = $dbName;
            $this->set();
        }

        private function set(){
            if(array_key_exists($this->dbname, self::$known) === false){
                $file = new ParsedXml();
                $file->__set('root', 'root');
                $file->__set('whatwhat/parameters/parameters.xml', 'path');
                $file->__set('xml', 'type');

                $context = array('user' => 'root/env/connection/user',
                                'pwd' => 'root/env/connection/pwd',
                                'host' => 'root/env/connection/host',
                                'port' => 'root/env/connection/port',
                                'dbtype' => 'root/env/connection/type');

                $file->__set($context, 'context');

                $dbParam = $file->getData();

                $this->dbtype = $this->switchConnectionHandler($dbParam['dbtype']);

                $pdostring = $this->dbtype->getPdoString();

                $pdostring = str_replace("#host#", $dbParam['host'], $pdostring);
                $pdostring = str_replace("#port#", (!empty($dbParam['port'])) ? $dbParam['port'] : $this->dbtype->getDefaultPort(), $pdostring);
                $pdostring = str_replace("#dbname#", $this->dbname, $pdostring);

                $pdo_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
                $this->ressource = new \PDO($pdostring, $dbParam['user'], 
                                        (isset($dbParam['pwd'])) ? $dbParam['pwd'] : '', $pdo_options);
                self::$known[$this->dbname] = array('ressource' => $this->ressource,
                                                    'dbtype' => $this->dbtype);
                
            }else{
                $this->ressource = self::$known[$this->dbname]['ressource'];
                $this->dbtype = self::$known[$this->dbname]['dbtype'];
            }
        }

        private function switchConnectionHandler($dbtype){
            switch($dbtype){
                case 'mysql':
                    require_once('whatwhat/database/handlers/mysqlhandler.php');
                    return new MysqlHandler();
                case 'sqlsrv':
                    require_once('whatwhat/database/handlers/sqlsrvhandler.php');
                    return new SqlsrvHandler();
                default:
                    paramcheck($dbtype, 'string');
                    throw new \Exception('Unknown database type : '.$dbtype);
            }
        }

        public function __set($value, $target){
            $this->$target = $value;
        }

        public function getRessource(){
            return $this->ressource;
        }

        public function getdbType(){
            return $this->dbtype;
        }

        public function getDbName(){
            return $this->dbname;
        }
    }