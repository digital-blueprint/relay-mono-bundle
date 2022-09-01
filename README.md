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
  payment_types:
    tuition_fee:
      service: 'Dbp\Relay\MonoConnectorCampusonlineBundle\Service\TuitionFeeService'
      auth_required: true
      return_url_override: 'https://www.digital-blueprint.org/'
      return_url_expression: 'payment.returnUrl matches "/^https:\\/\\/www\\.digital\\-blueprint\\.org\\//"'
      psp_return_url_expression: 'pspReturnUrl matches "/^https:\\/\\/0\\.0\\.0\\.0:8001\\//"'
      recipient: 'digital-blueprint.org'
      payment_contracts:
        payunity_flex_studienservice:
          service: 'Dbp\Relay\MonoConnectorPayunityBundle\Service\PayunityFlexService'
          payment_methods:
            - identifier: payunity_creditcard
              name: dbp_relay_mono.credit_card
              image: '/bundles/dbprelaymono/svg/credit-cards.svg'
            - identifier: payunity_applepay
              name: dbp_relay_mono.apple_pay
              image: '/bundles/dbprelaymono/svg/apple-pay.svg'
            - identifier: payunity_googlepay
              name: dbp_relay_mono.google_pay
              image: '/bundles/dbprelaymono/svg/google-pay.svg'
            - identifier: payunity_sofortueberweisung
              name: dbp_relay_mono.sofortueberweisung
              image: '/bundles/dbprelaymono/svg/sofortueberweisung.svg'
      notify_error:
        dsn: '%env(MAILER_DSN)%'
        from: '%env(MAILER_ENVELOPE_SENDER)%'
        to: notify-errors@digital-blueprint.org
        subject: 'Mono notify errors'
        html_template: 'emails/notify-error.html.twig'
        completed_begin: '-1 hour'
      reporting:
        dsn: '%env(MAILER_DSN)%'
        from: '%env(MAILER_ENVELOPE_SENDER)%'
        to: reporting@digital-blueprint.org
        subject: 'Mono reporting'
        html_template: 'emails/reporting.html.twig'
        created_begin: '-1 day'
  cleanup:
    - payment_status: prepared
      timeout_before: '-1 day'
    - payment_status: started
      timeout_before: '-1 day'
    - payment_status: cancelled
      timeout_before: '-1 day'

monolog:
  handlers:
    dbp_relay_mono_audit:
      type: rotating_file
      level: debug
      path: '%kernel.logs_dir%/dbp_relay_mono_audit.log'
      channels: ['dbp_relay_mono_audit']
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
