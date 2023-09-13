<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Bine\RinhaDeCompilerPhp\RinhaInterpreter;

$input = file_exists('/var/rinha/source.rinha.json')
    ? '/var/rinha/source.rinha.json'
    : 'php://stdin';

$rinhaInterpreter = new RinhaInterpreter($input);

$rinhaInterpreter->execute();
