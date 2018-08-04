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
        $rewriteDatabaseOnInitialMigrationChange = true, // TODO
        $rewriteDatabaseOnLatestMigrationChange = true; // TODO

    private static
        $defaultParams = [
            'migrationsDirectory'   =>  './migrations',
            'tableName'             =>  'db_migrations',
            'backupsDirectory'      =>  null, // force manual configuration for security
        ];

    function __construct($params) {
        $realParams = array_merge($defaultParams, $params);

        $this->db = $realParams['connection'];
        $this->datastore = new Datastore($this->db, $realParams['tableName']);
        $this->backups = new Backups($this->db, $realParam['backupDirectory']);
    }

    private function apply(Migration $migration) {
        // backup db
        $this->backups->backupBefore($migration);

        // apply migration
        $this->db->query('BEGIN');
        $migration->apply(); // TODO or applyTo
        $this->datastore->set(LAST_APPLIED_MIGRATION_ID, $migration->getId());
        $this->datastore->set(LATEST_MIGRATION_HASH, $migration->getHash());
        $this->db->query('COMMIT');
    }

    private function handleInitialMigrationChanges() {
        // checking of initial migration changes disabled
        if (!$this->rewriteDatabaseOnInitialMigrationChange)
            return;

        // no change in initial migration
        if ($this->getInitialMigration()->getHash() === $this->datastore->get(INITIAL_MIGRATION_HASH))
            return;

        // initial migration changed - handle all changes
        $this->clearDatabase();
        $this->apply($this->getInitialMigration());
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

    function run() {
        $this->handleInitialMigrationChanges();
        $this->handleNormalMigrations();
        $this->handleLatestMigrationChanges();
    }

}
