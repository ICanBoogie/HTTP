<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP;

/**
 * Representation of a list of request files.
 */
class FileList implements \ArrayAccess, \IteratorAggregate, \Countable
{
	static public function from($files)
	{
		if ($files instanceof self)
		{
			return clone $files;
		}

		if (!$files)
		{
			return new static([]);
		}

		foreach ($files as &$file)
		{
			$file = File::from($file);
		}

		return new static($files);
	}

	protected $list;

	public function __construct(array $files)
	{
		foreach ($files as $id => $file)
		{
			$this[$id] = $file;
		}
	}

	public function offsetExists($id)
	{
		return isset($this->list[$id]);
	}

	public function offsetGet($id)
	{
		if (!$this->offsetExists($id))
		{
			return;
		}

		return $this->list[$id];
	}

	public function offsetSet($id, $file)
	{
		if (!($file instanceof File || $file instanceof FileList))
		{
			$file = File::from($file);
		}

		$this->list[$id] = $file;
	}

	public function offsetUnset($id)
	{
		unset($this->list[$id]);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->list);
	}

	public function count()
	{
		return count($this->list);
	}
}
