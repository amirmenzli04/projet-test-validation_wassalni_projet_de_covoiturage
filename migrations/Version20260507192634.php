<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507192634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table avis et de ses relations';
    }

    public function up(Schema $schema): void
    {
        // Création de la table avis
        $this->addSql('CREATE TABLE avis (
            id INT AUTO_INCREMENT NOT NULL,
            note INT NOT NULL,
            commentaire LONGTEXT DEFAULT NULL,
            date_creation DATETIME NOT NULL,
            passager_id INT NOT NULL,
            conducteur_id INT NOT NULL,
            trajet_id INT NOT NULL,
            INDEX IDX_8F91ABF071A51189 (passager_id),
            INDEX IDX_8F91ABF0F16F4AC6 (conducteur_id),
            INDEX IDX_8F91ABF0D12A823 (trajet_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Relations de la table avis
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF071A51189 FOREIGN KEY (passager_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0F16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0D12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id)');
    }

    public function down(Schema $schema): void
    {
        // Suppression des relations de la table avis
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF071A51189');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0F16F4AC6');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0D12A823');

        // Suppression de la table avis
        $this->addSql('DROP TABLE avis');
    }
}