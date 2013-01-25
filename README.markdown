# FuelPHP Doctrine 2 Package

## About

This package contains a basic wrapper around the Doctrine 2 ORM functionality for access using the FuelPHP framework. It is distributed under the same LGPL license as Doctrine itself.


## How to install

You can install this package with Composer. So first you'll need Composer, which can be found at http://getcomposer.org/

1. If your application doesn't have a `composer.json` file please create it.
2. Add an additional `require`
```
"aspendigital/fuel-doctrine2": "dev-master"
```
3. Install with `composer install`

## Quick start

Configuration is simple and involves adding additional Doctrine configuration options to your FuelPHP db.php config.

To get running quickly, there are only three required settings:

in `app/config/db.php` add
```php
'doctrine2'=>array(
	'proxy_dir' => APPPATH . 'classes' . DS . 'proxy',
	'proxy_namespace' => 'Proxy',
	'metadata_path' => APPPATH . 'classes' . DS . 'entity'
)
```
and of course configure database settings (user, password etc) as you would normally do for Fuel.

## How to use
when you've configured your application, to get an EntityManager, use the following code:

```php
$em = \Fuel\Doctrine::manager(); // Uses the connection referred to by the 'active' index in your configuration
$em = \Fuel\Doctrine::manager('connection_2'); // Specify connection explicitly
```

Or you can check the versions of the Doctrine components:

```php
print_r(\Fuel\Doctrine::version_check());
```

## Typical configuration example
Using the cascading configuration files that FuelPHP offers, a typical configuration looks something like:

`app/config/db.php`:
```php
return array(
	'active'=>'default',

	'doctrine2'=>array(
		'proxy_dir'       => APPPATH . 'classes' . DS . 'proxy',
		'proxy_namespace' => 'Proxy',
		'metadata_path'   => APPPATH . 'classes' . DS . 'entity',
		'metadata_driver' => 'annotation'
	)

	/**
	 * Base config, just need to set the DSN, username and password in env. config.
	 */
	'default' => array(
		'type'        => 'pdo',
		'connection'  => array(
			'persistent' => false,
			'compress'   => false
		),
		'charset'      => 'utf8',
		'profiling'    => false
	)
);
```

`app/config/development/db.php`:
```php
return array(
	'doctrine2'=>array(
		'auto_generate_proxy_classes' => true
	),

	'default'=>array(
		'connection'  => array(
			'dsn'            => 'pgsql:host=localhost;dbname=fuel_db',
            'username'       => 'your_username',
            'password'       => 'y0uR_p@ssW0rd'
		)
		'profiling'   => true
	)
);
```

`app/config/production/db.php`:
```php
return array(
	'doctrine2'=>array(
		'auto_generate_proxy_classes'   => false,
		'cache_driver'                  => 'apc'
	),

	'default'=>array(
		'connection'  => array(
			'dsn'            => 'pgsql:host=production_server;dbname=fuel_db',
            'username'       => 'your_username',
            'password'       => 'y0uR_p@ssW0rd'
		)
		'profiling'    => false
	)
);
```

In the development environment, we use the default array cache (nothing is saved permanently) and enable profiling. In the production environment, we leave profiling off and use APC (or some other caching solution).

### Connection setting override
If for some reason you need to override Doctrine2 settings on a connection-by-connection basis, include a `doctrine2` key in your connection settings:
```php
return array(

	'default'=>array(
		'connection'  => array(
			'dsn'            => 'pgsql:host=production_server;dbname=fuel_db',
            'username'       => 'your_username',
            'password'       => 'y0uR_p@ssW0rd'
		)
		'profiling'    => false,
		'doctrine2'    => array(
			'cache_driver'   => 'zend' // Override the cache driver only for the 'default' connection
		)
	)
);
```

### Configuration options

Refer to the [Doctrine 2](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/configuration.html) documentation
* `proxy_dir`: the directory containing your proxy classes 
* `proxy_namespace`: the namespace where the proxy classes reside
* `metadata_path`: the directory containing your metadata
* `metadata_driver: options are 'annotation' (default), 'php', 'simplified_xml', 'simplified_yaml', 'xml', 'yaml'
* `auto_generate_proxy_classes`: true/false for whether Doctrine should generate proxy classes for entities it loads
* `cache_driver`: options are 'array' (default), 'apc', 'xcache', 'wincache', 'zend'

On connection:
* `driver`: we try to guess the DBAL driver to load to connect to your database, but you may have to set this if the guessing doesn't work for you
* Consult the [Doctrine DBAL](https://github.com/doctrine/dbal/blob/master/docs/en/reference/configuration.rst) documentation for other DBAL-specific options

For FuelPHP options, refer to [Fuel database configuration](http://fuelphp.com/docs/classes/database/introduction.html)
* `type`
* `charset`
* `profiling`
* `enable_cache`: in our case, there is always some caching taking place, but it's only temporary unless you've changed the `cache_driver` setting
* `connection.persistent`
* `connection.compress`

## Profiling

No configuration is required beyond enabling profiling for your connection. Queries sent through Doctrine ORM and directly through DBAL will automatically appear in the Fuel profiler.

# Doctrine 2 ORM

Doctrine 2 is an object-relational mapper (ORM) for PHP 5.3.2+ that provides transparent persistence for PHP objects. It sits on top of a powerful database abstraction layer (DBAL). One of its key features is the option to write database queries in a proprietary object oriented SQL dialect called Doctrine Query Language (DQL), inspired by Hibernates HQL. This provides developers with a powerful alternative to SQL that maintains flexibility without requiring unnecessary code duplication.

## More resources:

* [Website](http://www.doctrine-project.org)
* [Documentation](http://www.doctrine-project.org/projects/orm/2.0/docs/reference/introduction/en)
* [Issue Tracker](http://www.doctrine-project.org/jira/browse/DDC)
* [Downloads](http://github.com/doctrine/doctrine2/downloads)
