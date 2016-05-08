<?php

/**
 * Redis封装类，使用前请用try catch包裹
 */
class Redisdb
{
	public static $_instance = null;
	private $_link = null;

	/**
	 * @return Redisdb
	 * */
	public static function instance()
	{
		if (!(Redisdb::$_instance instanceof Redisdb))
		{
			Redisdb::$_instance = new Redisdb();
		}
		return Redisdb::$_instance;
	}

	//连接redis
	private function __construct()
	{
		$config = Config::get('redis');
		$this->_link = new Redis();

		$result = $this->_link->connect($config['ip'], $config['port']);
		if (!$result)
		{
			//todo 告警当前$ip的redis出现故障
			Core::quit("[Wrong Type 1]: redis connection error");
		}

		if (isset($config['password']))
		{
			$this->_link->auth($config['password']);
		}

		return $this;
	}

	//设置key的值
	public function set($key, $value, $time = null)
	{
		$this->_link->set($key, $value);
		if (!is_null($time))
		{
			return $this->expire($key, $time);
		}
	}

	//获取
	public function get($key)
	{
		return $this->_link->get($key);
	}

	//删除
	public function del($key)
	{
		$this->_link->delete($key);
		return true;
	}

	//改名
	public function rename($key, $new_key)
	{
		return $this->_link->rename($key, $new_key);
	}

	//获取key信息  user*获取user开头的所有key
	public function keys($key)
	{
		return $this->_link->keys($key);
	}
	
	//设置key的存活时间 生存多少s
	public function expire($key, $time)
	{
		return $this->_link->expire($key, $time);
	}

	//设置key的存活时间 生存至哪个时间戳
	public function expire_at($key, $time)
	{
		return $this->_link->expireAt($key, $time);
	}

	//获取key的到期时间
	public function ttl($key)
	{
		return $this->_link->ttl($key);
	}

	//集合 将一个元素插入集合中
	public function set_add($key, $value)
	{
		return $this->_link->sAdd($key, $value);
	}

	//从集合中移除一个元素
	public function set_rm($key, $value)
	{
		return $this->_link->sRem($key, $value);
	}

	//判断元素是否在该集合中
	public function set_check($key, $value)
	{
		return $this->_link->sIsMember($key, $value);
	}

	//队列 插入队列头部
	public function list_push_header($key, $value)
	{
		return $this->_link->lPush($key, $value);
	}

	//队列 插入队列结尾
	public function list_push_footer($key, $value)
	{
		return $this->_link->rPush($key, $value);
	}

	//队列 从队列头部取出数据
	public function list_pop_header($key)
	{
		return $this->_link->lPop($key);
	}

	//队列 从队列尾部取出数据
	public function list_pop_footer($key)
	{
		return $this->_link->rPop($key);
	}
	
	//hashmap
	public function hash_set($key, $field, $value)
	{
		return $this->_link->hSet($key, $field, $value);
	}


	//hashmap
	public function hash_get($key, $field = null)
	{
		if (is_null($field))
		{
			return $this->_link->hGetAll($key);
		}
		else
		{
			return $this->_link->hGet($key, $field);
		}
	}

	//特殊定制 栈(先进后出) 进栈
	public function stack_push($key, $value)
	{
		return $this->list_push_footer($key, $value);
	}

	//特殊定制 栈(先进后出) 出栈
	public function stack_pop($key)
	{
		return $this->list_pop_footer($key);
	}

	//特殊定制 队列(先进先出) 排队
	public function enqueue($key, $value)
	{
		return $this->list_push_footer($key, $value);
	}

	//特殊定制 队列(先进先出) 出队
	public function dequeue($key)
	{
		return $this->list_pop_header($key);
	}
}
