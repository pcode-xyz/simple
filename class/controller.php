<?php

class Controller
{
	public function before()
	{
		// Nothing by default
	}

	public function after()
	{
		// Nothing by default
	}

	//过滤器
	public function _filter($data, $key, $filter, $default)
	{
		if (isset($data[$key]))
		{
			$data = $data[$key];
			$filters = explode(',', $filter);
			foreach($filters as $filter)
			{
				if (function_exists($filter))
				{
					$data = is_array($data) ? array_map($filter, $data) : $filter($data); // 参数过滤
				}
			}
			return $data;
		}
		else
		{
			return $default;
		}
	}

	//处理GET参数
	public function _get($key, $filter = 'trim', $default = null)
	{
		return $this->_filter($_GET, $key, $filter, $default);
	}

	//处理post参数
	public function _post($key, $filter = 'trim', $default = null)
	{
		return $this->_filter($_POST, $key, $filter, $default);
	}
	
	public function _r($key, $filter = 'trim', $default = null)
	{
		if (isset($_GET[$key]))
		{
			return $this->_get($key, $filter, $default);
		}
		else
		{
			return $this->_post($key, $filter, $default);
		}
	}

} // End Controller
