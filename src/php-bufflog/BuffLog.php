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

    public function __construct()
    {
        $this->createLogger();
    }


    public function debug($message)
    {
        $this->formatMessage($message, Logger::DEBUG, $context = [], $extra = []);
        $this->getLogger()->debug($message);
    }

    public function info($message)
    {
        $this->formatMessage($message, Logger::INFO, $context = [], $extra = []);
        $this->getLogger()->info($message);
    }

    public function warn($message)
    {
        $this->formatMessage($message, Logger::WARNING, $context = [], $extra = []);
        $this->getLogger()->warn($message);
    }


    public function error($message)
    {
        $this->formatMessage($message, Logger::ERROR, $context = [], $extra = []);
        $this->getLogger()->error($message);
    }

    // @TODO: That one might could also create an alert in Datadog?
    public function critical($message)
    {
        $this->formatMessage($message, Logger::CRITICAL, $context = [], $extra = []);
        $this->getLogger()->critical($message);
    }

    private function formatMessage($message, $level, $context = [], $extra = [])
    {
        $output = [
            "message"   => $message,
            "level"     => $level,
            "datetime"  => date(\DateTime::ATOM),
            "context"   => $context,
            "extra"     => $extra
        ];

        return $output;
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
