<?php
   // use whatwhat\database\Select;
   use whatwhat\database\Migration;
   require("bootstrap/require.php");


   $ww = new Migration();
   $ww->setSourceDb('dev:eni');
   $ww->setTargetDb('dev:djBook');
   $ww->migrate();