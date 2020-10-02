<?php
    function wwnull($var){
        if($var == 'NULL' || $var == 'null' || $var == null) return null;
        else return $var;
    }

    function ln($nb = 1){
        $context = php_sapi_name();
        $cat = '';
        for($i = 0; $i < $nb; $i++){
            $cat .= ($context == 'cli') ? PHP_EOL : "<br/>";
        }
        return $cat;
    }
    
    function paramcheck($value, $expected){
        $given = gettype($value);

        if(is_string($expected)){
            if($given != $expected) throw new \Exception('Expecting '.$expected.", ".$given." given.");
        }else{
            $ok = false;
            foreach($expected as $exp){
                if($given == $expected) $ok = true;
            }
            if(!$ok){
                throw new \Exception('Expecting '.implode(' or ', $expected).", ".$given." given.");
            }
        }
    }