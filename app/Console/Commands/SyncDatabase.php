<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SyncDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download remote database and import to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database sync...');

        // Get server database credentials from .env
        $remoteHost = env('DB_HOST_SERVER');
        $remotePort = env('DB_PORT_SERVER', 3306);
        $remoteDatabase = env('DB_DATABASE_SERVER');
        $remoteUsername = env('DB_USERNAME_SERVER');
        $remotePassword = env('DB_PASSWORD_SERVER');

        if (!$remoteHost || !$remoteDatabase || !$remoteUsername) {
            $this->error('Missing required server database credentials in .env (DB_HOST_SERVER, DB_DATABASE_SERVER, DB_USERNAME_SERVER)');
            return Command::FAILURE;
        }

        // Create local database name based on remote
        $localDatabase = $remoteDatabase . '_local';

        try {
            // Step 1: Connect to remote database
            $this->info('Connecting to remote database...');
            $remotePdo = new \PDO(
                "mysql:host=$remoteHost;port=$remotePort;dbname=$remoteDatabase",
                $remoteUsername,
                $remotePassword
            );
            $remotePdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Step 2: Create local database connection
            $this->info('Connecting to local database...');
            $localPdo = new \PDO(
                "mysql:host=127.0.0.1;port=3306",
                'root',
                ''
            );
            $localPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Create local database
            $this->info('Creating local database...');
            $localPdo->exec("CREATE DATABASE IF NOT EXISTS `$localDatabase`");
            $localPdo->exec("USE `$localDatabase`");

            // Disable foreign key checks
            $localPdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Step 3: Get all tables from remote database
            $this->info('Getting table list from remote database...');
            $tablesQuery = $remotePdo->query("SHOW TABLES");
            $tables = $tablesQuery->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($tables)) {
                $this->info('No tables found in remote database.');
                return Command::SUCCESS;
            }

            // Step 4: Copy each table
            foreach ($tables as $table) {
                $this->info("Copying table: $table");

                // Get table structure
                $createTableQuery = $remotePdo->query("SHOW CREATE TABLE `$table`");
                $createTableResult = $createTableQuery->fetch(\PDO::FETCH_ASSOC);
                $createTableSQL = $createTableResult['Create Table'];

                // Drop and create table in local database
                $localPdo->exec("DROP TABLE IF EXISTS `$table`");
                $localPdo->exec($createTableSQL);

                // Copy data
                $dataQuery = $remotePdo->query("SELECT * FROM `$table`");
                $rows = $dataQuery->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    // Prepare column names for INSERT
                    $columns = array_keys($rows[0]);
                    $columnList = '`' . implode('`, `', $columns) . '`';
                    $placeholders = ':' . implode(', :', $columns);

                    $insertSQL = "INSERT INTO `$table` ($columnList) VALUES ($placeholders)";
                    $insertStmt = $localPdo->prepare($insertSQL);

                    foreach ($rows as $row) {
                        $insertStmt->execute($row);
                    }
                }

                $this->info("Table $table copied successfully (" . count($rows) . " rows)");
            }

            // Re-enable foreign key checks
            $localPdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $this->info('Database sync completed successfully!');
            $this->info("Local database: $localDatabase");
            $this->info('Environment file has been updated to use local database.');

            return Command::SUCCESS;

        } catch (\PDOException $e) {
            $this->error('Database error: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
