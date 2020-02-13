<?php
namespace Buffer\Bufflog;

use Monolog\Logger as Logger;
use Monolog\Handler\StreamHandler;

class BuffLog {

    protected   static $instance;
    private     static $logger = null;

    // default verbosity starting at this level
    private     static $verbosityLevel = Logger::NOTICE;

    // verbosity can be changed with setting this env var
    public      static $logLevelEnvVar = "LOG_LEVEL";

    // we can use strtolower(Logger::getLevels()) instead
    private     static $logOutputMethods = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];

    private     static $extraAllowedMethods = ['getName', 'pushHandler', 'setHandlers', 'getHandlers', 'pushProcessor', 'getProcessors', 'getLevels'];

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

        if (!class_exists("\DDTrace\GlobalTracer")) {
            error_log("Tip #1: Can't find \DDTrace\GlobalTracer class. Did you install the Datadog APM tracer extension? It will allow you to have logs enriched with traces making troubleshooting easier! :)");
            error_log("Tip #2: If you run a cli mode service (such as a worker), did you set the DD_TRACE_CLI_ENABLED env variable?");
        }

        // @TODO: We could potentially use the Kubernetes downward API to
        // define the logger name. This will make it easier for developers
        // to read and friendlier to identify where come the logs at a glance
        $logger = new Logger('php-bufflog');

        $logLevelFromEnv = getenv(self::$logLevelEnvVar);
        $monologLevels = $logger->getLevels();
        if ($logLevelFromEnv) {
            // only if the level exists, we change the verbosity level
            if (key_exists($logLevelFromEnv, $monologLevels)) {
                self::$verbosityLevel = $monologLevels[$logLevelFromEnv];
            } else {
                error_log(self::$logLevelEnvVar . "={$logLevelFromEnv} verbosity level does not exists. Please use: " . implode(', ', array_keys($monologLevels)));
            }
        }

        $handler = new StreamHandler('php://stdout', self::$verbosityLevel);
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

                    self::enrichLog();
                }
                // Where the magic happen. We "proxy" functions name with arguments to the Monolog instance
                return call_user_func_array(array(self::getLogger(), $methodName), $args);

            } else {
                error_log("BuffLog::$methodName() is not supported yet. Add it to the BuffLog whitelist to allow it");
            }
        } else {
            error_log("BuffLog::$methodName() method does not exist");
        }
    }

    private static function enrichLog()
    {
        // We should probably implement this as a Monolog Processor
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

            try {

                if (class_exists("\DDTrace\GlobalTracer", false) === false) {
                    throw new \Exception('DDTrace\GlobalTracer can\'t be found. Have you setup the Datadog Tracer extension? If you run cli worker, have you added the DD_TRACE_CLI_ENABLED env variable?');
                }

                // Add traces information to be able to correlate logs with APM
                $ddTraceSpan = \DDTrace\GlobalTracer::get()->getActiveSpan();
                $record['context']['dd'] = [
                    "trace_id" => $ddTraceSpan->getTraceId(),
                    "span_id"  => $ddTraceSpan->getSpanId()
                ];

            } catch (\Exception $e) {
                // we probably will want to make an no-op or it will be too verbose
                error_log($e->getMessage() . " Traces will not be added in the logs");
            }

            return $record;
        });
    }

}
