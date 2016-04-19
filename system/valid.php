<?php defined('DOCROOT') OR die('No direct script access.');

class Valid
{
	//系统的empty会判断0和'0'，此处只判断NULL，false，''，array()
	public static function not_empty($value)
	{
		return ! in_array($value, array(NULL, FALSE, '', array()), TRUE);
	}

	//执行正则匹配
	public static function regex($value, $expression)
	{
		return (bool) preg_match($expression, (string) $value);
	}

	//最小值
	public static function min_length($value, $length)
	{
		return mb_strlen($value, 'UTF-8') >= $length;
	}

	//最大值
	public static function max_length($value, $length)
	{
		return mb_strlen($value, 'UTF-8') <= $length;
	}

	//长度等于某值，$length可以为数组多个值
	public static function exact_length($value, $length)
	{
		if (is_array($length))
		{
			foreach ($length as $strlen)
			{
				if (mb_strlen($value, 'UTF-8') === $strlen)
					return TRUE;
			}
			return FALSE;
		}

		return mb_strlen($value, 'UTF-8') === $length;
	}

	//值等于某值
	public static function equals($value, $required)
	{
		return ($value === $required);
	}

	//email strict为RFC兼容
	public static function email($email, $strict = FALSE)
	{
		if (mb_strlen($email, 'UTF-8') > 254)
		{
			return FALSE;
		}

		if ($strict === TRUE)
		{
			$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
			$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
			$atom  = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
			$pair  = '\\x5c[\\x00-\\x7f]';

			$domain_literal = "\\x5b($dtext|$pair)*\\x5d";
			$quoted_string  = "\\x22($qtext|$pair)*\\x22";
			$sub_domain     = "($atom|$domain_literal)";
			$word           = "($atom|$quoted_string)";
			$domain         = "$sub_domain(\\x2e$sub_domain)*";
			$local_part     = "$word(\\x2e$word)*";

			$expression     = "/^$local_part\\x40$domain$/D";
		}
		else
		{
			$expression = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})$/iD';
		}

		return (bool) preg_match($expression, (string) $email);
	}

	//检验email的域名是否合法 checkdnsrr 5.3+可用
	public static function email_domain($email)
	{
		if ( ! Valid::not_empty($email))
			return FALSE; // Empty fields cause issues with checkdnsrr()

		// Check if the email domain has a valid MX record
		return (bool) checkdnsrr(preg_replace('/^[^@]++@/', '', $email), 'MX');
	}

	//url类型
	public static function url($url)
	{
		// Based on http://www.apps.ietf.org/rfc/rfc1738.html#sec-5
		if ( ! preg_match(
			'~^

			# scheme
			[-a-z0-9+.]++://

			# username:password (optional)
			(?:
				    [-a-z0-9$_.+!*\'(),;?&=%]++   # username
				(?::[-a-z0-9$_.+!*\'(),;?&=%]++)? # password (optional)
				@
			)?

			(?:
				# ip address
				\d{1,3}+(?:\.\d{1,3}+){3}+

				| # or

				# hostname (captured)
				(
					     (?!-)[-a-z0-9]{1,63}+(?<!-)
					(?:\.(?!-)[-a-z0-9]{1,63}+(?<!-)){0,126}+
				)
			)

			# port (optional)
			(?::\d{1,5}+)?

			# path (optional)
			(?:/.*)?

			$~iDx', $url, $matches))
			return FALSE;

		// We matched an IP address
		if ( ! isset($matches[1]))
			return TRUE;

		// Check maximum length of the whole hostname
		// http://en.wikipedia.org/wiki/Domain_name#cite_note-0
		if (strlen($matches[1]) > 253)
			return FALSE;

		// An extra check for the top level domain
		// It must start with a letter
		$tld = ltrim(substr($matches[1], (int) strrpos($matches[1], '.')), '.');
		return ctype_alpha($tld[0]);
	}

	/**
	 * Validate an IP.
	 *
	 * @param   string  $ip             IP address
	 * @param   boolean $allow_private  allow private IP networks
	 * @return  boolean
	 */
	public static function ip($ip, $allow_private = TRUE)
	{
		// Do not allow reserved addresses
		$flags = FILTER_FLAG_NO_RES_RANGE;

		if ($allow_private === FALSE)
		{
			// Do not allow private or reserved addresses
			$flags = $flags | FILTER_FLAG_NO_PRIV_RANGE;
		}

		return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
	}

	/**
	 * Checks if a phone number is valid.
	 *
	 * @param   string  $number     phone number to check
	 * @param   array   $lengths
	 * @return  boolean
	 */
	public static function phone($number, $lengths = NULL)
	{
		if ( ! is_array($lengths))
		{
			$lengths = array(7,10,11);
		}

		// Remove all non-digit characters from the number
		$number = preg_replace('/\D+/', '', $number);

		// Check if the number is within range
		return in_array(strlen($number), $lengths);
	}

	//时间
	public static function date($str)
	{
		return (strtotime($str) !== FALSE);
	}

	/**
	 * 检查字符串是否仅仅为字母
	 *
	 * @param   string  $str    input string
	 * @param   boolean $utf8   trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha($str, $utf8 = FALSE)
	{
		$str = (string) $str;

		if ($utf8 === TRUE)
		{
			return (bool) preg_match('/^\pL++$/uD', $str);
		}
		else
		{
			return ctype_alpha($str);
		}
	}

	/**
	 * 检查字符串是否仅仅为字母和数字
	 *
	 * @param   string  $str    input string
	 * @param   boolean $utf8   trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_numeric($str, $utf8 = FALSE)
	{
		if ($utf8 === TRUE)
		{
			return (bool) preg_match('/^[\pL\pN]++$/uD', $str);
		}
		else
		{
			return ctype_alnum($str);
		}
	}

	/**
	 * 检查字符串是否仅仅为字母和数字、下划线_、破折号-
	 *
	 * @param   string  $str    input string
	 * @param   boolean $utf8   trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_dash($str, $utf8 = FALSE)
	{
		if ($utf8 === TRUE)
		{
			$regex = '/^[-\pL\pN_]++$/uD';
		}
		else
		{
			$regex = '/^[-a-z0-9_]++$/iD';
		}

		return (bool) preg_match($regex, $str);
	}

	/**
	 * 只包含数字（过滤小数点和负数）
	 *
	 * @param   string  $str    input string
	 * @param   boolean $utf8   trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function digit($str, $utf8 = FALSE)
	{
		if ($utf8 === TRUE)
		{
			return (bool) preg_match('/^\pN++$/uD', $str);
		}
		else
		{
			return (is_int($str) AND $str >= 0) OR ctype_digit($str);
		}
	}

	/**
	 * 有效数字
	 *
	 * Uses {@link http://www.php.net/manual/en/function.localeconv.php locale conversion}
	 * to allow decimal point to be locale specific.
	 *
	 * @param   string  $str    input string
	 * @return  boolean
	 */
	public static function numeric($str)
	{
		// Get the decimal point for the current locale
		list($decimal) = array_values(localeconv());

		// A lookahead is used to make sure the string contains at least one digit (before or after the decimal point)
		return (bool) preg_match('/^-?+(?=.*[0-9])[0-9]*+'.preg_quote($decimal).'?+[0-9]*+$/D', (string) $str);
	}

	/**
	 * Checks if a string is a proper decimal format. Optionally, a specific
	 * number of digits can be checked too.
	 *
	 * @param   string  $str    number to check
	 * @param   integer $places number of decimal places
	 * @param   integer $digits number of digits
	 * @return  boolean
	 */
	public static function decimal($str, $places = 2, $digits = NULL)
	{
		if ($digits > 0)
		{
			// Specific number of digits
			$digits = '{'.( (int) $digits).'}';
		}
		else
		{
			// Any number of digits
			$digits = '+';
		}

		// Get the decimal point for the current locale
		list($decimal) = array_values(localeconv());

		return (bool) preg_match('/^[+-]?[0-9]'.$digits.preg_quote($decimal).'[0-9]{'.( (int) $places).'}$/D', $str);
	}

	/**
	 * Checks if a string is a proper hexadecimal HTML color value. The validation
	 * is quite flexible as it does not require an initial "#" and also allows for
	 * the short notation using only three instead of six hexadecimal characters.
	 *
	 * @param   string  $str    input string
	 * @return  boolean
	 */
	public static function color($str)
	{
		return (bool) preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $str);
	}

	//$a['b'] == $a['c'] 判断两个key下的值是否全等
	public static function matches($array, $field, $match)
	{
		return ($array[$field] === $array[$match]);
	}

}
