<?php
    namespace whatwhat\database;
    use whatwhat\parsing\ParsedXml;

    class Connection{
        private $dbname;
        private $user;
        private $pwd;
        private $host;
        private $port;
        private $alias;
        private $ressource;
        private $type;
        private static $known = array();

        public function __construct($dbName){
            paramcheck($dbName, 'string');
            $this->dbname = $dbName;
            $this->set($dbName);
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
                                'type' => 'root/env/connection/type');

                $file->__set($context, 'context');

                $dbParam = $file->getData();

                $pdostring = ConnectionType::getPdoString($dbParam['type']);

                foreach($dbParam as $target => $value){
                    $this->__set($value, $target);
                }
                echo "toto";

                $pdostring = str_replace("#host#", $this->host, $pdostring);
                $pdostring = str_replace("#port#", $this->port, $pdostring);
                $pdostring = str_replace("#dbname#", $this->dbname, $pdostring);

                $pdo_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
                $this->ressource = new \PDO($pdostring, $this->user, $this->pwd, $pdo_options);
                self::$known[$this->dbname] = $this->ressource;
                
            }else $this->ressource = self::$known[$this->dbname];
        }

        public function __set($value, $target){
            $this->$target = $value;
        }

        public function getRessource(){
            return $this->ressource;
        }

        public function getType(){
            return $this->type;
        }

        public function getDbName(){
            return $this->dbname;
        }
    }