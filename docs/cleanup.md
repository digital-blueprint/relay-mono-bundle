# Payment Data Cleanup

The payment bundle automatically cleans up old payment records from your
database once per day. This helps maintain database performance and comply with
data retention policies.

Payments are only deleted after successfully cleaning up data from both the
payment backen connector and the payment service provider connector (PSP).

## Configuration

Configure retention periods in `config/packages/dbp_relay_mono.yaml`:

```yaml
dbp_relay_mono:
  cleanup:
    # Default retention for all statuses (optional)
    default_retention_duration: P14D  # 14 days
    # Per-status retention periods
    statuses:
      - payment_status: 'completed'
        retention_duration: P30D  # 30 days
      - payment_status: 'failed'
        retention_duration: P30D  # 30 days
      - payment_status: 'prepared'
        retention_duration: P1D   # 1 day
```

## Manual Cleanup

Run cleanup manually if needed:

```bash
# Execute cleanup now
php bin/console dbp:relay:mono:cleanup

# Preview without deleting
php bin/console dbp:relay:mono:cleanup --dry-run
```

In case a payment connector is removed from the bundle configuration the payment
entries related to that connector can be cleaned up using the `--force-unknown`
option. This will attempt to clean up all payments that cannot be mapped to a
known connector configuration. Use this option with caution, as it may lead to
data inconsistencies if used incorrectly.

```bash
php bin/console dbp:relay:mono:cleanup --force-unknown
```
