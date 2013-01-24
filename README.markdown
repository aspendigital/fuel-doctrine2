# FuelPHP Doctrine 2 Package

## About

This package contains a basic wrapper around the Doctrine 2 ORM functionality for access via a FuelPHP package. It is distributed under the same LGPL license as Doctrine itself.


## How to install

You can install this package with Composer. So first you'll need Composer. About Composer http://getcomposer.org/

1. If your application doesn't have `composer.json` please create it.
2. Simply add additional `require`
```
"aspendigital/fuel-doctrine2": "dev-master"
```
3. Install with `composer install`

## How to configure

Configuration is really simple. It supports same database parameters as Fuel database configuration. In fact it uses same config file. There will need just to add additional Doctrine configuration options.

To get running quickly:

in `app/config/db.php` to config array add
```php
'proxy_dir' => APPPATH . 'classes' . DS . 'proxy',
'proxy_namespace' => 'Proxy',
'metadata_path' => APPPATH . 'classes' . DS . 'entity',
```
and of course configure database settings (user, password etc) same as like you do for Fuel.

## How to use
when you've configured application, to get an EntityManager, use the following code:

```php
$em = \Fuel\Doctrine::manager(); // Uses the connection labeled 'default' in your configuration
$em = \Fuel\Doctrine::manager('connection_2'); // Specify connection explicitly
```

Or you can check the versions of the Doctrine components:

```php
print_r(\Fuel\Doctrine::version_check());
```

## Configuration options

Options can be configured in either `app/config/db.php` or in `app/config/ENVIROMENT/db.php` (the latter will take precedence)

Example:
```php
return array(
    'auto_generate_proxy_classes' => true,
    'proxy_dir' => APPPATH . 'classes' . DS . 'proxy',
    'proxy_namespace' => 'Proxy',
    'metadata_path' => APPPATH . 'classes' . DS . 'entity',
    'metadata_driver' => 'yaml'
    'production' => array(
        'type'           => 'pdo',
        'connection'     => array(
            'dsn'            => 'pgsql:host=localhost;dbname=fuel_db',
            'username'       => 'your_username',
            'password'       => 'y0uR_p@ssW0rd',
            'persistent'     => false,
            'compress'       => false,
        ),
        'charset'        => 'utf8',
        'enable_cache'   => true,
        'profiling'      => false,
        'cache_driver'   => 'apc'
    )
);
```

For these refer to [Doctrine 2](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/configuration.html) and [Doctrine DBAL](https://github.com/doctrine/dbal/blob/master/docs/en/reference/configuration.rst) documentation
* `proxy_dir` - [C][G]
* `proxy_namespace` - [C][G]
* `metadata_path` - [C][G]
* `metadata_driver` - [C][G]
* `auto_generate_proxy_classes` - [C][G]
* `cache_driver` - [C][G]
* `driver` - [D][C][G]

Refer to [Fuel database configuration](http://fuelphp.com/docs/classes/database/introduction.html)
* `type` - [C]
* `enable_cache` - [C][G]
* `charset` - [D][C][G]
* `profiling` - [C][G]
* `connection.persistent` - [D][G]
* `connection.compress` - [D][G]


`connection` supports all Doctrine DBAL options and will take precedence over Fuel options.


Configuration options can be specified in multiple places.
* [D] - means this option will be taken from `connection` array if it exists there.
* [C] - option will be taken from configuration.
* [G] - is global and will be taken from outside of configuration.

In `db.php` config example above `proxy_dir` isn't defined in [C] so it will be taken from [G] and `charset` isn't in [D] so [C] will be used.

## PHP Quick Profiler

No configuration required and all queries can be seen with correct EXPLAIN details. But you've to enable profiling.

## Versions:

* Doctrine Common: 2.2.0
* Doctrine DBAL: 2.2.1
* Doctrine ORM: 2.2.1

# Doctrine 2 ORM

Master: [![Build Status](https://secure.travis-ci.org/doctrine/doctrine2.png?branch=master)](http://travis-ci.org/doctrine/doctrine2)
2.1.x: [![Build Status](https://secure.travis-ci.org/doctrine/doctrine2.png?branch=2.1.x)](http://travis-ci.org/doctrine/doctrine2)

Doctrine 2 is an object-relational mapper (ORM) for PHP 5.3.2+ that provides transparent persistence for PHP objects. It sits on top of a powerful database abstraction layer (DBAL). One of its key features is the option to write database queries in a proprietary object oriented SQL dialect called Doctrine Query Language (DQL), inspired by Hibernates HQL. This provides developers with a powerful alternative to SQL that maintains flexibility without requiring unnecessary code duplication.

## More resources:

* [Website](http://www.doctrine-project.org)
* [Documentation](http://www.doctrine-project.org/projects/orm/2.0/docs/reference/introduction/en)
* [Issue Tracker](http://www.doctrine-project.org/jira/browse/DDC)
* [Downloads](http://github.com/doctrine/doctrine2/downloads)
