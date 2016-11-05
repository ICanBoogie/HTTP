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
	/**
	 * Creates a {@link FileList} instance.
	 *
	 * @param array|FileList|null $files
	 *
	 * @return FileList
	 */
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

	/**
	 * @var File[]
	 */
	private $list = [];

	/**
	 * @param array $files
	 */
	public function __construct(array $files = [])
	{
		foreach ($files as $id => $file)
		{
			$this[$id] = $file;
		}
	}

	/**
	 * Checks if a file exists.
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function offsetExists($id)
	{
		return isset($this->list[$id]);
	}

	/**
	 * Returns a file.
	 *
	 * @param string $id
	 *
	 * @return File|null A {@link File} instance, or `null` if it does not exists.
	 */
	public function offsetGet($id)
	{
		if (!$this->offsetExists($id))
		{
			return null;
		}

		return $this->list[$id];
	}

	/**
	 * Adds a file.
	 *
	 * @param string $id
	 * @param string|array|File $file
	 */
	public function offsetSet($id, $file)
	{
		if (!($file instanceof File || $file instanceof FileList))
		{
			$file = File::from($file);
		}

		$this->list[$id] = $file;
	}

	/**
	 * Removes a file.
	 *
	 * @param string $id
	 */
	public function offsetUnset($id)
	{
		unset($this->list[$id]);
	}

	/**
	 * @inheritdoc
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->list);
	}

	/**
	 * Returns the number of files in the collection.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->list);
	}
}
