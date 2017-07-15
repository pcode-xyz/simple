<?php
echo $message . '['.$code.']'. "\n";
$key = 0;
foreach ($trace as $value)
{
	echo '#'.(++$key)."\t".\Simple\Arr::get($value, 'file', 'not_found').'['.\Simple\Arr::get($value, 'line', 0)."]\n";
	echo "\t".\Simple\Arr::get($value, 'class', '').\Simple\Arr::get($value, 'type', '').\Simple\Arr::get($value, 'function', '').'('.$value['arg'].')'."\n";
}