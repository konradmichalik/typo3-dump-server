# Security Policy

> [!CAUTION]
> **Please avoid using GitHub issues to report security vulnerabilities.**

If you have found a potential security flaw, please reach out directly to the [TYPO3 Security Team](https://typo3.community/contribute/teams-committees/security). For additional information and guidelines, you can also check out [TYPO3's Security Policy](https://github.com/TYPO3/typo3/blob/main/SECURITY.md).

## Security Model

This extension is a **development tool**. The underlying Symfony Var Dump Server protocol is unauthenticated and unencrypted:

- Any local process can connect to the dump server socket and send payloads, or bind the port first and receive all dump output. Dumps frequently contain credentials, session data, or personal data — on shared or multi-user hosts, treat the socket as readable by every local user.
- Keep `TYPO3_DUMP_SERVER_HOST` on a loopback address (default: `tcp://127.0.0.1:9912`). Never bind the server to `0.0.0.0` or a public interface.
- Install the extension with `composer require --dev` so it never ships to production. If it does end up on a production system, enable the `suppressDump` extension setting to prevent dump output from leaking into HTTP responses while the server is not running.
