<?php

/**
 * Routeur du serveur PHP intégré pour les tests navigateur (Playwright) et API (Newman).
 *
 * `php -S` ne recopie pas les variables d'environnement dans $_SERVER/$_ENV (variables_order sans « E »),
 * si bien que .env imposerait APP_ENV=dev, c'est-à-dire la base principale « wassalni ». Ce routeur force
 * l'environnement « test » AVANT le démarrage de Symfony : la base utilisée est alors wassalni_test
 * (suffixe _test ajouté par config/packages/doctrine.yaml).
 *
 * Usage : php -S 127.0.0.1:8000 -t public scripts/serveur-test-router.php
 */
foreach (['APP_ENV' => 'test', 'APP_DEBUG' => '0'] as $name => $value) {
    putenv("$name=$value");
    $_SERVER[$name] = $_ENV[$name] = $value;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicFile = dirname(__DIR__) . '/public' . $path;
if ($path !== '/' && is_file($publicFile)) {
    return false; // fichier statique servi directement par php -S
}

$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require dirname(__DIR__) . '/public/index.php';
