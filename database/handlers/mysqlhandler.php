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
                    LEFT JOIN INFORMATION_SCHEMA.TABLES T on (C.TABLE_NAME = T.TABLE_NAME AND C.TABLE_SCHEMA = T.TABLE_SCHEMA)
                    WHERE C.TABLE_SCHEMA = :dbName
                    AND TABLE_COMMENT <> 'VIEW'
                    GROUP BY wwtable, wwfield";
        }

        static public function getTableRequest(){
            return "SELECT C.TABLE_NAME as wwtable, COLUMN_NAME as wwfield,
                    CASE WHEN IS_NULLABLE = 'YES' THEN 1 ELSE 0 END as wwnullable, DATA_TYPE as wwtype,
                    CHARACTER_MAXIMUM_LENGTH as wwlength,
                    CASE WHEN COLUMN_KEY = 'PRI' THEN 1 ELSE 0 END as wwprimary,
                    CASE WHEN EXTRA = 'auto_increment' THEN 1 ELSE 0 END as wwautoincrement,
                    COLUMN_DEFAULT as wwdefault
                    FROM INFORMATION_SCHEMA.COLUMNS C
                    LEFT JOIN INFORMATION_SCHEMA.TABLES T on (C.TABLE_NAME = T.TABLE_NAME AND C.TABLE_SCHEMA = T.TABLE_SCHEMA)
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

        static public function alterTable($tableName, $addedColumns, $droppedColumns, $modifiedColumns, $droppedpk, $pks){
            $cmd = '';

            // if($droppedpk){
            //     $cmd .= "ALTER TABLE ".$tableName." DROP PRIMARY KEY;\n\n";
            // }

            if(!empty($droppedColumns)){
                $cmd .= 'ALTER TABLE '.$tableName;
                foreach($droppedColumns as $droppedColumn){
                    $cmd .= "\n\tDROP COLUMN ".$droppedColumn.",";
                }
                $cmd = substr($cmd, 0, strlen($cmd)-1).";\n\n";
            }

            if(!empty($modifiedColumns)){
                $cmd .= 'ALTER TABLE '.$tableName;
                $count = count($modifiedColumns);
                $i = 1;
                foreach($modifiedColumns as $name => $modifiedColumn){
                    $cmd .= "\n\tMODIFY COLUMN ".$modifiedColumn;
                    if(array_search($name, $pks) !== false) $cmd .= ' PRIMARY KEY,';
                    elseif($name == $droppedpk){
                        $cmd .= ";\n\nALTER TABLE ".$tableName." DROP PRIMARY KEY";
                        if($i < $count) $cmd .= ";\n\nALTER TABLE ".$tableName;
                        else $cmd .= '§'; //add a character to be deleted
                    }
                    else $cmd .= ',';
                    $i++;
                }
                $cmd = substr($cmd, 0, strlen($cmd)-1).";\n\n";
            }
            
            if(!empty($addedColumns)){
                $cmd .= 'ALTER TABLE '.$tableName;
                foreach($addedColumns as $name => $addedColumn){
                    $cmd .= "\n\tADD ".$addedColumn;
                    if(array_search($name, $pks) !== false) $cmd .= ' PRIMARY KEY';
                    $cmd .= ",";
                }
                $cmd = substr($cmd, 0, strlen($cmd)-1).";\n\n";
            }

            return $cmd;
        }

        static public function getDefaultPort(){
            return self::defaultPort;
        }
    }