<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

final class Version20260222093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change musique.audio from longblob to string filename and add updated_at for VichUploader triggers';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on mysql.');

        $this->addSql('UPDATE musique SET audio = NULL');
        $this->addSql('ALTER TABLE musique CHANGE audio audio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE musique ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on mysql.');

        $this->addSql("UPDATE musique SET audio = '' WHERE audio IS NULL");
        $this->addSql('ALTER TABLE musique DROP updated_at');
        $this->addSql('ALTER TABLE musique CHANGE audio audio LONGBLOB NOT NULL');
    }
}
