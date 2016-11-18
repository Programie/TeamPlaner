<?php
namespace com\selfcoders\teamplaner\service;

use com\selfcoders\teamplaner\auth\iUserAuth;
use com\selfcoders\teamplaner\Config;
use com\selfcoders\teamplaner\DBConnection;

abstract class AbstractService
{
    protected $config;
    protected $pdo;
    protected $userAuth;
    public $data;
    public $parameters;

    public function __construct(Config $config, iUserAuth $userAuth)
    {
        $this->config = $config;
        $this->pdo = DBConnection::getConnection($config);
        $this->userAuth = $userAuth;
    }
}