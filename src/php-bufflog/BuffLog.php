<?php
namespace Buffer;
require_once('vendor/autoload.php');

use Monolog\Logger as Logger;
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

    // This will be called when a static method in the class doesn't exists
    public static function __callStatic($methodName, $args)
    {
        $whitelistOutputMethods = ["debug", 'info', 'notice', 'warning', 'error', 'critical'];
        $whitelistExtraMethods = [];

        if (method_exists(self::getLogger(), $methodName)) {

            if (in_array($methodName, $whitelistOutputMethods)) {

                // @TODO: need to make sure we "output" only the correct level of log
                //    old version looked like:
                //     self::setVerbosity();
                //     if (self::$currentVerbosity > Logger::WARNING) {
                //         return;
                //     }

                self::enrichLog();
                self::getLogger()->$methodName($args[0], isset($args[1]) ? $args[1] : []);

            } elseif (in_array($methodName, $whitelistExtraMethods)) {

                // this might be tricky. we do not know how many arguments the dev will call.
                // Might have mutltiple solutions (counting/varargs...), which one would be the right one?
                // self::getLogger()->$methodName($args[0]);

            } else {

                error_log("BuffLog::$methodName() is not supported yet. Add it to the BuffLog repository to allow it");

            }
        } else {
            error_log("BuffLog::$methodName() does not exist");
        }
    }

    private function enrichLog()
    {
        // This should probably implemented as a Monolog Processor
        // https://github.com/Seldaek/monolog/tree/master/src/Monolog/Processor
        self::getLogger()->pushProcessor(function ($record) {

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

    private static function setVerbosity()
    {
        $envVerbosity = getenv("LOG_VERBOSITY");
        if ($envVerbosity !== FALSE && array_key_exists($envVerbosity, self::$verbosityList)) {
            self::$currentVerbosity = self::$verbosityList[$envVerbosity];
        }
    }

}
