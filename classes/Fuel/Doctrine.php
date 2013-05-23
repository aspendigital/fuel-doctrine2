<?php

namespace Fuel;

class DoctrineException extends \FuelException {}

/**
 * Convenience class to wrap Doctrine configuration with FuelPHP features.
 * I'm only trying to handle relatively simple usage here, so if your configuration needs
 * are more complicated, just extend/replace in your application
 *
 * Example:
 *
 * <code>
 * $em = \Fuel\Doctrine::manager();
 * $em->createQuery(...);
 * </code>
 *
 * Or to use a defined connection other than 'default'
 * <code>
 * $em = \Fuel\Doctrine::manager('connection_name');
 * $em->createQuery(...);
 * </code>
 *
 */
class Doctrine
{
	/** @var array */
	protected static $_managers;

	/** @var array */
	protected static $settings;

	/**
	 * Map cache types to class names
	 * Memcache/Memcached can't be set up automatically the way the other types can, so they're not included
	 *
	 * @var array
	 */
	protected static $cache_drivers = array(
			'array'=>'ArrayCache',
			'apc'=>'ApcCache',
			'xcache'=>'XcacheCache',
			'wincache'=>'WinCache',
			'zend'=>'ZendDataCache'
		);

	/**
	 * Map metadata driver types to class names
	 */
	protected static $metadata_drivers = array(
			'annotation'=>'', // We'll use the factory method; just here for the exception check
			'php'=>'PHPDriver',
			'simplified_xml'=>'SimplifiedXmlDriver',
			'simplified_yaml'=>'SimplifiedYamlDriver',
			'xml'=>'XmlDriver',
			'yaml'=>'YamlDriver'
		);

	/**
	 * Read configuration and set up EntityManager singleton
	 */
	public static function _init()
	{
		static::$settings = \Config::load('db', true);
	}

	public static function _init_manager($connection)
	{
		$settings = static::connection_settings($connection);

		$config = new \Doctrine\ORM\Configuration();

		$cache = static::_init_cache($settings);
		if ($cache)
		{
			$config->setMetadataCacheImpl($cache);
			$config->setQueryCacheImpl($cache);
			$config->setResultCacheImpl($cache);
		}

		$config->setProxyDir($settings['proxy_dir']);
		$config->setProxyNamespace($settings['proxy_namespace']);
		$config->setAutoGenerateProxyClasses(\Arr::get($settings, 'auto_generate_proxy_classes', false));
		$config->setMetadataDriverImpl(static::_init_metadata($config, $settings));

		$EventManager = new \Doctrine\Common\EventManager();

		static::$_managers[$connection] = \Doctrine\ORM\EntityManager::create($settings['connection'], $config, $EventManager);

		if (!empty($settings['profiling']))
			static::$_managers[$connection]->getConnection()->getConfiguration()->setSQLLogger(new Doctrine\Logger($connection));

		// Connection init callback
		if (!empty($settings['init_callback']))
		{
			// If array merge combined this numeric array, grab last two array elements as the real callback
			if (is_array($settings['init_callback']) && count($settings['init_callback']) > 2)
				$settings['init_callback'] = array_slice($settings['init_callback'], -2);

			call_user_func($settings['init_callback'], static::$_managers[$connection], $connection);
		}
	}

	/**
	 * @return \Doctrine\Common\Cache|false
	 */
	protected static function _init_cache($connection_settings)
	{
		$type = \Arr::get($connection_settings, 'cache_driver', 'array');
		if ($type)
		{
			if (!array_key_exists($type, static::$cache_drivers))
				throw new DoctrineException('Invalid Doctrine2 cache driver: ' . $type);

			$class = '\\Doctrine\\Common\\Cache\\' . static::$cache_drivers[$type];
			return new $class();
		}

		return false;
	}

	/**
	 * @return \Doctrine\ORM\Mapping\Driver\Driver
	 */
	protected static function _init_metadata($config, $connection_settings)
	{
		$type = \Arr::get($connection_settings, 'metadata_driver', 'annotation');
		if (!array_key_exists($type, static::$metadata_drivers))
			throw new DoctrineException('Invalid Doctrine2 metadata driver: ' . $type);

		if ($type == 'annotation')
			return $config->newDefaultAnnotationDriver($connection_settings['metadata_path']);

		$class = '\\Doctrine\\ORM\\Mapping\\Driver\\' . static::$metadata_drivers[$type];
		return new $class($connection_settings['metadata_path']);
	}

	public static function connection_settings($connection)
	{
		if (!isset(static::$settings['doctrine2']))
			throw new DoctrineException('Missing "doctrine2" key in DB config');
		
		if (!isset(static::$settings[$connection]) or !isset(static::$settings[$connection]['connection']))
			throw new DoctrineException("No connection configuration for '$connection'");

		$connection_settings = static::$settings[$connection];
		$settings = static::$settings['doctrine2'];
		if (isset($connection_settings['doctrine2']))
		{
			$settings = array_replace($settings, $connection_settings['doctrine2']);
			unset($connection_settings['doctrine2']);
		}

		// Required settings
		foreach (array('metadata_path', 'proxy_dir', 'proxy_namespace') as $key)
		{
			if (!isset($settings[$key]))
				throw new DoctrineException("'$key' not configured for connection '$connection'");
		}

		// Translate DB connection config to terms Doctrine understands
		$options = array();
		if (isset($connection_settings['type']))
		{
			$options['user'] = \Arr::get($connection_settings['connection'], 'username', null);
			switch ($connection_settings['type'])
			{
				case 'mysql':
				case 'mysqli':
					$options['host'] = \Arr::get($connection_settings['connection'], 'hostname', null);
					$options['dbname'] = \Arr::get($connection_settings['connection'], 'database', null);
					$options['driver'] = 'pdo_mysql';
					break;
				case 'pdo':
					$parts = explode(':', \Arr::get($connection_settings['connection'], 'dsn', ':'));
					if (!in_array($parts[0], array('mysql', 'sqlite', 'pgsql', 'oci', 'sqlsrv')))
						throw new DoctrineException('Unsupported driver '.$parts[0]);
					$options['driver'] = 'pdo_'.$parts[0];
					$conf = explode(';', $parts[1]);
					foreach ($conf as $opt)
					{
						$v = explode('=', $opt);
						$options[$v[0]] = $v[1];
					}
					break;
				default:
					throw new DoctrineException('Unsupported connection type '.$connection_settings['type']);
			}
		}

		if (isset($connection_settings['charset']))
			$options['charset'] = $connection_settings['charset'];
			
		$options = array_filter($options);
		$settings['connection'] = array_merge($options, $connection_settings['connection']);
		$settings['profiling'] = \Arr::get($connection_settings, 'profiling', false);
		$settings['init_callback'] = \Arr::get($settings, 'init_callback');

		return $settings;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public static function manager($connection = null)
	{
		if (empty($connection))
			$connection = static::$settings['active'];

		if (!isset(static::$_managers[$connection]))
			static::_init_manager($connection);

		return static::$_managers[$connection];
	}

	/**
	 * @return array Doctrine version information
	 */
	public static function version_check()
	{
		return array(
			'common' => \Doctrine\Common\Version::VERSION,
			'dbal' => \Doctrine\DBAL\Version::VERSION,
			'orm' => \Doctrine\ORM\Version::VERSION
		);
	}
}

Doctrine::_init();
