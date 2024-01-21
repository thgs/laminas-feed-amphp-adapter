## Laminas Feed Amphp HTTP Client adapter

This package provides an adapter to use [Amphp HTTP Client](https://github.com/amphp/http-client) with 
[Laminas Feed](https://github.com/laminas/laminas-feed).

### Usage

There is a convenience static method provided to create and install the adapter

```php
<?php
use \thgs\Adapter\LaminasFeedHttpClient\LaminasFeedAmphpHttpClientAdapter;

LaminasFeedAmphpHttpClientAdapter::installNew($httpClient = null);
```

If an `HttpClient` is not passed to `installNew` a default one will be 
created.

If you prefer to manually install into `Laminas Feed` you may use the constructor. 

```php
<?php
use \thgs\Adapter\LaminasFeedHttpClient\LaminasFeedAmphpHttpClientAdapter;

$adapter = new LaminasFeedAmphpHttpClientAdapter($httpClient = null);
\Laminas\Feed\Reader\Reader::setHttpClient($adapter);
```