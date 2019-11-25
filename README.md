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
Bufflog::warn("I am a warning");
Bufflog::error("I am an error");
Bufflog::critical("I am a warning");
```

If you wish add more context in your logs, 
```php
Bufflog::debug("some context", ["my key" => " my value"]);
Bufflog::info("I am a info with context", ["my key" => " my value"]);
Bufflog::warn("I am a warning", ["duration" => "40ms"]);

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
