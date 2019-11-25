<?php
require_once('./src/php-bufflog/BuffLog.php');

use Buffer\Bufflog;

// putenv("LOG_VERBOSITY=WARNING");
Bufflog::debug("I am a debug");
Bufflog::debug("I am a debug with context", ["my key" => " my value"]);

Bufflog::info("I am an info");
Bufflog::info("I am a info with context", ["my key" => " my value"]);

Bufflog::warning("I am a warning");
Bufflog::warning("I am a warning", ["duration" => "40ms"]);

Bufflog::error("I am an error");
Bufflog::error("I am an error", ["mean" => "70"]);

Bufflog::critical("I am critical information!");
Bufflog::critical("I am critical information!", ["user" => "betrand"]);

Bufflog::critical("I'm critical log, here some extra fancy informations", 
                    [
                        "duration" => "40ms",
                        "services_related" => [
                            "Twitter",
                            "Facebook",
                            "Instagram"
                        ]
                    ]
                );
