<?php

namespace Simple;

/**
 * Class Iptables
 * ip过滤类库
 * @package Simple
 */
class Iptables
{
	/**
	 * 将.*等转为正则可以理解的字段
	 * @param $list
	 * @return array
	 */
	public static function format($list)
	{
		return array_map(function($str){
			$rule = str_replace('.*', 'ph', $str);
			$rule = preg_quote($rule, '/');
			$rule = str_replace('ph', '\.[0-9]{1,3}', $rule);
			return $rule;
		}, $list);
	}

	/**
	 * 校验ip是否在ip_list中
	 * $rules = [
	 * 		'127.0.0.1',
	 * 		'192.168.1.*',
	 * 		'192.168.*.1',
	 * ]
	 * @param string $ip
	 * @param array $ip_list
	 * @return bool
	 */
	public static function check($ip, $ip_list)
	{
		$rules = Iptables::format($ip_list);
		$rule = implode('|', $rules);
		if (preg_match('/^'.$rule.'$/', $ip))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}