<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use unreal4u\DS18B20Sensor\Base;
use unreal4u\DS18B20Sensor\Configuration\DevelopmentConfig;

chdir(__DIR__ . '/../');
include 'vendor/autoload.php';

// Initialize objects we'll need
$logger = new Logger('main');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$baseProgram = new Base(new DevelopmentConfig(), $logger);
$baseProgram->runProgram();
