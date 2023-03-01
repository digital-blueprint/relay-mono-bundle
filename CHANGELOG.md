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