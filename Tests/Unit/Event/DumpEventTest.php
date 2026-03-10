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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Event;

use KonradMichalik\Typo3DumpServer\Event\DumpEvent;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * DumpEventTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpEventTest extends TestCase
{
    public function testGetValueReturnsOriginalValue(): void
    {
        $value = ['foo' => 'bar'];
        $event = new DumpEvent($value);

        self::assertSame($value, $event->getValue());
    }

    public function testGetContextReturnsEmptyArrayByDefault(): void
    {
        $event = new DumpEvent('test');

        self::assertSame([], $event->getContext());
    }

    public function testGetContextReturnsProvidedContext(): void
    {
        $context = ['file' => 'test.php', 'line' => 42];
        $event = new DumpEvent('test', $context);

        self::assertSame($context, $event->getContext());
    }

    public function testGetTypeReturnsCorrectTypeForString(): void
    {
        $event = new DumpEvent('test');

        self::assertSame('string', $event->getType());
    }

    public function testGetTypeReturnsCorrectTypeForArray(): void
    {
        $event = new DumpEvent(['foo' => 'bar']);

        self::assertSame('array', $event->getType());
    }

    public function testGetTypeReturnsCorrectTypeForObject(): void
    {
        $event = new DumpEvent(new stdClass());

        self::assertSame('object', $event->getType());
    }

    public function testGetTypeReturnsCorrectTypeForInteger(): void
    {
        $event = new DumpEvent(123);

        self::assertSame('integer', $event->getType());
    }

    public function testGetTypeReturnsCorrectTypeForBoolean(): void
    {
        $event = new DumpEvent(true);

        self::assertSame('boolean', $event->getType());
    }

    public function testGetTypeReturnsCorrectTypeForNull(): void
    {
        $event = new DumpEvent(null);

        self::assertSame('NULL', $event->getType());
    }
}
