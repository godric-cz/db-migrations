<?php

namespace Godric\DbMigrations;

class Datastore {

    private
        $connection,
        $tableName;

    function __construct($connection, $tableName) {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    function get($key) {

    }

    function set($key, $value) {

    }

}
