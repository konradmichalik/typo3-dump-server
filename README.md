<div align="center">

![Extension icon](Resources/Public/Icons/Extension.png)

# TYPO3 extension `typo3_dump_server`

[![Latest Stable Version](https://typo3-badges.dev/badge/typo3_dump_server/version/shields.svg)](https://extensions.typo3.org/extension/typo3_dump_server)
![TYPO3](https://img.shields.io/badge/TYPO3-11.5%20%7C%2012.4%20%7C%2013.4%20%7C%2014.3-orange.svg)
[![Coverage](https://coveralls.io/repos/github/konradmichalik/typo3-dump-server/badge.svg?branch=main)](https://coveralls.io/github/konradmichalik/typo3-dump-server)
[![CGL](https://img.shields.io/github/actions/workflow/status/konradmichalik/typo3-dump-server/cgl.yml?label=cgl&logo=github)](https://github.com/konradmichalik/typo3-dump-server/actions/workflows/cgl.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/konradmichalik/typo3-dump-server/tests.yml?label=tests&logo=github)](https://github.com/konradmichalik/typo3-dump-server/actions/workflows/tests.yml)
[![License](https://poser.pugx.org/konradmichalik/typo3-dump-server/license)](LICENSE.md)

</div>

This extension brings the [Symfony Var Dump Server](https://symfony.com/doc/current/components/var_dumper.html#the-dump-server) to TYPO3.

> [!NOTE]
> This package is an alternative approach to the default [TYPO3 debugging methods](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Debugging/Index.html) or
> the universal use of [xdebug](https://xdebug.org/).

The dump server gathers all `dump` call outputs, e.g. for preventing interference with HTTP or API responses.

![Console Command](./Documentation/Images/screenshot.png)

## ✨ Features

* Dump server collecting `dump()` output outside the HTTP/API response
* [IDE deep links](Documentation/ide-deep-links.md) — click a source path to open it in your editor
* [Output formats](Documentation/output-formats.md) — `cli`, `html`, and `json` (NDJSON), including AI-agent consumption
* [PSR-14 events](Documentation/events.md) — react to dumps programmatically
* [Extension configuration](Documentation/configuration.md) — suppress frontend output when no server is running

## 🔥 Installation

### Requirements

* TYPO3 >= 11.5
* PHP 8.2+

### Composer

[![Packagist](https://img.shields.io/packagist/v/konradmichalik/typo3-dump-server?label=version&logo=packagist)](https://packagist.org/packages/konradmichalik/typo3-dump-server)
[![Packagist Downloads](https://img.shields.io/packagist/dt/konradmichalik/typo3-dump-server?color=brightgreen)](https://packagist.org/packages/konradmichalik/typo3-dump-server)

```bash
composer require --dev konradmichalik/typo3-dump-server
```

### TER

[![TER version](https://typo3-badges.dev/badge/typo3_dump_server/version/shields.svg)](https://extensions.typo3.org/extension/typo3_dump_server)
[![TER downloads](https://typo3-badges.dev/badge/typo3_dump_server/downloads/shields.svg)](https://extensions.typo3.org/extension/typo3_dump_server)

Download the zip file from [TYPO3 extension repository (TER)](https://extensions.typo3.org/extension/typo3_dump_server).

## 🚀 Quick start

Start the dump server, then call `dump()` anywhere in your TYPO3 code. Output appears in the terminal instead of the frontend response.

```bash
vendor/bin/typo3 server:dump
```

```php
dump($variable);
```

![Console Command](./Documentation/Images/screenshot-command.png)

> [!WARNING]
> The dump server protocol is unauthenticated and unencrypted, and dumps often contain sensitive data (credentials, session data, personal data). Keep the server bound to a loopback address (`127.0.0.1`) — never expose it via `0.0.0.0` or a public interface. Install the extension as a dev dependency (`composer require --dev`), and make sure production deployments run `composer install --no-dev` (or an equivalent process that excludes dev dependencies) so it is not deployed to production systems.

## ⚡ Usage

![Screencast](./Documentation/Images/screencast.gif)

### Console command

Use the format option to change the output format to `html`:

```bash
vendor/bin/typo3 server:dump --format=html > dump.html
```

> [!NOTE]
> The dump server will be available at `tcp://127.0.0.1:9912` by default. Use the environment variable `TYPO3_DUMP_SERVER_HOST` to change the host.

### TYPO3 context

The dump server automatically displays the TYPO3 version and application context (e.g. `Development`, `Production`) alongside each dump output.

### Dump function

Use the `dump` function in your code:

```php
dump($variable);

// or

\Symfony\Component\VarDumper::dump($variable);
```

### ViewHelper

Use the `symfony:dump` ViewHelper in your Fluid templates:

```html
<html xmlns:symfony="http://typo3.org/ns/KonradMichalik/Typo3DumpServer/ViewHelpers">

<symfony:dump>{variable}</symfony:dump>
```

## 📚 Documentation

| Page | What's inside |
|------|----------------|
| [Output formats](Documentation/output-formats.md) | `cli`, `html`, and `json` (NDJSON) output, plus the file sink for reading dumps without a running server, so agents can consume them |
| [IDE deep links](Documentation/ide-deep-links.md) | Clickable source links in dump output, built-in IDEs and custom URL patterns |
| [Extension configuration](Documentation/configuration.md) | Suppressing frontend dump output when no server is running |
| [Events](Documentation/events.md) | Handling dumps programmatically via the PSR-14 `DumpEvent` |
| [Development & feature testing](Documentation/DEVELOPMENT.md) | Manual QA checklist for contributors, covering every feature above |

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## 💎 Credits

This project is highly inspired by the [laravel-dump-server](https://github.com/beyondcode/laravel-dump-server) & the symfony [var-dumper](https://github.com/symfony/var-dumper) component itself.

## ⭐ License

This project is licensed
under [GNU General Public License 2.0 (or later)](LICENSE.md).
