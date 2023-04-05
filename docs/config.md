# Configuration

Created via `./bin/console config:dump-reference DbpRelayMonoBundle | sed '/^$/d'`

```yaml
# Default configuration for "DbpRelayMonoBundle"
dbp_relay_mono:
  database_url:         '%env(resolve:DATABASE_URL)%' # Required
  payment_session_timeout: 1800 # Required
  payment_types:        # Required
    # Prototype
    -
      service:              ~ # Required
      auth_required:        false
      max_concurrent_payments: -1
      max_concurrent_auth_payments: -1
      max_concurrent_auth_payments_per_user: -1
      max_concurrent_unauth_payments: -1
      max_concurrent_unauth_payments_per_ip: -1
      return_url_override:  ''
      return_url_expression: ''
      notify_url_expression: ''
      psp_return_url_expression: ''
      data_protection_declaration_url: ~
      recipient:            ~
      payment_contracts:    # Required
        # Prototype
        -
          service:              ~ # Required
          conditions:           ~
          payment_methods:      # Required
            # Prototype
            -
              identifier:           ~
              name:                 ~
              image:                ~
              # If enabled the payment client will not be notified when a payment is completed
              demo_mode:            false
      notify_error:
        dsn:                  ~
        from:                 ~
        to:                   ~
        subject:              ~
        html_template:        emails/reporting.html.twig
        completed_begin:      '-1 hour'
      reporting:
        dsn:                  ~
        from:                 ~
        to:                   ~
        subject:              ~
        html_template:        emails/reporting.html.twig
        created_begin:        '-1 day'
  cleanup:              # Required
    # Prototype
    -
      payment_status:       ~ # Required
      timeout_before:       ~ # Required
```

* `database_url` - A DSN for a database. The database is used to store
  information regarding active payment processes.
* `payment_session_timeout` - Time in seconds after which a created payment can
  no longer be continued.
* `payment_types` - A list of payment type configurations. A payment type is a
  combination of a payment client configuration and a payment service provider
  configuration.
    * `service` - The payment client service class
    * `auth_required` - If starting the payment process requires the client to be authenticated
    * `return_url_override` - An URL to which to redirect the user to after the
      process is finished. This overrides any return URL passed by the payment
      initiator.
    * `return_url_expression` - A Symfony expression for validating the return
      url provided by the initiator. Should return true if the URL is valid. If
      not given then all URLs are allowed.
    * `notify_url_expression` - ???
    * `psp_return_url_expression` - ???
    * `data_protection_declaration_url` - ???
    * `recipient` - The name of the payment recipient. Will be shown to the user.
    * `payment_contracts` - ???
        * `service` - The service provider service class
        * `conditions` - ???
        * `payment_methods` - ???
            * `identifier` - ???
            * `name` - ???
            * `image` - ???
