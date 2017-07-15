<?php

namespace Simple\Build;

use Simple\Build;
use Simple\DB;

/**
 * Class INSERT
 * DB insert相关操作
 * @package Simple\Build
 */
class INSERT extends Build
{
	/**
	 * 插入一行数据
	 * @param array $data [key => value, key => value]
	 * @return mixed
	 */
	public function add($data)
	{
		$fields = array();
		$values = array();
		foreach ($data as $key=>$value)
		{
			$fields[] = $key;
			$values[] = $this->filter($value);
		}

		$sql = "INSERT INTO " . $this->_table . " (`" . implode('`,`', $fields) . "`) VALUES ('" . implode("','", $values) . "')";
		DB::instance($this->_db_config, $this->_db_node)->query($sql);
		return DB::instance($this->_db_config, $this->_db_node)->insert_id();
	}

	/**
	 * 批量插入数据
	 * @param array $data [[key => value, key => value], [key => value, key => value], [key => value, key => value]]
	 * @return mixed
	 */
	public function add_all($data)
	{
		$fields = array();
		$values = array();
		foreach ($data as $temp)
		{
			$t_values = array();
			$t_fields = array();
			foreach ($temp as $key=>$value)
			{
				$t_fields[] = $key;
				$t_values[] = $this->filter($value);
			}
			//fields只要一次
			$fields = $fields != $t_fields ? $t_fields : $fields;
			$str = implode("','", $t_values);
			$values[] = "('".$str."')";
		}

		$sql = "INSERT INTO " . $this->_table . " (`" . implode('`,`', $fields) . "`) VALUES " . implode(', ', $values);
		DB::instance($this->_db_config, $this->_db_node)->query($sql);
		return DB::instance($this->_db_config, $this->_db_node)->insert_id();
	}
}