# AGENTS.md

## Project overview

TYPO3 extension that brings the Symfony Var Dump Server to TYPO3. It collects all `dump()` output in one place (`vendor/bin/typo3 server:dump`) so debugging output never interferes with HTTP or API responses.

- Package: `konradmichalik/typo3-dump-server`, extension key `typo3_dump_server`
- Namespace: `KonradMichalik\Typo3DumpServer` (PSR-4, `Classes/`)
- Requirements: PHP 8.2 to 8.5, TYPO3 11.5, 12.4, 13.4 and 14.0

## Structure

- `Classes/Command/` `server:dump` command and its CLI, HTML and JSON (NDJSON) descriptors
- `Classes/Dumper/` context provider, payload factory and normalizer
- `Classes/Service/` `DumpHandler` (registers the VarDumper handler, chooses server or sink) and `DumpSink`
- `Classes/Event/` PSR-14 `DumpEvent`
- `Classes/Utility/` host, sink and IDE environment resolution, IDE link generation
- `Classes/ViewHelpers/` Fluid `DumpViewHelper`
- `Configuration/`, `Resources/`, `ext_localconf.php` TYPO3 registration (`ext_localconf.php` calls `DumpHandler::register()`)
- `Tests/Unit/` PHPUnit tests, mirrors `Classes/`
- `Tests/CGL/` separate Composer project with all code style, static analysis and migration tooling
- `Documentation/` user docs and `DEVELOPMENT.md` (manual feature checklist)
- `.ddev/` DDEV setup, including commands to install TYPO3 11 to 14 test instances

Configuration: extension setting `suppressDump`, environment variables `TYPO3_DUMP_SERVER_HOST` (default `tcp://127.0.0.1:9912`), `TYPO3_DUMP_SERVER_SINK` (NDJSON file, only in `Development` context) and `TYPO3_DUMP_SERVER_IDE`.

## Development commands

The project uses DDEV. Prefix commands with `ddev`.

```bash
ddev start
ddev composer install

# TYPO3 test instances
ddev install all      # or: ddev install 12
ddev 13 typo3 cache:flush
ddev launch
```

Lint, fix, static analysis and migration run through `ddev cgl`, which executes the scripts of `Tests/CGL/composer.json`:

```bash
ddev cgl lint      # composer, editorconfig, language, php, typoscript
ddev cgl fix       # composer, editorconfig, php
ddev cgl sca       # PHPStan
ddev cgl migration # Rector
```

Single linters: `ddev cgl lint:composer`, `lint:editorconfig`, `lint:language`, `lint:php`, `lint:typoscript`. Matching fixers: `fix:composer`, `fix:editorconfig`, `fix:php`.

## Testing

PHPUnit, configured in `phpunit.xml`, tests in `Tests/Unit/`.

```bash
ddev composer test           # without coverage
ddev composer test:coverage  # clover, HTML and JUnit reports in .Build/coverage/
ddev exec vendor/bin/phpunit Tests/Unit/Service/DumpHandlerTest.php
ddev exec vendor/bin/phpunit --filter testMethodName
```

CI runs the shared `tests-typo3` workflow on PHP 8.2, 8.3, 8.4 and 8.5. The CGL workflow runs the linters on every push.

## Code style and static analysis

- PHP CS Fixer with `konradmichalik/php-cs-fixer-preset`, config in `Tests/CGL/.php-cs-fixer.php`
- Every PHP file starts with `declare(strict_types=1);` followed by the license DocBlock header (`This file is part of the "typo3_dump_server" TYPO3 CMS extension.`, copyright, license). The fixer generates it, run `ddev cgl fix:php`
- PHPStan level `max` with `konradmichalik/phpstan-typo3-preset`, baseline in `Tests/CGL/phpstan-baseline.neon`
- Rector config in `Tests/CGL/rector.php`
- `composer-dependency-analyser` checks dependencies (`ddev cgl analyze`)
- EditorConfig is enforced via `.editorconfig`
- Explicit parameter and return types everywhere

## Git workflow

- Commit format: `<type>: <description>` with type one of `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`
- Single-line messages, no co-author trailers
- One commit per logical change, open a pull request against `main`
