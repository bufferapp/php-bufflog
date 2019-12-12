<?php
namespace Buffer\Bufflog;

use Monolog\Logger as Logger;
use Monolog\Handler\StreamHandler;

class BuffLog {

    protected   static $instance;
    private     static $logger = null;

    // we output every logs starting this level
    // It can be changed with setting an environment
    private     static $verbosityLevel = Logger::NOTICE;

    // we can use strtolower(Logger::getLevels()) instead
    private static $logOutputMethods = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];

    private static $extraAllowedMethods = ['getName', 'pushHandler', 'setHandlers', 'getHandlers', 'pushProcessor', 'getProcessors', 'getLevels'];

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
        if (method_exists(self::getLogger(), $methodName)) {

            if (in_array($methodName, array_merge(self::$logOutputMethods, self::$extraAllowedMethods))) {

                if (in_array($methodName, self::$logOutputMethods)) {

                    if (! self::shouldWriteLogs($methodName)) {
                        return;
                    }

                    self::enrichLog();
                }
                // Where the magic happen. We "proxy" functions name with arguments to the Monolog instance
                return call_user_func_array(array(self::getLogger(), $methodName), $args);

            } else {
                error_log("BuffLog::$methodName() is not supported yet. Add it to the BuffLog whitelist to allow it");
            }
        } else {
            error_log("BuffLog::$methodName() does not exist");
        }
    }

    private static function setVerbosityLevel()
    {
        $logLevelFromEnv = getenv("LOG_LEVEL");
        $monologLevels = self::getLogger()->getLevels();
        if ($logLevelFromEnv) {
            // only if the level exists, we change the verbosity level
            if (key_exists($logLevelFromEnv, $monologLevels)) {
                self::$verbosityLevel = $monologLevels[$logLevelFromEnv];
            } else {
                error_log("LOG_LEVEL {$logLevelFromEnv} is not defined, use one of: " . implode(', ', array_keys($monologLevels)));
            }
        }
    }


    private static function shouldWriteLogs($methodName)
    {
        // Optimization: we could move this at the singleton initialization
        self::setVerbosityLevel();

        if ( self::getLogger()->getLevels()[strtoupper($methodName)] >= self::$verbosityLevel) {
            return true;
        }

        return false;
    }

    private static function enrichLog()
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

}
