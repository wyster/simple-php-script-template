<?php

declare(strict_types=1);

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

ini_set('display_errors', 'on');
error_reporting(-1);

require  __DIR__ . '/vendor/autoload.php';

const DATA_DIR = __DIR__ . '/.data';

init();
run();

function run()
{
    throw new Exception('Not implemented');
}

function init()
{
    if (!is_writable(DATA_DIR)) {
        throw new Exception(sprintf('Data dir must be writable, path: %s', DATA_DIR));
    }

    $logger = initLogger();
    $timer = microtime(true);

    set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });

    register_shutdown_function(static function () use ($logger, $timer) {
        $logger->info('Shutdown');
        $logger->debug(sprintf('Work time: %s', secondsToTimeString(microtime(true) - $timer)));
    });

    set_exception_handler(static function (Throwable $e) use ($logger) {
        $logger->emergency(sprintf("Unhandled exception: %s\n%s", $e->getMessage(), $e->getTraceAsString()));
    });
}

function initLogger(): LoggerInterface
{
    $formatter = new LineFormatter();
    $formatter->allowInlineLineBreaks(true);
    $log = new Logger('main');
    $log->pushHandler((new StreamHandler(DATA_DIR . '/main.log', Logger::DEBUG))->setFormatter($formatter));
    $log->pushHandler((new StreamHandler('php://stdout', Logger::DEBUG))->setFormatter($formatter));

    return $log;
}

function secondsToTimeString(float $time): string
{
    $times = [
        'hour' => 3600000,
        'minute' => 60000,
        'second' => 1000,
    ];
    $ms = round($time * 1000);

    foreach ($times as $unit => $value) {
        if ($ms >= $value) {
            $time = floor($ms / $value * 100.0) / 100.0;

            return $time . ' ' . ($time === 1 ? $unit : $unit . 's');
        }
    }

    return $ms . ' ms';
}
