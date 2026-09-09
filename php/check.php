<?php
require __DIR__.'/bootstrap.php';json_out(['ok'=>true,'php'=>PHP_VERSION,'pdo_mysql'=>extension_loaded('pdo_mysql'),'mysql'=>'OK','database'=>$config['db']['name']]);
