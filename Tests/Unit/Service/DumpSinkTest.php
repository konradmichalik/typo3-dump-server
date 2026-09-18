<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_dump_server" TYPO3 CMS extension.
 *
 * (c) 2025-2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Service;

use KonradMichalik\Typo3DumpServer\Dumper\DumpPayloadFactory;
use KonradMichalik\Typo3DumpServer\Service\DumpSink;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;

use function array_map;
use function array_values;
use function explode;
use function file_get_contents;
use function fileperms;
use function is_file;
use function json_decode;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

/**
 * DumpSinkTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpSinkTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dump_sink_test_');
        self::assertNotFalse($path);
        $this->path = $path;
        unlink($this->path);
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    #[Test]
    public function writeCreatesFileWithOneNdjsonLine(): void
    {
        $sink = DumpSink::withDefaults();
        $data = (new VarCloner())->cloneVar('hello');

        $sink->write($this->path, $data, ['timestamp' => 1758182400]);

        $lines = $this->decodedLines();
        self::assertCount(1, $lines);
        self::assertSame('hello', $lines[0]['value']);
    }

    #[Test]
    public function writeAppendsSubsequentDumpsAsAdditionalLines(): void
    {
        $sink = DumpSink::withDefaults();
        $cloner = new VarCloner();

        $sink->write($this->path, $cloner->cloneVar('first'), ['timestamp' => 1758182400]);
        $sink->write($this->path, $cloner->cloneVar('second'), ['timestamp' => 1758182401]);

        $lines = $this->decodedLines();
        self::assertCount(2, $lines);
        self::assertSame('first', $lines[0]['value']);
        self::assertSame('second', $lines[1]['value']);
    }

    #[Test]
    public function writeTruncatesFileWhenMaxFileSizeIsExceeded(): void
    {
        $sink = new DumpSink(DumpPayloadFactory::withDefaults(), 1);
        $cloner = new VarCloner();

        $sink->write($this->path, $cloner->cloneVar('first'), ['timestamp' => 1758182400]);
        $sink->write($this->path, $cloner->cloneVar('second'), ['timestamp' => 1758182401]);

        $lines = $this->decodedLines();
        self::assertCount(1, $lines);
        self::assertSame('second', $lines[0]['value']);
    }

    #[Test]
    public function writeRestrictsFilePermissionsToOwnerOnly(): void
    {
        $sink = DumpSink::withDefaults();
        $data = (new VarCloner())->cloneVar('hello');

        $sink->write($this->path, $data, ['timestamp' => 1758182400]);

        self::assertSame(0600, fileperms($this->path) & 0777);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodedLines(): array
    {
        $raw = trim((string) file_get_contents($this->path));
        $lines = '' === $raw ? [] : explode("\n", $raw);

        return array_values(array_map(
            static fn (string $line): array => json_decode($line, true),
            $lines,
        ));
    }
}
