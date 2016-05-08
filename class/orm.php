<?php

class ORM
{
	//处table外，其他均在关键词前加空格
	private $_table = null;
	private $_where = null;
	private $_limit = null;
	private $_order = null;
	private $_sql = null;

	//简易工厂
	public static function factory($table)
	{
		return new ORM($table);
	}

	private function __construct($table)
	{
		return $this->table($table);
	}

	//选择表
	public function table($table)
	{
		$t_table = explode('.', $table);
		foreach ($t_table as $key=>$value)
		{
			$t_table[$key] = '`'.$value.'`';
		}
		$table = implode('.', $t_table);
		$this->_table = $table;
		return $this;
	}

	//设定filter	数组暂不支持or
	public function where($filters)
	{
		if (empty($filters))
		{
			return $this;
		}
		
		if (is_numeric($filters))
		{
			$this->_where = " WHERE `id` = " . $filters;
		}
		elseif (is_array($filters))
		{
			$where = ' WHERE ';
			$t = array();
			foreach($filters as $key => $value)
			{
				if (is_array($value))
				{
					if ($value[0] == 'IN' || $value[0] == 'NOT IN')
					{
						$t[] = "`" . $key . "` " . $value[0] . " ('" . implode("','", $this->filter($value[1])) . "')";
					}
					else
					{
						$t[] = "`" . $key . "` " . $value[0] . " '" . $this->filter($value[1]) . "'";
					}
				}
				else
				{
					$t[] = "`" . $key . "` = '" . $this->filter($value) . "'";
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
		if (!is_null($offset))
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
		if(is_array($order_by))
		{
			$t = array();
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

	//新增	返回插入id
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
		DB::instance()->query($sql);
		return DB::instance()->insert_id();
	}

	//插入一批	*需测试
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
		DB::instance()->query($sql);
		return DB::instance()->insert_id();
	}

	//快速修改
	public function save($data)
	{
		$sql = "UPDATE ".$this->_table." SET ";
		$data_array = array();
		foreach ($data as $key=>$value)
		{
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
		return DB::instance()->query($sql);
	}

	//删除
	public function delete()
	{
		$sql = "DELETE FROM ".$this->_table.$this->_where.$this->_order.$this->_limit;
		return DB::instance()->query($sql);
	}

	//搜索相关冗余	select逗号分隔
	private function _find($select = '*')
	{
		if ($select != '*' && $select != 'COUNT(*)')
		{
			$columns = explode(',', $select);
			$select = '`'.implode('`,`', $columns).'`';
		}
		$sql = "SELECT {$select} FROM ".$this->_table.$this->_where.$this->_order.$this->_limit;
		$query = DB::instance()->query($sql);
		return $query;
	}

	//统计
	public function count()
	{
		$query = $this->_find('COUNT(*)');
		$row = DB::instance()->fetch($query);
		return Arr::get($row, 'COUNT(*)', 0);
	}

	//查找一条
	public function find()
	{
		$query = $this->_find();
		return DB::instance()->fetch($query);
	}

	//查找一个数组
	public function find_all()
	{
		$query = $this->_find();
		$result = array();
		while ($row = DB::instance()->fetch($query))
		{
			$result[] = $row;
		}
		return $result;
	}

	//获取某一个字段的值
	public function get($column, $default = null)
	{
		$query = $this->_find($column);
		$row = DB::instance()->fetch($query);
		return Arr::get($row, $column, $default);
	}

	//获取某一列
	public function get_col($column, $default = null)
	{
		$query = $this->_find($column);
		$result = array();
		while ($row = DB::instance()->fetch($query))
		{
			$result[] = Arr::get($row, $column, $default);
		}
		return $result;
	}

	//获取字典
	public function get_dict($key, $value, $default = null)
	{
		$column = $key.','.$value;
		$query = $this->_find($column);
		$result = array();
		while ($row = DB::instance()->fetch($query))
		{
			$result[Arr::get($row, $key)] = Arr::get($row, $value, $default);
		}
		return $result;
	}

	//获取某些字段，逗号分隔 key为true则默认第一个为数组的key
	//例如 'id,title,name'			array(id => array(id => 1, title => 12, name => 123))
	//例如 'id,title,name', false	array(0 => array(id => 1, title => 12, name => 123))
	public function get_fields($fields, $key = true)
	{
		$columns = explode(',', $fields);
		$query = $this->_find($fields);
		$result = array();
		while ($row = DB::instance()->fetch($query))
		{
			if ($key)
			{
				$result[Arr::get($row, $columns[0])] = $row;
			}
			else
			{
				$result[] = $row;
			}
		}
		return $result;
		
	}

	//过滤
	public function filter($value)
	{
		return DB::instance()->check($value);
	}

}