<?php

namespace Simple\Exception;

use Simple\Error;
use Simple\Exception;

/**
 * Class Not_Found
 * 404 Error
 * 抛出此异常 直接进入用户自定义的函数中，无定义则直接结束
 * @package Simple\Exception
 */
class Not_Found extends Exception
{
	/**
	 * 调用Error的not_found方法
	 * @author simple
	 */
	public function handle()
	{
		Error::not_found($this);
	}
}