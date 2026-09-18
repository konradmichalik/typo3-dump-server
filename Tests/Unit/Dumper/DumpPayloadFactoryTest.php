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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Dumper;

use KonradMichalik\Typo3DumpServer\Dumper\DumpPayloadFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;

/**
 * DumpPayloadFactoryTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpPayloadFactoryTest extends TestCase
{
    #[Test]
    public function createIncludesIsoTimestampTypeAndNormalizedValue(): void
    {
        $factory = DumpPayloadFactory::withDefaults();
        $data = (new VarCloner())->cloneVar(['foo' => 'bar']);

        $payload = $factory->create($data, ['timestamp' => 1758182400]);

        self::assertSame(date('c', 1758182400), $payload['timestamp']);
        self::assertSame('array', $payload['type']);
        self::assertSame(['foo' => 'bar'], $payload['value']);
    }

    #[Test]
    public function createDefaultsTimestampWhenMissing(): void
    {
        $factory = DumpPayloadFactory::withDefaults();
        $data = (new VarCloner())->cloneVar('test');

        $payload = $factory->create($data, []);

        self::assertSame(date('c', 0), $payload['timestamp']);
    }

    #[Test]
    public function createIncludesSourceSectionWhenPresent(): void
    {
        $factory = DumpPayloadFactory::withDefaults();
        $data = (new VarCloner())->cloneVar('test');

        $context = [
            'timestamp' => 1758182400,
            'source' => ['name' => 'TestController.php', 'file' => '/path/TestController.php', 'line' => 42],
        ];

        $payload = $factory->create($data, $context);

        self::assertSame(
            ['name' => 'TestController.php', 'file' => '/path/TestController.php', 'line' => 42],
            $payload['source'],
        );
    }

    #[Test]
    public function createSetsSourceToNullWhenAbsent(): void
    {
        $factory = DumpPayloadFactory::withDefaults();
        $data = (new VarCloner())->cloneVar('test');

        $payload = $factory->create($data, ['timestamp' => 1758182400]);

        self::assertNull($payload['source']);
    }

    #[Test]
    public function createNormalizesRequestController(): void
    {
        $factory = DumpPayloadFactory::withDefaults();
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

        $payload = $factory->create($data, $context);

        self::assertSame(
            ['method' => 'GET', 'uri' => '/some/path', 'controller' => 'SomeController::action'],
            $payload['request'],
        );
    }

    #[Test]
    public function createIncludesCliAndTypo3Sections(): void
    {
        $factory = DumpPayloadFactory::withDefaults();
        $data = (new VarCloner())->cloneVar('test');

        $context = [
            'timestamp' => 1758182400,
            'cli' => ['command_line' => 'bin/console some:command'],
            'typo3' => ['version' => '13.4.0', 'context' => 'Development'],
        ];

        $payload = $factory->create($data, $context);

        self::assertSame(['command_line' => 'bin/console some:command'], $payload['cli']);
        self::assertSame(['version' => '13.4.0', 'context' => 'Development'], $payload['typo3']);
    }
}
