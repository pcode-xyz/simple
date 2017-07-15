<?php

namespace Simple\Build;

use Simple\Build;
use Simple\DB;
use Simple\Arr;

/**
 * Class SELECT
 * DB SELECT查询相关操作
 * @package Simple\Build
 */
class SELECT extends Build
{
	/**
	 * @var string $_db_node 查询默认走从库
	 */
	protected $_db_node = 'slave';

	/**
	 * 手动调用改为走主库查询
	 * @return $this
	 */
	public function use_master()
	{
		$this->_db_node = 'master';
		return $this;
	}

	/**
	 * 搜索相关 select逗号分隔
	 * @param string $select 要查找的字段名，逗号分隔
	 * @param bool $format true标准select查询，对select参数逗号分隔后拼装 false不需要处理直接拼装
	 * @return bool|\mysqli_result
	 */
	protected function _find($select = '*', $format = TRUE)
	{
		if ($format && $select != '*')
		{
			$columns = explode(',', $select);
			foreach ($columns as $key => $column)
			{
				$columns[$key] = trim($column);
			}
			$select = '`' . implode('`,`', $columns) . '`';
		}
		$sql = 'SELECT '.$select. ' FROM '.$this->_table.$this->_where.$this->_order.$this->_limit;
		$query = DB::instance($this->_db_config, $this->_db_node)->query($sql);
		return $query;
	}

	/**
	 * 获取统计计数
	 * @return int
	 */
	public function count()
	{
		$query = $this->_find('COUNT(*) AS `total`', FALSE);
		$row = DB::instance($this->_db_config, $this->_db_node)->fetch($query);
		return Arr::get($row, 'total', 0);
	}

	/**
	 * 获取sum叠加计数
	 * @param $column
	 * @return null
	 */
	public function sum($column)
	{
		$query = $this->_find('SUM(`'.$column.'`) AS `total`', FALSE);
		$row = DB::instance($this->_db_config, $this->_db_node)->fetch($query);
		return Arr::get($row, 'total', 0);
	}

	/**
	 * 获取一条查询 自动添加limit 1
	 * @param $column string 要查找的字段名，逗号分隔
	 * @return array
	 */
	public function find($column = '*')
	{
		$this->limit(1);
		$query = $this->_find($column);
		return DB::instance($this->_db_config, $this->_db_node)->fetch($query);
	}

	/**
	 * 获取一批查询
	 * @param string $column 要查找的字段名，逗号分隔
	 * @return array
	 */
	public function find_all($column = '*')
	{
		$query = $this->_find($column);
		$result = array();
		while ($row = DB::instance($this->_db_config, $this->_db_node)->fetch($query))
		{
			$result[] = $row;
		}
		return $result;
	}

	/**
	 * 获取一个值
	 * @param string $column 某字段名
	 * @param null|mixed $default 如果表中不存在该字段则返回default
	 * @return mixed
	 */
	public function get($column, $default = NULL)
	{
		$this->limit(1);
		$query = $this->_find($column);
		$result = DB::instance($this->_db_config, $this->_db_node)->fetch($query);
		return Arr::get($result, $column, $default);
	}

	/**
	 * 获取某一列值 例如get_col(id) 得到 [1, 3, 4, 5]
	 * @param string $column 要查询的字段名
	 * @param mixed $default
	 * @return array
	 */
	public function get_col($column, $default = null)
	{
		$query = $this->_find($column);
		$result = array();
		while ($row = DB::instance($this->_db_config, $this->_db_node)->fetch($query))
		{
			$result[] = Arr::get($row, $column, $default);
		}
		return $result;
	}

	/**
	 * 获取字典形式返回值
	 * @param string $key 作为字典下标的字段名
	 * @param string $value 作为对应内容的字段名
	 * @param mixed $default 如果不存在则返回
	 * @return array
	 */
	public function get_dict($key, $value, $default = null)
	{
		$column = $key.','.$value;
		$query = $this->_find($column);
		$result = array();
		while ($row = DB::instance($this->_db_config, $this->_db_node)->fetch($query))
		{
			$result[Arr::get($row, $key)] = Arr::get($row, $value, $default);
		}
		return $result;
	}

	/**
	 * 类似thinkphp，获取某些字段，key为true则以第一个元素为数组的key
	 * 例如 'id,title,name'			array(id => array(id => 1, title => 12, name => 123))
	 * 例如 'id,title,name', false	array(0 => array(id => 1, title => 12, name => 123))
	 * @param string $fields 要查找的字段名，逗号分隔
	 * @param bool $key 为true则把第一个字段作为数组下标
	 * @return array
	 */
	public function get_fields($fields, $key = true)
	{
		$columns = explode(',', $fields);
		$query = $this->_find($fields);
		$result = array();
		while ($row = DB::instance($this->_db_config, $this->_db_node)->fetch($query))
		{
			if ($key)
			{
				$result[Arr::get($row, trim($columns[0]))] = $row;
			}
			else
			{
				$result[] = $row;
			}
		}
		return $result;

	}
}