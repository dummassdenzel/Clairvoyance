<?php

/**
 * Migration Runner Script
 * 
 * This script runs database migrations in order.
 * Usage: php scripts/migrate.php [--fresh] [--migration=001]
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\Application;

class MigrationRunner
{
    private $db;
    private $migrationsPath;
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getContainer()->resolve(\PDO::class);
        $this->migrationsPath = __DIR__ . '/../migrations/';
    }
    
    public function run($fresh = false, $specificMigration = null)
    {
        echo "🚀 Starting database migrations...\n\n";
        
        if ($fresh) {
            echo "⚠️  Fresh migration requested - this will drop all tables!\n";
            $this->dropAllTables();
        }
        
        // Ensure migration tracking table exists
        $this->createMigrationTrackingTable();
        
        // Get list of migration files
        $migrations = $this->getMigrationFiles();
        
        if ($specificMigration) {
            $migrations = array_filter($migrations, function($file) use ($specificMigration) {
                return strpos($file, $specificMigration) !== false;
            });
        }
        
        foreach ($migrations as $migration) {
            $this->runMigration($migration);
        }
        
        echo "\n✅ All migrations completed successfully!\n";
    }
    
    private function createMigrationTrackingTable()
    {
        $sql = file_get_contents($this->migrationsPath . '000_create_migration_tracking.sql');
        $this->db->exec($sql);
        echo "✓ Migration tracking table ready\n";
    }
    
    private function getMigrationFiles()
    {
        $files = glob($this->migrationsPath . '*.sql');
        $files = array_filter($files, function($file) {
            return basename($file) !== '000_create_migration_tracking.sql';
        });
        sort($files);
        return $files;
    }
    
    private function runMigration($migrationFile)
    {
        $migrationName = basename($migrationFile);
        
        // Check if migration already ran
        $stmt = $this->db->prepare("SELECT id FROM migrations WHERE migration_name = ?");
        $stmt->execute([$migrationName]);
        
        if ($stmt->fetch()) {
            echo "⏭️  Skipping {$migrationName} (already executed)\n";
            return;
        }
        
        echo "🔄 Running {$migrationName}...\n";
        
        try {
            $sql = file_get_contents($migrationFile);
            $this->db->exec($sql);
            
            // Record migration as completed
            $stmt = $this->db->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
            $stmt->execute([$migrationName]);
            
            echo "✅ {$migrationName} completed successfully\n";
        } catch (Exception $e) {
            echo "❌ Error running {$migrationName}: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function dropAllTables()
    {
        echo "🗑️  Dropping all tables...\n";
        
        $tables = [
            'kpi_entries',
            'kpis', 
            'dashboard_share_tokens',
            'dashboard_access',
            'dashboards',
            'users',
            'migrations'
        ];
        
        foreach ($tables as $table) {
            try {
                $this->db->exec("DROP TABLE IF EXISTS {$table}");
                echo "✓ Dropped table: {$table}\n";
            } catch (Exception $e) {
                echo "⚠️  Could not drop table {$table}: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Parse command line arguments
$fresh = false;
$specificMigration = null;

foreach ($argv as $arg) {
    if ($arg === '--fresh') {
        $fresh = true;
    } elseif (strpos($arg, '--migration=') === 0) {
        $specificMigration = substr($arg, 12);
    }
}

// Run migrations
try {
    $runner = new MigrationRunner();
    $runner->run($fresh, $specificMigration);
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
