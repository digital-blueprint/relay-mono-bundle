# Logging

The "mono" bundle provides a separate logging channel for audit logs called
`dbp_relay_mono_audit`. Compared to the default logging channel it contains the
following information:

* Logs for every action taken by the client in regards to the payment
* Communication with every external service provider regarding the payment
* Personally identifiable information of the paying users and all metadata
  around the payment process, with the exception of payment card numbers, bank
  account numbers and other bank account related information.
* Every log message contains a `relay-mono-payment-id` field, which ideally
  connects the message to both the database and the metadata stored by the
  payment service provider.

The goal of the logging channel is to provide a detailed trail of all payment
processes.

Due to the different nature and sensitivity of data logged in this channel you
will probably want to handle it differently on the application level, in regards
to where it is logged to, which log levels are forwarded, who can access it, and
for how long it will be stored. The monolog config allows you to specify
different log handlers for different logging channels. In the following example
we log the channel to a separate file and exclude it from the general log
handler:

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        file-log:
            type: rotating_file
            level: notice
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            max_files: 10
            channels: ['!dbp_relay_mono_audit']
        dbp_relay_mono_audit:
            type: rotating_file
            level: debug
            date_format: 'Y-m'
            path: '%kernel.logs_dir%/dbp_relay_mono_audit-%kernel.environment%.log'
            channels: ['dbp_relay_mono_audit']
```

See the [Symfony documentation](https://symfony.com/doc/current/logging.html#handlers-writing-logs-to-different-locations) for more details.
