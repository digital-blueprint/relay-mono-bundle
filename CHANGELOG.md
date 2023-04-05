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