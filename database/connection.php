<?php
    namespace whatwhat\database;
    use whatwhat\parsing\ParsedXml;

    class Connection{
        private $dbname;
        private $user;
        private $pwd;
        private $host;
        private $port;
        private $pdostring;
        private $alias;
        private $ressource;
        private $type;

        public function __construct($dbName){
            paramcheck($dbName, 'string');
            $this->dbname = $dbName;
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

            $this->setPdoString($dbParam['type']);

            foreach($dbParam as $target => $value){
                $this->__set($value, $target);
            }

            $this->pdostring = str_replace("#host#", $this->host, $this->pdostring);
            $this->pdostring = str_replace("#port#", $this->port, $this->pdostring);
            $this->pdostring = str_replace("#dbname#", $this->dbname, $this->pdostring);

            $this->ressource = new \PDO($this->pdostring, $this->user, $this->pwd);
        }

        private function setPdoString($type){
            switch($type){
                case 'mysql':
                    $this->pdostring = "mysql:host=#host#:#port#;dbname=#dbname#";
                    break;
                case 'sqlsrv':
                    $this->pdostring = "sqlsrv:server=#host#,#port#;database=#dbname#";
                    break;
                default:
                    throw new \Exception('Unknown database type.');
            }
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