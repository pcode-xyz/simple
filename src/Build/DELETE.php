<?php

namespace Simple\Build;

use Simple\Build;
use Simple\DB;

/**
 * Class DELETE
 * DB 删除相关操作
 * @package Simple\Build
 */
class DELETE extends Build
{
	/**
	 * 删除
	 * @return bool|\mysqli_result
	 */
	public function del()
	{
		$sql = "DELETE FROM ".$this->_table.$this->_where.$this->_order.$this->_limit;
		return DB::instance($this->_db_config, $this->_db_node)->query($sql);
	}

	/**
	 * 清空表 慎用
	 * @return bool|\mysqli_result
	 *
	 * @author simple
	 */
	public function truncate()
	{
		$sql = "TRUNCATE ".$this->_table;
		return DB::instance($this->_db_config, $this->_db_node)->query($sql);
	}
}