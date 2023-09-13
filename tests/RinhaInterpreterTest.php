<?php

declare(strict_types=1);

use Bine\RinhaDeCompilerPhp\RinhaInterpreter;
use PHPUnit\Framework\TestCase;

final class RinhaInterpreterTest extends TestCase
{
    /**
     * @dataProvider fixturesDataProvider
     */
    public function testItBehavesAsExpected(string $file, string $output): void
    {
        $this->expectOutputString($output);

        (new RinhaInterpreter($file))->execute();
    }

    public static function fixturesDataProvider(): array
    {
        $testCases = [];
        $fixtures = glob(__DIR__ . "/fixtures/*.json");

        foreach ($fixtures as $fixture) {
            $testCases[] = [
                strval($fixture),
                file_get_contents($fixture . ".output")
            ];
        }

        return $testCases;
    }
}
