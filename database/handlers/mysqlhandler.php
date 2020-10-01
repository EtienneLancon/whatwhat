<?php
    namespace whatwhat\database;

    class MysqlHandler implements ConnectionHandler{
        const defaultPort = "3306";

        static public function getPdoString(){
            return "mysql:host=#host#:#port#;dbname=#dbname#";
        }

        static public function getTableListRequest(){
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
                    and C.TABLE_NAME = :table
                    and TABLE_COMMENT <> 'VIEW'";
        }

        static public function getViewListRequest(){
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
                        'wwcolumn' => 'Column_name');
        }

        static public function getTableExistsRequest(){
            return "select *
                    from information_schema.TABLES t
                    where t.TABLE_NAME  = :table
                    and t.TABLE_SCHEMA = :schema";
        }

        static public function alterTable($tableName, $addedColumns, $droppedColumns, $modifiedColumns){
            $cmd = '';
            
            if(!empty($addedColumns)){
                $cmd .= 'ALTER TABLE '.$tableName."\n\tADD ";
                $first = true;
                foreach($addedColumns as $addedColumn){
                    if($first){
                        $cmd .= $addedColumn;
                        $first = false;
                    }else $cmd .= "\n\t, ".$addedColumn;
                }
                $cmd .= ";\n\n";
            }

            if(!empty($droppedColumns)){
                $cmd .= 'ALTER TABLE '.$tableName." DROP";
                $first = true;
                foreach($droppedColumns as $droppedColumn){
                    $cmd .= "\n\tCOLUMN ".$droppedColumn.(($first) ? "" : ",");
                    $first = false;
                }
                $cmd .= ";\n\n";
            }

            if(!empty($modifiedColumns)){
                $cmd .= 'ALTER TABLE '.$tableName;
                $first = true;
                foreach($modifiedColumns as $modifiedColumn){
                    $cmd .= "\n\tMODIFY ".$modifiedColumn.(($first) ? "" : ",");
                    $first = false;
                }
                $cmd .= ";\n\n";
            }

            return $cmd;
        }

        static public function getDefaultPort(){
            return self::defaultPort;
        }
    }