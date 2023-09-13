<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Bine\RinhaDeCompilerPhp\RinhaInterpreter;

$bufferFile = "./repl.buff";
$buffer = fopen("./repl.buff", "a+");
$command = ".start";
$stack = [];
$interpreter = new RinhaInterpreter();

echo "Rinha Language!\n";
echo "Start typing commands!\n";
echo "Type .exit and press enter to exit!\n";

while ($command !== ".exit\n") {
    ftruncate($buffer, 0);

    $command = fgets(STDIN);
    if ($command == ".exit\n") {
        break;
    }

    fwrite($buffer, $command);

    try {
        $output = shell_exec("rinha " . $bufferFile);
        if (empty($output)) {
            echo "Invalid command!\n";
            continue;
        }
        $ast = json_decode($output, true);
        $interpreter->interpret($ast['expression'], $stack);
    } catch (\Exception $e) {
        echo "Invalid command!\n";
    }
}

fclose($buffer);
unlink($bufferFile);

echo "Bye!\n";
