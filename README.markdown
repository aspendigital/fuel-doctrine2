# FuelPHP Doctrine2 Package

This contains a basic wrapper around the Doctrine 2 ORM functionality for access via a FuelPHP package. It is distributed under the same LGPL license as Doctrine itself.

To use, copy the included configuration files to your application directory and update directory and connection information. In order to run EXPLAIN queries on DBAL SELECT queries in the profiler, copy the modified file vendor/phpquickprofiler/phpquickprofiler.php to your FuelPHP core directory.

After loading the package in your application, to get an EntityManager, use the following code:

```php
$em = Doctrine_Fuel::manager(); // Uses the connection labeled 'default' in your configuration
$em = Doctrine_Fuel::manager('connection_2'); // Specify connection explicitly
```

Or you can check the versions of the Doctrine components:

```php
print_r(Doctrine_Fuel::version_check());
```

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

