# DbpRelayMonoBundle

[GitLab](https://gitlab.tugraz.at/dbp/relay/dbp-relay-mono-bundle) |
[Packagist](https://packagist.org/packages/dbp/relay-mono-bundle)

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

## Configuration

For this create `config/packages/dbp_relay_mono.yaml` in the app with the following
content:

```yaml
dbp_relay_mono:
  database_url: '%env(resolve:DATABASE_URL)%'
  payment_session_timeout: 1800
  payment_session_number_of_uses: 3
  payment_types:
    tuition_fee:
      service: 'Dbp\Relay\MonoConnectorCampusonlineBundle\Service\TuitionFeeService'
      auth_required: true
      psp_return_url_expression: 'pspReturnUrl matches "/^https:\\/\\/0\\.0\\.0\\.0:8001\\//"'
      recipient: 'digital-blueprint.org'
      payment_contracts:
        payunity_flex_studienservice:
          service: 'Dbp\Relay\MonoConnectorPayunityBundle\Service\PayunityFlexService'
          payment_methods:
            - identifier: payunity_creditcard
              name: Kreditkarte
              image: '/bundles/dbprelaymonoconnectorpayunity/svg/credit-cards.svg'
            - identifier: payunity_applepay
              name: Apple Pay
              image: '/bundles/dbprelaymonoconnectorpayunity/svg/apple-pay.svg'
            - identifier: payunity_googlepay
              name: Google Pay
              image: '/bundles/dbprelaymonoconnectorpayunity/svg/google-pay.svg'
```

For more info on bundle configuration see [Symfony bundles configuration](https://symfony.com/doc/current/bundles/configuration.html).

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
