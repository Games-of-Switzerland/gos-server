#  🎮👾 Swiss Games Garden

Swiss Games Garden API project is based on 💦 [Drupal](https://drupal.org/), 🕸 [Json:API](https://jsonapi.org/) and 🔍 [Elasticsearch](https://www.elastic.co/) to expose Search Engine capabilities.
It uses 🐳 [Docker](http://docker.com/) for running, 🥃 [Gin](https://github.com/EasyCorp/EasyAdminBundle) as Admin UI, 📝 [Swagger](https://swagger.io/) for documentation and ✅ [PHPUnit](https://phpunit.de/)/[Behat](https://docs.behat.org) for testing.
We deploy with 🚀 [Capistrano](https://github.com/capistrano/capistrano).

| Build Status | Swagger | Issues | Activity |
|:-------------------:|:----------------:|:----------------:|:----------------:|
| [![Build Status](https://travis-ci.com/Games-of-Switzerland/gos-server.svg?branch=dev)](https://travis-ci.com/Games-of-Switzerland/gos-server) | [![Swagger](https://img.shields.io/badge/documentation-swagger-blue)](https://api.swissgames.garden/swagger) | ![GitHub issues](https://img.shields.io/github/issues/Games-of-Switzerland/gos-server?style=flat-square) | ![GitHub last commit](https://img.shields.io/github/last-commit/Games-of-Switzerland/gos-server?style=flat-square) |

## 🔧 Prerequisites

First of all, you need to have the following tools installed globally on your environment:

* docker
* composer
* drush

don't forget to add bins to your path such:

* php
* mysql

## 🐳 Docker Install

### Project setup

```bash
cp docker-compose.override-example.yml docker-compose.override.yml
```

Update any values as needed, example when you already use the 8080 port:

```yml
services:
  # Drupal development server
  dev:
    hostname: dev
    ports:
      - "8082:80"
```

Another example when you already have a local MySQL server using port 3306:

```yml
# Database
db:
  ports:
    - "13306:3306"
```

### Project bootstrap

    docker-compose build --pull
    docker-compose up --build -d
    docker-compose exec app docker-as-drupal bootstrap --with-default-content --with-elasticsearch
    (get a coffee, this will take some time...)
    docker-compose exec app drush eshs
    docker-compose exec app drush eshr
    docker-compose exec app drush queue-run elasticsearch_helper_indexing

### Project setup

Once the project up and running via Docker, you may need to setup some configurations in the `web/sites/default/setting.php`.

#### Project base URL

As we are working in a decoupled architecture, we need to set the Website URL.

```php
/**
 * Base URL of the Next App.
 *
 * This value should not contain a leading slash (/).
 *
 * @var string
 */
$config['frontend']['base_url'] = 'https://gos.museebolo.ch';
```

#### Sitemap

The base URL of sitemap links can be overridden using the following settings.

```php
/**
 * The base URL of sitemap links can be overridden here.
 *
 * @var string
 */
$config['simple_sitemap.settings']['base_url'] = 'https://api-gos.museebolo.ch';
```

#### Elasticsearch prefix

We use only 1 Elasticsearch server for both Production & Staging environments. Doing so, we need to separate our indexes
by name. We decide to use prefixes to achieve this goal.

```php
/**
 * Setting used to add a prefix for ES index based on the environment.
 */
$settings['gos_elasticsearch.index_prefix'] = 'local';
```

### When it's not the first time

    docker-compose build --pull
    docker-compose up --build -d
    docker-compose exec app drush cr (or any other drush command you need)
    docker-compose exec app docker-as-drupal db-reset --with-default-content
    docker-compose exec app drush eshr
    docker-compose exec app drush queue-run elasticsearch_helper_indexing

### (optional) Get the productions images

    bundle exec cap production files:download

### Docker help

    docker-compose exec app docker-as-drupal --help

## 🚔 Check Drupal coding standards & Drupal best practices
Maintaining code quality by adding the custom post-commit hook to yours.

```bash
cat ./bin/hooks/post-commit >> ../.git/hooks/post-commit
```

These checks are automatically run in [our CI](../.github/workflows/styles.yml).
You can read more about it in our [CONTRIBUTING section](./CONTRIBUTING.md).



## After a git pull/merge

```bash
docker-compose down
docker-compose build --pull
docker-compose up --build -d
docker-compose exec app docker-as-drupal db-reset --with-default-content --with-elasticsearch
```

Prepend every command with `docker-compose exec app` to run them on the Docker
environment.

## 🚀 Deploy

### First time

```bash
# You need to have ruby & bundler installed
$ bundle install
```

### Each times

We use Capistrano to deploy:

```bash
bundle exec cap -T
bundle exec cap staging deploy
```

## 🔍 Elasticsearch

All given port may be changed by your own `docker-compose.override.yml`.

The Docker installation ship with a working **Elasticsearch in version 6.8.5**.

You may browse your ES server by using [DejaVu UI](https://github.com/appbaseio/dejaVu).

1. Open **DejaVu** in your local browser `http://localhost:1358/`

2. Connect to your Elasticsearch instance using `http://localhost:19200` on index `real_estate`.

    Example working link:
    [http://localhost:1358/?appname=development_gos_node_game_en&url=http://localhost:19200](http://localhost:1358/?appname=development_gos_node_game_en&url=http://localhost:19200)

    The local machine port is the one defined in your `docker-compose` or `docker-compose.override.yml`.
    In the following example the local port is `19200`. and the port inside the Docker is `9200`.

    ```yaml
      elasticsearch:
        ports:
          - "19200:9200"
    ```

### Index

```bash
docker-compose exec [app|test] drush eshr
docker-compose exec [app|test] drush queue-run elasticsearch_helper_indexing
```

### List of Indexes

```bash
docker-compose exec elasticsearch curl http://127.0.0.1:9200/_cat/indices
```

This should print

```bash
$ yellow open gos lsSuUuMjTyizjL_WLECfyQ 5 1 0 0 1.2kb 1.2kb
```

### Recreate Index from scratch

This operation is necessary when the Elasticsearch schema has been updated.

```bash
    docker-compose exec app drush eshd -y
    docker-compose exec app drush eshs
```

### Health Check

Check that Elasticsearch is up and running.

```bash
docker-compose exec elasticsearch curl http://127.0.0.1:9200/_cat/health
```

## 📋 Documentations

We use *Swagger* to document our custom REST endpoints.

Expects the `swagger.json` file it to be stored ìn `./swagger/swagger.json`.
You may access to the *staging* or *production* REST specification with those links:

- Production: [api.gos.ch/swagger](https://api.gos.ch/swagger)
- Staging: [staging-api.gos.ch/swagger](https://staging-api.gos.ch/swagger)

Customs modules:

 - [Migration](./web/modules/custom/gos_migrate/README.md)

## 🚑 Troubleshootings

### Error while running Elasticsearch Setup ?

```
  No alive nodes found in your cluster
```

It seems your Elasticsearch cluster is not reachable by the Docker container.

The common mistake is a misconfiguration on `docker-compose.yml` with missing `host`:

```
DRUPAL_CONFIG_SET: >-
    elasticsearch_helper.settings elasticsearch_helper.host elasticsearch
```

Run the diagnostic command to show the value of `elasticsearch_helper.host` on your container:

```
docker-compose exec app drush cget elasticsearch_helper.settings --include-overridden
```

It should print:

```
elasticsearch_helper:
  scheme: http
  host: elasticsearch
  port: 9200
  authentication: 0
  user: ''
  password: ''
  defer_indexing: 0
```

If you get something else in `host` (such as `localhost`), then your initial bootstrap was made without the `host` config key and need to be rerun:

```
docker-compose exec app docker-as-drupal db-reset --update-dump --with-default-content
```

### Error while importing config ?

```
The import failed due for the following reasons:                                                                                                   [error]
Entities exist of type <em class="placeholder">Shortcut link</em> and <em class="placeholder"></em> <em class="placeholder">Default</em>. These
entities need to be deleted before importing.
```

Solution 1: Delete all your shortcuts from the Drupal Admin on [admin/config/user-interface/shortcut/manage/default/customize](admin/config/user-interface/shortcut/manage/default/customize).

Solution 2: Delete all your shortcuts with drush

```bash
drush ev '\Drupal::entityManager()->getStorage("shortcut_set")->load("default")->delete();'
```

### How to disable the Drupal Cache for dev ?

The tricks is to add this two lines in your `settings.php`:

```php
// do this only after you have installed the drupal
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
$settings['cache']['bins']['render'] = 'cache.backend.null';
```

A better way is to use the `example.settings.local.php` that do more for your dev environement (think about it like the `app_dev.php` front controller):

1. Copy the example local file:

    ```bash
    cp sites/example.settings.local.php sites/default/settings.local.php
    ```

2. Uncomment the following line in your `settings.php`

    ```php
    if (file_exists(__DIR__ . '/settings.local.php')) {
      include __DIR__ . '/settings.local.php';
    }
    ```

3. Clear the cache

    ```bash
    drush cr
    ```

### How to enable the Twig Debug for dev ?

1. Copy the example local file:

    ```bash
    cp sites/default/default.services.yml sites/default/services.yml
    ```

2. set the debug value of twig to `true`

    ```php
    twig.config:
      debug: true
    ```

3. Clear the cache

    ```bash
    drush cr
    ```

[Read More about it](https://www.drupal.org/node/1903374)

### Trouble when running coding standard validations

```bash
ERROR: the "Drupal" coding standard is not installed. The installed coding standards are MySource, PEAR, PHPCS, PSR1, PSR2, Squiz and Zend
```

You have to register the Drupal and DrupalPractice Standard with PHPCS:

```bash
./vendor/bin/phpcs --config-set installed_paths [absolute-path-to-vendor]/drupal/coder/coder_sniffer
```

## 🏆 Tests

Every tests should be run into the Docker environment.

1. Run a shell on your Docker test env.

```bash
docker-compose exec test bash
```

1. Once connected via ssh on your Docker test, you may run any `docker-as-drupal` commands

```bash
docker-as-drupal [behat|phpunit|nightwatch]
```

You also may use the direct access - without opening a bash on the Docket test env. using:

```bash
docker-compose exec test docker-as-drupal [behat|phpunit|nightwatch]
```

## 💻 Drush Commands

## 🕙 Crons

```
# Drupal - Production
# ----------------
## Every 5 minutes
*/5 * * * * root /var/www/docker/cron.sh 2>&1
```

### Crontab

## 📢 RSS

## 📈 Monitoring

### New Relic

New Relic requires two components to work: the PHP agent (inside our `app` container) and a daemon (another container), which aggregates data sent from one or more agents and sends it to New Relic.

By default, we removed the New Relic Docker Container `ARGS` and `depends_on` to avoid building extra containers for developers.
Therefore, on Staging & Production `docker-compose.override.yml` we have added thoses extra parameters

```yaml
build:
  context: .
  args:
    - 'NEW_RELIC_AGENT_VERSION=9.13.0.270'
    - 'NEW_RELIC_LICENSE_KEY=LICENSE'
    - 'NEW_RELIC_APPNAME=Games of Switzerland'
    - 'NEW_RELIC_DAEMON_ADDRESS=newrelic-apm-daemon:31339'
depends_on:
    - newrelic-apm-daemon
```

You also may add the API Key in `settings.php` (on staging / production) to enable data-collection of contrib module `new_relic_rpm

```php
$config['new_relic_rpm.settings']['api_key'] = 'YOUR_API_KEY';
```

## Authors

👨‍💻 **Kevin Wenger**

* Twitter: [@wengerk](https://twitter.com/wengerk)
* Github: [@wengerk](https://github.com/wengerk)

👨‍💻 **Toni Fisler**

* Twitter: [@tonifisler](https://twitter.com/tonifisler)
* Github: [@tonifisler](https://github.com/tonifisler)

👩‍💻 **Camille Létang**

* Github: [@CamilleLetang](https://github.com/CamilleLetang)

👨‍💻 **Pierre Georges**

* Github: [@pierre-georges](https://github.com/pierre-georges)

## 🤝 Contributing

Contributions, issues and feature requests are welcome!

Feel free to check [issues page](https://github.com/Games-of-Switzerland/gos-server/issues).
