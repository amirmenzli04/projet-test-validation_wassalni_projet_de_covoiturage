<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506204822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs de vérification du compte utilisateur';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les champs nécessaires à la vérification du compte
        $this->addSql('ALTER TABLE `user` ADD is_verified TINYINT NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD verification_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD verification_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer uniquement les champs ajoutés par cette migration
        $this->addSql('ALTER TABLE `user` DROP is_verified, DROP verification_token, DROP verification_token_expires_at');
    }
}