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

namespace KonradMichalik\Typo3DumpServer\Command\Descriptor;

use KonradMichalik\Typo3DumpServer\Utility\IdeLinkGenerator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Command\Descriptor\DumpDescriptorInterface;
use Symfony\Component\VarDumper\Dumper\CliDumper;

use function date;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function rtrim;
use function sprintf;

/**
 * Typo3CliDescriptor.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class Typo3CliDescriptor implements DumpDescriptorInterface
{
    private mixed $lastIdentifier = null;

    public function __construct(
        private readonly CliDumper $dumper,
        private readonly ?IdeLinkGenerator $ideLinkGenerator = null,
    ) {}

    /** @phpstan-ignore missingType.iterableValue */
    public function describe(OutputInterface $output, Data $data, array $context, int $clientId): void
    {
        $io = $output instanceof SymfonyStyle ? $output : new SymfonyStyle(new ArrayInput([]), $output);
        $this->dumper->setColors($output->isDecorated());

        /** @var array<string, mixed> $context */
        $timestamp = isset($context['timestamp']) && is_float($context['timestamp'])
            ? (int) $context['timestamp']
            : 0;
        $rows = [['date', date('r', $timestamp)]];
        $lastIdentifier = $this->lastIdentifier;
        $this->lastIdentifier = $clientId;

        $section = sprintf('Received from client #%d', $clientId);
        $this->resolveRequestContext($context, $rows, $section);
        $this->resolveCliContext($context, $section);

        if ($this->lastIdentifier !== $lastIdentifier) {
            $io->section($section);
        }

        $this->resolveSourceContext($context, $rows);
        $this->resolveTypo3Context($context, $rows);

        $io->table([], $rows);

        $this->dumper->dump($data);
        $io->newLine();
    }

    /**
     * @param array<string, mixed>        $context
     * @param list<array{string, string}> $rows
     */
    private function resolveRequestContext(array $context, array &$rows, string &$section): void
    {
        if (!isset($context['request']) || !is_array($context['request'])) {
            return;
        }

        $request = $context['request'];
        $this->lastIdentifier = $request['identifier'] ?? $this->lastIdentifier;

        $method = is_string($request['method'] ?? null) ? $request['method'] : '';
        $uri = is_string($request['uri'] ?? null) ? $request['uri'] : '';
        $section = sprintf('%s %s', $method, $uri);

        $controller = $request['controller'] ?? null;
        if ($controller instanceof Data) {
            $dumpOutput = $this->dumper->dump($controller, true);
            $rows[] = ['controller', rtrim(is_string($dumpOutput) ? $dumpOutput : '', "\n")];
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveCliContext(array $context, string &$section): void
    {
        if (isset($context['request']) || !isset($context['cli']) || !is_array($context['cli'])) {
            return;
        }

        $cli = $context['cli'];
        $this->lastIdentifier = $cli['identifier'] ?? $this->lastIdentifier;
        $commandLine = is_string($cli['command_line'] ?? null) ? $cli['command_line'] : '';
        $section = '$ '.$commandLine;
    }

    /**
     * @param array<string, mixed>        $context
     * @param list<array{string, string}> $rows
     */
    private function resolveSourceContext(array $context, array &$rows): void
    {
        if (!isset($context['source']) || !is_array($context['source'])) {
            return;
        }

        /** @var array<string, mixed> $source */
        $source = $context['source'];
        $name = is_string($source['name'] ?? null) ? $source['name'] : '-';
        $line = $this->extractLineNumber($source);
        $sourceInfo = sprintf('%s on line %d', $name, $line);

        $fileLink = $this->resolveFileLink($source);
        if (null !== $fileLink) {
            $sourceInfo = sprintf('<href=%s>%s</>', $fileLink, $sourceInfo);
        }

        $rows[] = ['source', $sourceInfo];

        $file = is_string($source['file_relative'] ?? null)
            ? $source['file_relative']
            : (is_string($source['file'] ?? null) ? $source['file'] : '-');
        $rows[] = ['file', $file];
    }

    /**
     * @param array<string, mixed>        $context
     * @param list<array{string, string}> $rows
     */
    private function resolveTypo3Context(array $context, array &$rows): void
    {
        if (!isset($context['typo3']) || !is_array($context['typo3'])) {
            return;
        }

        $typo3 = $context['typo3'];
        $version = is_string($typo3['version'] ?? null) ? $typo3['version'] : '-';
        $appContext = is_string($typo3['context'] ?? null) ? $typo3['context'] : '-';
        $rows[] = ['typo3', sprintf('%s (%s)', $version, $appContext)];
    }

    /**
     * @param array<string, mixed> $source
     */
    private function resolveFileLink(array $source): ?string
    {
        if (null !== $this->ideLinkGenerator) {
            $file = is_string($source['file'] ?? null) ? $source['file'] : null;
            $line = $this->extractLineNumber($source);

            if (null !== $file && 0 !== $line) {
                return $this->ideLinkGenerator->generate($file, $line);
            }
        }

        $fileLink = $source['file_link'] ?? null;

        return is_string($fileLink) ? $fileLink : null;
    }

    /**
     * @param array<string, mixed> $source
     */
    private function extractLineNumber(array $source): int
    {
        $line = $source['line'] ?? 0;

        return is_int($line) ? $line : 0;
    }
}
