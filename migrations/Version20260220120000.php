<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add article locking fields: locked_by_id and locked_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles ADD locked_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE articles ADD locked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_ARTICLES_LOCKED_BY FOREIGN KEY (locked_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_article_locked_by ON articles (locked_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles DROP FOREIGN KEY FK_ARTICLES_LOCKED_BY');
        $this->addSql('DROP INDEX idx_article_locked_by ON articles');
        $this->addSql('ALTER TABLE articles DROP locked_by_id');
        $this->addSql('ALTER TABLE articles DROP locked_at');
    }
}
