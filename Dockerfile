FROM php:8.4-cli

# SQLite, multibyte text handling, and uploaded-image MIME detection.
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libsqlite3-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite mbstring fileinfo \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app
RUN chmod +x /app/docker-entrypoint.sh

ENV PORT=10000
EXPOSE 10000
CMD ["/app/docker-entrypoint.sh"]
