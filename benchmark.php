<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Bine\RinhaDeCompilerPhp\RinhaInterpreter;
use Illuminate\Support\Benchmark;

$testCases = [];
$fixtures = glob(__DIR__ . "/tests/fixtures/*.json");

foreach ($fixtures as $fixture) {
    $testCases[] = strval($fixture);
}

$scenarios = [];
foreach ($testCases as $testCase) {
    $parts = explode("/", $testCase);
    $name = end($parts);
    $scenarioName = $name;
    $scenarios[$scenarioName] = function () use ($testCase) {
        (new RinhaInterpreter($testCase))->execute();
    };
}

Benchmark::dd($scenarios, 100);
