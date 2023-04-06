# DbpRelayMonoBundle

[GitHub](https://github.com/digital-blueprint/relay-mono-bundle) |
[Packagist](https://packagist.org/packages/dbp/relay-mono-bundle)

[![Test](https://github.com/digital-blueprint/relay-mono-bundle/actions/workflows/test.yml/badge.svg)](https://github.com/digital-blueprint/relay-mono-bundle/actions/workflows/test.yml)

Electronic Payments Symfony Bundle

## Bundle installation

You can install the bundle directly from [packagist.org](https://packagist.org/packages/dbp/relay-mono-bundle).

```bash
composer require dbp/relay-mono-bundle
```

## Integration into the API Server

* Add the necessary bundles to your `config/bundles.php`:

```php
...
Dbp\Relay\MonoConnectorGenericBundle\DbpRelayMonoConnectorGenericBundle::class => ['all' => true],
Dbp\Relay\CoreBundle\DbpRelayCoreBundle::class => ['all' => true],
];
```

* Run `composer install` to clear caches

## Development & Testing

* Install dependencies: `composer install`
* Run tests: `composer test`
* Run linters: `composer run lint`
* Run cs-fixer: `composer run cs-fix`

## Bundle dependencies

Don't forget you need to pull down your dependencies in your main application if you are installing packages in a bundle.

```bash
# updates and installs dependencies from dbp/relay-your-bundle
composer update dbp/relay-mono-bundle
```

### Database migration

Run this script to migrate the database. Run this script after installation of the bundle and
after every update to adapt the database to the new source code.

```bash
php bin/console doctrine:migrations:migrate --em=dbp_relay_mono_bundle
```

## CLI Commands

```bash
# Send reports to a custom email address foo@bar.com
./bin/console dbp:relay-mono:reporting --email foo@bar.com
```
