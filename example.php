<?php
require_once('./src/php-bufflog/BuffLog.php');

use Buffer\Bufflog;

Bufflog::debug("I am a debug");
Bufflog::debug("I am a debug with context", ["my key" => " my value"]);

Bufflog::info("I am an info");
Bufflog::debug("I am a info with context", ["my key" => " my value"]);

Bufflog::warn("I am a warning");
Bufflog::warn("I am a warning", ["duration" => "40ms"]);

Bufflog::error("I am an error");
Bufflog::error("I am an error", ["mean" => "70"]);

Bufflog::critical("I am critical information!");
Bufflog::critical("I am critical information!", ["user" => "betrand"]);
