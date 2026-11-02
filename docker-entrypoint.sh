#!/bin/sh
set -eu

# Render persistent disk should be mounted at /var/data.
mkdir -p /var/data /var/data/uploads

# Keep bundled sample photos on the persistent disk on first deployment.
if [ -d /app/uploads ] && [ ! -L /app/uploads ]; then
    cp -an /app/uploads/. /var/data/uploads/ 2>/dev/null || true
    rm -rf /app/uploads
fi
if [ ! -L /app/uploads ]; then
    ln -s /var/data/uploads /app/uploads
fi

# Database is created automatically by db.php on the first web request.
exec php -d upload_max_filesize=4M -d post_max_size=6M \
    -S "0.0.0.0:${PORT:-10000}" -t /app
