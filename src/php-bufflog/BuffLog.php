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
    private static $currentVerbosity = Logger::WARNING;
    private static $verbosityList = [
        "DEBUG" =>      Logger::DEBUG,
        "INFO" =>       Logger::INFO,
        "WARNING" =>    Logger::WARNING,
        "ERROR" =>      Logger::ERROR,
        "CRITICAL" =>   Logger::CRITICAL
    ];

    public static function debug($message, $context = [])
    {
        self::setVerbosity();
        if (self::$currentVerbosity > Logger::DEBUG) {
            return;
        }

        $logOutput = self::formatLog($message, Logger::DEBUG, $context);
        self::getLogger()->debug($logOutput);
    }

    public static function info($message, $context = [])
    {
        self::setVerbosity();
        if (self::$currentVerbosity > Logger::INFO) {
            return;
        }

        $logOutput = self::formatLog($message, Logger::INFO, $context);
        self::getLogger()->info($logOutput);
    }

    public static function warning($message, $context = [])
    {
        self::setVerbosity();
        if (self::$currentVerbosity > Logger::WARNING) {
            return;
        }

        $logOutput = self::formatLog($message, Logger::WARNING, $context);
        self::getLogger()->warn($logOutput);
    }

    public static function error($message, $context = [])
    {
        self::setVerbosity();
        if (self::$currentVerbosity > Logger::ERROR) {
            return;
        }

        $logOutput = self::formatLog($message, Logger::ERROR, $context);
        self::getLogger()->error($logOutput);
    }

    // @TODO: That one might could also create an alert in Datadog?
    public static function critical($message, $context = [])
    {
        self::setVerbosity();
        $logOutput = self::formatLog($message, Logger::CRITICAL, $context);
        self::getLogger()->critical($logOutput);
    }

    private function formatLog($message, $level, $context = [])
    {
        // Add traces information to logs to be able correlate with APM
        $ddTraceSpan = \DDTrace\GlobalTracer::get()->getActiveSpan();
        $context['dd'] = [
            "trace_id" => $ddTraceSpan->getTraceId(),
            "span_id"  => $ddTraceSpan->getSpanId()
        ];

        $output = [
            "message"   => $message,
            "level"     => $level,
            "datetime"  => date(\DateTime::ATOM),
            // we could use timestamp if we need ms precision (but it isn't readable) https://docs.datadoghq.com/logs/processing/#reserved-attributes
            // 'timestamp' => round(microtime(true) * 1000),
            "context"   => $context
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

    private static function setVerbosity()
    {
        $envVerbosity = getenv("LOG_VERBOSITY");
        if ($envVerbosity !== FALSE && array_key_exists($envVerbosity, self::$verbosityList)) {
            self::$currentVerbosity = self::$verbosityList[$envVerbosity];
        }
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
