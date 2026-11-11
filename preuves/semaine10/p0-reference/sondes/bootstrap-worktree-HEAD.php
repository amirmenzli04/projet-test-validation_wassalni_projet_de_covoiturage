<?php

use Symfony\Component\Dotenv\Dotenv;

$loader = require dirname(__DIR__).'/vendor/autoload.php';
// Force le chargement des classes App depuis le worktree HEAD (et non depuis le depot reel)
$loader->addPsr4('App\\', dirname(__DIR__).'/src/', true);
$loader->addPsr4('App\\Tests\\', __DIR__.'/', true);

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

fwrite(STDERR, 'Reservation chargee depuis: '.(new ReflectionClass(App\Entity\Reservation::class))->getFileName()."\n");
