<?php

namespace Simple;

use Simple\Exception\Service_Error;

/**
 * Class Redis
 * Redis操作类
 * @package Simple
 */
class Redis
{
	private static $_instance = null;
	private $_link = null;

	/**
	 * @return \Simple\Redis
	 * */
	public static function instance()
	{
		if (!(Redis::$_instance instanceof Redis))
		{
			Redis::$_instance = new Redis();
		}
		return Redis::$_instance;
	}

	/**
	 * 读取配置文件 链接redis
	 * Redis constructor.
	 */
	private function __construct()
	{
		$config = Config::get('redis');
		$this->_link = new \Redis();

		$result = $this->_link->connect($config['ip'], $config['port']);
		if (!$result)
		{
			throw new Service_Error('redis['.$config['ip'].':'.$config['port'].'] connection error');
		}

		if (isset($config['password']))
		{
			$this->_link->auth($config['password']);
		}

		return $this;
	}

	/**
	 * 设置一个值
	 * @param $key
	 * @param $value
	 * @param null $time
	 * @return bool
	 */
	public function set($key, $value, $time = null)
	{
		$result = $this->_link->set($key, $value);
		if ($result && !is_null($time))
		{
			return $this->expire($key, $time);
		}
		return $result;
	}

	/**
	 * 获取一个值
	 * @param $key
	 * @return bool|string
	 */
	public function get($key)
	{
		return $this->_link->get($key);
	}

	/**
	 * 删除一个值
	 * @param $key
	 */
	public function del($key)
	{
		return $this->_link->delete($key);
	}

	/**
	 * 改名
	 * @param $key
	 * @param $new_key
	 * @return bool
	 */
	public function rename($key, $new_key)
	{
		return $this->_link->rename($key, $new_key);
	}

	/**
	 * 获取key信息
	 * @param $key string user*获取user开头的所有key
	 * @return array
	 */
	public function keys($key)
	{
		return $this->_link->keys($key);
	}

	/**
	 * 设置key的存活时间 生存多少s
	 * @param $key
	 * @param $time int
	 * @return bool
	 */
	public function expire($key, $time)
	{
		return $this->_link->expire($key, $time);
	}

	/**
	 * 设置key的存活时间 生存至哪个时间戳
	 * @param $key
	 * @param $time int 时间戳
	 * @return bool
	 */
	public function expire_at($key, $time)
	{
		return $this->_link->expireAt($key, $time);
	}

	/**
	 * 获取key的到期时间
	 * @param $key
	 * @return int
	 */
	public function ttl($key)
	{
		return $this->_link->ttl($key);
	}

	/**
	 * 集合 将一个元素插入集合中
	 * @param $key
	 * @param $value
	 * @return int
	 */
	public function set_add($key, $value)
	{
		return $this->_link->sAdd($key, $value);
	}

	/**
	 * 从集合中移除一个元素
	 * @param $key
	 * @param $value
	 * @return int
	 */
	public function set_rm($key, $value)
	{
		return $this->_link->sRem($key, $value);
	}

	/**
	 * 判断元素是否在该集合中
	 * @param $key
	 * @param $value
	 * @return bool
	 */
	public function set_check($key, $value)
	{
		return $this->_link->sIsMember($key, $value);
	}

	/**
	 * 查询队列的长度
	 * @param $key
	 * @return int
	 *
	 * @author simple
	 */
	public function list_length($key)
	{
		return $this->_link->lLen($key);
	}

	/**
	 * 返回队列所有的值
	 * @param $key
	 * @param int $start
	 * @param int $end
	 * @return array
	 *
	 * @author simple
	 */
	public function list_range($key, $start = 0, $end = -1)
	{
		return $this->_link->lRange($key, $start, $end);
	}

	/**
	 * 队列 插入头部
	 * @param $key
	 * @param $value
	 * @return int
	 */
	public function list_push_header($key, $value)
	{
		return $this->_link->lPush($key, $value);
	}

	/**
	 * 队列 插入队列结尾
	 * @param $key
	 * @param $value
	 * @return int
	 */
	public function list_push_footer($key, $value)
	{
		return $this->_link->rPush($key, $value);
	}

	/**
	 * 队列 从队列头部取出数据
	 * @param $key
	 * @return string
	 */
	public function list_pop_header($key)
	{
		return $this->_link->lPop($key);
	}

	/**
	 * 队列 从队列尾部取出数据
	 * @param $key
	 * @return string
	 */
	public function list_pop_footer($key)
	{
		return $this->_link->rPop($key);
	}

	/**
	 * hash map 设置
	 * @param $key
	 * @param $field
	 * @param $value
	 * @return int
	 */
	public function hash_set($key, $field, $value)
	{
		return $this->_link->hSet($key, $field, $value);
	}

	/**
	 * 获取一个hash map中的值 或 获取一个hash map
	 * @param $key
	 * @param null $field
	 * @return array|string
	 */
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

	/**
	 * 栈(先进后出) 进栈
	 * @param $key
	 * @param $value
	 * @return int
	 */
	public function stack_push($key, $value)
	{
		return $this->list_push_footer($key, $value);
	}

	/**
	 * 栈(先进后出) 出栈
	 * @param $key
	 * @return string
	 */
	public function stack_pop($key)
	{
		return $this->list_pop_footer($key);
	}

	/**
	 * 队列(先进先出) 排队
	 * @param $key
	 * @param $value
	 * @return int
	 */
	public function enqueue($key, $value)
	{
		return $this->list_push_footer($key, $value);
	}

	/**
	 * 队列(先进先出) 出队
	 * @param $key
	 * @return string
	 */
	public function dequeue($key)
	{
		return $this->list_pop_header($key);
	}

	/**
	 * 直接调用redis默认函数
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		return call_user_func_array([$this->_link, $name], $arguments);
	}
}
