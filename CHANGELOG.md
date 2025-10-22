# Changelog

## Unreleased

## v0.5.3

* Add new `dbp:relay:mono:cleanup` for manually cleaning up old payment data.

## v0.5.2

* Fix a case where a payment would fail to complete if a webhook would request
  the completion after the session timeout (30min by default).
* Add a new `dbp:relay:mono:complete-payment` command to complete a payment
  manually, e.g. in case the webhook failed.
* config: bump the default session timeout to 60 minutes, to avoid hitting
  the timeout in valid use cases.

## v0.5.1

* Drop support for PHP 8.1
* Remove legacy/undocumented "/mono/payment" endpoint
* Add support for api-platform 4.1+ and drop support for <3.4

## v0.5.0

* config: Various bundle config cleanups (better defaults, more validation)
* config: No longer reference connector services by class name, but by custom
  connector ID. backend service is referenced via "backend_type", psp
  contract/method is referenced via payment_method.contract/method.
* config: Don't require the payment types/contracts/methods to be named the same
  in the connectors.
* Move bundle translations into a custom "dbp_relay_mono" domain
* config: move "payment_session_timeout" to "session_timeout" for each payment
  type
* limits: correctly limit the number of payments per payment type instead of globally

## v0.4.14

* Fixes for newer phpstan

## v0.4.13

* Drop support for Symfony 5
* Drop support for api-platform 2
* Test with PHP 8.4

## v0.4.12

* Add support for doctrine/dbal v4
* Add support for doctrine/orm v3.2

## v0.4.11

* Improved logging around backend notifications

## v0.4.10

* Port from doctrine annotations to PHP 8 attributes
* Port to PHPUnit 10

## v0.4.9

* Minor psalm fixes

## v0.4.8

* Add support for api-platform 3.2

## v0.4.7

* Add support for Symfony 6

## v0.4.6

* dev: replace abandoned composer-git-hooks with captainhook.
  Run `vendor/bin/captainhook install -f` to replace the old hooks with the new ones
  on an existing checkout.

## v0.4.5

* Symfony upgrade preparations: Remove dependency on injecting ContainerInterface

## v0.4.4

* Drop support for PHP 7.4/8.0

## v0.4.3

* Drop support for PHP 7.3

## v0.4.2

* JSON-LD contexts are no longer embedded in the API responses, they have to be fetched separately.
* The expressions in the bundle config now support the relay extensions
* Make sure that any changes made by the connector bundles are persisted even if they error out/throw

## v0.4.1

* config: return_url_expression, notify_url_expression and psp_return_url_expression now get passed the URL via
  the "url" variable and are "false" by default. The payment object is no longer accessible in the expression.
* Added a health check for parsing all config Symfony expressions
* Some minor documentation improvements

## v0.4.0

* PaymentServiceProviderServiceInterface::complete() lost the pspData parameter, there is no replacement
* PaymentStatus::CANCELLED was removed, use PaymentStatus::FAILED instead
* Removed deprecated constants like PAYMENT_STATUS_PREPARED
* Changed the namespace of various types (Payment, PaymentPersistence, etc.)
* StartPayAction lost the restart parameter, it will be ignored and there is no replacement
* Drop the unused `number_of_uses` column from the main database table
* Port to new api-platform metadata APIs
* Payment.paymentStatus: change from PaymentStatusType to just Text
* Rename /mono/payment endpoint to /mono/payments, the old endpoint still works but is deprecated

## v0.3.6

* Minor logging improvements

## v0.3.5

* Update to api-platform v2.7

## v0.3.4

* Minor translation changes

## v0.3.3

* Don't allow payment restarts if the payment is pending or completed.

## v0.3.2

* All duration based config entries like `payment_session_timeout`, `completed_begin`, `created_begin` and `timeout_before` are now in the ISO 8601 duration format.

## v0.3.1

* config: demo_mode is now per method instead of per type

## v0.3.0

* New PaymentServiceProviderServiceInterface::getPaymentIdForPspData() which each PSP connector needs to implement.
  This moves the last PSP specific logic into the connector.

## v0.2.0

* BackendServiceInterface::updateData() is now only called in the prepared state
* Introduce a new BackendServiceInterface::updateEntity() which is called in all states and can for example
  set the payment translation based on the current locale, independend of the payment state.

## v0.1.9

* Don't require the backend to check for the right payment state when notified
* composer: add pre-commit hooks for linting
* Add a new PaymentStatus enum
* Add locking to the backend notification, so the backend isn't notified for the same payment twice.

## v0.1.8

* logs: More logging and better audit logs
* logs: Always add a relay-mono-payment-id to the audit logs
* docs: document the audit logging channel
* Only clean up the main payment data if the connector cleanup worked
* Fix coverage reporting with newer xdebug

## v0.1.7

* Fix an error during cleanup in case there are payments in the DB that haven't been started
* Disable log masking for the audit log channel

## v0.1.6

* tests: don't fail if symfony/dotenv is installed

## v0.1.0

* Initial release