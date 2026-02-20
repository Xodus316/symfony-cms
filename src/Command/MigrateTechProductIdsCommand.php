<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:migrate-techproduct-ids',
    description: 'Migrate TechProduct MongoDB ObjectIds to UUIDs',
)]
class MigrateTechProductIdsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get MongoDB connection from the container
        $mongoClient = new \MongoDB\Client($_ENV['MONGODB_URL']);
        $db = $mongoClient->selectDatabase($_ENV['MONGODB_DB']);
        $collection = $db->selectCollection('tech_products');

        // Collect all documents first
        $migrations = [];
        foreach ($collection->find() as $doc) {
            $oldId = (string) $doc['_id'];
            // Skip documents that already have UUID format
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $oldId)) {
                $io->text("Skipping (already UUID): $oldId");
                continue;
            }
            $migrations[] = ['oldId' => $doc['_id'], 'doc' => (array) $doc, 'uuid' => Uuid::v4()->toRfc4122()];
        }

        // Delete all old documents first (avoids unique index conflicts)
        foreach ($migrations as $m) {
            $collection->deleteOne(['_id' => $m['oldId']]);
        }

        // Insert with new UUIDs and update MySQL references
        $count = 0;
        foreach ($migrations as $m) {
            $newDoc = $m['doc'];
            unset($newDoc['_id']);
            $newDoc['_id'] = $m['uuid'];
            $collection->insertOne($newDoc);

            $this->connection->executeStatement(
                'UPDATE article_techproduct SET techproduct_mongo_id = ? WHERE techproduct_mongo_id = ?',
                [$m['uuid'], (string) $m['oldId']]
            );

            $io->text("Migrated: {$m['oldId']} -> {$m['uuid']}");
            $count++;
        }

        $io->success("Migrated $count tech product(s) to UUID format.");

        return Command::SUCCESS;
    }
}
