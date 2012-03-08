<?php

namespace Doctrine_Fuel;

/**
 * Convenience class to wrap Doctrine configuration with FuelPHP features.
 * I'm only trying to handle relatively simple usage here, so if your configuration needs
 * are more complicated, just extend/replace in your application
 * 
 * Example:
 * 
 * <code>
 * $em = Doctrine_Fuel::manager();
 * $em->createQuery(...);
 * </code>
 * 
 * Or to use a defined connection other than 'default'
 * <code>
 * $em = Doctrine_Fuel::manager('connection_name');
 * $em->createQuery(...);
 * </code>
 * 
 */
class Doctrine_Fuel
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
		static::$settings = \Config::load('doctrine2', true);
	}
	
	
	public static function _init_manager($connection)
	{
		$settings = static::$settings;
		
		if (!isset($settings[$connection]))
			throw new Exception('No connection configuration for '.$connection);
		
		$config = new \Doctrine\ORM\Configuration();
		$cache = static::_init_cache();
		if ($cache)
		{
			$config->setMetadataCacheImpl($cache);
			$config->setQueryCacheImpl($cache);
		}
		
		$config->setProxyDir($settings['proxy_dir']);
		$config->setProxyNamespace($settings['proxy_namespace']);
		$config->setAutoGenerateProxyClasses($settings['auto_generate_proxy_classes']);
		$config->setMetadataDriverImpl(static::_init_metadata($config));
		
		static::$_managers[$connection] = \Doctrine\ORM\EntityManager::create($settings[$connection]['connection'], $config);
		
		if (!empty($settings[$connection]['profiling']))
		{
			static::$_managers[$connection]->getConnection()->getConfiguration()->setSQLLogger(new Logger($connection));
		}
	}
	
	/**
	 * @return \Doctrine\Common\Cache|false
	 */
	protected static function _init_cache()
	{
		$type = \Arr::get(static::$settings, 'cache_driver', 'array');
		if ($type)
		{
			if (!array_key_exists($type, static::$cache_drivers))
				throw new \Exception('Invalid Doctrine2 cache driver: ' . $type);
			
			$class = '\\Doctrine\\Common\\Cache\\' . static::$cache_drivers[$type];
			return new $class();
		}
		
		return false;
	}
	
	/**
	 * @return \Doctrine\ORM\Mapping\Driver\Driver
	 */
	protected static function _init_metadata($config)
	{
		$type = \Arr::get(static::$settings, 'metadata_driver', 'annotation');
		if (!array_key_exists($type, static::$metadata_drivers))
			throw new \Exception('Invalid Doctrine2 metadata driver: ' . $type);
		
		if ($type == 'annotation')
			return $config->newDefaultAnnotationDriver(static::$settings['metadata_path']);
			
		$class = '\\Doctrine\\ORM\\Mapping\\Driver\\' . static::$metadata_drivers[$type];
		return new $class($settings['metadata_path']);
	}
	
	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public static function manager($connection = 'default')
	{
		if (!isset(static::$_managers[$connection]))
			static::_init_manager($connection); 
		
		return static::$_managers[$connection];
	}
}
