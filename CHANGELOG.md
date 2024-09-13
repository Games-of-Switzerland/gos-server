# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Security
- update imbo/behat-api-extension (v2.3.3 => v5.0.0)
- upgrade drupal/metatag (1.26.0 => 2.0.2)

## [1.2.0] - 2024-09-11
### Added
- add SIMPLETEST_IGNORE_DIRECTORIES to phpunit.xml.dist

### Removed
- remove dependency on HAL & RDF modules

### Security
- update drupal/core-dev (10.2.3 => 10.3.3)
- update all minors (25) dependencies
- update drush/drush (12.5.3 => 13.1.1)
- update vimeo/psalm (4.30.0 => 5.26.1)

### Changed
- replace all deprecated usage of MigrateSkipProcessException by stopPipeline & ->saveMessage

### Fixed
- fix psalm UndefinedInterfaceMethod pluginDefinition
- fix Admin UI Content Views Actions Buttons list

## [1.1.1] - 2024-09-11
### Removed
- uninstall module HAL

### Changed
- enable CKeditor 5 to replace CKeditor 4
- fix obsolete docker-compose command

### Security
- upgrade from PHP 8.1 -> 8.2
- upgrade module drupal/upgrade_status (4.0.0 => 4.1.0)
- update composer/installers (v2.2.0 => v2.3.0)
- update phpspec/prophecy-phpunit (v2.1.0 => v2.2.0)
- update mglaman/phpstan-drupal (1.2.6 => 1.2.12)
- update drush/drush (12.4.3 => 12.5.3)
- update alexandresalome/mailcatcher (v1.4.0 => v1.4.1)
- update drupal/webp (1.0.0-rc1 => 1.0.0-rc2)
- update drupal/admin_toolbar (3.4.2 => 3.5.0)
- update drupal/field_group (3.4.0 => 3.6.0)
- update drupal/gin (3.0.0-rc9 => 3.0.0-rc13)
- update drupal/gin_login (2.0.3 => 2.1.3)
- update drupal/upgrade_status (4.1.0 => 4.3.5)
- update drupal/default_content (2.0.0-alpha2 => 2.0.0-alpha3)
- update drupal/hal (2.0.2 => 2.0.3)
- update drupal/aggregator (2.2.0 => 2.2.2)
- update drupal/pathauto (1.12.0 => 1.13.0)
- update drupal/focal_point (2.0.2 => 2.1.1)
- update drupal/cdn (4.0.2 => 4.1.0)
- update drupal/consumers (1.17.0 => 1.19.0)
- update drupal/jsonapi_extras (3.24.0 => 3.25.0)
- update drupal/restui (1.21.0 => 1.22.0)
- update drupal/simple_sitemap (4.1.8 => 4.2.1)

## [1.1.0] - 2024-02-23
### Changed
- upgrade drupal/elasticsearch_helper (dev-6.x 1443af5 => 8.1.0) - Games-of-Switzerland/swissgamesgarden#143

### Added
- add alias in Docker app container to run drush using ./vendor/bin/drush

## [1.0.0] - 2024-02-13
### Security
- upgrade Drupal Core to Drupal 10

### Changed
- use browserkit over goutte for Behat testing
- apply new Normalizer usage since Drupal 10 update
- replace Doctrine Annotation by PHP Attribute

### Fixed
- fix possible iteration over scalar values
- add missing return type on ::setUp() methods
- fix psalm drupal 10 autoloader
- fix phpstan configuration file
- use vendor/bin/drush instead of drush on update.sh

## [0.6.2] - 2024-02-12
### Added
- add module drupal/ckeditor for Drupal 10 update compliancy

### Removed
- remove module RDF as deprecated on Drupal 10
- remove theme seven as deprecated on Drupal 10

### Security
- upgrade drupal/core (9.5.11 => 10.2.3)

## [0.6.1] - 2024-02-09
### Security
- update drupal/core (9.5.3 => 9.5.11)
- update phpspec/prophecy-phpunit (v2.0.2 => v2.1.0)
- upgrade module drupal/cdn (3.6.0 => 4.0.2)
- upgrade module drupal/focal_point (1.5.0 => 2.0.2)

### Changed
- remove caches on both Search & Autocomplete endpoints (Elasticsearch)

## [0.6.0] - 2024-02-09
### Changed
- move Linters php-deprecation-detector, php-cs-fixer & psalm into own Tools/ComposerJson
- upgrade Default Content files from JSON -> YAML

### Removed
- remove phpmd
- remove phpcpd

### Security
- update module drupal/migrate_source_csv (3.5.0 => 3.6.0)
- drupal/migrate_tools (6.0.1 => 6.0.4)
- update module drupal/fieldable_path (1.0.0-rc6 => 1.0.0)
- update phpstan linter extensions mglaman/phpstan-drupal (1.1.28 => 1.1.30)
- update module drupal/admin_toolbar (3.3.0 => 3.4.2)
- update module drupal/gin_toolbar (1.0.0-rc1 => 1.0.0-rc5)
- update theme drupal/gin (3.0.0-rc2 => 3.0.0-rc9)
- update tests library phpspec/prophecy-phpunit (v2.0.1 => v2.0.2)
- update drupal/gin_login (2.0.1 => 2.0.3)
- update module drupal/jsonapi_extras (3.23.0 => 3.24.0)
- update module drupal/consumers (1.16.0 => 1.17.0)
- update module drupal/webp (1.0.0-beta6 => 1.0.0-rc1)
- update module drupal/new_relic_rpm (2.1.0 => 2.1.1)
- update module drupal/pathauto (1.11.0 => 1.12.0)
- update module drupal/simple_sitemap (4.1.4 => 4.1.8)
- update module drupal/symfony_mailer (1.2.0-beta2 => 1.4.1)
- update module drupal/views_ef_fieldset (1.5.0 => 1.7.0)
- update behat/behat (v3.12.0 => v3.14.0)
- upgrade drupal/upgrade_status (3.18.0 => 4.0.0)
- upgrade module drupal/default_content (1.0.0-alpha9 => 2.0.0-alpha2)
- update module drupal/metatag (1.22.0 => 1.26.0)
- update module drupal/ctools (4.0.3 => 4.0.4)
- update module drupal/token (1.11.0 => 1.13.0)
- update PHP 8.0 -> 8.1

## [0.5.5] - 2023-04-20
### Fixed
- fix issue when member is deleted leading to un-indexable games

## [0.5.4] - 2023-04-20
### Security
- update library marcortola/behat-seo-contexts (3.1.1 => 4.0.0)
- remove abandonned behatch/contexts
- update module drupal/migrate_tools (5.2.0 => 6.0.1)
- update theme drupal/gin (3.0.0-rc1 => 3.0.0-rc2)

### Added
- add the new Release Type 'Prototype' - Games-of-Switzerland/swissgamesgarden#132

## [0.5.3] - 2023-02-07
### Fixed
- fix field_cantons json:api public name to be 'cantons'

### Security
- update module drupal/bamboo_twig (5.1.0 => 6.0.0)
- update module drupal/gin_toolbar (1.0.0-beta22 => 1.0.0-rc1)
- update theme drupal/gin (3.0.0-beta5 => 3.0.0-rc1)
- update module drupal/simple_sitemap (4.1.3 => 4.1.4)
- update module drupal/admin_toolbar (3.2.1 => 3.3.0)
- update module drupal/cdn (3.5.0 => 3.6.0)
- update module drupal/ctools (4.0.1 => 4.0.3)
- update module drupal/consumers (1.14.0 => 1.16.0)
- update module drupal/jsonapi_extras (3.21.0 => 3.23.0)
- update module drupal/consumer_image_styles (4.0.7 => 4.0.8)
- update module drupal/gin_login (1.4.0 => 2.0.1)
- update module drupal/symfony_mailer (1.1.0-beta2 => 1.2.0-beta2)
- update library composer/installers (v1.12.0 => v2.2.0)
- update drupal/core-dev (9.4.8 => 9.5.3)
- update all minors dependencies

### Removed
- remove Gin Login Unsplash library images

## [0.5.2] - 2022-11-07
### Security
- update module drupal/simple_sitemap (3.11.0 => 4.1.2)
- update module drupal/admin_toolbar (3.1.1 => 3.2.1)
- update module drupal/bamboo_twig (5.0.0 => 5.1.0)
- update module drupal/consumers (1.13.0 => 1.14.0)
- update module drupal/field_group (3.2.0 => 3.4.0)
- update module drupal/crop (2.2.0 => 2.3.0)
- update module drupal/file_mdm (2.4.0 => 2.5.0)
- update module drupal/image_effects (3.3.0 => 3.4.0)
- update module drupal/metatag (1.21.0 => 1.22.0)
- update module drupal/simple_sitemap (4.1.2 => 4.1.3)
- update drupal/core (9.4.5 => 9.4.8)
- update module drupal/symfony_mailer (1.0.0-alpha11 => 1.1.0-beta2)

## [0.5.1] - 2022-09-10
### Added
- allow link to be button - Games-of-Switzerland/swissgamesgarden#9

### Changed
- rename Game Json:api field_cantons to cantons- Games-of-Switzerland/swissgamesgarden#51

## [0.5.0] - 2022-09-09
### Added
- configure Gandi to send e-mails - Games-of-Switzerland/swissgamesgarden#91
- add the role 'Contributor' - Games-of-Switzerland/swissgamesgarden#101

### Fixed
- ensure master is deployed on production instead of dev

### Security
- update Drupal 9.3.7 => 9.4.3 with all dependencies
- upgrade module drupal/bamboo_twig (5.0.0-alpha1 => 5.0.0)
- upgrade theme drupal/gin (3.0.0-beta1 => 3.0.0-beta5)
- update module drupal/gin_toolbar (1.0.0-beta21 => 1.0.0-beta22)
- update module drupal/gin_login (1.1.0 => 1.3.0)
- update module drupal/ctools (3.7.0 => 4.0.1)
- update module drupal/admin_toolbar (3.1.0 => 3.1.1)
- update module drupal/pathauto (1.9.0 => 1.11.0)
- update module drupal/token (1.10.0 => 1.11.0)
- update module drupal/metatag (1.19.0 => 1.21.0)
- update module drupal/image_effects (3.2.0 => 3.3.0)
- update module drupal/consumer_image_styles (4.0.6 => 4.0.7)
- update module drupal/consumers (1.12.0 => 1.13.0)
- update module drupal/jsonapi_extras (3.20.0 => 3.21.0)
- update module drupal/restui (1.20.0 => 1.21.0)
- update module drupal/symfony_mailer (1.0.0-alpha4 => 1.0.0-alpha11)
- update all other dependencies (16 updates)
- update linter phar phpmd 2.9.1 => 2.12.0
- update linter phar psalm 4.13.0 => 4.27.0
- update linter phar phpstan 1.2.0 => 1.8.5

## [0.4.0] - 2022-09-08
### Changed
- use Docker Builtkit on Github Action
- rework Docker integration using antistatique/php instead of deprecated image antistatique/docker-php

## [0.3.1] - 2022-05-05
### Security
- update module drupal/pathauto (1.9.0 => 1.10.0)
- update module drupal/migrate_plus (5.2.0 => 5.3.0)
- update module drupal/drupal-driver (v2.1.1 => v2.1.2)
- update module drupal/jsonapi_hypermedia (1.6.0 => 1.7.0)
- update module drupal/drupal-extension (v4.1.0 => v4.2.1)

## [0.3.0] - 2022-04-22
### Security
- update Drupal 9.3.7 => 9.3.12 with all dependencies
- update module drupal/gin (3.0.0-beta1 => 3.0.0-beta2)
- update module drupal/gin_login (1.1.0 => 1.2.0)
- update module drupal/gin_toolbar (1.0.0-beta21 => 1.0.0-beta22)
- update module drupal/migrate_file (2.1.0 => 2.1.1)
- update module drupal/consumer_image_styles (4.0.6 => 4.0.7)

## [0.2.0] - 2022-03-24
### Security
- update Drupal 9.2.8 => 9.3.7 with all dependencies

### Added
- add a migration update to fillup Website field on Games
- add facets extra data for platform name, location name & genre name - Games-of-Switzerland/swissgamesgarden#50
- add support of Games canton(s) - Games-of-Switzerland/swissgamesgarden#51

## [0.1.0] - 2022-03-14
### Added
- add the CDN module to serve all images via HTTPs

### Changed
- install some static analyzer via Phive instead of Composer
- update to PHP 8.0
- update New Relic Agent 9.13.0.270 -> 9.18.1.303 for PHP8 compatibility

## [0.0.3] - 2021-11-20
### Added
- update deployment process to use Github Actions Composite
- update deployment process to set Github Environements

### Changed
- bump composer/composer from 2.0.13 to 2.1.12
- upgrade from Composer v1 to v2

## [0.0.2] - 2021-11-15
### Changed
- update to Drupal 9

## [0.0.1] - 2021-11-15
### Added
- init repo on 2019-11-27
- add Basic pages to JSON:API - #140

### Changed
- update changelog following 'keep a changelog'
- add Video Games collection to Gin Login
- prepare for Drupal 9 update

[Unreleased]: https://github.com/Games-of-Switzerland/api.swissgamesgarden/compare/1.2.0...HEAD
[1.2.0]: https://github.com/Games-of-Switzerland/api.swissgamesgarden/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/Games-of-Switzerland/api.swissgamesgarden/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/Games-of-Switzerland/api.swissgamesgarden/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/Games-of-Switzerland/gos-server/compare/0.6.2...1.0.0
[0.6.2]: https://github.com/Games-of-Switzerland/gos-server/compare/0.6.1...0.6.2
[0.6.1]: https://github.com/Games-of-Switzerland/gos-server/compare/0.6.0...0.6.1
[0.6.0]: https://github.com/Games-of-Switzerland/gos-server/compare/0.5.5...0.6.0
[0.5.5]: https://github.com/Games-of-Switzerland/gos-server/compare/0.5.4...0.5.5
[0.5.4]: https://github.com/Games-of-Switzerland/gos-server/compare/0.5.3...0.5.4
[0.5.3]: https://github.com/Games-of-Switzerland/gos-server/compare/0.5.2...0.5.3
[0.5.2]: https://github.com/Games-of-Switzerland/gos-server/compare/0.5.1...0.5.2
[0.5.1]: https://github.com/Games-of-Switzerland/gos-server/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/Games-of-Switzerland/gos-server/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/Games-of-Switzerland/gos-server/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/Games-of-Switzerland/gos-server/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/Games-of-Switzerland/gos-server/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/Games-of-Switzerland/gos-server/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/Games-of-Switzerland/gos-server/compare/0.0.3...0.1.0
[0.0.3]: https://github.com/Games-of-Switzerland/gos-server/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/Games-of-Switzerland/gos-server/compare/0.0.1...0.0.2
[0.0.1]: https://github.com/Games-of-Switzerland/gos-server/releases/tags/0.0.1
