# Development & Feature Testing

Quick guide to test all extension features in the DDEV environment.

## Setup

```bash
ddev start
ddev composer install
ddev install 13
```

## 1. CLI Dump Server

Start the dump server in the terminal:

```bash
ddev ssh
cd .Build/13
vendor/bin/typo3 server:dump
```

Open the TYPO3 frontend in a second terminal to trigger a dump via the DemoMiddleware:

```bash
ddev launch
```

Expected output in the dump server terminal:

- **date** — timestamp
- **source** — file and line of the `dump()` call
- **file** — relative file path
- **typo3** — version and application context (e.g. `13.4.x (Development)`)

Stop the server with `Ctrl+C`.

## 2. HTML Format

```bash
ddev ssh
cd .Build/13
vendor/bin/typo3 server:dump --format=html --no-ansi > /var/www/html/.Build/dump.html
```

Trigger a dump by visiting the frontend, then stop the server with `Ctrl+C`.

Open the HTML file in the browser (from the host, not inside `ddev ssh`):

```bash
ddev launch /dump.html
```

Expected: styled HTML page with dump output, TYPO3 badge, and source file info.

> **Note:** Always use `ddev ssh` instead of `ddev 13` for file redirection, otherwise DDEV's command prefix ends up in the HTML file.

## 3. IDE Deep Links

Set the IDE environment variable and start the server:

```bash
ddev ssh
export TYPO3_DUMP_SERVER_IDE=phpstorm
cd .Build/13
vendor/bin/typo3 server:dump
```

Trigger a dump. Expected: source file paths are clickable links with `phpstorm://open?file=...&line=...` in the terminal (requires a terminal with hyperlink support).

For HTML format:

```bash
export TYPO3_DUMP_SERVER_IDE=vscode
vendor/bin/typo3 server:dump --format=html --no-ansi > /var/www/html/.Build/dump.html
```

Expected: source file links in the HTML are `<a href="vscode://file/...">` and open the IDE on click.

Supported values: `phpstorm`, `vscode`, `sublime`, `textmate`, `atom`, or a custom pattern:

```bash
export TYPO3_DUMP_SERVER_IDE="myide://open?file=%file%&line=%line%"
```

## 4. TYPO3 Context Info

The TYPO3 version and application context are automatically included in every dump.

- **CLI format:** shown as `typo3` row in the table (e.g. `13.4.x (Development)`)
- **HTML format:** shown as `TYPO3` badge in the dump header

No configuration needed — the `Typo3ContextProvider` reads `Typo3Version` and `Environment::getContext()` automatically.

## 5. Suppress Dump Output

When the dump server is **not running**, `dump()` renders output in the frontend by default. To suppress this:

1. Go to **Admin Tools > Settings > Extension Configuration > typo3_dump_server**
2. Enable **suppressDump**

Or via CLI:

```bash
ddev 13 typo3 configuration:set EXTENSIONS/typo3_dump_server/suppressDump 1
```

Now `dump()` calls produce no output when the server is offline.

## 6. DumpEvent (PSR-14)

The `DemoListener` in `.ddev/test/packages/sitepackage/Classes/EventListener/DemoListener.php` listens to `DumpEvent`. Uncomment the `DebuggerUtility::var_dump()` line to see it in action:

```php
public function __invoke(DumpEvent $event): void
{
    $value = $event->getValue();
    $type = $event->getType();

    DebuggerUtility::var_dump($value, $type);
}
```

## 7. ViewHelper

In any Fluid template:

```html
<html xmlns:symfony="http://typo3.org/ns/KonradMichalik/Typo3DumpServer/ViewHelpers">

<symfony:dump>{variable}</symfony:dump>
```

The output is sent to the dump server when running, or rendered inline in the frontend.

## 8. Multiple TYPO3 Versions

```bash
ddev install all

# Test with TYPO3 12
ddev ssh
cd .Build/12
vendor/bin/typo3 server:dump

# Test with TYPO3 13
cd .Build/13
vendor/bin/typo3 server:dump
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `TYPO3_DUMP_SERVER_HOST` | `tcp://127.0.0.1:9912` | Dump server address |
| `TYPO3_DUMP_SERVER_IDE` | *(unset)* | IDE for deep links (`phpstorm`, `vscode`, `sublime`, `textmate`, `atom`, or custom pattern) |
