<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change article and article_techproduct IDs from integer to UUID (CHAR(36))';
    }

    public function up(Schema $schema): void
    {
        // Drop foreign key constraint first
        $this->addSql('ALTER TABLE article_techproduct DROP FOREIGN KEY FK_A0F7E8727294869C');

        // Convert article_techproduct.id from INT to CHAR(36) with UUID default
        $this->addSql('ALTER TABLE article_techproduct MODIFY id CHAR(36) NOT NULL');

        // Convert article_techproduct.article_id from INT to CHAR(36)
        $this->addSql('ALTER TABLE article_techproduct MODIFY article_id CHAR(36) NOT NULL');

        // Convert articles.id from INT to CHAR(36)
        $this->addSql('ALTER TABLE articles MODIFY id CHAR(36) NOT NULL');

        // Re-add foreign key constraint
        $this->addSql('ALTER TABLE article_techproduct ADD CONSTRAINT FK_A0F7E8727294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint
        $this->addSql('ALTER TABLE article_techproduct DROP FOREIGN KEY FK_A0F7E8727294869C');

        // Revert articles.id back to INT AUTO_INCREMENT
        $this->addSql('ALTER TABLE articles MODIFY id INT AUTO_INCREMENT NOT NULL');

        // Revert article_techproduct.article_id back to INT
        $this->addSql('ALTER TABLE article_techproduct MODIFY article_id INT NOT NULL');

        // Revert article_techproduct.id back to INT AUTO_INCREMENT
        $this->addSql('ALTER TABLE article_techproduct MODIFY id INT AUTO_INCREMENT NOT NULL');

        // Re-add foreign key constraint
        $this->addSql('ALTER TABLE article_techproduct ADD CONSTRAINT FK_A0F7E8727294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE');
    }
}
