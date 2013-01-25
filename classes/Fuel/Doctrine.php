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

		if (!empty($settings['enable_cache']))
		{
			$cache = static::_init_cache($settings);
			if ($cache)
			{
				$config->setMetadataCacheImpl($cache);
				$config->setQueryCacheImpl($cache);
				$config->setResultCacheImpl($cache);
			}
		}

		$config->setProxyDir($settings['proxy_dir']);
		$config->setProxyNamespace($settings['proxy_namespace']);
		$config->setAutoGenerateProxyClasses(\Arr::get($settings,'auto_generate_proxy_classes',false));
		$config->setMetadataDriverImpl(static::_init_metadata($config, $settings));

		$EventManager = new \Doctrine\Common\EventManager();

		static::$_managers[$connection] = \Doctrine\ORM\EntityManager::create($settings['connection'], $config, $EventManager);

		if (!empty($settings['profiling']))
		{
			static::$_managers[$connection]->getConnection()->getConfiguration()->setSQLLogger(new Doctrine\Logger($connection));
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

		if ($type == 'annotation') {
			$Reader = new \Doctrine\Common\Annotations\SimpleAnnotationReader();
			$Reader->addNamespace('\Doctrine\ORM\Mapping');
			$CachedReader = new \Doctrine\Common\Annotations\CachedReader($Reader);
			return new AnnotationDriver($CachedReader, $connection_settings['metadata_path']);
		}

		$class = '\\Doctrine\\ORM\\Mapping\\Driver\\' . static::$metadata_drivers[$type];
		return new $class($connection_settings['metadata_path']);
	}

	public static function connection_settings($connection)
	{
		if (!isset(static::$settings[$connection]) or !isset(static::$settings[$connection]['connection']))
			throw new DoctrineException('No connection configuration for '.$connection);

		$settings = static::$settings[$connection];

                if (!isset($settings['metadata_path']))
		{
			if (!isset(static::$settings['metadata_path']) or !is_string(static::$settings['metadata_path']))
				throw new DoctrineException('metadata_path not configured for '.$connection);
			$settings['metadata_path'] = static::$settings['metadata_path'];
		}

                if (!isset($settings['proxy_dir']))
		{
			if (!isset(static::$settings['proxy_dir']) or !is_string(static::$settings['proxy_dir']))
				throw new DoctrineException('proxy_dir not configured for '.$connection);
			$settings['proxy_dir'] = static::$settings['proxy_dir'];
		}

                if (!isset($settings['proxy_namespace']))
		{
			if (!isset(static::$settings['proxy_namespace']) or !is_string(static::$settings['proxy_namespace']))
				throw new DoctrineException('proxy_namespace not configured for '.$connection);
			$settings['proxy_namespace'] = static::$settings['proxy_namespace'];
		}

		if (!isset($settings['cache_driver']))
		{
			if (isset(static::$settings['cache_driver']) and is_string(static::$settings['cache_driver']))
				$settings['cache_driver'] = static::$settings['cache_driver'];
		}

		if (!isset($settings['auto_generate_proxy_classes']))
		{
			if (isset(static::$settings['auto_generate_proxy_classes']) and !is_array(static::$settings['auto_generate_proxy_classes']))
				$settings['auto_generate_proxy_classes'] = static::$settings['auto_generate_proxy_classes'];
		}

		if (!isset($settings['metadata_driver']))
		{
			if (isset(static::$settings['metadata_driver']) and is_string(static::$settings['metadata_driver']))
				$settings['metadata_driver'] = static::$settings['metadata_driver'];
		}

		if (!isset($settings['profiling']))
		{
			if (isset(static::$settings['profiling']) and !is_array(static::$settings['profiling']))
				$settings['profiling'] = static::$settings['profiling'];
		}

		if (!isset($settings['enable_cache']))
		{
			if (isset(static::$settings['enable_cache']) and !is_array(static::$settings['enable_cache']))
				$settings['enable_cache'] = static::$settings['enable_cache'];
		}

		$options = Array();
		$driver = null;

		if (isset($settings['type']))
		{
			$options['user'] = \Arr::get($settings['connection'],'username', null);
			switch ($settings['type']) {
				case 'mysql':
				case 'mysqli':
					$options['host'] = \Arr::get($settings['connection'],'hostname', null);
					$options['dbname'] = \Arr::get($settings['connection'],'database', null);
					$driver = 'pdo_mysql';
					break;
				case 'pdo':
					$parts = explode(':',\Arr::get($settings['connection'],'dsn',':'));
					if (!in_array($parts[0],Array('mysql', 'sqlite', 'pgsql', 'oci', 'sqlsrv')))
						throw new DoctrineException('Unsupported driver '.$parts[0]);
					$driver = 'pdo_'.$parts[0];
					$conf = explode(';',$parts[1]);
					foreach ($conf as $opt) {
						$v = explode('=',$opt);
						$options[$v[0]]=$v[1];
					}
					break;
				default:
					throw new DoctrineException('Unsupported connection type '.$settings['type']);
			}
		}

		$options = array_filter($options);

		if (!isset($settings['connection']['driver']))
		{
			if (isset(static::$settings['driver']))
				$settings['connection']['driver'] = static::$settings['driver'];
			elseif (!empty($driver))
				$settings['connection']['driver'] = $driver;
		}

		$settings['connection'] = array_merge($options, $settings['connection']);

		if (!isset($settings['connection']['charset']))
		{
			if (isset($settings['charset']))
				$settings['connection']['charset'] = $settings['charset'];
			elseif (isset(static::$settings['charset']) and is_string(static::$settings['charset']))
				$settings['connection']['charset'] = static::$settings['charset'];
		}

		if (!isset($settings['connection']['persistent']))
		{
			if (isset(static::$settings['persistent']) and !is_array(static::$settings['persistent']))
				$settings['connection']['persistent'] = static::$settings['persistent'];
		}

		if (!isset($settings['connection']['compress']))
		{
			if (isset(static::$settings['compress']) and !is_array(static::$settings['compress']))
				$settings['connection']['compress'] = static::$settings['compress'];
		}

		return $settings;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public static function manager($connection = null)
	{
		if (empty($connection)) {
			$connection = static::$settings['active'];
		}

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
