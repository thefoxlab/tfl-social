# tfl-social
A provider-based PHP library for aggregating Facebook and Instagram feeds into a unified, normalized API for websites and applications.

## Core API

```php
$social = service('tflSocial');

$social->feed();
$social->connect();
$social->sync();
$social->providers();
```

The current package exposes the core typed architecture only. Provider integrations, OAuth, HTTP clients, database persistence, and widgets are intentionally outside this foundation layer.
