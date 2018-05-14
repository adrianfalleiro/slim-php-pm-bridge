# Slim Framework PHP-PM Adapter #

This is an experimental PHP-PM adapter for the Slim Framework.

## Setup ##

```php
$ composer require php-pm/php-pm "^1.0"
$ composer require adrianfalleiro/slim-php-pm-bridge dev-master
```

## Run ##

```php
$ ./vendor/bin/ppm --bridge="adrianfalleiro\PHPPM\Slim\Bridges\Slim" start --debug 1 --workers 1 --static-directory public
```