<?php
namespace Rickfo\Bittracker\Core;

class Database extends \PDO
{
    public function __construct($driver, $host, $database, $user, $password = '')
    {
        parent::__construct(
            "$driver:host=$host;dbname=$database;charset=utf8", $user, $password
        );
    }
}
