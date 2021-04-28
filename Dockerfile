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

# Keep composer version 1 until drupal-console-extend-plugin has not been updated.
# @see https://github.com/hechoendrupal/drupal-console-extend-plugin/pull/25
RUN composer self-update --1

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

# Register the Drupal and DrupalPractice Standard with PHPCS.
RUN ./vendor/bin/phpcs --config-set installed_paths \
    `pwd`/vendor/drupal/coder/coder_sniffer

# Copy the Analyzer definition files installed from Composer.
COPY phpcs.xml.dist phpstan.neon .php_cs.dist ./

# Setup, Download & install PHP CS Fixer.
# COPY .php_cs.dist.dist .php_cs.dist ./
# RUN set -eux; \
  # curl -L https://cs.symfony.com/download/php-cs-fixer-v2.phar -o php-cs-fixer; \
  # chmod a+x php-cs-fixer; \
  # mv php-cs-fixer /usr/bin/php-cs-fixer;

# Setup, Download & install PHPMD.
COPY phpmd.xml ./
RUN set -eux; \
  curl -LJO https://phpmd.org/static/latest/phpmd.phar; \
  chmod +x phpmd.phar; \
  mv phpmd.phar /usr/bin/phpmd

# Setup, Download & install PHPCPD.
RUN set -eux; \
  curl -LJO https://phar.phpunit.de/phpcpd.phar; \
  chmod +x phpcpd.phar; \
  mv phpcpd.phar /usr/bin/phpcpd

# Setup, Download & install PHPStan.
# COPY phpstan.neon ./
# RUN set -eux; \
  # curl -LJO https://github.com/phpstan/phpstan/releases/latest/download/phpstan.phar; \
  # chmod +x phpstan.phar; \
  # mv phpstan.phar /usr/bin/phpstan

# Setup, Download & install Psalm.
COPY psalm.xml ./
RUN set -eux; \
  curl -LJO https://github.com/vimeo/psalm/releases/latest/download/psalm.phar; \
  chmod +x psalm.phar; \
  mv psalm.phar /usr/bin/psalm

# Setup, Download & install PHPDD (PhpDeprecationDetector).
 RUN set -eux; \
   \
   apt-get update; \
   apt-get install -y \
    libbz2-dev \
   ; \
   \
   docker-php-ext-install bz2; \
   \
   curl -LJO https://github.com/wapmorgan/PhpDeprecationDetector/releases/latest/download/phpdd-2.0.25.phar; \
   chmod +x phpdd-2.0.25.phar; \
   mv phpdd-2.0.25.phar /usr/bin/phpdd
