# IDE deep links

Click on source file paths in the dump output to open them directly in your IDE. Configure the IDE via environment variable:

```bash
export TYPO3_DUMP_SERVER_IDE=phpstorm
```

Supported IDEs: `phpstorm`, `vscode`, `sublime`, `textmate`, `atom`

You can also use a custom URL pattern with `%file%` and `%line%` placeholders:

```bash
export TYPO3_DUMP_SERVER_IDE="myide://open?file=%file%&line=%line%"
```
