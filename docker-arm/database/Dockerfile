FROM --platform=linux/arm64 pgbouncer/pgbouncer:latest

# Switch to root to install files and set permissions
USER root

# Create pgbouncer user if it doesn't exist
RUN id -u pgbouncer &>/dev/null || adduser -D -S -G postgres pgbouncer

# Copy configuration files for ARM dev server
COPY ./docker-arm/database/pgbouncer.ini /etc/pgbouncer/pgbouncer.ini
COPY ./docker-arm/database/userlist.txt /etc/pgbouncer/userlist.txt

# Set proper permissions
RUN chown -R postgres:postgres /etc/pgbouncer \
    && chmod 600 /etc/pgbouncer/userlist.txt \
    && chmod 644 /etc/pgbouncer/pgbouncer.ini

# Switch back to postgres user
USER postgres

EXPOSE 6432

# Run pgbouncer
ENTRYPOINT ["/opt/pgbouncer/pgbouncer"]
CMD ["/etc/pgbouncer/pgbouncer.ini"]
