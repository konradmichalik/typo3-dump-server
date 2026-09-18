# Events

You can listen to dump events programmatically using TYPO3's PSR-14 event system:

```php
use KonradMichalik\Typo3DumpServer\Event\DumpEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener]
final class MyDumpEventListener
{
    public function __invoke(DumpEvent $event): void
    {
        $type = $event->getType();

        // $event->getValue() returns the original dumped value, which can contain
        // credentials, session data, or personal data. Log only $type or explicitly
        // redacted metadata, never the raw value.
        error_log("Dumped value of type: {$type}");
    }
}
```

> [!NOTE]
> Register your event listener via the `AsEventListener` attribute (TYPO3 >= 13) or in your service configuration (see [docs](https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/HowTo/Events/Index.html#extension-development-event-listener)).
