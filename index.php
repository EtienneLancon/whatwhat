<?php
   // use whatwhat\database\Select;
   use whatwhat\file\File;
   use whatwhat\database\Request;
   use whatwhat\database\Migration;
   require("bootstrap/require.php");


   echo "toto";
   echo ln(10);
   echo "tutu";
   $ww = new Migration('eni');
   $ww->migrate();