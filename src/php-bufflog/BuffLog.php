<?php
namespace Buffer;
require_once('vendor/autoload.php');

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

    private static $logger = null;

    public static function debug($message, $context = [], $extra = [])
    {
        $logOutput = self::formatLog($message, Logger::DEBUG, $context, $extra);
        self::getLogger()->debug($logOutput);
    }

    public static function info($message, $context = [], $extra = [])
    {
        $logOutput = self::formatLog($message, Logger::INFO, $context, $extra);
        self::getLogger()->info($logOutput);
    }

    public static function warn($message, $context = [], $extra = [])
    {
        $logOutput = self::formatLog($message, Logger::WARNING, $context, $extra);
        self::getLogger()->warn($logOutput);
    }

    public static function error($message, $context = [], $extra = [])
    {
        $logOutput = self::formatLog($message, Logger::ERROR, $context, $extra);
        self::getLogger()->error($logOutput);
    }

    // @TODO: That one might could also create an alert in Datadog?
    public static function critical($message, $context = [], $extra = [])
    {
        $logOutput = self::formatLog($message, Logger::CRITICAL, $context, $extra);
        self::getLogger()->critical($logOutput);
    }

    private function formatLog($message, $level, $context = [], $extra = [])
    {
        $output = [
            "message"   => $message,
            "level"     => $level,
            "datetime"  => date(\DateTime::ATOM),
            // we could use timestamp if we need ms precision (but it isn't readable) https://docs.datadoghq.com/logs/processing/#reserved-attributes
            // 'timestamp' => round(microtime(true) * 1000),
            "context"   => $context,
            "extra"     => $extra
        ];

        try {
            $output = json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("can't json_encode your message");
        }

        return $output;
    }

    private static function createLogger()
    {
        // @TODO: We could potentially use the Kubernetes downward API to 
        // define the logger name. This will make it easier for developers 
        // to read and friendlier to identify where come the logs at a glance
        self::$logger = new Logger('php-bufflog');
        $handler = new MonologStreamHandler('php://stdout');

        self::$logger->pushHandler($handler);
        return self::$logger;
    }

    public static function getLogger()
    {
      if (!isset(self::$logger)) {
        echo "Initializing logger\n";
        self::createLogger();
      }

      return self::$logger;
    }

}
