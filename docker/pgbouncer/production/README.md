# Production PgBouncer Configuration for AWS RDS

## Prerequisites

1. AWS RDS PostgreSQL instance running
2. RDS endpoint accessible from your application servers
3. RDS security group allows connections on port 5432
4. SSL/TLS enabled in RDS parameter group

## Setup Instructions

### 1. Download AWS RDS CA Bundle

The RDS CA bundle is required for SSL/TLS connections to AWS RDS.

```bash
# Download AWS RDS global CA bundle
curl -o docker/pgbouncer/production/rds-ca-bundle.pem \
  https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem

# Verify the download
ls -lh docker/pgbouncer/production/rds-ca-bundle.pem
```

**Alternative:** Download region-specific CA certificate:
```bash
# For us-east-1
curl -o docker/pgbouncer/production/rds-ca-bundle.pem \
  https://truststore.pki.rds.amazonaws.com/us-east-1/us-east-1-bundle.pem
```

### 2. Configure RDS Endpoint

Update `pgbouncer.ini` and `.env.pgbouncer` with your actual RDS endpoints:

1. Get your RDS endpoint from AWS Console or CLI:
   ```bash
   aws rds describe-db-instances \
     --db-instance-identifier wellmed-prod \
     --query 'DBInstances[0].Endpoint.Address' \
     --output text
   ```

2. Replace `wellmed-prod.xxxxx.us-east-1.rds.amazonaws.com` in:
   - `pgbouncer.ini` (lines for `wellmed` database and `*` wildcard)
   - `.env.pgbouncer` (DB_HOST, RDS_ENDPOINT)

### 3. Create User Credentials

1. Copy the example file:
   ```bash
   cp userlist.txt.example userlist.txt
   ```

2. Generate MD5 hash for your database user:
   ```bash
   # For user "postgres" with password "YourSecurePassword"
   echo -n "YourSecurePasswordpostgres" | md5sum
   ```

3. Edit `userlist.txt` and add:
   ```
   "postgres" "md5<hash_from_step_2>"
   ```

4. Set proper permissions:
   ```bash
   chmod 600 docker/pgbouncer/production/userlist.txt
   ```

### 4. Verify RDS SSL Configuration

Ensure SSL is enabled in your RDS parameter group:

```bash
aws rds describe-db-parameters \
  --db-parameter-group-name your-parameter-group \
  --query 'Parameters[?ParameterName==`rds.force_ssl`]'
```

If `rds.force_ssl` is not set to `1`, enable it:

```bash
aws rds modify-db-parameter-group \
  --db-parameter-group-name your-parameter-group \
  --parameters "ParameterName=rds.force_ssl,ParameterValue=1,ApplyMethod=immediate"
```

### 5. Test Connection

Before deploying, test the connection to RDS:

```bash
# Test direct connection to RDS with SSL
psql "host=wellmed-prod.xxxxx.us-east-1.rds.amazonaws.com \
      port=5432 \
      dbname=wellmed \
      user=postgres \
      sslmode=require"
```

### 6. Deploy PgBouncer

```bash
# Build and start pgbouncer container
docker-compose -f docker-compose-prod.yaml up -d wellmed_pgbouncer_prod

# Check logs
docker logs wellmed-pgbouncer-prod

# Verify pgbouncer is connected to RDS
docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"
```

## Security Best Practices

### Use Docker Secrets (Recommended)

Instead of using `userlist.txt` directly, use Docker secrets:

1. Create secret:
   ```bash
   docker secret create pgbouncer_userlist docker/pgbouncer/production/userlist.txt
   ```

2. Update `docker-compose-prod.yaml` to use secret (already configured)

3. Remove plain text `userlist.txt` from server

### Use AWS Secrets Manager (Advanced)

For enhanced security, store credentials in AWS Secrets Manager:

1. Create secret in AWS Secrets Manager
2. Use AWS SDK or CLI to retrieve at runtime
3. Update `userlist.txt` dynamically on container start

## Monitoring

### Connection Pool Status

```bash
# View current pool status
docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"

# View statistics
docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW STATS;"

# View active clients
docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW CLIENTS;"
```

### Automated Monitoring

Use the monitoring script:

```bash
./docker/pgbouncer/shared/monitor-pgbouncer.sh
```

Set up cron job for continuous monitoring:

```bash
# Add to crontab
*/5 * * * * /var/www/projects/wellmed/docker/pgbouncer/shared/monitor-pgbouncer.sh >> /var/log/pgbouncer-monitor.log
```

## Troubleshooting

### SSL Certificate Issues

If you see SSL certificate errors:

1. Verify CA bundle is downloaded:
   ```bash
   ls -lh docker/pgbouncer/production/rds-ca-bundle.pem
   ```

2. Check certificate validity:
   ```bash
   openssl x509 -in docker/pgbouncer/production/rds-ca-bundle.pem -text -noout
   ```

3. Update to latest CA bundle if expired

### Connection Timeout Issues

If connections timeout:

1. Check RDS security group allows inbound on port 5432
2. Verify network connectivity from application server to RDS
3. Check RDS parameter group settings
4. Review pgbouncer logs for specific errors

### High Connection Usage

If connection pool is saturated:

1. Monitor pool usage:
   ```bash
   docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"
   ```

2. Increase pool sizes in `pgbouncer.ini` if needed
3. Check for connection leaks in application code
4. Consider scaling RDS instance or adding read replicas

## Rollback Plan

If issues occur, immediately rollback by bypassing pgbouncer:

1. Update `.env` file:
   ```bash
   DB_HOST=wellmed-prod.xxxxx.us-east-1.rds.amazonaws.com
   DB_PORT=5432
   ```

2. Restart application:
   ```bash
   docker-compose -f docker-compose-prod.yaml restart wellmed wellmed_listener wellmed_hq
   ```

## Performance Tuning

### RDS Connection Limits

Check your RDS instance's max_connections:

```sql
SHOW max_connections;
```

Ensure `max_db_connections` in pgbouncer.ini is less than RDS max_connections.

### Optimize for Your Workload

Adjust these settings based on monitoring:

- `default_pool_size`: Increase if you see high wait times
- `reserve_pool_size`: Increase if you see connection spikes
- `query_timeout`: Adjust based on your longest queries
- `server_lifetime`: Lower if you see connection state issues

## Certificate Rotation

AWS rotates RDS certificates periodically. To update:

1. Download new CA bundle:
   ```bash
   curl -o docker/pgbouncer/production/rds-ca-bundle.pem \
     https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem
   ```

2. Restart pgbouncer:
   ```bash
   docker-compose -f docker-compose-prod.yaml restart wellmed_pgbouncer_prod
   ```

3. Monitor for any connection issues

## Support

For issues or questions:
- Check pgbouncer logs: `docker logs wellmed-pgbouncer-prod`
- Review AWS RDS documentation
- Check pgbouncer documentation: https://www.pgbouncer.org/
