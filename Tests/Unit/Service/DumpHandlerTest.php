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

use KonradMichalik\Ttt\Attribute\{WithEnvironment, WithTypo3ConfVars};
use KonradMichalik\Ttt\Traits\ConfVarsSandbox;
use KonradMichalik\Typo3DumpServer\Service\DumpHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\VarDumper\VarDumper;

use function file_get_contents;
use function is_file;
use function is_string;
use function json_decode;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

/**
 * DumpHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpHandlerTest extends TestCase
{
    use ConfVarsSandbox;

    private string $originalHostValue;

    private string $originalSinkValue;

    private ?string $sinkPath = null;

    protected function setUp(): void
    {
        $dumpServerHost = getenv('TYPO3_DUMP_SERVER_HOST');
        $this->originalHostValue = is_string($dumpServerHost) ? $dumpServerHost : '';

        $dumpServerSink = getenv('TYPO3_DUMP_SERVER_SINK');
        $this->originalSinkValue = is_string($dumpServerSink) ? $dumpServerSink : '';
    }

    protected function tearDown(): void
    {
        // Reset VarDumper handler after each test (not sandboxed by ttt)
        VarDumper::setHandler(null);

        // Restore any mid-test TYPO3_CONF_VARS manipulations
        $this->restoreTypo3ConfVars();

        // Reset cached event dispatcher
        (new ReflectionProperty(DumpHandler::class, 'eventDispatcher'))->setValue(null, null);

        if ('' !== $this->originalHostValue) {
            putenv('TYPO3_DUMP_SERVER_HOST='.$this->originalHostValue);
        } else {
            putenv('TYPO3_DUMP_SERVER_HOST');
        }

        if ('' !== $this->originalSinkValue) {
            putenv('TYPO3_DUMP_SERVER_SINK='.$this->originalSinkValue);
        } else {
            putenv('TYPO3_DUMP_SERVER_SINK');
        }

        if (null !== $this->sinkPath && is_file($this->sinkPath)) {
            unlink($this->sinkPath);
        }
    }

    public function testRegisterInstallsHandlerWithoutConnectingToServer(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        self::assertNotFalse($server);
        $address = stream_socket_get_name($server, false);
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://'.$address);

        DumpHandler::register();

        $handler = VarDumper::setHandler(null);
        self::assertNotNull($handler, 'register() should install a dump handler');
        self::assertFalse(
            $this->hasPendingConnection($server),
            'register() must not connect to the dump server',
        );

        fclose($server);
    }

    public function testFirstDumpConnectsToServer(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        self::assertNotFalse($server);
        $address = stream_socket_get_name($server, false);
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://'.$address);

        DumpHandler::register();
        dump('test');

        self::assertTrue(
            $this->hasPendingConnection($server),
            'The first dump() call should connect to the dump server',
        );

        fclose($server);
    }

    public function testDumpStillReachesServerWhenEventListenerThrows(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        self::assertNotFalse($server);
        $address = stream_socket_get_name($server, false);
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://'.$address);

        $throwingDispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                throw new RuntimeException('listener failure', 2834025464);
            }
        };
        (new ReflectionProperty(DumpHandler::class, 'eventDispatcher'))->setValue(null, $throwingDispatcher);

        DumpHandler::register();
        dump('test');

        self::assertTrue(
            $this->hasPendingConnection($server),
            'A throwing event listener must not prevent the dump from reaching the server',
        );

        fclose($server);
    }

    #[WithTypo3ConfVars(['EXTENSIONS' => ['typo3_dump_server' => ['suppressDump' => true]]])]
    public function testRegisterWithSuppressDumpSetsEmptyHandler(): void
    {
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://127.0.0.1:59999');

        DumpHandler::register();

        // Verify that a handler was set (dump() should not produce output)
        ob_start();
        dump('test');
        $output = ob_get_clean();

        self::assertSame('', $output);
    }

    public function testDumpFallsBackToDefaultHandlerWhenServerUnavailableAndNotSuppressed(): void
    {
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://127.0.0.1:59999');

        DumpHandler::register();

        $result = dump('fallback-value');

        self::assertSame('fallback-value', $result);
    }

    #[Test]
    #[WithEnvironment(context: 'Development')]
    public function dumpWritesToSinkWhenServerUnavailableAndDevelopmentContext(): void
    {
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://127.0.0.1:59999');
        $this->sinkPath = (string) tempnam(sys_get_temp_dir(), 'dump_handler_sink_test_');
        putenv('TYPO3_DUMP_SERVER_SINK='.$this->sinkPath);

        DumpHandler::register();
        dump('sink-value');

        $line = json_decode(trim((string) file_get_contents($this->sinkPath)), true);
        self::assertSame('sink-value', $line['value']);
    }

    #[Test]
    #[WithEnvironment(context: 'Production')]
    public function dumpDoesNotWriteToSinkOutsideDevelopmentContext(): void
    {
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://127.0.0.1:59999');
        $this->sinkPath = (string) tempnam(sys_get_temp_dir(), 'dump_handler_sink_test_');
        unlink($this->sinkPath);
        putenv('TYPO3_DUMP_SERVER_SINK='.$this->sinkPath);

        DumpHandler::register();
        $result = dump('fallback-value');

        self::assertFalse(is_file($this->sinkPath));
        self::assertSame('fallback-value', $result);
    }

    #[Test]
    #[WithEnvironment(context: 'Development')]
    public function dumpPrefersServerOverSinkWhenBothAreAvailable(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        self::assertNotFalse($server);
        $address = stream_socket_get_name($server, false);
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://'.$address);

        $this->sinkPath = (string) tempnam(sys_get_temp_dir(), 'dump_handler_sink_test_');
        unlink($this->sinkPath);
        putenv('TYPO3_DUMP_SERVER_SINK='.$this->sinkPath);

        DumpHandler::register();
        dump('test');

        self::assertTrue($this->hasPendingConnection($server));
        self::assertFalse(is_file($this->sinkPath));

        fclose($server);
    }

    #[Test]
    #[WithEnvironment(context: 'Development')]
    public function dumpStillWritesToSinkWhenEventListenerThrows(): void
    {
        putenv('TYPO3_DUMP_SERVER_HOST=tcp://127.0.0.1:59999');
        $this->sinkPath = (string) tempnam(sys_get_temp_dir(), 'dump_handler_sink_test_');
        putenv('TYPO3_DUMP_SERVER_SINK='.$this->sinkPath);

        $throwingDispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                throw new RuntimeException('listener failure', 3423423423);
            }
        };
        (new ReflectionProperty(DumpHandler::class, 'eventDispatcher'))->setValue(null, $throwingDispatcher);

        DumpHandler::register();
        dump('sink-value');

        $line = json_decode(trim((string) file_get_contents($this->sinkPath)), true);
        self::assertSame('sink-value', $line['value']);
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

    #[WithTypo3ConfVars(['EXTENSIONS' => ['typo3_dump_server' => ['suppressDump' => true]]])]
    public function testShouldSuppressDumpReturnsTrueWhenConfigured(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        self::assertTrue($method->invoke(null));
    }

    #[WithTypo3ConfVars(['EXTENSIONS' => ['typo3_dump_server' => ['suppressDump' => false]]])]
    public function testShouldSuppressDumpReturnsFalseWhenSetToFalse(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        self::assertFalse($method->invoke(null));
    }

    public function testShouldSuppressDumpHandlesPartialConfiguration(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        // Only TYPO3_CONF_VARS set
        $this->setTypo3ConfVars([]);
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        self::assertFalse($method->invoke(null));

        // EXTENSIONS set but not typo3_dump_server
        $GLOBALS['TYPO3_CONF_VARS'] = ['EXTENSIONS' => []];
        self::assertFalse($method->invoke(null));

        // typo3_dump_server set but no suppressDump
        $GLOBALS['TYPO3_CONF_VARS'] = ['EXTENSIONS' => ['typo3_dump_server' => []]];
        self::assertFalse($method->invoke(null));
    }

    public function testShouldSuppressDumpHandlesInvalidTypes(): void
    {
        $reflection = new ReflectionClass(DumpHandler::class);
        $method = $reflection->getMethod('shouldSuppressDump');

        // Register a restore point; the scalar assignments below cannot be
        // expressed through the array-only sandbox API, but restoreTypo3ConfVars()
        // still reverts $GLOBALS['TYPO3_CONF_VARS'] to this snapshot afterwards.
        $this->setTypo3ConfVars([]);

        // Non-array TYPO3_CONF_VARS
        $GLOBALS['TYPO3_CONF_VARS'] = 'not-an-array';
        self::assertFalse($method->invoke(null));

        // Non-array EXTENSIONS
        $GLOBALS['TYPO3_CONF_VARS'] = ['EXTENSIONS' => 'not-an-array'];
        self::assertFalse($method->invoke(null));

        // Non-array extension config
        $GLOBALS['TYPO3_CONF_VARS'] = ['EXTENSIONS' => ['typo3_dump_server' => 'not-an-array']];
        self::assertFalse($method->invoke(null));
    }

    /**
     * @param resource $server
     */
    private function hasPendingConnection($server): bool
    {
        $read = [$server];
        $write = [];
        $except = [];

        return stream_select($read, $write, $except, 0, 50000) > 0;
    }
}
