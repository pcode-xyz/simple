<?php

namespace Simple\Build;

use Simple\Build;
use Simple\DB;

/**
 * Class UPDATE
 * DB update更新操作
 * @package Simple\Build
 */
class UPDATE extends Build
{
	/**
	 * 修改某一条数据
	 * @param array $data [key => value]
	 * @return bool|\mysqli_result
	 */
	public function save($data)
	{
		$sql = "UPDATE ".$this->_table." SET ";
		$data_array = array();
		foreach ($data as $key=>$value)
		{
			$key = trim($key);
			if (substr($value, 0, 6) == 'SELF::')
			{
				$temp = substr($value, 6);
				switch ($temp)
				{
					case 'INC':
						$data_array[] = "`$key` = `$key` + 1";
						break;
					case 'DEC':
						$data_array[] = "`$key` = `$key` - 1";
						break;
					default:
						$data_array[] = "`$key` = `$key` ".$value;
						break;
				}
			}
			else
			{
				$data_array[] = "`$key` = '".$this->filter($value)."'";
			}
		}
		$str = implode(',', $data_array);
		$sql .= $str . $this->_where.$this->_order.$this->_limit;
		return DB::instance($this->_db_config, $this->_db_node)->query($sql);
	}
}