<?php
   // use whatwhat\database\Select;
   use whatwhat\file\File;
   use whatwhat\database\Request;
   require("bootstrap/require.php");
   // $req = new Select();
   // var_dump($req->getAll('livre', array('titre' => 'zdfadvadv')));
   // $req->setCmd('select * from steril');
   // var_dump($req->getResults());
   // $s = new Migration('eni');
   // $s->collect();

   $req = new Request('Syface');
   var_dump($req->getAll('FOURNISSEUR'));

