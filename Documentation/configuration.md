# Extension configuration

By default, a `dump()` call adds output like the following to the frontend if the dump server isn't running:

![Dump output in frontend](./Images/output.jpg)

### `suppressDump`

Suppress this output with the `suppressDump` setting in the extension configuration. If enabled, the output is suppressed and the dump is only sent to the dump server.

You can find the extension settings in the TYPO3 backend under `Admin Tools > Settings > Extension Configuration > typo3_dump_server`.
