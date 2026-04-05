FROM php:8.1-apache

# Install SQLite and other extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy entrypoint first to root, then copy everything else
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set working directory and copy app files
WORKDIR /var/www/html
COPY . /var/www/html/

# Apply Apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Create required directories with correct ownership
RUN mkdir -p /var/www/html/public/uploads \
    && mkdir -p /var/www/html/src \
    && mkdir -p /var/secrets \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/src \
    && chmod 755 /var/www/html/public/uploads \
    && chmod 755 /var/www/html/src

# Write flag to non-web-accessible location at build time
RUN printf 'softwarica_ctf{+#1$_1$_+#3_f|@8_y0u_w3R3_|00k1n9_f0r}' > /var/secrets/.flag_db9f2a \
    && chmod 644 /var/secrets/.flag_db9f2a \
    && chown www-data:www-data /var/secrets/.flag_db9f2a

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
