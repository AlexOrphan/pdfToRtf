#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

ini_set("log_errors", 1);
ini_set("error_log", __DIR__  . "/logs/php-error.log");
ini_set("memory_limit", '196M');

spl_autoload_register(function ($class) {
    $file = __DIR__ .'/'. str_replace('\\', '/', $class) .'.php';
    if (file_exists($file)) {
        require $file;
    }
});

use Symfony\Component\Console\Application;
use App\Command\ConvertPdfCommand;

$application = new Application();
$application->add(new ConvertPdfCommand());
$application->run();
