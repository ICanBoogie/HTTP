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

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

use function count;

/**
 * Representation of a list of request files.
 *
 * @implements ArrayAccess<string, File>
 * @implements IteratorAggregate<string, File>
 */
class FileList implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Creates a {@link FileList} instance.
     *
     * @param array|FileList|null $files
     *
     * @return FileList
     */
    public static function from(array|FileList|null $files): self
    {
        if ($files instanceof self) {
            return clone $files;
        }

        if (!$files) {
            return new self([]);
        }

        foreach ($files as &$file) {
            $file = File::from($file);
        }

        return new self($files);
    }

    /**
     * @var File[]
     */
    private array $list = [];

    /**
     * @param array $files
     */
    public function __construct(array $files = [])
    {
        foreach ($files as $id => $file) {
            $this[$id] = $file;
        }
    }

    /**
     * Checks if a file exists.
     *
     * @param mixed $offset File identifier.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->list[$offset]);
    }

    /**
     * Returns a file.
     *
     * @param mixed $offset File identifier.
     *
     * @return File|null A {@link File} instance, or `null` if it does not exist.
     */
    public function offsetGet(mixed $offset): ?File
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->list[$offset];
    }

    /**
     * Adds a file.
     *
     * @param mixed $offset File identifier.
     * @param mixed|array|File|FileList $value File.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!($value instanceof File || $value instanceof FileList)) {
            $value = File::from($value);
        }

        $this->list[$offset] = $value;
    }

    /**
     * Removes a file.
     *
     * @param mixed $offset File identifier.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->list[$offset]);
    }

    /**
     * @inheritdoc
     *
     * @return ArrayIterator<string, File>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->list);
    }

    /**
     * Returns the number of files in the collection.
     *
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->list);
    }
}
