FROM antistatique/php-dev:7.4

WORKDIR /var/www

# Export New Relic as ENV variables
ARG NEW_RELIC_AGENT_VERSION
ARG NEW_RELIC_LICENSE_KEY
ARG NEW_RELIC_APPNAME
ARG NEW_RELIC_DAEMON_ADDRESS

# Add and Run the newrelic agent installer.
ADD ./docker/newrelic.sh ./docker/newrelic.sh
RUN ./docker/newrelic.sh

# Install additional dependencies.
RUN apt-get update && apt-get -y install \
    cron;

# Install additional PHP extensions.
RUN docker-php-ext-install \
    exif;

# Copy cron file to the cron.d directory with proper execution rights on the cron job.
ADD ./docker/cron /etc/cron.d/cron

# Update/Ensure Composer v2 is used.
RUN composer self-update --2

# Add the composer files to install dependencies before copying (optimization).
ADD ./composer.json ./composer.lock ./
RUN set -eux; \
  \
  composer install --prefer-dist --no-scripts --no-progress --no-suggest --no-interaction; \
  composer clear-cache

# Copy everything excepted things excluded from .dockerignore.
COPY . ./

# Ensure patch from Composer will be applied.
RUN set -eux; \
  \
  jq 'del(.. |."patches_applied"? | select(. != null))' ./vendor/composer/installed.json > ./vendor/composer/installed.json.new; \
  mv ./vendor/composer/installed.json.new ./vendor/composer/installed.json; \
  \
  composer install --prefer-dist --no-progress --no-suggest --no-interaction; \
  composer clear-cache;
