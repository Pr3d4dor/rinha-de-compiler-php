<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Bine\RinhaDeCompilerPhp\RinhaInterpreter;

$sourceCode = $_POST['source_code'] ?? null;
if (empty($sourceCode)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header("Access-Control-Allow-Headers: X-Requested-With");
    echo "Bad Request";

    return;
}

$bufferFile = './repl.buff';
$buffer = fopen('./repl.buff', 'a+');
ftruncate($buffer, 0);
fwrite($buffer, $sourceCode);

try {
    $output = shell_exec(getenv('HOME') . '/.cargo/bin/rinha ' . $bufferFile);
    if (empty($output)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header("Access-Control-Allow-Headers: X-Requested-With");
        echo "Source Code Contains Error";

        return;
    }

    $ast = json_decode($output, true);

    ob_start();
    (new RinhaInterpreter())->interpret($ast['expression']);
    $result = ob_get_clean();

    header('Content-Type: text/plain; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header("Access-Control-Allow-Headers: X-Requested-With");
    echo $result;
} catch (\Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header("Access-Control-Allow-Headers: X-Requested-With");
    echo $e->getMessage();
}
