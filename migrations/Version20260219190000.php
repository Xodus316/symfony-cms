<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill UUID values for existing article and article_techproduct rows';
    }

    public function up(Schema $schema): void
    {
        // Drop FK so we can update both sides
        $this->addSql('ALTER TABLE article_techproduct DROP FOREIGN KEY FK_A0F7E8727294869C');

        // Generate UUIDs for existing articles, updating article_techproduct FKs to match
        $articles = $this->connection->fetchAllAssociative('SELECT id FROM articles');
        foreach ($articles as $article) {
            $oldId = $article['id'];
            $uuid = $this->generateUuid();

            // Update the article_techproduct FK first (while old ID still exists)
            $this->addSql(
                'UPDATE article_techproduct SET article_id = ? WHERE article_id = ?',
                [$uuid, $oldId]
            );

            // Update the article ID
            $this->addSql(
                'UPDATE articles SET id = ? WHERE id = ?',
                [$uuid, $oldId]
            );
        }

        // Generate UUIDs for existing article_techproduct rows
        $associations = $this->connection->fetchAllAssociative('SELECT id FROM article_techproduct');
        foreach ($associations as $assoc) {
            $oldId = $assoc['id'];
            $uuid = $this->generateUuid();

            $this->addSql(
                'UPDATE article_techproduct SET id = ? WHERE id = ?',
                [$uuid, $oldId]
            );
        }

        // Re-add FK constraint
        $this->addSql('ALTER TABLE article_techproduct ADD CONSTRAINT FK_A0F7E8727294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Cannot reliably reverse UUID generation back to original integer IDs
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
}
