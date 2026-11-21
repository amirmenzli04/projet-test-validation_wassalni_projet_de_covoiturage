<?php
require __DIR__.'/tests/bootstrap.php';
$k = new App\Kernel('test', true); $k->boot();
$em = $k->getContainer()->get('doctrine')->getManager();
$errors = (new Doctrine\ORM\Tools\SchemaValidator($em))->validateMapping();
echo json_encode($errors, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";
