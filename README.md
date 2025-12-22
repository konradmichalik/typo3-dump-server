<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `typo3_dump_server`

[![Latest Stable Version](https://typo3-badges.dev/badge/typo3_dump_server/version/shields.svg)](https://extensions.typo3.org/extension/typo3_dump_server)
[![Supported TYPO3 versions](https://typo3-badges.dev/badge/typo3_dump_server/typo3/shields.svg)](https://extensions.typo3.org/extension/typo3_dump_server)
[![Coverage](https://img.shields.io/coverallsCoverage/github/konradmichalik/typo3-dump-server?logo=coveralls)](https://coveralls.io/github/konradmichalik/typo3-dump-server)
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

## 📊 Usage

![Screencast](./Documentation/Images/screencast.gif)

### Console command

Start the dump server with the following command:

```bash
vendor/bin/typo3 server:dump
```

![Console Command](./Documentation/Images/screenshot-command.png)

Use the format option to change the output format to `html`:

```bash
vendor/bin/typo3 server:dump --format=html > dump.html
```

> [!NOTE]  
> The dump server will be available at `tcp://127.0.0.1:9912` by default. Use the environment variable `TYPO3_DUMP_SERVER_HOST` to change the host.

### Dump

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

### Extension settings

By default, a `dump()` call will add something like the following output to the frontend if the dump server isn't running:

![Dump output in frontend](./Documentation/Images/output.jpg)

You can suppress this output with the `suppressDump` setting in the extension configuration. If this setting is enabled, the output will be suppressed and the dump will only be sent to the dump server.

You can find the extension settings in the TYPO3 backend under `Admin Tools > Settings > Extension Configuration > typo3_dump_server`.

### Programmatic Handling

You can listen to dump events programmatically using TYPO3's PSR-14 event system:

```php
use KonradMichalik\Typo3DumpServer\Event\DumpEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener]
final class MyDumpEventListener
{
    public function __invoke(DumpEvent $event): void
    {
        $value = $event->getValue();
        $type = $event->getType();
        
        // Your custom logic here
        error_log("Dumped {$type}: " . print_r($value, true));
    }
}
```

> [!NOTE]
> Register your event listener via the `AsEventListener` attribute (TYPO3 >= 13) or in your service configuration (see [docs](https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/HowTo/Events/Index.html#extension-development-event-listener)).

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## 💛 Acknowledgements

This project is highly inspired by the [laravel-dump-server](https://github.com/beyondcode/laravel-dump-server) & the symfony [var-dumper](https://github.com/symfony/var-dumper) component itself.

## ⭐ License

This project is licensed
under [GNU General Public License 2.0 (or later)](LICENSE.md).
