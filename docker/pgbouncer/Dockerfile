FROM edoburu/pgbouncer:latest

# Switch to root to install files and set permissions
USER root

# Create pgbouncer user if it doesn't exist (some base images don't have it)
RUN id -u pgbouncer &>/dev/null || adduser -D -S -G postgres pgbouncer

# Copy configuration files
COPY ./docker/pgbouncer/pgbouncer.ini /etc/pgbouncer/pgbouncer.ini
COPY ./docker/pgbouncer/userlist.txt /etc/pgbouncer/userlist.txt

# Set proper permissions
RUN chown -R postgres:postgres /etc/pgbouncer \
    && chmod 600 /etc/pgbouncer/userlist.txt \
    && chmod 644 /etc/pgbouncer/pgbouncer.ini

# Switch back to postgres user (pgbouncer runs as postgres in the official image)
USER postgres

EXPOSE 6432

CMD ["/usr/bin/pgbouncer", "/etc/pgbouncer/pgbouncer.ini"]
