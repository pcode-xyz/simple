<?php

namespace Simple\Exception;

use Simple\Error;
use Simple\Exception;

/**
 * Class Error
 * 500 System Error
 * 抛出此异常 则直接进入定义的500函数中 无定义则自动结束
 * @package Simple\Exception
 */
class Service_Error extends Exception
{
	/**
	 * 调用Error的service_error方法
	 * @author simple
	 */
	public function handle()
	{
		Error::service_error($this);
	}
}