<?php

namespace Simple\Exception;

use Simple\Exception;

/**
 * Class NotFoundException
 * 404 Error
 * 抛出此异常 直接进入用户自定义的函数中，无定义则直接结束
 * @package Simple\Exception
 */
class NotFoundException extends Exception
{
}