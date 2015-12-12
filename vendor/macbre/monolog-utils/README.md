# monolog-utils
Addiitonal formatters and processors for Monolog

## `ExceptionProcessor`

Allows you to pass an `Exception` instance as a part of `$context` - it will be automically expanded to log exception class, message, code and a backtrace

```php
try {
	// do something
}
catch (NastyException $ex) {
	$logger->error('Something bad happended', [
		'exception' => $ex
	]);
}
```

`exception` field will be expanded to something similar to:

```json
{
	"@context": {
		"exception": {
			"class": "NastyException",
			"message": "Things went wrong",
			"code": 42,
			"trace": [
				"/home/macbre/app/Foo.class.php:979",
				"/home/macbre/app/App.class.php:29",
				"/home/macbre/app/index.php:18"
			]
		}
	}
}
```

## `RequestIdProcessor`

Automatically generates a unique, per request ID that is sent with every message log as `request_id` field in `$extra`.

## `JsonFormatter`

JSON log formatter for elastic / Kibana.

An example entry:

```php
$logger->error('Foo Bar', [
	'size' => 42
]);
```

```json
{
	"@timestamp": "2015-12-12T12:28:03.010299+01:00",
	"@message": "Foo Bar",
	"@context": {
		"size": 42
	},
	"@fields": {
		"request_id": "566c04c2f22693.59900054"
	},
	"severity": "error",
	"program": "my.app",
	"@source_host": "my.server.net"
}
```

# Example

```php
$logger = new Monolog\Logger('my.app');

$logger->pushProcessor(new Macbre\Logger\Processors\ExceptionProcessor());
$logger->pushProcessor(new Macbre\Logger\Processors\RequestIdProcessor());

// Syslog and JSON formatter for elastic / Kibana
$syslog = new Monolog\Handler\SyslogUdpHandler('127.0.0.1', 514, LOG_USER, Monolog\Logger::INFO);
$syslog->setFormatter(new Macbre\Logger\Formatters\JsonFormatter());
$logger->pushHandler($syslog);

// and now let's use the logger...
$logger->error('Foo Bar', [
	'exception' => new Exception('An error', 123),
	'size' => 42
]);
```
