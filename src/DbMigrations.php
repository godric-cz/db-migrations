<?php

namespace Godric\DbMigrations;

define('INITIAL_MIGRATION_HASH', 'initial_migration_hash');
define('LAST_APPLIED_MIGRATION_ID', 'last_applied_migration_id');
define('LATEST_MIGRATION_HASH', 'latest_migration_hash');

class DbMigrations {

    private
        $backups, // TODO
        $db,
        $datastore,
        $directory,
        $rewriteDatabaseOnInitialMigrationChange = true, // TODO
        $rewriteDatabaseOnLatestMigrationChange = true; // TODO

    private static
        $defaultParams = [
            'migrationsDirectory'   =>  './migrations',
            'tableName'             =>  'db_migrations',
            'doBackups'             =>  true, // backups must be explicitly disabled
            'backupsDirectory'      =>  null, // force manual configuration for security
        ];

    function __construct($params) {
        $realParams = array_merge(self::$defaultParams, $params);

        $this->db = $realParams['connection'];
        $this->directory = $realParams['migrationsDirectory'];
        $this->datastore = new Datastore($this->db, $realParams['tableName']);
        $this->backups = new Backups($this->db, $realParams['backupsDirectory'], !$realParams['doBackups']);

        $this->loadMigrations();
    }

    private function apply(Migration $migration) {
        echo "applying migration {$migration->getId()}\n";

        // backup db
        $this->backups->backupBefore($migration);

        // apply migration
        $this->db->query('BEGIN');
        $migration->apply(); // TODO or applyTo
        $this->datastore->set(LAST_APPLIED_MIGRATION_ID, $migration->getId());
        $this->datastore->set(LATEST_MIGRATION_HASH, $migration->getHash());
        $this->db->query('COMMIT');
    }

    private function getInitialMigration() {
        return reset($this->migrations);
    }

    private function getLatestMigration() {
        return end($this->migrations);
    }

    private function getUnappliedMigrations() {
        return array_filter($this->migrations, function($migration) {
            return $migration->getId() > $this->datastore->get(LAST_APPLIED_MIGRATION_ID);
        });
    }

    private function handleInitialMigrationChanges() {
        // checking of initial migration changes disabled
        if (!$this->rewriteDatabaseOnInitialMigrationChange)
            return;

        $migration = $this->getInitialMigration();

        // no change in initial migration
        if ($migration->getHash() === $this->datastore->get(INITIAL_MIGRATION_HASH))
            return;

        // initial migration changed - handle all changes
        $this->backups->clearDatabase();
        $this->apply($migration);
        $this->datastore->set(INITIAL_MIGRATION_HASH, $migration->getHash());
    }

    private function handleLatestMigrationChanges() {
        // checking of lastest migration changes disabled
        if (!$this->rewriteDatabaseOnLatestMigrationChange)
            return;

        $migration = $this->getLatestMigration();

        // latest migration not yet applied
        if ($migration->getId() !== $this->datastore->get(LAST_APPLIED_MIGRATION_ID))
            throw new \Exception('Latest migration is not applied yet.');

        // no change in latest migration
        if ($migration->getHash() === $this->datastore->get(LATEST_MIGRATION_HASH))
            return;

        // latest migration changed - handle all changes
        $this->backups->restoreBefore($migration);
        $this->apply($migration);
    }

    private function handleNormalMigrations() {
        foreach ($this->getUnappliedMigrations() as $migration) {
            $this->apply($migration);
        }
    }

    private function loadMigrations() {
        $migrations = [];
        foreach (glob($this->directory . '/*') as $f) {
            if (!preg_match('/(\d{3})\.php$/', $f, $m))
                continue;

            $migrations[$f] = new Migration($f, $m[1], $this->db);
        }

        ksort($migrations);

        $this->migrations = array_values($migrations);
    }

    function run() {
        $driver = new \mysqli_driver();
        $oldReportMode = $driver->report_mode;
        $driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

        $this->handleInitialMigrationChanges();
        $this->handleNormalMigrations();
        $this->handleLatestMigrationChanges();

        $driver->report_mode = $oldReportMode;
    }

}
