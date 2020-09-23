<?php

    function paramcheck($value, $expected){
        $given = gettype($value);
        if($given != $expected) throw new \Exception('Expecting '.$expected.", ".$given." given.");
    }