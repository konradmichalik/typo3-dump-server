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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Command;

use KonradMichalik\Typo3DumpServer\Command\DumpServerCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * DumpServerCommandTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpServerCommandTest extends TestCase
{
    private DumpServerCommand $command;

    protected function setUp(): void
    {
        $this->command = new DumpServerCommand('server:dump');
    }

    public function testCommandHasCorrectName(): void
    {
        self::assertSame('server:dump', $this->command->getName());
    }

    public function testCommandHasFormatOption(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('format'));
    }

    public function testInvalidFormatThrowsException(): void
    {
        $input = new ArrayInput(['--format' => 'invalid']);
        $output = new BufferedOutput();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format "invalid".');

        $this->command->run($input, $output);
    }

    public function testValidFormatsAreAccepted(): void
    {
        $definition = $this->command->getDefinition();
        $formatOption = $definition->getOption('format');

        self::assertSame('cli', $formatOption->getDefault());
        self::assertTrue($formatOption->isValueRequired());
    }
}
