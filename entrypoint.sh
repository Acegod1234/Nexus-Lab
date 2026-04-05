#!/bin/bash
set -e

DB_PATH="/var/www/html/src/database.sqlite"

# Ensure uploads dir is writable (important on Render)
mkdir -p /var/www/html/public/uploads
chown www-data:www-data /var/www/html/public/uploads
chmod 755 /var/www/html/public/uploads

# Ensure src dir is writable for SQLite
chown www-data:www-data /var/www/html/src
chmod 755 /var/www/html/src

# Initialize DB if it doesn't exist
if [ ! -f "$DB_PATH" ]; then
    echo "[*] Initializing challenge database..."
    php /var/www/html/src/init_db.php
    chown www-data:www-data "$DB_PATH"
    chmod 664 "$DB_PATH"
    echo "[*] Database initialized."
fi

# Re-write flag in case container restarted (Render ephemeral FS)
printf 'softwarica_ctf{+#1$_1$_+#3_f|@8_y0u_w3R3_|00k1n9_f0r}' > /var/secrets/.flag_db9f2a
chmod 644 /var/secrets/.flag_db9f2a
chown www-data:www-data /var/secrets/.flag_db9f2a

echo "[*] Starting Apache..."
exec apache2-foreground
