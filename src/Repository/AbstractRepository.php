<?php

namespace UrlShortener\Repository;

use Doctrine\DBAL\Connection;


abstract class AbstractRepository
{
    protected $dbConnection;


    /**
     * AbstractRepository constructor.
     * @param Connection $dbConnection
     */
    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
}