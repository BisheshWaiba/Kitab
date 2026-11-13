# KITAB on Vercel (Container preset): Apache + PHP 8.3, the same way the site runs on XAMPP.
#
# Database: with DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME (and DB_SSL=1 if the host wants encryption) set in
# Vercel > Project > Settings > Environment Variables, the site uses that hosted database (see DEPLOY.md).
# With none of them set it starts in DEMO MODE: a private MariaDB inside the container, filled from docker/seed.sql.
FROM php:8.3-apache

# mysqli talks to MySQL. mod_rewrite runs the rules in .htaccess (the 404 page, hidden folders, uploaded pictures).
# mariadb-server is only used in demo mode.
RUN apt-get update \
    && apt-get install -y --no-install-recommends mariadb-server \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli \
    && a2enmod rewrite \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

COPY docker/kitab.ini /usr/local/etc/php/conf.d/kitab.ini
COPY docker/start.sh /usr/local/bin/kitab-start
COPY docker/seed.sql /opt/kitab/seed.sql
RUN sed -i 's/\r$//' /usr/local/bin/kitab-start && chmod +x /usr/local/bin/kitab-start

COPY . /var/www/html/
RUN rm -rf /var/www/html/docker && chown -R www-data:www-data /var/www/html

# The disk is thrown away between visits, so sessions and uploaded pictures live in the database.
# KITAB_ONLINE also stops the "show the reset link on screen" shortcut that exists for XAMPP only.
ENV SESSION_STORE=db \
    UPLOAD_STORE=db \
    KITAB_ONLINE=1 \
    PORT=80

EXPOSE 80
CMD ["kitab-start"]
