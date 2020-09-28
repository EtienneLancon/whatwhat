<?php
   // use whatwhat\database\Select;
   use whatwhat\file\File;
   use whatwhat\database\Request;
   use whatwhat\database\Migration;
   require("bootstrap/require.php");




   $ww = new Migration('eni');
   $ww->migrate();