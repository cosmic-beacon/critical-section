<?php
require(__DIR__ . '/../vendor/autoload.php');

function initPostgreSQL()
{
    try {
        $pdo = new \PDO('pgsql:host=localhost;port=5432;user=postgres');
        $pdo->exec('DROP DATABASE IF EXISTS cb_critical_section_test;');
        $pdo->exec('CREATE DATABASE cb_critical_section_test;');
    } catch (\PDOException $e) {
        echo $e->getMessage();
    }

}

initPostgreSQL();
