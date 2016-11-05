<?php

namespace ICanBoogie\HTTP\Headers;

class AcceptHeaderItem
{
	static public function from($source)
	{
		$bits = preg_split('/\s*(?:;*("[^"]+");*|;*(\'[^\']+\');*|;+)\s*/', $source, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		var_dump($bits);
	}
}
