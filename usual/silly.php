<?php
    function wwnull($var){
        if($var == 'NULL' || $var == 'null' || $var == null) return null;
        else return $var;
    }