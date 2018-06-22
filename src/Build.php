<?php

namespace Simple;

use Simple\Exception\Service_Error;

/**
 * Class Build
 * 用数组的方式构造sql
 * @package Simple\DB
 */
abstract class Build
{
	/**
	 * @var string $_db_config 默认读取的DB config文件
	 */
	protected $_db_config = 'default';
	/**
	 * @var string $_db_node 默认读取的DB config文件中的节点
	 */
	protected $_db_node = 'master';

	protected $_table = null;
	protected $_where = null;
	protected $_limit = null;
	protected $_order = null;

	/**
	 * @param $table
	 * @param $config
	 * @return static
	 */
	final public static function table($table, $config = 'default')
	{
		return new static($table, $config);
	}

	private function __construct($table, $config)
	{
		$this->set_table($table);
		$this->set_config($config);
	}

	/**
	 * @param $table string [db_name].表名
	 * @return $this
	 */
	private function set_table($table)
	{
		if (strpos($table, '.') !== FALSE)
		{
			list($db_name, $table) = explode('.', $table);
			$this->_table = '`'.$db_name.'`.'. '`'.$table.'`';
		}
		else
		{
			$this->_table = '`'.$table.'`';
		}

		return $this;
	}

	/**
	 * 设置db读取的config配置
	 * @param $config
	 */
	private function set_config($config)
	{
		$this->_db_config = $config;
	}

	/**
	 * 构造where
	 * $filter = int时，表示select * from table where id = $filter
	 * $filter = string时，表示select * from table where $filter
	 * $filter = null时，表示select * from table where 0
	 * $filter = array时，具体含义如下，数组内全部使用and拼接
	 * [
	 * //新模式
	 * 		[key, =, value],			select * from table where key = value
	 * 		[key, value],				select * from table where key = value
	 * 		[key, LIKE%, value],		select * from table where key LIKE value%
	 * 		[key, %LIKE, value],		select * from table where key LIKE %value
	 * 		[key, %LIKE%, value],		select * from table where key LIKE %value%
	 * 		[key, [1,2,3,4,5]],			select * from table where key IN (1, 2, 3, 4, 5)
	 * 		[key, IN, [1,2,3,4]],		select * from table where key IN (1, 2, 3, 4, 5)
	 * 		[key, NOT IN, [1,2,3,4]],	select * from table where key NOT IN (1, 2, 3, 4, 5)
	 * //旧模式
	 * 		key => value,				select * from table where key = value
	 * 		key => [LIKE, value],		select * from table where key LIKE value
	 * 		key => [IN, [1,2,3,45]],	select * from table where key IN (1, 2, 3, 4)
	 * ]
	 *
	 * @param $filters mixed 123 | ['abc' => '123'] | ['acb' => ['IN', [1, 2, 3]]]
	 * @throws Service_Error
	 *
	 * @return $this
	 */
	public function where($filters)
	{
		if (is_numeric($filters))
		{
			$this->_where = " WHERE `id` = " . $filters;
		}
		elseif (empty($filters))
		{
			$this->_where = " WHERE 0";
		}
		elseif (is_array($filters))
		{
			$where = ' WHERE ';
			$t = [];
			foreach($filters as $column => $filter)
			{
				$symbol = '=';
				if (is_numeric($column))
				{
					/**
					 * [
					 * 		[key, =, value],
					 * 		[key, value],
					 * ]
					 */
					if (count($filter) == 3)
					{
						//key 符号 value
						$column = $filter[0];
						$symbol = $filter[1];
						$value = $filter[2];
					}
					else
					{
						//key value
						$column = $filter[0];
						$value = $filter[1];
					}
				}
				else
				{
					/**
					 * [
					 * 		key => value,
					 * 		key => [=, value],
					 * ],
					 */
					if (is_array($filter))
					{
						$symbol = $filter[0];
						$value = $filter[1];
					}
					else
					{
						$value = $filter;
					}
				}

				//对['id', [1,2,3,4]]这种情况特殊处理
				if (is_array($value) && $symbol == '=')
				{
					$symbol = 'IN';
				}
				$symbol = strtoupper($symbol);
				$column = trim($column);

				if ($symbol == 'IN' || $symbol == 'NOT IN')
				{
					if (!is_array($value))
					{
						throw new Service_Error('Build SQL with IN must array');
					}
					if (!empty($value))
					{
						foreach ($value as $key => $item)
						{
							$value[$key] = $this->filter($item);
						}
						$t[] = "`" . $column . "` " . $symbol . " ('" . implode("','", $value) . "')";
					}
					else
					{
						$t[] = '0';
					}
				}
				elseif ($symbol == 'BETWEEN' || $symbol == 'NOT BETWEEN')
				{
					$t[] = "`" . $column . "` " . $symbol . " '" . $this->filter($value[0]) . "' AND '" . $this->filter($value[1]) . "'";
				}
				elseif ($symbol == 'LIKE%' || $symbol == 'NOT LIKE%')
				{
					$t[] = "`" . $column . "` " . substr($symbol, 0, -1). " '" . $this->filter($value) . "%'";
				}
				elseif ($symbol == '%LIKE')
				{
					$t[] = "`" . $column . "` " . substr($symbol, 1). " '%" . $this->filter($value) . "'";
				}
				elseif ($symbol == '%LIKE%')
				{
					$t[] = "`" . $column . "` " . substr($symbol, 1, -1). " '%" . $this->filter($value) . "%'";
				}
				else
				{
					$t[] = "`" . $column . "` " . $symbol . " '" . $this->filter($value) . "'";
				}
			}
			$this->_where = $where . implode(' AND ', $t);
		}
		else
		{
			$this->_where = ' WHERE '.$filters;
		}
		return $this;
	}

	//设定limit
	public function limit($limit, $offset = null)
	{
		if (is_null($limit) && is_null($offset))
		{
			return $this;
		}

		if (is_null($offset))
		{
			$this->_limit = ' LIMIT ' . $limit;
		}
		else
		{
			$this->_limit = ' LIMIT ' . $limit . ' OFFSET '. $offset;
		}
		return $this;
	}

	//order by
	public function order($order_by)
	{
		if (empty($order_by))
		{
			return $this;
		}

		if(is_array($order_by))
		{
			$t = [];
			foreach($order_by as $key => $value)
			{
				$t[] = "`" . $key . "` " . $value;
			}
			$this->_order = ' ORDER BY ' . implode(',', $t);
		}
		elseif ($order_by == 'RAND')
		{
			$this->_order = ' ORDER BY RAND()';
		}
		else
		{
			$this->_order = ' ORDER BY `' . $order_by . '`';
		}
		return $this;
	}

	/**
	 * 过滤参数
	 * @param $value
	 * @return string
	 */
	protected function filter($value)
	{
		return DB::instance($this->_db_config, $this->_db_node)->check($value);
	}
}