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

namespace KonradMichalik\Typo3DumpServer\Command;

use KonradMichalik\Typo3DumpServer\Command\Descriptor\{Typo3CliDescriptor, Typo3HtmlDescriptor};
use KonradMichalik\Typo3DumpServer\Utility\{EnvironmentHelper, IdeLinkGenerator};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Command\Descriptor\DumpDescriptorInterface;
use Symfony\Component\VarDumper\Dumper\{CliDumper, HtmlDumper};
use Symfony\Component\VarDumper\Server\DumpServer;

use function is_string;
use function sprintf;

/**
 * DumpServerCommand.
 *
 * @see https://github.com/symfony/symfony/blob/7.3/src/Symfony/Component/VarDumper/Command/ServerDumpCommand.php
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpServerCommand extends Command
{
    /** @var array<string, DumpDescriptorInterface> */
    private array $descriptors;

    /**
     * @param array<string, DumpDescriptorInterface> $descriptors
     */
    public function __construct(?string $name = null, array $descriptors = [])
    {
        $ideLinkGenerator = $this->createIdeLinkGenerator();

        $this->descriptors = $descriptors + [
            'cli' => new Typo3CliDescriptor(new CliDumper(), $ideLinkGenerator),
            'html' => new Typo3HtmlDescriptor(new HtmlDumper(), $ideLinkGenerator),
        ];
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('format', null, InputOption::VALUE_REQUIRED, sprintf('The output format (%s)', implode(', ', $this->getAvailableFormats())), 'cli')
            ->setHelp(
                <<<'EOF'
<info>%command.name%</info> starts a dump server that collects and displays
dumps in a single place for debugging you application:

<info>php %command.full_name%</info>

You can consult dumped data in HTML format in your browser by providing the <comment>--format=html</comment> option
and redirecting the output to a file:

<info>php %command.full_name% --format="html" > dump.html</info>

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $format = $input->getOption('format');

        if (!is_string($format)) {
            throw new InvalidArgumentException('Format option must be a string.', 8369534571);
        }

        $descriptor = $this->descriptors[$format] ?? null;
        if (null === $descriptor) {
            throw new InvalidArgumentException(sprintf('Unsupported format "%s".', $format), 8369534570);
        }

        $server = new DumpServer(EnvironmentHelper::getHost());

        $errorIo = $io->getErrorStyle();
        $errorIo->title('TYPO3 Var Dump Server');

        $server->start();

        $errorIo->success(sprintf('Server listening on %s', $server->getHost()));
        $errorIo->comment('Quit the server with CONTROL-C.');

        $server->listen(function (Data $data, array $context, int $clientId) use ($descriptor, $io) {
            $descriptor->describe($io, $data, $context, $clientId);
        });

        return Command::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function getAvailableFormats(): array
    {
        return array_keys($this->descriptors);
    }

    private function createIdeLinkGenerator(): ?IdeLinkGenerator
    {
        $ide = EnvironmentHelper::getIde();

        if (null === $ide || !IdeLinkGenerator::isSupported($ide)) {
            return null;
        }

        return new IdeLinkGenerator($ide);
    }
}
