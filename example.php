<?php
require_once('./vendor/autoload.php');
require_once('./src/BuffLog/BuffLog.php');

use Buffer\BuffLog\Bufflog;

// putenv("LOG_VERBOSITY=WARNING");
BuffLog::debug("I am a debug string");
BuffLog::debug("I am a debug with context", ["my key" => " my value"]);

BuffLog::info("I am an info");
BuffLog::info("I am a info with context", ["my key" => " my value"]);

BuffLog::warning("I am a warning");
BuffLog::warning("I am a warning", ["duration" => "40ms"]);

BuffLog::error("I am an error");
BuffLog::error("I am an error", ["mean" => "70"]);

BuffLog::critical("I am criticals information with a typo and you shouldn't see me!");
BuffLog::critical("I am critical information!", ["user" => "betrand"]);

BuffLog::critical("I'm critical log, here some extra fancy informations", 
                    [
                        "duration" => "40ms",
                        "services_related" => [
                            "Twitter",
                            "Facebook",
                            "Instagram"
                        ]
                    ]);
