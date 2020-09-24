<?php
   // use whatwhat\database\Select;
   use whatwhat\file\File;
   use whatwhat\database\Migration;
   require("bootstrap/require.php");
   // $req = new Select();
   // var_dump($req->getAll('livre', array('titre' => 'zdfadvadv')));
   // $req->setCmd('select * from steril');
   // var_dump($req->getResults());
   // $s = new Migration('eni');
   // $s->collect();
   $req = new Migration('test');
   //$req->collect();
   $req->makeMigration('wwmigrations/test2020-24-9_17-07-25.mig');

