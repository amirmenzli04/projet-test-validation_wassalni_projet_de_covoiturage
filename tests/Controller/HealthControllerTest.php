<?php

namespace App\Tests\Controller;

use App\Controller\HealthController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class HealthControllerTest extends TestCase
{
    public function testHealthCheckReturnsOkWhenDatabaseIsAvailable(): void
    {
        // Simuler la connexion à la base de données
        $connection = $this->createMock(Connection::class);

        // Simuler le résultat de la requête SQL
        $result = $this->createStub(Result::class);

        $connection->expects(self::once())
            ->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($result);

        // Créer un logger simulé sans attentes particulières
        $logger = $this->createStub(LoggerInterface::class);

        // Exécuter le contrôleur
        $response = (new HealthController())($connection, $logger);

        // Vérifier la réponse HTTP
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('{"status":"ok"}', $response->getContent());
    }
}