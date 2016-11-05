<?php

namespace ICanBoogie\HTTP\Headers;

class Accept extends Header
{
	const ITEM_SEPARATOR_REGEX = '/\s*(?:,*("[^"]+"),*|,*(\'[^\']+\'),*|,+)\s*/';

	/**
	 * @inheritdoc
	 */
	static protected function parse($source)
	{
		$source = self::ensure_source_is_a_string($source);
		$index = 0;

		return new static(array_map(function ($item_source) use (&$index) {

			AcceptHeaderItem::from($item_source);

			/*
			$item = AcceptHeaderItem::fromString($itemValue);
			$item->setIndex($index++);
			return $item;
			*/

		}, preg_split(self::ITEM_SEPARATOR_REGEX, $source, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)));
	}
}
