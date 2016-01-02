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

use ICanBoogie\Accessor\AccessorTrait;

/**
 * Representation of a multipart content type.
 *
 * @property-read string $boundary The encapsulation boundary.
 * @property-read int $length Length of the rendered mutipart.
 *
 * @see http://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
 */
class Multipart implements \ArrayAccess, \Countable, \IteratorAggregate
{
	use AccessorTrait;

	/**
	 * The encapsulation boundary.
	 *
	 * @var string
	 */
	protected $boundary;

	/**
	 * Returns the encapsulation boundary.
	 *
	 * @return string
	 */
	protected function get_boundary()
	{
		return $this->boundary;
	}

	/**
	 * Parts of the multipart.
	 *
	 * @var Response[]
	 */
	protected $parts = [];

	/**
	 * Computes the length of the rendered mutipart.
	 *
	 * @return int
	 */
	protected function get_length()
	{
		$l = 0;

		$n = count($this->parts);

		if (!$n)
		{
			return 0;
		}

		foreach ($this->parts as $part)
		{
			$headers = $part->headers;
			$pl = $headers['Content-Length'];

			if ($pl)
			{
				$pl += strlen((string) $headers) + 2;
			}
			else
			{
				$pl = strlen(strstr((string) $part, "\r\n")) - 2;
			}

			$l += $pl;
		}

		return $l
		+ (strlen($this->boundary) * ($n + 1)) // boundaries + closing boundary
		+ ($n * 4)                             // \r\n\r\n
		+ 2                                    // --
		;
	}

	/**
	 * @param array $parts
	 * @param string|null $boundary
	 */
	public function __construct(array $parts = [], $boundary = null)
	{
		$this->boundary = $boundary ?: uniqid('---');

		foreach ($parts as $id => $part)
		{
			$this[$id] = $part;
		}
	}

	public function offsetExists($id)
	{
		return isset($this->parts[$id]);
	}

	public function offsetGet($id)
	{
		return $this->parts[$id];
	}

	public function offsetSet($id, $part)
	{
		if (!($part instanceof Response))
		{
			$part = new Response($part);
		}

		if ($id === null || is_numeric($id))
		{
			$this->parts[] = $part;
		}
		else
		{
			$this->parts[$id] = $part;
		}
	}

	public function offsetUnset($id)
	{
		unset($this->parts[$id]);
	}

	public function count()
	{
		return count($this->parts);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->parts);
	}

	public function __toString()
	{
		$boundary = $this->boundary;

		$rc = '';

		foreach ($this->parts as $part)
		{
			$rc .= $boundary;
			$rc .= strstr((string) $part, "\r\n"); // Removes HTTP status line, but keeps its "\r\n"
			$rc .= "\r\n";
		}

		if ($this->parts)
		{
			$rc .= "{$boundary}--";
		}

		return $rc;
	}
}
