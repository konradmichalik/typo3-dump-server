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

namespace KonradMichalik\Typo3DumpServer\Service;

use KonradMichalik\Typo3DumpServer\Event\DumpEvent;
use KonradMichalik\Typo3DumpServer\Utility\EnvironmentHelper;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\{CliDumper, HtmlDumper, ServerDumper};
use Symfony\Component\VarDumper\Dumper\ContextProvider\{CliContextProvider, SourceContextProvider};
use Symfony\Component\VarDumper\VarDumper;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function in_array;
use function is_array;

/**
 * DumpHandler.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpHandler
{
    private const SERVER_CONNECTION_TIMEOUT = 0.5;

    private static ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * @see https://symfony.com/doc/current/components/var_dumper.html#the-dump-server
     */
    public static function register(): void
    {
        if (self::isServerAvailable(EnvironmentHelper::getHost())) {
            self::registerServerHandler();
        } elseif (self::shouldSuppressDump()) {
            VarDumper::setHandler(function (): void {});
        }
    }

    private static function registerServerHandler(): void
    {
        $cloner = new VarCloner();
        $fallbackDumper = in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) ? new CliDumper() : new HtmlDumper();
        $dumper = new ServerDumper(EnvironmentHelper::getHost(), $fallbackDumper, [
            'cli' => new CliContextProvider(),
            'source' => new SourceContextProvider(),
        ]);

        VarDumper::setHandler(static function (mixed $var) use ($cloner, $dumper): ?string {
            $data = $cloner->cloneVar($var);
            $context = [];

            // Dispatch PSR-14 event with original variable
            $eventDispatcher = self::getEventDispatcher();
            if (null !== $eventDispatcher) {
                $event = new DumpEvent($var, $context);
                $eventDispatcher->dispatch($event);
            }

            return $dumper->dump($data);
        });
    }

    private static function shouldSuppressDump(): bool
    {
        if (
            isset($GLOBALS['TYPO3_CONF_VARS'])
            && is_array($GLOBALS['TYPO3_CONF_VARS'])
            && isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'])
            && isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server'])
            && isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server']['suppressDump'])
        ) {
            return (bool) $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_dump_server']['suppressDump'];
        }

        return false;
    }

    private static function isServerAvailable(string $host): bool
    {
        $urlParts = parse_url($host);

        if (false === $urlParts || !isset($urlParts['host'], $urlParts['port'])) {
            return false;
        }

        if ('' === $urlParts['host'] || 0 === $urlParts['port']) {
            return false;
        }

        $connection = @fsockopen(
            $urlParts['host'],
            $urlParts['port'],
            $errno,
            $errstr,
            self::SERVER_CONNECTION_TIMEOUT,
        );

        if (false !== $connection) {
            fclose($connection);

            return true;
        }

        return false;
    }

    private static function getEventDispatcher(): ?EventDispatcherInterface
    {
        if (null === self::$eventDispatcher) {
            try {
                // Get TYPO3 PSR-14 event dispatcher
                self::$eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
            } catch (Throwable) {
                // Event dispatcher not available (e.g., during bootstrap)
                self::$eventDispatcher = null;
            }
        }

        return self::$eventDispatcher;
    }
}
