# =========================
# BASE (shared PHP runtime)
# =========================
FROM dunglas/frankenphp:1-php8.4 AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl supervisor \
    && rm -rf /var/lib/apt/lists/*

# maatwebsite/excel needs gd + zip; sqlite is bundled with the image.
RUN install-php-extensions pdo_mysql zip gd intl bcmath opcache pcntl

# Node is needed to build the assets and to run the Inertia SSR server.
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# =========================
# BUILDER (compile assets)
# =========================
FROM base AS builder

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --no-dev

COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .

COPY .env.docker .env

# The client bundle bakes in VITE_APP_NAME (expanded from APP_NAME in .env), and
# it has to match the APP_NAME the container runs with.
ARG APP_NAME="POS Biasa"
RUN sed -i "s|^APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|" .env

RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

RUN composer dump-autoload -o --no-scripts \
    && php artisan key:generate --force \
    && php artisan wayfinder:generate --with-form

# Builds the browser bundle and bootstrap/ssr/app.js (Inertia SSR server).
RUN npm run build:ssr

# The SSR bundle keeps its imports external, so production node_modules must
# ship with the runtime image.
RUN npm prune --omit=dev --ignore-scripts

# The build-time .env must not leak into the image; runtime uses --env-file.
RUN rm -f .env


# =========================
# RUNTIME (actual server)
# =========================
FROM base AS runtime

WORKDIR /var/www/html

COPY --from=builder /var/www/html /var/www/html

COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /etc/supervisor/conf.d \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 8080 is the web server. The SSR renderer listens on 127.0.0.1:13714 and is
# deliberately not exposed.
EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
