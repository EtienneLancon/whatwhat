<?php
    namespace whatwhat\database;

    class MysqlHandler implements ConnectionHandler{
        const defaultPort = "3306";

        static public function getPdoString(){
            return "mysql:host=#host#:#port#;dbname=#dbname#";
        }

        static public function getTableRequest(){
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
        }

        static public function getViewRequest(){
            return "SELECT TABLE_NAME as wwview,
                    VIEW_DEFINITION as wwdefinition
                    FROM INFORMATION_SCHEMA.VIEWS
                    where TABLE_SCHEMA = :dbName";
        }

        static public function getIndexRequest(){
            return "SHOW INDEXES FROM __table
                    WHERE Key_name <> 'primary'";
        }

        static public function getIndexRequestBindName(){
            return "__table";
        }

        static public function getIndexFilter(){
            return array('wwindex' => 'Key_name',
                        'wwcolumn' => 'Column_name',
                        'wwnull' => 'Null',
                        'wwtype' => 'Index_type');
        }

        static public function getDefaultPort(){
            return self::defaultPort;
        }
    }