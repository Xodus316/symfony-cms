<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen techproduct_mongo_id column from VARCHAR(24) to VARCHAR(36) for UUID format';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_techproduct MODIFY techproduct_mongo_id VARCHAR(36) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_techproduct MODIFY techproduct_mongo_id VARCHAR(24) NOT NULL');
    }
}
