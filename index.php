<?php
   // use whatwhat\database\Select;
   use whatwhat\database\Migration;
   require("bootstrap/require.php");


   $ww = new Migration();
   $ww->setSourceDb('dev:djBook');
   $ww->setTargetDb('dev:test');
   $ww->setDumpedTables(array('django_migrations', 'django_session'));
   $ww->migrate();