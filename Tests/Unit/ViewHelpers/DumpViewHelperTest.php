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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\ViewHelpers;

use KonradMichalik\Typo3DumpServer\ViewHelpers\DumpViewHelper;
use PHPUnit\Framework\TestCase;

/**
 * DumpViewHelperTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpViewHelperTest extends TestCase
{
    private DumpViewHelper $viewHelper;

    protected function setUp(): void
    {
        $this->viewHelper = new DumpViewHelper();
    }

    public function testInitializeArgumentsDoesNotThrowException(): void
    {
        $this->expectNotToPerformAssertions();
        $this->viewHelper->initializeArguments();
    }

    public function testViewHelperExtendsAbstractViewHelper(): void
    {
        self::assertInstanceOf(\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper::class, $this->viewHelper);
    }
}
