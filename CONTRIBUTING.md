CONTRIBUTING
------------

## 🚔 Follow Drupal coding standards & Drupal best practices

During Docker build, the following Static Analyzers will be installed on the Docker `app|test` via Composer:

- `drupal/coder^8.3.1`  (including `squizlabs/php_codesniffer` & `phpstan/phpstan`),
- `mglaman/phpstan-drupal`
- `phpstan/phpstan-deprecation-rules`
- `friendsofphp/php-cs-fixer`
- `drupol/phpcsfixer-configs-drupal`
- `fenetikm/autoload-drupal`
- `friendsofphp/php-cs-fixer`
- `mglaman/drupal-check`

The following Analyzer will be downloaded & installed as PHAR:

- `phpmd/phpmd`
- `sebastian/phpcpd`
- `vimeo/psalm`
- `wapmorgan/PhpDeprecationDetector`

### Command Line Usage

    ./scripts/hooks/post-commit
    # or run command on the container itself
    docker-compose exec app bash

#### Running Code Sniffer Drupal & DrupalPractice

https://github.com/squizlabs/PHP_CodeSniffer

PHP_CodeSniffer is a set of two PHP scripts; the main `phpcs` script that tokenizes PHP, JavaScript and CSS files to
detect violations of a defined coding standard, and a second `phpcbf` script to automatically correct coding standard
violations.
PHP_CodeSniffer is an essential development tool that ensures your code remains clean and consistent.

The Drupal and DrupalPractice Standard will automatically be applied the rules from `phpcs.xml.dist` file.

  ```
  $ docker-compose exec app ./vendor/bin/phpcs  --no-cache
  ```

Automatically fix coding standards

  ```
  $ docker-compose exec app ./vendor/bin/phpcbf
  ```

#### Running PHP Mess Detector

https://github.com/phpmd/phpmd

Detect overcomplicated expressions & Unused parameters, methods, properties.

  ```
  $ docker-compose exec app phpmd ./web/modules/custom text ./phpmd.xml \
--suffixes php,module,inc,install,test,profile,theme,css,info,txt --exclude *Test.php,*vendor/*
  ```

  ```
  $ docker-compose exec app phpmd ./behat text ./phpmd.xml --suffixes php
  ```

#### Running PHP Copy/Paste Detector

https://github.com/sebastianbergmann/phpcpd

`phpcpd` is a Copy/Paste Detector (CPD) for PHP code.

  ```
  $ docker-compose exec app phpcpd ./web/modules/custom --suffix .php --suffix .module --suffix .inc --suffix .install --suffix .test --suffix .profile --suffix .theme --suffix .css --suffix .info --suffix .txt --exclude *.md --exclude *.info.yml --exclude tests --exclude vendor/
  ```

  ```
  $ docker-compose exec app phpcpd ./behat --suffix .php
  ```

#### Running PhpDeprecationDetector

https://github.com/wapmorgan/PhpDeprecationDetector

A scanner that checks compatibility of your code with PHP interpreter versions.

  ```
  $ docker-compose exec app phpdd ./web/modules/custom ./behat \
    --file-extensions php,module,inc,install,test,profile,theme,info --exclude vendor
  ```

### Ensure PHP Community Best Practices using PHP Coding Standards Fixer

https://github.com/FriendsOfPHP/PHP-CS-Fixer

It can modernize your code (like converting the pow function to the ** operator on PHP 5.6) and (micro) optimize it.

```bash
docker-compose run app ./vendor/bin/php-cs-fixer fix --config=.php_cs.dist -v --dry-run --using-cache=no --format=checkstyle
```

### Running Psalm

https://github.com/vimeo/psalm

Attempts to dig into your program and find as many type-related bugs as possible via Psalm.

```bash
docker-compose exec app psalm --config=psalm.xml --no-cache
```

### Running PHPStan

https://github.com/phpstan/phpstan

Catches whole classes of bugs even before you write tests using PHPStan.

```bash
docker-compose exec app ./vendor/bin/phpstan analyse ./web/modules/custom ./behat --error-format=checkstyle
```

#### Running Drupal-Check

https://github.com/mglaman/drupal-check

Built on PHPStan, this static analysis tool will check for correctness (e.g. using a class that doesn't exist),
deprecation errors, and more.

While there are many static analysis tools out there, none of them run with the Drupal context in mind.
This allows checking contrib modules for deprecation errors thrown by core.

  ```
  $ docker-compose exec app drupal-check -dvvv ./web/modules/custom ./behat --no-progress
  ```

### Enforce code standards with git hooks

Maintaining code quality by adding the custom post-commit hook to yours.

  ```bash
  cat ./scripts/hooks/post-commit >> ./.git/hooks/post-commit
  ```
