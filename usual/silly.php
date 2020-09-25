<?php
    function wwscandir($directory){
        $no = array('.', '..', '.DS_STORE');
        return array_diff(scandir($directory), $no);
    }

    function wwnull($var){
        if($var == 'NULL' || $var == 'null' || $var == null) return null;
        else return $var;
    }
?>
