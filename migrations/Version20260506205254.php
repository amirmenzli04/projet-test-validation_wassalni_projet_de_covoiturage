<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506205254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration sans modification : les clés étrangères existent déjà';
    }

    public function up(Schema $schema): void
    {
        // Les clés étrangères ont déjà été créées par la première migration.
    }

    public function down(Schema $schema): void
    {
        // Aucune modification à annuler.
    }
}