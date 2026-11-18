<?php

declare(strict_types=1);

namespace Elkuku\SymfonyUtils\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Flex recipe copies resources/bin/ into a project's bin/ folder and keeps the executable bit.
 */
class ScriptsTest extends TestCase
{
    private const BIN_DIR = __DIR__.'/../resources/bin';

    /**
     * @return array<string, array{string, bool}>
     */
    public static function scripts(): array
    {
        return [
            'install' => ['install', true],
            'start' => ['start', true],
            'stop' => ['stop', true],
            'deploy' => ['deploy', true],
            'functions.sh' => ['functions.sh', false],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function scriptNames(): array
    {
        return \array_map(static fn (array $script): array => [$script[0]], self::scripts());
    }

    #[DataProvider('scripts')]
    public function testPermissions(string $script, bool $executable): void
    {
        self::assertFileExists(self::BIN_DIR.'/'.$script);
        self::assertSame($executable, \is_executable(self::BIN_DIR.'/'.$script));
    }

    #[DataProvider('scriptNames')]
    public function testSyntax(string $script): void
    {
        \exec('bash -n '.\escapeshellarg(self::BIN_DIR.'/'.$script).' 2>&1', $output, $exitCode);

        self::assertSame(0, $exitCode, \implode("\n", $output));
    }

    public function testNoOtherFiles(): void
    {
        $files = \array_values(\array_diff(\scandir(self::BIN_DIR) ?: [], ['.', '..']));
        \sort($files);

        $expected = \array_keys(self::scripts());
        \sort($expected);

        self::assertSame($expected, $files, 'Every file in resources/bin ends up in the projects\' bin/ folder.');
    }
}
