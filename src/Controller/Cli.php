<?php

namespace Simple\Controller;

use Simple\Controller;
use Simple\Exception\Service_Error;
use Simple\Route;

/**
 * Class Cli
 * 需要以命令行方式执行的controller
 * @package Simple\Controller
 */
class Cli extends Controller
{
	/**
	 * Cli constructor.
	 * 判断当前是否为CLI环境 不是则抛出异常
	 */
	public function __construct()
	{
		if (!CLI_MODE)
		{
			throw new Service_Error('['.Route::$view.'] Cli Mode Only');
		}
	}

	/**
	 * 输出当前controller的所有可执行方法
	 */
	public function action_help()
	{
		echo "\n";
		echo "This is Simple version " . SIMPLE_VERSION . "\n\n";
		echo "The following commands are available: \n\n";

		$class = new \ReflectionClass($this);

		$class_name = $class->getName();
		$shot_name = $class->getShortName();

		$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

		$max_length = 0;
		$functions = [];

		foreach ($methods as $method)
		{
			if ($method->class == $class_name && strpos($method->name, 'action_') === 0)
			{
				$function = new \ReflectionMethod($method->class, $method->name);

				$doc = $function->getDocComment();
				$doc = str_replace("/*", "", $doc);
				$doc = str_replace("*/", "", $doc);
				$doc = str_replace("*", "", $doc);
				$doc = str_replace("\n", " ", $doc);

				$temp = [
					'name'	=> str_replace('_', '-', strtolower($shot_name.'/'.substr($method->name, 7))),
					'doc'	=> trim($doc),
				];
				$max_length = max($max_length, strlen($temp['name']));
				$functions[] = $temp;
			}
		}

		foreach ($functions as $function)
		{
			echo '    '.str_pad($function['name'], $max_length+(4-$max_length % 4)+8).$function['doc']."\n";
		}

		echo "\nend.\n\n";
	}

}
