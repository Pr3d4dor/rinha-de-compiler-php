<?php

declare(strict_types=1);

$file = $argv[1] ?? null;
$pharFile = $argv[2] ?? null;

if (empty($file) || empty($pharFile)) {
    throw new \InvalidArgumentException('Invalid params!');
}

try {
    if (file_exists($pharFile)) {
        unlink($pharFile);
    }

    if (file_exists($pharFile . '.gz')) {
        unlink($pharFile . '.gz');
    }

    $phar = new Phar($pharFile);

    $phar->startBuffering();

    $defaultStub = $phar->createDefaultStub($file);

    $phar->buildFromDirectory(__DIR__, '/\.php$/');

    $stub = "#!/usr/bin/env php \n" . $defaultStub;

    $phar->setStub($stub);

    $phar->stopBuffering();

    $phar->compressFiles(Phar::GZ);

    chmod(__DIR__ . "/{$pharFile}", 0770);

    echo "$pharFile successfully created" . PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage();
}
