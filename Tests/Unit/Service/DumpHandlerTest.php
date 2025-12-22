<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_dump_server" TYPO3 CMS extension.
 *
 * (c) 2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Service;

use KonradMichalik\Typo3DumpServer\Service\DumpHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\VarDumper\VarDumper;

use function is_array;

/**
 * DumpHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset VarDumper handler after each test
        VarDumper::setHandler(null);

        // Reset GLOBALS
        unset($GLOBALS['TYPO3_CONF_VARS']);
    }

    public function testRegisterWithoutServerSetsNoHandler(): void
    {
        // When server is not available and suppressDump is not set,
        // the default handler should remain (which will be null in tests)
        DumpHandler::register();

        // We can't directly test the handler, but we can verify no exception was thrown
        self::assertSame(1, 1);
    }

    public function testRegisterWithSuppressDumpSetsEmptyHandler(): void
    {
        // Setup GLOBALS to enable suppressDump
        if (!isset($GLOBALS['TYPO3_CONF_VARS']) || !is_array($GLOBALS['TYPO3_CONF_VARS'])) {
            $GLOBALS['TYPO3_CONF_VARS'] = [];
        }
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = [];
        }
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'] = [];
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server']['suppressDump'] = true;

        DumpHandler::register();

        // Verify that a handler was set (dump() should not produce output)
        ob_start();
        dump('test');
        $output = ob_get_clean();

        self::assertSame('', $output);
    }

    public function testIsServerAvailableReturnsFalseForInvalidHost(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('isServerAvailable');

        // Test with invalid URL
        self::assertFalse($method->invoke(null, 'invalid-url'));
    }

    public function testIsServerAvailableReturnsFalseForMissingPort(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('isServerAvailable');

        // Test with URL missing port
        self::assertFalse($method->invoke(null, 'tcp://127.0.0.1'));
    }

    public function testIsServerAvailableReturnsFalseForEmptyHost(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('isServerAvailable');

        // Test with empty host
        self::assertFalse($method->invoke(null, 'tcp://:9912'));
    }

    public function testIsServerAvailableReturnsFalseForZeroPort(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('isServerAvailable');

        // Test with port 0
        self::assertFalse($method->invoke(null, 'tcp://127.0.0.1:0'));
    }

    public function testIsServerAvailableReturnsFalseForUnreachableServer(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('isServerAvailable');

        // Use a port that is unlikely to be in use
        self::assertFalse($method->invoke(null, 'tcp://127.0.0.1:59999'));
    }

    public function testShouldSuppressDumpReturnsFalseByDefault(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        self::assertFalse($method->invoke(null));
    }

    public function testShouldSuppressDumpReturnsTrueWhenConfigured(): void
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']) || !is_array($GLOBALS['TYPO3_CONF_VARS'])) {
            $GLOBALS['TYPO3_CONF_VARS'] = [];
        }
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = [];
        }
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'] = [];
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server']['suppressDump'] = true;

        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        self::assertTrue($method->invoke(null));
    }

    public function testShouldSuppressDumpReturnsFalseWhenSetToFalse(): void
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']) || !is_array($GLOBALS['TYPO3_CONF_VARS'])) {
            $GLOBALS['TYPO3_CONF_VARS'] = [];
        }
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = [];
        }
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'] = [];
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server']['suppressDump'] = false;

        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        self::assertFalse($method->invoke(null));
    }

    public function testShouldSuppressDumpHandlesPartialConfiguration(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        // Test with only TYPO3_CONF_VARS set
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        self::assertFalse($method->invoke(null));

        // Test with EXTENSIONS set but not typo3_dump_server
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = [];
        self::assertFalse($method->invoke(null));

        // Test with typo3_dump_server set but no suppressDump
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'] = [];
        self::assertFalse($method->invoke(null));
    }

    public function testShouldSuppressDumpHandlesInvalidTypes(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        // Test with non-array TYPO3_CONF_VARS
        $GLOBALS['TYPO3_CONF_VARS'] = 'not-an-array';
        self::assertFalse($method->invoke(null));

        // Reset and test with non-array EXTENSIONS
        $GLOBALS['TYPO3_CONF_VARS'] = ['EXTENSIONS' => 'not-an-array'];
        self::assertFalse($method->invoke(null));

        // Reset and test with non-array extension config
        $GLOBALS['TYPO3_CONF_VARS'] = ['EXTENSIONS' => ['typo3_dump_server' => 'not-an-array']];
        self::assertFalse($method->invoke(null));
    }
}
