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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Command\Descriptor;

use KonradMichalik\Typo3DumpServer\Command\Descriptor\Typo3JsonDescriptor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Command\Descriptor\DumpDescriptorInterface;

use function array_map;
use function array_values;
use function explode;
use function json_decode;
use function trim;

/**
 * Typo3JsonDescriptorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class Typo3JsonDescriptorTest extends TestCase
{
    #[Test]
    public function implementsDumpDescriptorInterface(): void
    {
        self::assertInstanceOf(DumpDescriptorInterface::class, Typo3JsonDescriptor::withDefaults());
    }

    #[Test]
    public function describeOutputsOneValidJsonLine(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar(['foo' => 'bar']);

        $descriptor->describe($output, $data, ['timestamp' => 1758182400], 1);

        $lines = $this->decodedLines($output);
        self::assertCount(1, $lines);
    }

    #[Test]
    public function describeIncludesNormalizedValueAndType(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar(['foo' => 'bar']);

        $descriptor->describe($output, $data, ['timestamp' => 1758182400], 1);

        $line = $this->decodedLines($output)[0];
        self::assertSame('array', $line['type']);
        self::assertSame(['foo' => 'bar'], $line['value']);
    }

    #[Test]
    public function describeIncludesIsoTimestampAndClientId(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar('test');

        $descriptor->describe($output, $data, ['timestamp' => 1758182400], 7);

        $line = $this->decodedLines($output)[0];
        self::assertSame(date('c', 1758182400), $line['timestamp']);
        self::assertSame(7, $line['clientId']);
    }

    #[Test]
    public function describeDefaultsTimestampWhenMissing(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar('test');

        $descriptor->describe($output, $data, [], 1);

        $line = $this->decodedLines($output)[0];
        self::assertSame(date('c', 0), $line['timestamp']);
    }

    #[Test]
    public function describeIncludesSourceInfoWhenPresent(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar('test');

        $context = [
            'timestamp' => 1758182400,
            'source' => [
                'name' => 'TestController.php',
                'file' => '/var/www/html/Classes/Controller/TestController.php',
                'line' => 42,
            ],
        ];

        $descriptor->describe($output, $data, $context, 1);

        $line = $this->decodedLines($output)[0];
        self::assertSame([
            'name' => 'TestController.php',
            'file' => '/var/www/html/Classes/Controller/TestController.php',
            'line' => 42,
        ], $line['source']);
    }

    #[Test]
    public function describeOmitsSourceWhenAbsent(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar('test');

        $descriptor->describe($output, $data, ['timestamp' => 1758182400], 1);

        $line = $this->decodedLines($output)[0];
        self::assertNull($line['source']);
    }

    #[Test]
    public function describeIncludesTypo3ContextWhenPresent(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar('test');

        $context = [
            'timestamp' => 1758182400,
            'typo3' => ['version' => '13.4.0', 'context' => 'Development'],
        ];

        $descriptor->describe($output, $data, $context, 1);

        $line = $this->decodedLines($output)[0];
        self::assertSame(['version' => '13.4.0', 'context' => 'Development'], $line['typo3']);
    }

    #[Test]
    public function describeIncludesRequestContextWithNormalizedController(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $cloner = new VarCloner();
        $data = $cloner->cloneVar('test');

        $context = [
            'timestamp' => 1758182400,
            'request' => [
                'method' => 'GET',
                'uri' => '/some/path',
                'controller' => $cloner->cloneVar('SomeController::action'),
            ],
        ];

        $descriptor->describe($output, $data, $context, 1);

        $line = $this->decodedLines($output)[0];
        self::assertSame([
            'method' => 'GET',
            'uri' => '/some/path',
            'controller' => 'SomeController::action',
        ], $line['request']);
    }

    #[Test]
    public function describeIncludesCliContext(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar('test');

        $context = [
            'timestamp' => 1758182400,
            'cli' => ['command_line' => 'bin/console some:command'],
        ];

        $descriptor->describe($output, $data, $context, 1);

        $line = $this->decodedLines($output)[0];
        self::assertSame(['command_line' => 'bin/console some:command'], $line['cli']);
    }

    #[Test]
    public function describeWritesOneNdjsonLinePerCall(): void
    {
        $descriptor = Typo3JsonDescriptor::withDefaults();
        $output = new BufferedOutput();
        $cloner = new VarCloner();

        $descriptor->describe($output, $cloner->cloneVar('first'), ['timestamp' => 1758182400], 1);
        $descriptor->describe($output, $cloner->cloneVar('second'), ['timestamp' => 1758182401], 2);

        $lines = $this->decodedLines($output);
        self::assertCount(2, $lines);
        self::assertSame('first', $lines[0]['value']);
        self::assertSame('second', $lines[1]['value']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodedLines(BufferedOutput $output): array
    {
        $raw = trim($output->fetch());
        $lines = '' === $raw ? [] : explode("\n", $raw);

        return array_values(array_map(
            static fn (string $line): array => json_decode($line, true),
            $lines,
        ));
    }
}
