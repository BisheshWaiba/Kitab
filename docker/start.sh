#!/bin/bash
# Starts KITAB inside the container.
#
# With DB_HOST set (a hosted database, see DEPLOY.md) it only starts Apache.
#
# With no DB_HOST it runs in DEMO MODE: a small private MariaDB is started inside this container and filled from
# /opt/kitab/seed.sql (the store's books and categories, plus one admin account). The site works with no signup at all,
# but everything visitors add (accounts, orders, pictures) is forgotten when the container restarts or scales to zero.

if [ -z "${DB_HOST:-}" ]; then
    echo "KITAB: no DB_HOST set, starting the demo database inside the container" >&2

    export DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=kitab DB_NAME=kitab
    export DB_PASS="$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 24)"
    DATADIR=/var/lib/kitab-db

    start_demo_db() {
        mkdir -p "$DATADIR" /run/mysqld || return 1
        chown -R mysql:mysql "$DATADIR" /run/mysqld || return 1
        mariadb-install-db --user=mysql --datadir="$DATADIR" --skip-test-db >/tmp/mariadb-install.log 2>&1 || { cat /tmp/mariadb-install.log >&2; return 1; }

        # Only this container can reach it (127.0.0.1), and the password is made fresh on every start
        mariadbd --user=mysql --datadir="$DATADIR" --bind-address=127.0.0.1 --port=3306 --skip-name-resolve \
            --innodb-buffer-pool-size=32M --max-connections=80 --max-allowed-packet=16M >/tmp/mariadb.log 2>&1 &

        for _ in $(seq 1 120); do
            mariadb-admin --silent ping >/dev/null 2>&1 && break
            sleep 0.5
        done
        mariadb-admin --silent ping >/dev/null 2>&1 || { cat /tmp/mariadb.log >&2; return 1; }

        mariadb -e "CREATE DATABASE kitab CHARACTER SET utf8mb4; CREATE USER 'kitab'@'127.0.0.1' IDENTIFIED BY '$DB_PASS'; GRANT ALL ON kitab.* TO 'kitab'@'127.0.0.1'; FLUSH PRIVILEGES;" || return 1
        mariadb kitab < /opt/kitab/seed.sql || return 1
        echo "KITAB: demo database ready" >&2
    }

    # If it fails, Apache still starts: the site then shows its "temporarily unavailable" page and the reason is in the logs
    start_demo_db || echo "KITAB: the demo database could not be started" >&2
fi

exec apache2-foreground
