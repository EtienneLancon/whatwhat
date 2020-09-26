<?php
    namespace whatwhat\database;
    //the boring class which makes switches
    class ConnectionType{

        public static function getPdoString($type){
            switch($type){
                case 'mysql':
                    return "mysql:host=#host#:#port#;dbname=#dbname#";
                case 'sqlsrv':
                    return "sqlsrv:server=#host#,#port#;database=#dbname#";
                    break;
                default:
                    throw new \Exception('Unknown database type.');
            }
        }

        public static function getTableRequest($type){
            switch($type){
                case 'mysql':
                    return "SELECT C.TABLE_NAME as wwtable, COLUMN_NAME as wwfield,
                            CASE WHEN IS_NULLABLE = 'YES' THEN 1 ELSE 0 END as wwnullable, DATA_TYPE as wwtype,
                            CHARACTER_MAXIMUM_LENGTH as wwlength,
                            CASE WHEN COLUMN_KEY = 'PRI' THEN 1 ELSE 0 END as wwprimary,
                            CASE WHEN EXTRA = 'auto_increment' THEN 1 ELSE 0 END as wwautoincrement,
                            COLUMN_DEFAULT as wwdefault
                            FROM INFORMATION_SCHEMA.COLUMNS C
                            LEFT JOIN INFORMATION_SCHEMA.TABLES T on C.TABLE_NAME = T.TABLE_NAME
                            where C.TABLE_SCHEMA = :dbName
                            and TABLE_COMMENT <> 'VIEW'";
                case 'sqlsrv':
                default:
                    throw new \Exception('Unable to determine database type.');
            }
        }

        public static function getViewRequest($type){
            switch($type){
                case 'mysql':
                    return "SELECT TABLE_NAME as wwview,
                            VIEW_DEFINITION as wwdefinition
                            FROM INFORMATION_SCHEMA.VIEWS
                            where TABLE_SCHEMA = :dbName";
                case 'sqlsrv':
                default:
                    throw new \Exception('Unable to determine database type.');
            }
        }
    }