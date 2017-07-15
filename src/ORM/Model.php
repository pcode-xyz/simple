<?php

namespace Simple\ORM;

use Simple\Build\SELECT;
use Simple\DB;
use Simple\ORM;

/**
 * Class Model
 * 基于select实现model操作
 * @package Simple\ORM
 */
class Model extends SELECT
{
	/**
	 * @var $model ORM
	 */
	protected $model = NULL;

	/**
	 * 设置model
	 * @param ORM $model
	 * @return $this
	 *
	 * @author simple
	 */
	public function set_model($model)
	{
		$this->model = $model;
		return $this;
	}

	/**
	 * 获取一个model
	 * @return ORM|null
	 */
	public function one()
	{
		$data = $this->find();
		return empty($data) ? NULL : $this->model->_load_values($data);
	}

	/**
	 * 获取一个数组，每个数组都是一个model
	 * @return array
	 */
	public function all()
	{
		$query = $this->_find();
		$result = array();
		while ($row = DB::instance($this->_db_config, $this->_db_node)->fetch($query))
		{
			$temp = clone $this->model;
			$result[] = $temp->_load_values($row);
		}
		return $result;
	}
}