<?php
namespace Buffer;
require_once('vendor/autoload.php');

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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

    protected static $instance;

	/**
	 * Method to return the Monolog instance
	 *
	 * @return \Monolog\Logger
	 */
	static public function getLogger()
	{
		if (! self::$instance) {
			self::configureInstance();
		}
		return self::$instance;
	}

	protected static function configureInstance()
	{
        // @TODO: We could potentially use the Kubernetes downward API to
        // define the logger name. This will make it easier for developers
        // to read and friendlier to identify where come the logs at a glance
        $logger = new Logger('php-bufflog');
        $handler = new StreamHandler('php://stdout');
        $handler->setFormatter( new \Monolog\Formatter\JsonFormatter() );
        $logger->pushHandler($handler);

        self::$instance = $logger;
	}

    public static function debug($message, $context = [])
    {
        self::setVerbosity();
        if (self::$currentVerbosity > Logger::DEBUG) {
            return;
        }
        self::processLog();
        self::getLogger()->addDebug($message, $context);
    }

    public static function info($message, $context = [])
    {
        self::setVerbosity();
        if (self::$currentVerbosity > Logger::INFO) {
            return;
        }

        self::processLog();
        self::getLogger()->addInfo($message, $context);
    }

    public static function warning($message, $context = [])
    {
        self::setVerbosity();
        if (self::$currentVerbosity > Logger::WARNING) {
            return;
        }

        self::processLog();
        self::getLogger()->addWarning($message, $context);
    }

    public static function error($message, $context = [])
    {
        self::setVerbosity();
        if (self::$currentVerbosity > Logger::ERROR) {
            return;
        }

        self::processLog();
        self::getLogger()->addError($message, $context);
    }

    // @TODO: That one might could also create an alert in Datadog?
    public static function critical($message, $context = [])
    {
        self::setVerbosity();
        self::processLog();
        self::getLogger()->addCritical($message, $context);
    }

    private function processLog()
    {
        // This should probably implemented as a Monolog Processor
        // https://github.com/Seldaek/monolog/tree/master/src/Monolog/Processor
        $self::getLogger()->pushProcessor(function ($record) {

            // We should grab any Buffer information useful when available
            // Need to check with the Core team: accountID / userID / profileID
            // $user = Buffer/Core::getCurrentUser();
            // That should look like:
            // $record['context']['user'] = array(
            //     'accountID' => $user->getAccountID(),
            //     'userID' => $user->getUserID(),
            //     'profileID' => $user->getProfileID()
            // );

            // Add traces information to logs to be able correlate with APM
            $ddTraceSpan = \DDTrace\GlobalTracer::get()->getActiveSpan();
            $record['context']['dd'] = [
                "trace_id" => $ddTraceSpan->getTraceId(),
                "span_id"  => $ddTraceSpan->getSpanId()
            ];
            return $record;
        });
    }

    private static function formatLog()
    {
        try {
            $output = json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("can't json_encode your message");
        }
    }

    private static function setVerbosity()
    {
        $envVerbosity = getenv("LOG_VERBOSITY");
        if ($envVerbosity !== FALSE && array_key_exists($envVerbosity, self::$verbosityList)) {
            self::$currentVerbosity = self::$verbosityList[$envVerbosity];
        }
    }

}
