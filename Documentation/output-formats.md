# Output formats

The dump server supports three output formats, selected via `--format`.

## `cli` (default)

Formatted tables in the terminal:

```bash
vendor/bin/typo3 server:dump
```

## `html`

A styled HTML page. Redirect it to a file to view in a browser:

```bash
vendor/bin/typo3 server:dump --format=html > dump.html
```

## `json`

One JSON object per line (NDJSON) instead of formatted tables. Redirect it to a file:

```bash
vendor/bin/typo3 server:dump --format=json > dump.ndjson
```

> [!NOTE]
> The `json` format, together with the file sink below, is what makes dumps consumable by AI coding agents instead of only by humans reading a terminal.

## File sink without a running server

Set `TYPO3_DUMP_SERVER_SINK` to a file path and dumps are appended there directly, so an agent can trigger a request and read the file afterwards instead of managing a background server process:

```bash
export TYPO3_DUMP_SERVER_SINK=/tmp/dumps.ndjson
```

> [!WARNING]
> The file sink only activates in the `Development` application context and is a no-op otherwise. Dumps often contain credentials, session data, or personal data. Treat the sink file like any other debug output: keep it outside the webroot, add it to `.gitignore`, and never enable it in `Production`.

## Payload schema

Both the `json` format and the file sink emit the same schema, one line per dump (pretty-printed here for readability):

```json
{
  "timestamp": "2026-09-18T10:12:33+02:00",
  "clientId": 1,
  "type": "array",
  "value": {
    "uid": 42,
    "title": "Foo"
  },
  "source": {
    "name": "MyController.php",
    "file": "/path/MyController.php",
    "line": 42
  },
  "request": null,
  "cli": null,
  "typo3": {
    "version": "13.4.2",
    "context": "Development"
  }
}
```

`value` is a JSON-encodable rendering of the dumped variable: object property keys are cleaned of internal visibility markers, strings and array sizes are capped, and structures deeper than the configured limit are replaced with a `*MAX_DEPTH:<type>*` marker instead of being cut off silently.
