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

    // Global Tracer comes with the datadog tracing extension
    private     static $hasGlobalTracer = false;

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

        if (class_exists("\DDTrace\GlobalTracer")) {
            self::$hasGlobalTracer = true;
        } else {
            // local envs don't need tracing
            if (getenv("ENVIRONMENT") !== "local") {
                echo json_encode([
                        "message" => "Can't find \DDTrace\GlobalTracer class. Did you install the Datadog APM tracer extension? It will allow you to have logs enriched with traces making troubleshooting easier. If you run a cli mode service (such as a worker), did you set the DD_TRACE_CLI_ENABLED env variable?",
                        "level" => 300,
                        "level_name" => "WARNING",
                        "context" => ["bufflog_error" => "no_tracer"]
                ]);
            }
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

        // We should probably implement this as a Monolog Processor
        // https://github.com/Seldaek/monolog/tree/master/src/Monolog/Processor
        $logger->pushProcessor(function ($record) {
            // We should grab any Buffer information useful when available
            // Need to check with the Core team: accountID / userID / profileID
            // $user = Buffer/Core::getCurrentUser();
            // That should look like:
            // $record['context']['user'] = array(
            //     'accountID' => $user->getAccountID(),
            //     'userID' => $user->getUserID(),
            //     'profileID' => $user->getProfileID()
            // );

            if (self::$hasGlobalTracer) {
                try {
                    // Add traces information to be able to correlate logs with APM
                    $ddTraceSpan = \DDTrace\GlobalTracer::get()->getActiveSpan();
                    $record['context']['dd'] = [
                        "trace_id" => $ddTraceSpan->getTraceId(),
                        "span_id"  => $ddTraceSpan->getSpanId()
                    ];

                } catch (\Exception $e) {
                    // no-op
                }
            }
            return $record;
        });

        self::$instance = $logger;
	}

    // This will be called when a static method in the class doesn't exists
    public static function __callStatic($methodName, $args)
    {
        if (method_exists(self::getLogger(), $methodName)) {

            if (in_array($methodName, array_merge(self::$logOutputMethods, self::$extraAllowedMethods))) {

                if (in_array($methodName, self::$logOutputMethods)) {

                    if (self::checkLogParametersType($args)) {
                        // Where the magic happen. We "proxy" functions name with arguments to the Monolog instance
                        return call_user_func_array(array(self::getLogger(), $methodName), $args);
                    }
                }
            } else {
                self::getLogger()->warning("BuffLog::$methodName() is not supported yet. Add it to the BuffLog whitelist to allow it", ["bufflog_error" => "method_not_supported"]);
            }
        } else {
            self::getLogger()->warning("BuffLog::$methodName() method does not exist", ["bufflog_error" => "method_not_exist"]);
        }

        return false;
    }

    private static function checkLogParametersType($args)
    {
        if (count($args) > 2) {
            self::getLogger()->warning("BuffLog: Malformed logs: Too many parameters", ["bufflog_error" => "incorrect_parameters", "args" => $args]);
            return false;
        }

        if (isset($args[0]) && !is_string($args[0])) {
            self::getLogger()->warning("BuffLog: Malformed logs: First parameter must be a string", ["args" => $args, "bufflog_error" => "incorrect_parameters"]);
            return false;
        }

        if (isset($args[1]) && !is_array($args[1])) {
            self::getLogger()->warning("BuffLog: Malformed logs: Second parameter must be an array", ["args" => $args, "bufflog_error" => "incorrect_parameters"]);
            return false;
        }

        return true;
    }

}
