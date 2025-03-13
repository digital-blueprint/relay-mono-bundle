# Configuration

Created via `./bin/console config:dump-reference DbpRelayMonoBundle | sed '/^$/d'`

```yaml
# Default configuration for "DbpRelayMonoBundle"
dbp_relay_mono:
  database_url:         '%env(resolve:DATABASE_URL)%' # Required
  # A list of payment type configurations. A payment type is a combination of a payment client configuration and a payment service provider configuration.
  payment_types:
    # Prototype
    identifier:
      # The ID of the payment backend type to use. This references an ID from a mono connector.
      backend_type:         ~ # Required
      # If starting the payment process requires the client to be authenticated
      auth_required:        false
      # Time after which a created or started payment can no longer be continued. In ISO duration format.
      session_timeout:      PT1800S
      # An URL to which to redirect the user to after the process is finished. This overrides any return URL passed by the payment initiator and is not affected by "return_url_expression"
      return_url_override:  null
      # A Symfony expression for validating the return url provided by the initiator. Gets passed an "url" variable. Should return true if the URL is valid.
      return_url_expression: 'false'
      # A Symfony expression for validating the notify url provided by the initiator. Gets passed an "url" variable. Should return true if the URL is valid.
      notify_url_expression: 'false'
      # A Symfony expression for validating the PSP return url provided by the initiator. Gets passed an "url" variable. Should return true if the URL is valid.
      psp_return_url_expression: 'false'
      # The data protection declaration url that will be set on the created payment.
      data_protection_declaration_url: null
      # The name of the payment recipient, if any.
      recipient:            null
      # A list of payment methods that are provided to the user
      payment_methods:
        # Prototype
        identifier:
          # The PSP contract ID to be used. This references an ID from a mono connector.
          contract:             ~ # Required
          # The PSP contract method ID to be used. This references an ID from a mono connector.
          method:               ~ # Required
          # The display name for the payment method as shown to the user. A message ID for a translations string. In case no ID is found the text is used as is.
          name:                 ~ # Required
          # Path to the image - can be an absolute URL, absolute path, or path relative to public directory
          image:                null
          # If enabled the payment client will not be notified when a payment is completed
          demo_mode:            false
      # Various limits for how many payments can be active at the same time
      concurrency_limits:
        # The maximum of globally active payments that are active, i.e. that have not expired and have not been completed.
        max_concurrent_payments: null
        # Same as "max_concurrent_payments" but only counts payments from authenticated users
        max_concurrent_auth_payments: null
        # Same as "max_concurrent_auth_payments" but is a limit per user
        max_concurrent_auth_payments_per_user: null
        # Same as "max_concurrent_payments" but only counts payments from unauthenticated users
        max_concurrent_unauth_payments: null
        # Same as "max_concurrent_unauth_payments" but is limited per user IP address
        max_concurrent_unauth_payments_per_ip: null
      # Configuration for reports about recently completed payments that have not been notified
      notify_error:
        # The mailer transport DSN to use for sending the email
        dsn:                  ~ # Required
        # The sender email address for the reporting emails
        from:                 ~ # Required
        # The recipient email address for the reporting emails
        to:                   ~ # Required
        # The subject line for the reporting emails
        subject:              ~ # Required
        # The Twig template path for the HTML version of the reporting email
        html_template:        emails/reporting.html.twig
        # The report includes all payments that have been completed in the last "completed_begin" interval (e.g., P1D for 1 day) but have not been notified yet
        completed_begin:      P1D
      # Configuration for recurring email reporting about which payments happened recently.
      reporting:
        # The mailer transport DSN to use for sending the email
        dsn:                  ~ # Required
        # The sender email address for the reporting emails
        from:                 ~ # Required
        # The recipient email address for the reporting emails
        to:                   ~ # Required
        # The subject line for the reporting emails
        subject:              ~ # Required
        # The Twig template path for the HTML version of the reporting email
        html_template:        emails/reporting.html.twig
        # The report includes all payments that have been created in the last "created_begin" interval (e.g., P1D for 1 day).
        created_begin:        P1D
  # Configuration for when a payment is pruned from the database. By default none are pruned.
  cleanup:
    # Prototype
    -
      # Payment status for which the provided "timeout_before" is used
      payment_status:       ~ # One of "prepared"; "started"; "pending"; "failed"; "completed", Required
      # Time after the payment has expired (see payment_session_timeout) when the payment will be considered for cleanup. In ISO duration format.
      timeout_before:       ~ # Required
```
