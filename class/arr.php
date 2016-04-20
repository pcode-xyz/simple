<?php

class Arr
{
	public static function get($array, $key, $default = null)
	{
		return isset($array[$key]) ? $array[$key] : $default;
	}
}