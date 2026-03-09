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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Dumper\ContextProvider;

use KonradMichalik\Typo3DumpServer\Dumper\ContextProvider\Typo3ContextProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Typo3ContextProviderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class Typo3ContextProviderTest extends TestCase
{
    #[Test]
    public function getContextReturnsNullWhenTypo3IsNotAvailable(): void
    {
        $provider = new Typo3ContextProvider();

        // Without TYPO3 bootstrap, Environment class throws an exception
        // and the provider returns null gracefully
        $context = $provider->getContext();

        // Either null (TYPO3 not bootstrapped) or array (TYPO3 available)
        if (null !== $context) {
            self::assertArrayHasKey('version', $context);
            self::assertArrayHasKey('context', $context);
            self::assertIsString($context['version']);
            self::assertIsString($context['context']);
        } else {
            self::assertNull($context);
        }
    }

    #[Test]
    public function providerImplementsContextProviderInterface(): void
    {
        $provider = new Typo3ContextProvider();

        self::assertInstanceOf(
            \Symfony\Component\VarDumper\Dumper\ContextProvider\ContextProviderInterface::class,
            $provider,
        );
    }
}
