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

    private $logger;

    public function debug($message)
    {
        $this->getLogger()->debug($message);
    }

    public function info($message)
    {
        $this->getLogger()->info($message);
    }

    public function warn($message)
    {
        $this->getLogger()->warn($message);
    }


    public function error($message)
    {
        $this->getLogger()->error($message);
    }

    public function critical($message)
    {
        $this->getLogger()->critical($message);
    }

    private function createLogger()
    {
        $logger = new Logger('php-bufflog');
        $handler = new MonologStreamHandler('php://stdout', Logger::INFO);

        $logger->pushHandler($handler);

        return $logger;
    }

    private function getLogger()
    {
      if (!isset($this->logger)) {
        $this->logger = $this->createLogger();
      }

      return $this->logger;
    }

}
