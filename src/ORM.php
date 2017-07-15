<?php

namespace Simple;

use Simple\Build\DELETE;
use Simple\Build\INSERT;
use Simple\Build\SELECT;
use Simple\Build\UPDATE;
use Simple\Exception\Service_Error;
use Simple\ORM\Model;

/**
 * Class ORM
 * 对象关系映射
 * @package Simple
 */
class ORM
{
	/**
	 * @var string $_db_config 读取配置文件
	 */
	protected $_db_config = 'default';
	/**
	 * @var string $_table 表名
	 */
	protected $_table = NULL;
	/**
	 * @var string $_pk 主键名
	 */
	protected $_pk = 'id';

	/**
	 * @var mixed $_pk_value 当前model的主键值
	 */
	protected $_pk_value = NULL;

	/**
	 * @var array $_data 当前model的真实数据
	 */
	protected $_data = [];

	/**
	 * @var array $_raw_data 数据库取出的原始数据
	 */
	protected $_raw_data = [];

	/**
	 * @var bool $_loaded 是否有加载过数据
	 */
	protected $_loaded = FALSE;

	/**
	 * @return ORM\Model
	 */
	public static function find()
	{
		$model = new static();
		return Model::table($model->_table, $model->_db_config)->set_model($model);
	}

	/**
	 * @param mixed $where
	 * @return static
	 */
	public static function factory($where = NULL)
	{
		return new static($where);
	}

	/**
	 * 初始化,有则自动获取数据
	 * @param mixed $where
	 * @throws Service_Error
	 */
	public function __construct($where = NULL)
	{
		if (empty($this->_table))
		{
			throw new Service_Error('Model[' . __CLASS__ . '] table name is undefined');
		}

		if (empty($this->_pk))
		{
			throw new Service_Error('Model[' . __CLASS__ . '] has not defined the pk');
		}

		if (!empty($where))
		{
			//分表
			$this->_sub_table();
			$this->_initialize($where);
		}
	}

	/**
	 * 分表函数 待继承后设置
	 */
	public function _sub_table()
	{

	}

	/**
	 * 从数据库读取数据
	 * @param $where mixed
	 * @return $this
	 */
	protected function _initialize($where)
	{
		if (is_array($where))
		{
			$this->_data = SELECT::table($this->_table, $this->_db_config)->where($where)->find();
		}
		else
		{
			$this->_data = SELECT::table($this->_table, $this->_db_config)->where([$this->_pk => $where])->find();
		}

		if (!empty($this->_data))
		{
			$this->_pk_value = $this->_data[$this->_pk];
			$this->_raw_data = $this->_data;
			$this->_loaded = TRUE;
		}

		return $this;
	}

	/**
	 * 从数组初始化到当前对象
	 * @param $data
	 * @return $this
	 */
	public function _load_values($data)
	{
		$this->_data = $data;
		$this->_raw_data = $data;
		$this->_pk_value = $data[$this->_pk];
		$this->_loaded = TRUE;
		return $this;
	}

	/**
	 * 读取$_data中的值
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return Arr::get($this->_data, $name);
	}

	/**
	 * 用户主动设置数据
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		//TODO 增加校验功能
		$this->_data[$name] = $value;
	}

	/**
	 * isset
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->_data[$name]);
	}

	/**
	 * 修改
	 */
	public function save()
	{
		if (!$this->_loaded)
		{
			throw new Service_Error('Model[' . __CLASS__ . '] method save cannot be called on unload objects');
		}

		//只修改有发生变化的值
		$data = [];
		foreach ($this->_data as $key => $value)
		{
			if ($this->_raw_data[$key] != $value)
			{
				$data[$key] = $value;
			}
		}

		UPDATE::table($this->_table, $this->_db_config)->where([$this->_pk => $this->_pk_value])->save($data);
		//判断有无修改过主键
		if ($this->_data[$this->_pk] != $this->_raw_data[$this->_pk])
		{
			$this->_pk_value = $this->_data[$this->_pk];
		}
		$this->_raw_data = $this->_data;

		return $this;
	}

	/**
	 * 插入一条数据
	 */
	public function add()
	{
		//分表
		$this->_sub_table();

		$id = INSERT::table($this->_table, $this->_db_config)->add($this->_data);
		if ($id == 0)
		{
			//非数字主键
			$this->_pk_value = $this->_data[$this->_pk];
		}
		else
		{
			$this->_pk_value = $id;
		}

		//防止有些有默认值 需要再获取一次最新的
		$this->_initialize($this->_pk_value);
		return $this->_pk_value;
	}

	/**
	 * @throws Service_Error
	 */
	public function del()
	{
		if (!$this->_loaded)
		{
			throw new Service_Error('Model[' . __CLASS__ . '] method del cannot be called on unload objects');
		}

		//分表
		$this->_sub_table();

		return DELETE::table($this->_table, $this->_db_config)->where([$this->_pk => $this->_pk_value])->del();
	}

	/**
	 * 获取为array
	 */
	public function to_array()
	{
		return $this->_data;
	}
}