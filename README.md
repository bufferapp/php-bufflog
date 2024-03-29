# PHP BuffLog

PHP logger for all Buffer services


## Requirements

PHP 8.1 and later.

## Setup BuffLog in your PHP project via Composer

You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require bufferapp/php-bufflog
```

## Usage
As simple as...

```php
use Buffer\BuffLog\BuffLog;

BuffLog::debug("I am a debug");
BuffLog::info("I am an info");
BuffLog::warning("I am a warning");
BuffLog::error("I am an error");
BuffLog::critical("I am a warning");
```

If you wish add more context in your logs, 
```php
BuffLog::debug("some context", ["my key" => " my value"]);
BuffLog::info("I am a info with context", ["my key" => " my value"]);
BuffLog::warning("I am a warning", ["duration" => "40ms"]);

BuffLog::critical("I'm critical log, here some extra fancy informations", [
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

If you wish to see more logs, simply set the `LOG_LEVEL` to the desired level. Here the list with their level and their use case:

| Levels  | Use case  | Examples  |
|:-:|---|---|
| DEBUG  | Information used for interactive investigation, with no long-term value. Activate it with `LOG_LEVEL=DEBUG`| Printing function names, steps inside a function. |
| INFO | Interesting events. Track the general flow of the application.  Activate it with `LOG_LEVEL=INFO`| User logs in, SQL logs, worker process/delete a message... |
| NOTICE | Uncommon events. **This is the default verbosity level**. |  Missing environment variables, page redirection, pod starting/restarting/terminating, retrying to query an API... |
| WARNING | Exceptional occurrences that are not errors. Undesirable things that are not necessarily wrong. | Use of deprecated APIs,  poor use of an API, unauthorized access, pod restart because of memory limit ... |
| ERROR | Runtime errors. Highlight when the current flow of execution is stopped due to a failure. | Exceptions messages, incorect credentials or permissions...  |
| CRITICAL  | Critical conditions. Describe an unrecoverable application, system crash, or a catastrophic failure that requires immediate attention.  | Application component unavailable, unexpected exception. entire website down, database unavailable ...|

