<?php

namespace Simple\Exception;

use Simple\Error;
use Simple\Exception;

/**
 * Class Method_Not_Allowed
 * 405 Error
 * 抛出此异常 直接进入用户自定义的函数中，无定义则直接结束
 * @package Simple\Exception
 */
class Method_Not_Allowed extends Exception
{
	/**
	 * 调用Error的method_not_allowed方法
	 * @author simple
	 */
	public function handle()
	{
		Error::method_not_allowed($this);
	}
}