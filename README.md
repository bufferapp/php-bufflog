# PHP BuffLog

PHP logger for all Buffer services


## Requirements

PHP 7.1 and later.

## Setup BuffLog in your PHP project via Composer

You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require bufferapp/php-bufflog
```

## Usage
As simple as...

```php
use Buffer\Bufflog;

Bufflog::debug("I am a debug");
Bufflog::info("I am an info");
Bufflog::warning("I am a warning");
Bufflog::error("I am an error");
Bufflog::critical("I am a warning");
```

If you wish add more context in your logs, 
```php
Bufflog::debug("some context", ["my key" => " my value"]);
Bufflog::info("I am a info with context", ["my key" => " my value"]);
Bufflog::warning("I am a warning", ["duration" => "40ms"]);

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
```

## Log verbosity levels

By default, only logs with the WARNING level are output. 
If you wish to see more logs, simply set the `LOG_VERBOSITY` to the desired level. Here the list with their level and their use case:

| Levels  | Use case  | Examples  |
|:-:|---|---|
| DEBUG  | Logs that are used for interactive investigation during development. These logs should primarily contain information useful for debugging and have no long-term value.  |   |
| INFO | Logs that track the general flow of the application.   |   |
| WARNING | Logs that highlight an abnormal or unexpected event in the application flow, but do not otherwise cause the application execution to stop.  |   |
| ERROR |  Logs that highlight when the current flow of execution is stopped due to a failure. These should indicate a failure in the current activity, not an application-wide failure. |   |
| CRITICAL  | Logs that describe an unrecoverable application or system crash, or a catastrophic failure that requires immediate attention.  |   |
