# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2020-04-15

### Added

*None*

### Changed

* `Client\ArtaxClient` renamed to `Client\HttpClient`.
* `Message\CachingRequest` has a new order of constructor arguments: `new CachingRequest('google1', 'https://google.com')` where 1 arg is caching key, 2 is url, 3 is http method (GET by default) and last is Ttl (one hour by default).
* Hash key for caching now has a new generating algo because `Amp\Http\Client\Request` does not support serialization.
* Rewritten tests to use official `amphp/phpunit-util` package instead of `harmonyio/phpunit-extensions`.    

### Deprecated

*None*

### Removed

* `amphp/artax` composer package and it support because is deprecated.
* `Message\Request` class because it non-functional wrapper around `Amp\Http\Client\Request`.
* `harmonyio/phpunit-extensions` composer package. 

### Fixed

* PHP 7.4 compatibility (in tests).

### Security

*None*
