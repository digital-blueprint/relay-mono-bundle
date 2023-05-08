# Miscellaneous

## Database Migration

Run this script to migrate the database. Run this script after installation of the bundle and
after every update to adapt the database to the new source code.

```bash
php bin/console doctrine:migrations:migrate --em=dbp_relay_mono_bundle
```

## CLI Commands

```bash
# Send reports to a custom email address foo@bar.com
./bin/console dbp:relay-mono:reporting --email foo@bar.com
```
