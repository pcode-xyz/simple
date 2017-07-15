<?php

namespace Simple;

/**
 * Class Exception
 * Simple框架默认异常
 * @package Simple
 */
class Exception extends \Exception
{
	/**
	 * 调用Error的default_handle处理根异常
	 * @author simple
	 */
	public function handle()
	{
		Error::default_handle($this);
	}
}
