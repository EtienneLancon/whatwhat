<?php
    namespace whatwhat\database;
    
    interface ConnectionHandler{

        static public function getPdoString();

        static public function getTableRequest();

        static public function getViewRequest();

        static public function getIndexRequest();

        static public function getIndexFilter();
    }