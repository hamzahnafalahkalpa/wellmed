# PgBouncer Environment Separation Implementation Guide

## Implementation Summary

This document describes the completed implementation of environment-specific PgBouncer configurations for the WellMed multi-tenant application.

**Implementation Date:** 2026-01-29
**Status:** ✅ COMPLETE

---

## What Was Implemented

### 1. Environment-Specific Configuration Structure

Created a new directory structure for environment-specific PgBouncer configurations:

```
docker/pgbouncer/
├── local/                          # Local development (host PostgreSQL)
│   ├── pgbouncer.ini              # Pool: 100 max clients, 10 default pool size
│   ├── userlist.txt               # User credentials
│   └── .env.pgbouncer             # Environment variables
├── development/                    # Containerized full stack
│   ├── pgbouncer.ini              # Pool: 500 max clients, 20 default pool size
│   ├── userlist.txt               # User credentials
│   └── .env.pgbouncer             # Environment variables
├── staging/                        # Production-like testing
│   ├── pgbouncer.ini              # Pool: 1000 max clients, 30 default pool size
│   ├── userlist.txt.example       # Template (actual file not committed)
│   └── .env.pgbouncer             # Environment variables
├── production/                     # AWS RDS with SSL/TLS
│   ├── pgbouncer.ini              # Pool: 2000 max clients, 40 default pool size
│   ├── userlist.txt.example       # Template (actual file not committed)
│   ├── .env.pgbouncer             # Environment variables
│   ├── rds-ca-bundle.pem          # AWS RDS SSL certificate (to be downloaded)
│   └── README.md                  # Production-specific documentation
├── shared/                         # Shared utilities across all environments
│   ├── test-tenant-isolation.sh   # Security test script
│   ├── monitor-pgbouncer.sh       # Monitoring script
│   └── README.md                  # Utilities documentation
├── pgbouncer.ini.backup           # Backup of original config
└── userlist.txt.backup            # Backup of original credentials
```

### 2. Key Configuration Changes

#### Local Environment (docker/pgbouncer/local/)
- **Target:** Single developer with host PostgreSQL (port 5433)
- **Connection Limits:** 100 max clients, 10 default pool, 2 min pool
- **Timeouts:** Unlimited query timeout for debugging
- **Logging:** Verbose (verbose=1)
- **SSL:** Disabled

#### Development Environment (docker/pgbouncer/development/)
- **Target:** Team development with containerized PostgreSQL
- **Connection Limits:** 500 max clients, 20 default pool, 5 min pool
- **Timeouts:** 120s query timeout
- **Logging:** Standard (verbose=0)
- **SSL:** Disabled (internal Docker network)

#### Staging Environment (docker/pgbouncer/staging/)
- **Target:** Production-like testing with external managed PostgreSQL
- **Connection Limits:** 1000 max clients, 30 default pool, 10 min pool
- **Timeouts:** 90s query timeout
- **Logging:** Standard with connection tracking
- **SSL:** Prefer

#### Production Environment (docker/pgbouncer/production/)
- **Target:** AWS RDS PostgreSQL (50-500 tenants)
- **Connection Limits:** 2000 max clients, 40 default pool, 15 min pool, 20 reserve pool
- **Timeouts:** 60s query timeout, 1800s server lifetime
- **Logging:** Minimal (syslog=1)
- **SSL:** Required with AWS RDS CA bundle
- **DNS Caching:** 60s TTL for RDS failover support
- **TCP Keepalive:** Enabled for cloud connections

### 3. Docker Compose Updates

Updated all docker-compose files to use volume-based configuration:

- **docker-compose-local.yaml:** Updated to mount local/ configs, added healthcheck
- **docker-compose-dev.yaml:** Updated to mount development/ configs, added healthcheck
- **docker-compose-staging.yaml:** Added pgbouncer service with Docker secrets support
- **docker-compose-prod.yaml:** Added pgbouncer service with AWS RDS SSL and Docker secrets

### 4. Security Enhancements

- **Transaction Pooling:** All environments use `pool_mode = transaction` for tenant isolation
- **Search Path Reset:** `server_reset_query = DISCARD ALL` prevents tenant data leakage
- **SSL/TLS:** Required in production with AWS RDS CA certificate validation
- **Secrets Management:** Production and staging use Docker secrets instead of plain text
- **Credential Protection:** userlist.txt files excluded from git (use .example templates)

### 5. Testing & Monitoring Scripts

Created two essential scripts in `docker/pgbouncer/shared/`:

**test-tenant-isolation.sh:**
- Tests search_path isolation between transactions
- Verifies DISCARD ALL behavior
- Tests concurrent tenant access
- Validates pool mode and reset query configuration
- **Usage:** Run after any configuration changes or before production deployment

**monitor-pgbouncer.sh:**
- Real-time connection pool monitoring
- Statistics on transactions, queries, data transfer
- Alert thresholds (saturation > 85% = CRITICAL, > 70% = WARNING)
- Wait time monitoring
- Configuration summary
- **Usage:** Run manually or via cron for continuous monitoring

---

## Configuration Comparison

| Setting | Local | Development | Staging | Production |
|---------|-------|-------------|---------|------------|
| **Database Host** | host.docker.internal:5433 | wellmed_postgres:5432 | staging-db.example.com | AWS RDS endpoint |
| **max_client_conn** | 100 | 500 | 1000 | 2000 |
| **default_pool_size** | 10 | 20 | 30 | 40 |
| **min_pool_size** | 2 | 5 | 10 | 15 |
| **reserve_pool_size** | 5 | 10 | 15 | 20 |
| **max_db_connections** | 50 | 100 | 120 | 150 |
| **query_timeout** | 0 (unlimited) | 120s | 90s | 60s |
| **server_lifetime** | 0 (none) | 3600s | 3600s | 1800s |
| **SSL/TLS** | Disabled | Disabled | Prefer | Required |
| **Logging** | Verbose | Standard | Standard | Minimal |
| **DNS Cache TTL** | 15s | 15s | 30s | 60s |
| **Container Name** | wellmed-pgbouncer-local | wellmed-pgbouncer-dev | wellmed-pgbouncer-staging | wellmed-pgbouncer-prod |

---

## Deployment Instructions

### Local Environment Setup

1. **Start the PgBouncer container:**
   ```bash
   docker-compose -f docker-compose-local.yaml up -d wellmed_pgbouncer
   ```

2. **Verify connection:**
   ```bash
   docker logs wellmed-pgbouncer-local
   ```

3. **Test connectivity:**
   ```bash
   docker exec wellmed-pgbouncer-local psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"
   ```

4. **Run tenant isolation tests:**
   ```bash
   docker exec wellmed-pgbouncer-local /etc/pgbouncer/shared/test-tenant-isolation.sh
   ```

### Development Environment Setup

1. **Start the full stack:**
   ```bash
   docker-compose -f docker-compose-dev.yaml up -d
   ```

2. **Verify PgBouncer is connected to PostgreSQL:**
   ```bash
   docker exec wellmed-pgbouncer-dev psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"
   ```

3. **Test application connectivity:**
   ```bash
   docker exec wellmed-backbone php artisan tinker
   >>> DB::connection('central')->select('SELECT 1 as test');
   >>> exit
   ```

4. **Run isolation tests:**
   ```bash
   docker exec wellmed-pgbouncer-dev /etc/pgbouncer/shared/test-tenant-isolation.sh
   ```

### Staging Environment Setup

1. **Update configuration with actual database host:**
   ```bash
   # Edit docker/pgbouncer/staging/pgbouncer.ini
   # Replace: staging-db.example.com with actual staging database hostname
   ```

2. **Create userlist.txt from template:**
   ```bash
   cp docker/pgbouncer/staging/userlist.txt.example docker/pgbouncer/staging/userlist.txt
   chmod 600 docker/pgbouncer/staging/userlist.txt
   # Edit and add actual credentials
   ```

3. **Deploy:**
   ```bash
   docker-compose -f docker-compose-staging.yaml up -d wellmed_pgbouncer_staging
   ```

4. **Verify and test:**
   ```bash
   docker logs wellmed-pgbouncer-staging
   docker exec wellmed-pgbouncer-staging /etc/pgbouncer/shared/test-tenant-isolation.sh
   ```

### Production Environment Setup

**⚠️ CRITICAL: Follow these steps carefully for production deployment**

#### Step 1: Download AWS RDS CA Certificate

```bash
# Download AWS RDS global CA bundle
curl -o docker/pgbouncer/production/rds-ca-bundle.pem \
  https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem

# Verify download
ls -lh docker/pgbouncer/production/rds-ca-bundle.pem
```

#### Step 2: Update RDS Endpoints

1. Get your RDS endpoint:
   ```bash
   aws rds describe-db-instances \
     --db-instance-identifier wellmed-prod \
     --query 'DBInstances[0].Endpoint.Address' \
     --output text
   ```

2. Update configuration files:
   - Edit `docker/pgbouncer/production/pgbouncer.ini`
   - Replace `wellmed-prod.xxxxx.us-east-1.rds.amazonaws.com` with actual endpoint
   - Update both `wellmed` database and `*` wildcard entries

3. Update environment file:
   - Edit `docker/pgbouncer/production/.env.pgbouncer`
   - Update DB_HOST and RDS_ENDPOINT with actual values

#### Step 3: Create Production Credentials

1. Copy template:
   ```bash
   cp docker/pgbouncer/production/userlist.txt.example docker/pgbouncer/production/userlist.txt
   chmod 600 docker/pgbouncer/production/userlist.txt
   ```

2. Generate MD5 hash for your database user:
   ```bash
   # For user "postgres" with password "YourSecurePassword"
   echo -n "YourSecurePasswordpostgres" | md5sum
   ```

3. Edit `docker/pgbouncer/production/userlist.txt`:
   ```
   "postgres" "md5<hash_from_step_2>"
   ```

#### Step 4: Create Docker Secret

```bash
# Create secret for credentials (recommended for production)
docker secret create pgbouncer_userlist docker/pgbouncer/production/userlist.txt

# Remove plain text file from server (after secret is created)
# Keep it in secure backup location only
```

#### Step 5: Verify RDS SSL Configuration

```bash
# Ensure SSL is enabled in RDS parameter group
aws rds describe-db-parameters \
  --db-parameter-group-name your-parameter-group \
  --query 'Parameters[?ParameterName==`rds.force_ssl`]'

# If not enabled, enable it:
aws rds modify-db-parameter-group \
  --db-parameter-group-name your-parameter-group \
  --parameters "ParameterName=rds.force_ssl,ParameterValue=1,ApplyMethod=immediate"
```

#### Step 6: Test Connection to RDS

```bash
# Test direct connection to RDS with SSL (before deploying PgBouncer)
psql "host=wellmed-prod.xxxxx.us-east-1.rds.amazonaws.com \
      port=5432 \
      dbname=wellmed \
      user=postgres \
      sslmode=require"
```

#### Step 7: Deploy PgBouncer

```bash
# Start PgBouncer container
docker-compose -f docker-compose-prod.yaml up -d wellmed_pgbouncer_prod

# Check logs for any errors
docker logs wellmed-pgbouncer-prod

# Verify connection to RDS
docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"
```

#### Step 8: Run Critical Security Tests

```bash
# MUST PASS before routing production traffic
docker exec wellmed-pgbouncer-prod /etc/pgbouncer/shared/test-tenant-isolation.sh

# All tests must show ✓ PASS
# Any failures indicate CRITICAL security issues - DO NOT proceed
```

#### Step 9: Gradual Rollout

**Do NOT switch all traffic at once. Use gradual rollout:**

1. **Initial Test (10% traffic):**
   - Update 1-2 test tenant applications to use pgbouncer
   - Update their .env: `DB_HOST=wellmed-pgbouncer-prod DB_PORT=6432`
   - Monitor for 24 hours

2. **Monitor:**
   ```bash
   # Run monitoring script
   docker exec wellmed-pgbouncer-prod /etc/pgbouncer/shared/monitor-pgbouncer.sh

   # Watch for:
   # - Connection pool saturation
   # - Wait times
   # - Error rates
   # - Application performance
   ```

3. **Expand (50% traffic):**
   - If no issues, route 50% of tenants through pgbouncer
   - Monitor for another 24 hours

4. **Full Rollout (100% traffic):**
   - Update all application .env files:
     ```bash
     DB_HOST=wellmed-pgbouncer-prod
     DB_PORT=6432
     DB_SSLMODE=require
     ```
   - Restart applications:
     ```bash
     docker-compose -f docker-compose-prod.yaml restart wellmed wellmed_listener wellmed_hq
     ```

#### Step 10: Setup Continuous Monitoring

```bash
# Add to crontab
crontab -e

# Add these lines:
# Monitor every 5 minutes
*/5 * * * * /var/www/projects/wellmed/docker/pgbouncer/shared/monitor-pgbouncer.sh >> /var/log/pgbouncer-monitor.log 2>&1

# Alert on critical issues
*/5 * * * * /var/www/projects/wellmed/docker/pgbouncer/shared/monitor-pgbouncer.sh | grep -i "CRITICAL" && echo "PgBouncer Critical Alert" | mail -s "PgBouncer Alert" ops@example.com
```

---

## Rollback Procedures

### Emergency Rollback (Production)

If critical issues occur, immediately bypass PgBouncer:

```bash
# Step 1: Update application .env files
# Change:
DB_HOST=wellmed-pgbouncer-prod
DB_PORT=6432

# To:
DB_HOST=wellmed-prod.xxxxx.us-east-1.rds.amazonaws.com
DB_PORT=5432

# Step 2: Restart applications
docker-compose -f docker-compose-prod.yaml restart wellmed wellmed_listener wellmed_hq

# Step 3: Verify direct RDS connection works
docker exec wellmed-backbone php artisan tinker
>>> DB::connection('central')->select('SELECT 1 as test');
>>> exit

# Step 4: Stop PgBouncer (optional, keeps it available for debugging)
docker-compose -f docker-compose-prod.yaml stop wellmed_pgbouncer_prod

# Step 5: Investigate issue
docker logs wellmed-pgbouncer-prod
```

### Partial Rollback

If only specific tenants have issues:

1. Route affected tenants directly to RDS
2. Keep other tenants on PgBouncer
3. Investigate tenant-specific issues
4. Re-route to PgBouncer after fix

---

## Monitoring & Maintenance

### Daily Monitoring Tasks

1. **Check pool saturation:**
   ```bash
   docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"
   ```

2. **Review logs for errors:**
   ```bash
   docker logs --tail 100 wellmed-pgbouncer-prod | grep -i error
   ```

3. **Run monitoring script:**
   ```bash
   ./docker/pgbouncer/shared/monitor-pgbouncer.sh
   ```

### Weekly Maintenance Tasks

1. **Review performance metrics:**
   - Average transaction time
   - Connection pool utilization trends
   - Query patterns

2. **Check for connection leaks:**
   ```bash
   docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW CLIENTS;"
   ```

3. **Validate tenant isolation:**
   ```bash
   docker exec wellmed-pgbouncer-prod /etc/pgbouncer/shared/test-tenant-isolation.sh
   ```

### Monthly Maintenance Tasks

1. **Review and adjust pool sizes** based on growth
2. **Update AWS RDS CA certificate** if needed
3. **Performance tuning** based on metrics
4. **Audit security settings**

---

## Troubleshooting Guide

### Issue: Connection Refused

**Symptoms:** Application cannot connect to PgBouncer

**Solutions:**
1. Check if PgBouncer is running:
   ```bash
   docker ps | grep pgbouncer
   ```

2. Check PgBouncer logs:
   ```bash
   docker logs wellmed-pgbouncer-prod
   ```

3. Verify port is exposed:
   ```bash
   netstat -tulpn | grep 6432
   ```

4. Test connection:
   ```bash
   psql -h localhost -p 6432 -U postgres -d pgbouncer
   ```

### Issue: SSL Certificate Errors

**Symptoms:** "SSL certificate verification failed" errors

**Solutions:**
1. Verify CA bundle exists:
   ```bash
   docker exec wellmed-pgbouncer-prod ls -la /etc/pgbouncer/rds-ca-bundle.pem
   ```

2. Check certificate validity:
   ```bash
   openssl x509 -in docker/pgbouncer/production/rds-ca-bundle.pem -text -noout
   ```

3. Re-download if expired:
   ```bash
   curl -o docker/pgbouncer/production/rds-ca-bundle.pem \
     https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem
   docker-compose -f docker-compose-prod.yaml restart wellmed_pgbouncer_prod
   ```

### Issue: Pool Saturation

**Symptoms:** Clients waiting, slow response times

**Solutions:**
1. Check current pool usage:
   ```bash
   docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"
   ```

2. Identify long-running queries:
   ```bash
   docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "SHOW SERVERS;"
   ```

3. Temporary increase pool size:
   - Edit `docker/pgbouncer/production/pgbouncer.ini`
   - Increase `default_pool_size` and `reserve_pool_size`
   - Reload configuration:
     ```bash
     docker exec wellmed-pgbouncer-prod psql -h localhost -p 6432 -U postgres -d pgbouncer -c "RELOAD;"
     ```

4. Investigate application for connection leaks

### Issue: Tenant Isolation Test Failures

**Symptoms:** test-tenant-isolation.sh shows FAIL results

**Solutions:**
1. **CRITICAL:** Do not deploy to production
2. Verify pgbouncer.ini settings:
   - `pool_mode = transaction`
   - `server_reset_query = DISCARD ALL`

3. Check PgBouncer version supports DISCARD ALL

4. Review PgBouncer logs for errors

5. Contact security team immediately

---

## Performance Tuning Recommendations

### Based on Monitoring Data

After collecting 1-2 weeks of production metrics, adjust these settings:

1. **If pool saturation > 70% consistently:**
   - Increase `default_pool_size` by 25%
   - Increase `reserve_pool_size` proportionally

2. **If average wait time > 2 seconds:**
   - Increase `max_db_connections`
   - Check for slow queries in PostgreSQL
   - Consider read replicas for read-heavy operations

3. **If connection churn is high:**
   - Increase `server_lifetime` (current: 1800s)
   - Reduce `server_idle_timeout` if connections stay idle

4. **If memory usage is high:**
   - Reduce `max_prepared_statements`
   - Review query patterns for excessive prepared statements

### Scaling Considerations

**For 500-1000 tenants:**
- Increase `max_client_conn` to 3000
- Increase `default_pool_size` to 60
- Consider multiple PgBouncer instances with load balancing

**For 1000+ tenants:**
- Deploy 2-3 PgBouncer instances
- Use HAProxy or similar for load balancing
- Dedicated pools for high-traffic tenants
- Read replicas for reporting workloads

---

## Security Checklist

Before production deployment, verify:

- [ ] SSL/TLS enabled and enforced (`server_tls_sslmode = require`)
- [ ] AWS RDS CA certificate downloaded and mounted
- [ ] `server_reset_query = DISCARD ALL` configured
- [ ] `pool_mode = transaction` set
- [ ] All tenant isolation tests passing
- [ ] userlist.txt has proper permissions (chmod 600)
- [ ] Credentials stored in Docker secrets (not plain text)
- [ ] userlist.txt NOT committed to git
- [ ] RDS security group properly configured
- [ ] RDS force_ssl parameter enabled
- [ ] Connection strings use sslmode=require
- [ ] Monitoring and alerting configured
- [ ] Rollback procedure documented and tested
- [ ] Team trained on emergency procedures

---

## Files Modified/Created

### New Files Created

1. `docker/pgbouncer/local/pgbouncer.ini`
2. `docker/pgbouncer/local/userlist.txt`
3. `docker/pgbouncer/local/.env.pgbouncer`
4. `docker/pgbouncer/development/pgbouncer.ini`
5. `docker/pgbouncer/development/userlist.txt`
6. `docker/pgbouncer/development/.env.pgbouncer`
7. `docker/pgbouncer/staging/pgbouncer.ini`
8. `docker/pgbouncer/staging/userlist.txt.example`
9. `docker/pgbouncer/staging/.env.pgbouncer`
10. `docker/pgbouncer/production/pgbouncer.ini`
11. `docker/pgbouncer/production/userlist.txt.example`
12. `docker/pgbouncer/production/.env.pgbouncer`
13. `docker/pgbouncer/production/README.md`
14. `docker/pgbouncer/shared/test-tenant-isolation.sh`
15. `docker/pgbouncer/shared/monitor-pgbouncer.sh`
16. `docker/pgbouncer/shared/README.md`
17. `PGBOUNCER_IMPLEMENTATION_GUIDE.md` (this file)

### Backup Files Created

1. `docker/pgbouncer/pgbouncer.ini.backup`
2. `docker/pgbouncer/userlist.txt.backup`

### Modified Files

1. `docker-compose-local.yaml` - Updated pgbouncer service with volume mounts
2. `docker-compose-dev.yaml` - Updated pgbouncer service with volume mounts
3. `docker-compose-staging.yaml` - Added pgbouncer service with Docker secrets
4. `docker-compose-prod.yaml` - Added pgbouncer service with AWS RDS SSL

### Files to Create (Production Deployment)

1. `docker/pgbouncer/production/rds-ca-bundle.pem` - Download from AWS
2. `docker/pgbouncer/production/userlist.txt` - Create from .example template
3. `docker/pgbouncer/staging/userlist.txt` - Create from .example template (if using staging)

---

## Next Steps

1. **Test in local environment** to verify configuration works
2. **Deploy to development** and run full test suite
3. **Test in staging** with production-like data and load
4. **Plan production deployment** with stakeholders
5. **Execute gradual production rollout** following the deployment guide
6. **Monitor continuously** for first 2 weeks after production deployment
7. **Tune performance** based on actual production metrics
8. **Document lessons learned** and update this guide

---

## Support & Contact

For issues or questions:
- Review this implementation guide
- Check PgBouncer logs: `docker logs <container_name>`
- Run monitoring script: `./docker/pgbouncer/shared/monitor-pgbouncer.sh`
- Review PgBouncer documentation: https://www.pgbouncer.org/
- Contact DevOps team for production issues

---

## References

- PgBouncer Official Documentation: https://www.pgbouncer.org/
- AWS RDS SSL/TLS: https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.SSL.html
- PostgreSQL Multi-tenancy Best Practices
- Docker Secrets: https://docs.docker.com/engine/swarm/secrets/

---

**Document Version:** 1.0
**Last Updated:** 2026-01-29
**Maintained By:** DevOps Team
