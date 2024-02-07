# v0.4.3

* Drop support for PHP 7.3

# v0.4.2

* JSON-LD contexts are no longer embedded in the API responses, they have to be fetched separately.
* The expressions in the bundle config now support the relay extensions
* Make sure that any changes made by the connector bundles are persisted even if they error out/throw

# v0.4.1

* config: return_url_expression, notify_url_expression and psp_return_url_expression now get passed the URL via
  the "url" variable and are "false" by default. The payment object is no longer accessible in the expression.
* Added a health check for parsing all config Symfony expressions
* Some minor documentation improvements

# v0.4.0

* PaymentServiceProviderServiceInterface::complete() lost the pspData parameter, there is no replacement
* PaymentStatus::CANCELLED was removed, use PaymentStatus::FAILED instead
* Removed deprecated constants like PAYMENT_STATUS_PREPARED
* Changed the namespace of various types (Payment, PaymentPersistence, etc.)
* StartPayAction lost the restart parameter, it will be ignored and there is no replacement
* Drop the unused `number_of_uses` column from the main database table
* Port to new api-platform metadata APIs
* Payment.paymentStatus: change from PaymentStatusType to just Text
* Rename /mono/payment endpoint to /mono/payments, the old endpoint still works but is deprecated

# v0.3.6

* Minor logging improvements

# v0.3.5

* Update to api-platform v2.7

# v0.3.4

* Minor translation changes

# v0.3.3

* Don't allow payment restarts if the payment is pending or completed.

# v0.3.2

* All duration based config entries like `payment_session_timeout`, `completed_begin`, `created_begin` and `timeout_before` are now in the ISO 8601 duration format.

# v0.3.1

* config: demo_mode is now per method instead of per type

# v0.3.0

* New PaymentServiceProviderServiceInterface::getPaymentIdForPspData() which each PSP connector needs to implement.
  This moves the last PSP specific logic into the connector.

# v0.2.0

* BackendServiceInterface::updateData() is now only called in the prepared state
* Introduce a new BackendServiceInterface::updateEntity() which is called in all states and can for example
  set the payment translation based on the current locale, independend of the payment state.

# v0.1.9

* Don't require the backend to check for the right payment state when notified
* composer: add pre-commit hooks for linting
* Add a new PaymentStatus enum
* Add locking to the backend notification, so the backend isn't notified for the same payment twice.

# v0.1.8

* logs: More logging and better audit logs
* logs: Always add a relay-mono-payment-id to the audit logs
* docs: document the audit logging channel
* Only clean up the main payment data if the connector cleanup worked
* Fix coverage reporting with newer xdebug

# v0.1.7

* Fix an error during cleanup in case there are payments in the DB that haven't been started
* Disable log masking for the audit log channel

# v0.1.6

* tests: don't fail if symfony/dotenv is installed

# v0.1.0

* Initial release