<?php

namespace Fuel\Doctrine;

/**
 * Log Doctrine DBAL queries to FuelPHP profiler
 */
class Logger implements \Doctrine\DBAL\Logging\SQLLogger
{
	/** @var string */
	protected $db_name;

	/** @var mixed */
	protected $benchmark;

	/**
	 * @param string $db_name database name to save in profiler
	 */
	public function __construct($db_name = '')
	{
		$this->db_name = $db_name;
	}

	public function startQuery($sql, array $params = null, array $types = null)
	{
		$this->benchmark = false;
		if (substr($sql, 0, 7) == 'EXPLAIN') // Don't re-log EXPLAIN statements from profiler
			return;

		if ($params)
		{
			// Attempt to replace placeholders so that we can log a final SQL query for profiler's EXPLAIN statement
			// (this is not perfect-- getPlaceholderPositions has some flaws-- but it should generally work with ORM-generated queries)
			
			$is_positional = is_numeric(key($params));
			list($sql, $params, $types) = \Doctrine\DBAL\SQLParserUtils::expandListParameters($sql, $params, $types);
			if (empty($types))
				$types = array();
			$placeholders = \Doctrine\DBAL\SQLParserUtils::getPlaceholderPositions($sql, $is_positional);
			
			if ($is_positional)
				$map = array_flip($placeholders);
			else
			{
				$map = array();
				foreach ($placeholders as $name=>$positions)
				{
					foreach ($positions as $pos)
						$map[$pos] = $name;
				}
			}
			
			ksort($map);
			$src_pos = 0;
			$final_sql = '';
			$first_param_index = key($params);
			foreach ($map as $pos=>$replace_name)
			{
				$final_sql .= substr($sql, $src_pos, $pos-$src_pos);
				
				if ($sql[$pos] == ':')
				{
					$src_pos = $pos + strlen($replace_name);
					$index = trim($replace_name, ':');
				}
				else // '?' positional placeholder
				{
					$src_pos = $pos + 1;
					$index = $replace_name + $first_param_index;
				}
				
				$final_sql .= \Fuel\Doctrine::manager()->getConnection()->quote( $params[ $index ], \Arr::get($types, $index) );
			}
			
			$final_sql .= substr($sql, $src_pos);
			
			$sql = $final_sql;
		}

		$this->benchmark = \Profiler::start("Database (Doctrine: $this->db_name)", $sql);
	}

	public function stopQuery()
	{
		if ($this->benchmark)
		{
			\Profiler::stop($this->benchmark);
			$this->benchmark = null;
		}
	}
}
