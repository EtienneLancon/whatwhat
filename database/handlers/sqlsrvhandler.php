<?php
    namespace whatwhat\database;

    class SqlsrvHandler implements ConnectionHandler{
        const defaultPort = "1433";

        static public function getPdoString(){
            return "sqlsrv:server=#host#,#port#;database=#dbname#";
        }

        static public function getTableListRequest(){
            return "SELECT TABLE_NAME as wwtable, S.name as wwfield,
                    S.is_nullable as wwnullable, TY.name as wwtype,
                    S.max_length / 2 as wwlength,
                    is_identity as wwprimary,
                    is_identity as wwautoincrement
                    FROM INFORMATION_SCHEMA.TABLES T 
                    left join sys.columns S on S.object_id = OBJECT_ID(T.TABLE_NAME)
                    left join sys.types TY on S.user_type_id = TY .user_type_id
                    where TABLE_TYPE = 'BASE TABLE'
                    and T.TABLE_NAME <> 'sysdiagrams'
					and TABLE_CATALOG = :dbName";
        }

        static public function getTableRequest(){
            return "SELECT TABLE_NAME as wwtable, S.name as wwfield,
                    S.is_nullable as wwnullable, TY.name as wwtype,
                    S.max_length / 2 as wwlength,
                    is_identity as wwprimary,
                    is_identity as wwautoincrement
                    FROM INFORMATION_SCHEMA.TABLES T 
                    left join sys.columns S on S.object_id = OBJECT_ID(T.TABLE_NAME)
                    left join sys.types TY on S.user_type_id = TY .user_type_id
                    where T.TABLE_NAME = :table
                    and TABLE_CATALOG = :dbName";
        }

        static public function getViewListRequest(){
            return "SELECT TABLE_NAME as wwview,
                    SUBSTRING(VIEW_DEFINITION, CHARINDEX('SELECT', VIEW_DEFINITION), LEN(VIEW_DEFINITION) - CHARINDEX('SELECT', VIEW_DEFINITION)) as wwdefinition
                    FROM INFORMATION_SCHEMA.VIEWS
                    WHERE TABLE_CATALOG = :dbName";
        }

        static public function getIndexRequest(){
            return "select i.name as wwindex, c.name as wwcolumn from INFORMATION_SCHEMA.TABLES T
                    left join sys.indexes i on i.object_id = OBJECT_ID(T.TABLE_NAME)
                    left join sys.index_columns ic on i.index_id = ic.index_id
                    left join sys.columns c on (ic.object_id = c.object_id and ic.column_id = c.column_id)
                    where i.name is not null
                    and i.is_primary_key = 0
                    and T.TABLE_NAME = :table";
        }

        static public function getIndexRequestBindName(){
            return "table";
        }

        static public function getIndexFilter(){
            return array('wwindex' => 'wwindex',
                        'wwcolumn' => 'wwcolumn');
        }

        static public function getTableExistsRequest(){
            return "select *
                    from INFORMATION_SCHEMA.TABLES t
                    where t.TABLE_NAME  = :table";
        }

        static public function alterTable($tableName, $addedColumns, $droppedColumns, $modifiedColumns, $droppedpk){
            
        }

        static public function getDefaultPort(){
            return self::defaultPort;
        }
    }