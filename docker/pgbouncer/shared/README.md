# Shared PgBouncer Utilities

This directory contains shared utilities for testing and monitoring PgBouncer across all environments (local, development, staging, production).

## Scripts

### 1. test-tenant-isolation.sh

Tests tenant isolation to ensure search_path is properly reset between transactions, preventing tenant data leakage.

**Purpose:** Critical security test for multi-tenant applications

**Tests Performed:**
1. Verify search_path is reset after transaction
2. Verify DISCARD ALL behavior (prepared statements cleared)
3. Concurrent tenant access isolation (5 simultaneous connections)
4. Verify pool mode is set to 'transaction'
5. Verify server_reset_query is 'DISCARD ALL'

**Usage:**

```bash
# Local environment
docker exec wellmed-pgbouncer-local /etc/pgbouncer/shared/test-tenant-isolation.sh

# Development environment
docker exec wellmed-pgbouncer-dev /etc/pgbouncer/shared/test-tenant-isolation.sh

# Production environment (during deployment validation)
docker exec wellmed-pgbouncer-prod /etc/pgbouncer/shared/test-tenant-isolation.sh

# Custom configuration
PGHOST=localhost PGPORT=6432 PGUSER=postgres PGPASSWORD=yourpassword ./test-tenant-isolation.sh
```

**Expected Output:**
- All tests should show ✓ PASS for production deployment
- Any failures indicate critical security issues that must be resolved

**When to Run:**
- Before deploying to production
- After any pgbouncer configuration changes
- As part of CI/CD pipeline
- During security audits

---

### 2. monitor-pgbouncer.sh

Monitors PgBouncer connection pool status, statistics, and provides alerts based on configurable thresholds.

**Purpose:** Real-time monitoring and alerting for connection pool health

**Metrics Monitored:**
1. Connection pool status (active/waiting clients, active/idle servers)
2. Connection statistics (transactions, queries, data transfer)
3. Active client connections
4. Server connections
5. Pool saturation percentage
6. Wait times
7. Configuration summary

**Alerts:**
- 🔴 CRITICAL: Pool saturation ≥ 85%
- ⚠️  WARNING: Pool saturation ≥ 70%
- ⚠️  WARNING: More than 10 clients waiting
- 🔴 CRITICAL: Max wait time > 5 seconds

**Usage:**

```bash
# Run once
./docker/pgbouncer/shared/monitor-pgbouncer.sh

# Run with custom configuration
PGHOST=localhost PGPORT=6432 PGUSER=postgres PGPASSWORD=yourpassword ./monitor-pgbouncer.sh

# Inside Docker container
docker exec wellmed-pgbouncer-prod /etc/pgbouncer/shared/monitor-pgbouncer.sh

# Continuous monitoring (every 5 minutes)
watch -n 300 ./docker/pgbouncer/shared/monitor-pgbouncer.sh

# Log output to file
./docker/pgbouncer/shared/monitor-pgbouncer.sh >> /var/log/pgbouncer-monitor.log
```

**Automated Monitoring:**

Add to crontab for continuous monitoring:

```bash
# Monitor every 5 minutes and log
*/5 * * * * /var/www/projects/wellmed/docker/pgbouncer/shared/monitor-pgbouncer.sh >> /var/log/pgbouncer-monitor.log 2>&1

# Send alert email if critical
*/5 * * * * /var/www/projects/wellmed/docker/pgbouncer/shared/monitor-pgbouncer.sh | grep -i "CRITICAL" && echo "PgBouncer Critical Alert" | mail -s "PgBouncer Alert" admin@example.com
```

**Integration with Monitoring Systems:**

The script output can be parsed for integration with:
- Prometheus/Grafana
- Datadog
- New Relic
- CloudWatch
- Custom monitoring solutions

---

## Environment Variables

Both scripts support these environment variables for configuration:

| Variable | Default | Description |
|----------|---------|-------------|
| PGHOST | localhost | PgBouncer host |
| PGPORT | 6432 | PgBouncer port |
| PGUSER | postgres | Database user |
| PGPASSWORD | password123 | Database password |
| PGDATABASE | wellmed | Database name (test script only) |

## Docker Integration

To make these scripts available inside Docker containers, mount them as volumes:

```yaml
# docker-compose.yaml example
services:
  wellmed_pgbouncer:
    volumes:
      - ./docker/pgbouncer/local/pgbouncer.ini:/etc/pgbouncer/pgbouncer.ini:ro
      - ./docker/pgbouncer/local/userlist.txt:/etc/pgbouncer/userlist.txt:ro
      - ./docker/pgbouncer/shared:/etc/pgbouncer/shared:ro
```

## Security Considerations

### Credentials
- Never hardcode passwords in scripts
- Use environment variables or secrets management
- Ensure scripts have appropriate file permissions (chmod 700)
- Don't commit passwords to git

### Access Control
- Limit execution to authorized users only
- Use readonly mounts in Docker containers
- Audit script usage in production

### Logging
- Monitor script logs are sanitized (no sensitive data)
- Secure log file permissions
- Rotate logs regularly

## Troubleshooting

### "Connection refused" Error
- Check if PgBouncer is running: `docker ps | grep pgbouncer`
- Verify PGHOST and PGPORT are correct
- Check firewall rules

### "Authentication failed" Error
- Verify PGUSER and PGPASSWORD are correct
- Check userlist.txt contains the user
- Ensure auth_type in pgbouncer.ini matches

### "No such database" Error
- Verify database name is correct
- Check pgbouncer.ini has the database configured
- Ensure PostgreSQL is accessible from PgBouncer

### Tests Failing
- Review pgbouncer.ini configuration
- Check server_reset_query is set to DISCARD ALL
- Verify pool_mode is transaction
- Review PgBouncer logs for errors

## Best Practices

### Testing
1. Run tenant isolation tests after any configuration change
2. Include tests in CI/CD pipeline
3. Test in staging before production deployment
4. Document test results and any failures

### Monitoring
1. Set up automated monitoring with alerts
2. Monitor continuously in production
3. Review metrics weekly
4. Set up dashboards for visualization
5. Keep historical data for trend analysis

### Incident Response
1. If critical alerts occur:
   - Check monitor output for specific issues
   - Review PgBouncer logs
   - Check PostgreSQL performance
   - Consider temporarily increasing pool sizes
   - Investigate application for connection leaks

2. If isolation tests fail:
   - DO NOT deploy to production
   - Review configuration immediately
   - This indicates a security vulnerability
   - Contact security team

## Additional Resources

- PgBouncer Documentation: https://www.pgbouncer.org/
- PostgreSQL Documentation: https://www.postgresql.org/docs/
- Multi-tenant Best Practices: See project documentation

## Support

For issues with these scripts:
1. Check script output for specific error messages
2. Review this README
3. Check PgBouncer logs
4. Review production deployment documentation
5. Contact DevOps team
