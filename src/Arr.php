<?php

namespace Simple;

/**
 * Class Arr 数组类
 * @package Simple
 */
class Arr
{
	/**
	 * 从一个数组中取出指定key的值，如果不存在则返回default
	 * @param array $array 要取值的数组
	 * @param string $key 指定的key
	 * @param null|mixed $default $array[$key]不存在时则返回
	 * @return null
	 *
	 * @author simple
	 */
	public static function get($array, $key, $default = null)
	{
		return isset($array[$key]) ? $array[$key] : $default;
	}
}