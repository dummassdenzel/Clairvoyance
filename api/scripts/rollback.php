<?php

/**
 * Migration Rollback Script
 * 
 * This script rolls back database migrations.
 * Usage: php scripts/rollback.php [--migration=001] [--all]
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\Application;

class MigrationRollback
{
    private $db;
    private $migrationsPath;
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getContainer()->resolve(\PDO::class);
        $this->migrationsPath = __DIR__ . '/../migrations/';
    }
    
    public function rollback($specificMigration = null, $all = false)
    {
        echo "🔄 Starting migration rollback...\n\n";
        
        if ($all) {
            echo "⚠️  Rolling back ALL migrations!\n";
            $this->rollbackAll();
            return;
        }
        
        if ($specificMigration) {
            $this->rollbackSpecific($specificMigration);
        } else {
            echo "❌ Please specify --migration=XXX or --all\n";
            echo "Usage: php scripts/rollback.php --migration=001\n";
            echo "       php scripts/rollback.php --all\n";
        }
    }
    
    private function rollbackSpecific($migrationNumber)
    {
        $migrationName = "{$migrationNumber}_*.sql";
        $files = glob($this->migrationsPath . $migrationName);
        
        if (empty($files)) {
            echo "❌ Migration {$migrationNumber} not found\n";
            return;
        }
        
        $migrationFile = $files[0];
        $migrationName = basename($migrationFile);
        
        // Check if migration was executed
        $stmt = $this->db->prepare("SELECT id FROM migrations WHERE migration_name = ?");
        $stmt->execute([$migrationName]);
        
        if (!$stmt->fetch()) {
            echo "⏭️  Migration {$migrationName} was not executed\n";
            return;
        }
        
        echo "🔄 Rolling back {$migrationName}...\n";
        
        try {
            // Get rollback SQL (we'll need to create these)
            $rollbackSql = $this->getRollbackSql($migrationNumber);
            
            if ($rollbackSql) {
                $this->db->exec($rollbackSql);
                
                // Remove from migration tracking
                $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration_name = ?");
                $stmt->execute([$migrationName]);
                
                echo "✅ {$migrationName} rolled back successfully\n";
            } else {
                echo "⚠️  No rollback SQL available for {$migrationName}\n";
            }
        } catch (Exception $e) {
            echo "❌ Error rolling back {$migrationName}: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function rollbackAll()
    {
        // Drop all tables in reverse order
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
        
        echo "✅ All tables dropped successfully\n";
    }
    
    private function getRollbackSql($migrationNumber)
    {
        // For now, we'll just drop tables
        // In a production system, you'd want proper rollback SQL files
        $rollbackMap = [
            '001' => "DROP TABLE IF EXISTS users;",
            '002' => "DROP TABLE IF EXISTS dashboards;",
            '003' => "DROP TABLE IF EXISTS dashboard_access;",
            '004' => "DROP TABLE IF EXISTS dashboard_share_tokens;",
            '005' => "DROP TABLE IF EXISTS kpis;",
            '006' => "DROP TABLE IF EXISTS kpi_entries;",
            '007' => "-- No rollback needed for indexes"
        ];
        
        return $rollbackMap[$migrationNumber] ?? null;
    }
}

// Parse command line arguments
$specificMigration = null;
$all = false;

foreach ($argv as $arg) {
    if ($arg === '--all') {
        $all = true;
    } elseif (strpos($arg, '--migration=') === 0) {
        $specificMigration = substr($arg, 12);
    }
}

// Run rollback
try {
    $rollback = new MigrationRollback();
    $rollback->rollback($specificMigration, $all);
} catch (Exception $e) {
    echo "\n❌ Rollback failed: " . $e->getMessage() . "\n";
    exit(1);
}
