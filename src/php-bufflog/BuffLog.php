<?php
namespace Buffer;

use Monolog\Logger;
use Monolog\Handler\StreamHandler as MonologStreamHandler;

/*
    Level of logs we use:

    This level require manual action to appear in Datadog Logs
    Logger::DEBUG
    Logger::INFO

    Everything at this level appears by default in Datadog Logs
    Logger::WARNING
    Logger::ERROR
    Logger::CRITICAL
*/

class BuffLog {

    protected function createLogger()
    {
        $logger = new Logger('php-bufflog');
        $handler = new MonologStreamHandler('php://stdout', Logger::INFO);

        $logger->pushHandler($handler);

        return $logger;
    }

}
