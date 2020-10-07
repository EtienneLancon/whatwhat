<?php
    namespace whatwhat\database;
    
    interface ConnectionHandler{

        static public function getPdoString();

        static public function getTableListRequest();

        static public function getTableRequest();

        static public function getViewListRequest();

        static public function getIndexRequest();

        static public function getIndexRequestBindName();

        static public function getIndexFilter();

        static public function getTableExistsRequest();

        static public function alterTable($tableName, $addedColumns, $droppedColumns, $modifiedColumns, $droppedpk, $pks);

        static public function getDefaultPort();
    }