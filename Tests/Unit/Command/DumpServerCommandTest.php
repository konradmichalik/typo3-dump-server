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
use KonradMichalik\Typo3DumpServer\Utility\IdeLinkGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function assert;
use function is_array;
use function is_string;

/**
 * DumpServerCommandTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpServerCommandTest extends TestCase
{
    private DumpServerCommand $command;

    private string $originalIdeValue;

    private string $originalPathMapValue;

    private string $originalDdevAppRootValue;

    protected function setUp(): void
    {
        $this->command = new DumpServerCommand('server:dump');

        $dumpServerIde = getenv('TYPO3_DUMP_SERVER_IDE');
        $this->originalIdeValue = is_string($dumpServerIde) ? $dumpServerIde : '';

        $dumpServerPathMap = getenv('TYPO3_DUMP_SERVER_PATH_MAP');
        $this->originalPathMapValue = is_string($dumpServerPathMap) ? $dumpServerPathMap : '';

        $ddevAppRoot = getenv('DDEV_APPROOT');
        $this->originalDdevAppRootValue = is_string($ddevAppRoot) ? $ddevAppRoot : '';
    }

    protected function tearDown(): void
    {
        if ('' !== $this->originalIdeValue) {
            putenv('TYPO3_DUMP_SERVER_IDE='.$this->originalIdeValue);
        } else {
            putenv('TYPO3_DUMP_SERVER_IDE');
        }

        if ('' !== $this->originalPathMapValue) {
            putenv('TYPO3_DUMP_SERVER_PATH_MAP='.$this->originalPathMapValue);
        } else {
            putenv('TYPO3_DUMP_SERVER_PATH_MAP');
        }

        if ('' !== $this->originalDdevAppRootValue) {
            putenv('DDEV_APPROOT='.$this->originalDdevAppRootValue);
        } else {
            putenv('DDEV_APPROOT');
        }
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

    public function testNonStringFormatOptionThrowsException(): void
    {
        $input = new class(['--format' => 'cli']) extends ArrayInput {
            public function getOption(string $name): mixed
            {
                return 'format' === $name ? true : parent::getOption($name);
            }
        };
        $output = new BufferedOutput();

        $method = new ReflectionMethod($this->command, 'execute');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format option must be a string.');

        $method->invoke($this->command, $input, $output);
    }

    public function testValidFormatsAreAccepted(): void
    {
        $definition = $this->command->getDefinition();
        $formatOption = $definition->getOption('format');

        self::assertSame('cli', $formatOption->getDefault());
        self::assertTrue($formatOption->isValueRequired());
    }

    public function testIdeLinkGeneratorAppliesConfiguredPathMapping(): void
    {
        putenv('TYPO3_DUMP_SERVER_IDE=phpstorm');
        putenv('TYPO3_DUMP_SERVER_PATH_MAP=/var/www/html=/Users/me/Projects');
        putenv('DDEV_APPROOT');

        $ideLinkGenerator = $this->extractIdeLinkGenerator(new DumpServerCommand('server:dump'));

        self::assertInstanceOf(IdeLinkGenerator::class, $ideLinkGenerator);
        self::assertSame(
            'phpstorm://open?file=/Users/me/Projects/test.php&line=42',
            $ideLinkGenerator->generate('/var/www/html/test.php', 42),
        );
    }

    public function testIdeLinkGeneratorWorksWithoutPathMapping(): void
    {
        putenv('TYPO3_DUMP_SERVER_IDE=phpstorm');
        putenv('TYPO3_DUMP_SERVER_PATH_MAP');
        putenv('DDEV_APPROOT');

        $ideLinkGenerator = $this->extractIdeLinkGenerator(new DumpServerCommand('server:dump'));

        self::assertInstanceOf(IdeLinkGenerator::class, $ideLinkGenerator);
        self::assertSame(
            'phpstorm://open?file=/var/www/html/test.php&line=42',
            $ideLinkGenerator->generate('/var/www/html/test.php', 42),
        );
    }

    private function extractIdeLinkGenerator(DumpServerCommand $command): ?IdeLinkGenerator
    {
        $descriptors = (new ReflectionProperty($command, 'descriptors'))->getValue($command);
        assert(is_array($descriptors));

        $ideLinkGenerator = (new ReflectionProperty($descriptors['cli'], 'ideLinkGenerator'))->getValue($descriptors['cli']);

        return $ideLinkGenerator instanceof IdeLinkGenerator ? $ideLinkGenerator : null;
    }
}
