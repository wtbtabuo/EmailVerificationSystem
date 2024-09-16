<?php

namespace Database\DataAccess;

use Database\DataAccess\Implementations\UserDAOImpl;
use Database\DataAccess\Implementations\ComputerPartDAOImpl;
use Database\DataAccess\Implementations\ComputerPartDAOMemcachedImpl;
use Database\DataAccess\Interfaces\UserDAO;
use Database\DataAccess\Interfaces\ComputerPartDAO;
use Helpers\Settings;

class DAOFactory
{
    public static function getUserDAO(): UserDAO{
        $driver = Settings::env('DATABASE_DRIVER');

        return match ($driver) {
            default => new UserDAOImpl(),
    
        };
    }
    public static function getComputerPartDAO(): ComputerPartDAO{
        $driver = Settings::env('DATABASE_DRIVER');

        return match ($driver) {
            default => new ComputerPartDAOImpl(),
        };
    }
}