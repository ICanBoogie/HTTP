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
use ICanBoogie\ToArray;

use function ICanBoogie\format;

/**
 * Representation of a POST file.
 *
 * @property-read string $name Name of the file.
 * @property-read string $type MIME type of the file.
 * @property-read string $size Size of the file.
 * @property-read string $error Error code, one of `UPLOAD_ERR_*`.
 * @property-read string $error_message A formatted message representing the error.
 * @property-read string $pathname Pathname of the file.
 * @property-read string $extension The extension of the file. If any, the dot is included e.g.
 * ".zip".
 * @property-read string $unsuffixed_name The name of the file without its extension.
 * @property-read bool $is_uploaded `true` if the file is uploaded, `false` otherwise.
 * @property-read bool $is_valid `true` if the file is valid, `false` otherwise.
 * See: {@link get_is_valid()}.
 */
class File implements ToArray, FileOptions
{
	use AccessorTrait;

	public const MOVE_OVERWRITE = true;
	public const MOVE_NO_OVERWRITE = false;

	private const INITIAL_PROPERTIES = [

		self::OPTION_NAME,
		self::OPTION_TYPE,
		self::OPTION_SIZE,
		self::OPTION_TMP_NAME,
		self::OPTION_ERROR,
		self::OPTION_PATHNAME

	];

	/**
	 * Creates a {@link File} instance.
	 *
	 * @param array|string $properties_or_name An array of properties or a file identifier.
	 *
	 * @return File
	 */
	static public function from($properties_or_name): File
	{
		$properties = [];

		if (\is_string($properties_or_name))
		{
			$properties = isset($_FILES[$properties_or_name])
			? $_FILES[$properties_or_name]
			: [ self::OPTION_NAME => \basename($properties_or_name) ];
		}
		else if (\is_array($properties_or_name))
		{
			$properties = $properties_or_name;
		}

		$properties = self::filter_initial_properties($properties);

		return new static($properties);
	}

	/**
	 * Keeps only initial properties.
	 *
	 * @param array $properties
	 *
	 * @return array
	 */
	static private function filter_initial_properties(array $properties): array
	{
		return \array_intersect_key($properties, \array_fill_keys(self::INITIAL_PROPERTIES, true));
	}

	/**
	 * Format a string.
	 *
	 * @param string $format The format of the string.
	 * @param array $args The arguments.
	 * @param array $options Some options.
	 *
	 * @return \ICanBoogie\FormattedString|\ICanBoogie\I18n\FormattedString|string
	 */
	static private function format($format, array $args = [], array $options = [])
	{
		if (\class_exists(\ICanBoogie\I18n\FormattedString::class, true))
		{
			return new \ICanBoogie\I18n\FormattedString($format, $args, $options); // @codeCoverageIgnore
		}

		if (\class_exists(\ICanBoogie\FormattedString::class, true))
		{
			return new \ICanBoogie\FormattedString($format, $args, $options);
		}

		return format($format, $args); // @codeCoverageIgnore
	}

	/*
	 * Instance
	 */

	/**
	 * Name of the file.
	 *
	 * @var string|null
	 */
	private $name;

	protected function get_name(): ?string
	{
		return $this->name;
	}

	protected function get_unsuffixed_name(): ?string
	{
		return $this->name ? \basename($this->name, $this->extension) : null;
	}

	private $type;

	/**
	 * Returns the type of the file.
	 *
	 * If the {@link $type} property was not defined during construct, the type
	 * is guessed from the name or the pathname of the file.
	 *
	 * @return string|null The MIME type of the file, or `null` if it cannot be determined.
	 */
	protected function get_type(): ?string
	{
		if (!empty($this->type))
		{
			return $this->type;
		}

		if (!$this->pathname && !$this->tmp_name)
		{
			return null;
		}

		return FileInfo::resolve_type($this->pathname ?: $this->tmp_name);
	}

	private $size;

	/**
	 * Returns the size of the file.
	 *
	 * If the {@link $size} property was not defined during construct, the size
	 * is guessed using the pathname of the file. If the pathname is not available the method
	 * returns `null`.
	 *
	 * @return int|false The size of the file or `false` if it cannot be determined.
	 */
	protected function get_size()
	{
		if (!empty($this->size))
		{
			return $this->size;
		}

		if ($this->pathname)
		{
			return \filesize($this->pathname);
		}

		return null;
	}

	private $tmp_name;

	/**
	 * @var int|null
	 */
	private $error;

	protected function get_error(): ?int
	{
		return $this->error;
	}

	/**
	 * Returns the message associated with the error.
	 *
	 * @return \ICanBoogie\I18n\FormattedString|\ICanBoogie\FormattedString|string|null
	 */
	protected function get_error_message()
	{
		switch ($this->error)
		{
			case UPLOAD_ERR_OK:

				return null;

			case UPLOAD_ERR_INI_SIZE:

				return $this->format("Maximum file size is :size Mb", [ ':size' => (int) ini_get('upload_max_filesize') ]);

			case UPLOAD_ERR_FORM_SIZE:

				return $this->format("Maximum file size is :size Mb", [ ':size' => 'MAX_FILE_SIZE' ]);

			case UPLOAD_ERR_PARTIAL:

				return $this->format("The uploaded file was only partially uploaded.");

			case UPLOAD_ERR_NO_FILE:

				return $this->format("No file was uploaded.");

			case UPLOAD_ERR_NO_TMP_DIR:

				return $this->format("Missing a temporary folder.");

			case UPLOAD_ERR_CANT_WRITE:

				return $this->format("Failed to write file to disk.");

			case UPLOAD_ERR_EXTENSION:

				return $this->format("A PHP extension stopped the file upload.");

			default:

				return $this->format("An error has occurred.");
		}
	}

	/**
	 * Whether the file is valid.
	 *
	 * A file is considered valid if it has no error code, if it has a size,
	 * if it has either a temporary name or a pathname and that the file actually exists.
	 *
	 * @return boolean `true` if the file is valid, `false` otherwise.
	 */
	protected function get_is_valid(): bool
	{
		return !$this->error
		&& $this->size
		&& ($this->tmp_name || ($this->pathname && \file_exists($this->pathname)));
	}

	/**
	 * @var string|null
	 */
	private $pathname;

	protected function get_pathname(): ?string
	{
		return $this->pathname ?: $this->tmp_name;
	}

	protected function __construct(array $properties)
	{
		foreach ($properties as $property => $value)
		{
			$this->$property = $value;
		}

		if (!$this->name && $this->pathname)
		{
			$this->name = \basename($this->pathname);
		}

		if (empty($this->type))
		{
			unset($this->type);
		}

		if (empty($this->size))
		{
			unset($this->size);
		}
	}

	/**
	 * Returns an array representation of the instance.
	 *
	 * The following properties are exported:
	 *
	 * - {@link $name}
	 * - {@link $unsuffixed_name}
	 * - {@link $extension}
	 * - {@link $type}
	 * - {@link $size}
	 * - {@link $pathname}
	 * - {@link $error}
	 * - {@link $error_message}
	 *
	 * @return array
	 */
	public function to_array(): array
	{
		$error_message = $this->error_message;

		if ($error_message !== null)
		{
			$error_message = (string) $error_message;
		}

		return [

			'name' => $this->name,
			'unsuffixed_name' => $this->unsuffixed_name,
			'extension' => $this->extension,
			'type' => $this->type,
			'size' => $this->size,
			'pathname' => $this->pathname,
			'error' => $this->error,
			'error_message' => $error_message

		];
	}

	/**
	 * Returns the extension of the file, if any.
	 *
	 * **Note:** The extension includes the dot e.g. ".zip". The extension is always in lower case.
	 *
	 * @return string|null
	 */
	protected function get_extension(): ?string
	{
		$extension = \pathinfo($this->name, PATHINFO_EXTENSION);

		if (!$extension)
		{
			return null;
		}

		return '.' . \strtolower($extension);
	}

	/**
	 * Checks if a file is uploaded.
	 *
	 * @return boolean `true` if the file is uploaded, `false` otherwise.
	 */
	protected function get_is_uploaded(): bool
	{
		return $this->tmp_name && \is_uploaded_file($this->tmp_name);
	}

	/**
	 * Checks if the file matches a MIME class, a MIME type, or a file extension.
	 *
	 * @param string|array $type The type can be a MIME class (e.g. "image"),
	 * a MIME type (e.g. "image/png"), or an extensions (e.g. ".zip"). An array can be used to
	 * check if a file matches multiple type e.g. `[ "image", ".mp3" ]`, which matches any type
	 * of image or files with the ".mp3" extension.
	 *
	 * @return bool `true` if the file matches (or `$type` is empty), `false` otherwise.
	 */
	public function match($type): bool
	{
		if (!$type)
		{
			return true;
		}

		if (\is_array($type))
		{
			return $this->match_multiple($type);
		}

		if ($type[0] === '.')
		{
			return $type === $this->extension;
		}

		if (\strpos($type, '/') === false)
		{
			return (bool) \preg_match('#^' . \preg_quote($type) . '/#', $this->type);
		}

		return $type === $this->type;
	}

	/**
	 * Checks if the file matches one of the types in the list.
	 *
	 * @param array $type_list
	 *
	 * @return bool `true` if the file matches, `false` otherwise.
	 */
	private function match_multiple(array $type_list): bool
	{
		foreach ($type_list as $type)
		{
			if ($this->match($type))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Moves the file.
	 *
	 * @param string $destination Pathname to the destination file.
	 * @param bool $overwrite Use {@link MOVE_OVERWRITE} to delete the destination before the file
	 * is moved. Defaults to {@link MOVE_NO_OVERWRITE}.
	 *
	 * @throws \Throwable if the file failed to be moved.
	 */
	public function move($destination, $overwrite = self::MOVE_NO_OVERWRITE): void
	{
		if (\file_exists($destination))
		{
			if (!$overwrite)
			{
				throw new \Exception("The destination file already exists: $destination.");
			}

			\unlink($destination);
		}

		if ($this->pathname)
		{
			if (!\rename($this->pathname, $destination))
			{
				throw new \Exception("Unable to move file to destination: $destination.");  // @codeCoverageIgnore
			}
		}
		// @codeCoverageIgnoreStart
		elseif (!\move_uploaded_file($this->tmp_name, $destination))
		{
			throw new \Exception("Unable to move file to destination: $destination.");
		}
		// @codeCoverageIgnoreEnd

		$this->pathname = $destination;
	}
}
