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
 * See: {@link is_valid()}.
 */
class File
{
	use \ICanBoogie\PrototypeTrait;

	static protected $types = [

		'.doc'  => 'application/msword',
		'.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'.gif'  => 'image/gif',
		'.jpg'  => 'image/jpeg',
		'.jpeg' => 'image/jpeg',
		'.js'   => 'application/javascript',
		'.mp3'  => 'audio/mpeg',
		'.odt'  => 'application/vnd.oasis.opendocument.text',
		'.pdf'  => 'application/pdf',
		'.php'  => 'application/x-php',
		'.png'  => 'image/png',
		'.psd'  => 'application/psd',
		'.rar'  => 'application/rar',
		'.txt'  => 'text/plain',
		'.zip'  => 'application/zip',
		'.xls'  => 'application/vnd.ms-excel',
		'.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

	];

	static protected $types_alias = [

		'text/x-php' => 'application/x-php'

	];

	static public function from($properties_or_name)
	{
		$properties = [];

		if (is_string($properties_or_name))
		{
			$properties = isset($_FILES[$properties_or_name])
			? $_FILES[$properties_or_name]
			: [ 'name' => $properties_or_name];
		}
		else if (is_array($properties_or_name))
		{
			$properties = $properties_or_name;
		}

		return new static($properties);
	}

	/**
	 * Resolve the MIME type of a file.
	 *
	 * @param string $pathname Pathname to the file.
	 * @param string $extension The variable passed by reference recieves the extension
	 * of the file.
	 *
	 * @return string The MIME type of the file, or 'application/octet-stream' if it could not
	 * be determined.
	 */
	static public function resolve_type($pathname, &$extension=null)
	{
		$extension = '.' . strtolower(pathinfo($pathname, PATHINFO_EXTENSION));

		if (file_exists($pathname) && extension_loaded('fileinfo'))
		{
			$fi = new \finfo(FILEINFO_MIME_TYPE);
			$type = $fi->file($pathname);

			if ($type)
			{
				return isset(self::$types_alias[$type]) ? self::$types_alias[$type] : $type;
			}
		}

		if (isset(self::$types[$extension]))
		{
			return self::$types[$extension];
		}

		return 'application/octet-stream';
	}

	/**
	 * Format a message.
	 *
	 * @return \ICanBoogie\I18n\FormattedString|\ICanBoogie\FormattedString|string
	 */
	static private function format($format, array $args=[], array $options=[])
	{
		if (class_exists('ICanBoogie\I18n\FormattedString', true))
		{
			return new \ICanBoogie\I18n\FormattedString($format, $args, $options);
		}

		if (class_exists('ICanBoogie\FormattedString', true))
		{
			return new \ICanBoogie\FormattedString($format, $args, $options);
		}

		return \ICanBoogie\format($format, $args, $options);
	}

	/*
	 * Instance
	 */

	protected $name;

	/**
	 * Returns the name of the file.
	 *
	 * @return string
	 */
	protected function get_name()
	{
		return $this->name;
	}

	/**
	 * Returns the name of the file, without its extension.
	 *
	 * @return string
	 */
	protected function get_unsuffixed_name()
	{
		return basename($this->name, $this->extension);
	}

	protected $type;

	/**
	 * Returns the type of the file.
	 *
	 * If the {@link $type} property was not defined during construct, the type
	 * is guessed from the name or the pathname of the file.
	 *
	 * @return string|null The MIME type of the file, or `null` if it cannot be determined.
	 */
	protected function get_type()
	{
		if (!empty($this->type))
		{
			return $this->type;
		}

		if (!$this->pathname && !$this->tmp_name)
		{
			return;
		}

		return self::resolve_type($this->pathname ?: $this->tmp_name);
	}

	protected $size;

	/**
	 * Returns the size of the file.
	 *
	 * If the {@link $size} property was not defined during construct, the size
	 * is guessed using the pathname of the file. If the pathname is not available the method
	 * returns `null`.
	 *
	 * @return int|null The size of the file or `null` if it cannot be determined.
	 */
	protected function get_size()
	{
		if (!empty($this->size))
		{
			return $this->size;
		}

		if ($this->pathname)
		{
			return filesize($this->pathname);
		}
	}

	protected $tmp_name;

	protected $error;

	/**
	 * Check if the file is valid.
	 *
	 * A file is considered valid if it has no error code, if it has a size,
	 * if it has either a temporary name or a pathname and that the file actually exists.
	 *
	 * @return boolean `true` if the file is valid, `false` otherwise.
	 */
	protected function get_is_valid()
	{
		return !$this->error
		&& $this->size
		&& ($this->tmp_name || ($this->pathname && file_exists($this->pathname)));
	}

	protected $pathname;

	/**
	 * Return the pathname of the file.
	 *
	 * Note: If the {@link $pathname} property is empty, the {@link $tmp_name} property
	 * is returned.
	 *
	 * @return string
	 */
	protected function get_pathname()
	{
		return $this->pathname ?: $this->tmp_name;
	}

	protected function __construct(array $properties)
	{
		static $initial_properties = [ 'name', 'type', 'size', 'tmp_name', 'error', 'pathname' ];

		foreach ($properties as $property => $value)
		{
			if (!in_array($property, $initial_properties))
			{
				continue;
			}

			$this->$property = $value;
		}

		if (!$this->name && $this->pathname)
		{
			$this->name = basename($this->pathname);
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
	 * Returns the error code.
	 *
	 * @return string
	 */
	protected function get_error()
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

				return;

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

				return $this->format("An error has occured.");
		}
	}

	/**
	 * Returns the extension of the file, if any.
	 *
	 * Note: The extension includes the dot e.g. ".zip". The extension if always in lower case.
	 *
	 * @return string|null
	 */
	protected function get_extension()
	{
		$extension = pathinfo($this->name, PATHINFO_EXTENSION);

		if (!$extension)
		{
			return;
		}

		return '.' . strtolower($extension);
	}

	/**
	 * Check if a file is uploaded.
	 *
	 * @return boolean `true` if the file is uploaded, `false` otherwise.
	 */
	protected function get_is_uploaded()
	{
		return $this->tmp_name && is_uploaded_file($this->tmp_name);
	}

	/**
	 * Check if the file matches a MIME class, a MIME type, or a file extension.
	 *
	 * @param string|array $type The type can be a MIME class (e.g. "image"),
	 * a MIME type (e.g. "image/png"), or an extensions (e.g. ".zip"). An array can be used to
	 * check if a file matches multiple type e.g. `[ "image", ".mp3" ]`, which matches any type
	 * of image or files with the ".mp3" extension.
	 *
	 * @return boolean `true` if the file matches, `false` otherwise.
	 */
	public function match($type)
	{
		if (is_array($type))
		{
			$type_list = $type;

			foreach ($type_list as $type)
			{
				if ($this->match($type))
				{
					return true;
				}
			}

			return false;
		}

		if ($type{0} === '.')
		{
			return $type === $this->extension;
		}

		if (strpos($type, '/') === false)
		{
			return (bool) preg_match('#^' . preg_quote($type) . '/#', $this->type);
		}

		return $type === $this->type;
	}

	/**
	 * Move the file.
	 *
	 * @param string $destination Pathname to the destination file.
	 * @param string $overwrite If `true` the destination file is deleted before the file is move.
	 *
	 * @throws \Exception if the file failed to be moved.
	 */
	public function move($destination, $overwrite=false)
	{
		if (file_exists($destination))
		{
			if (!$overwrite)
			{
				throw new \Exception("The destination file already exists: $destination.");
			}

			unlink($destination);
		}

		if ($this->pathname)
		{
			if (!rename($this->pathname, $destination))
			{
				throw new \Exception("Unable to move file to destination: $destination.");
			}
		}
		else
		{
			if (!move_uploaded_file($this->tmp_name, $destination))
			{
				throw new \Exception("Unable to move file to destination: $destination.");
			}
		}

		$this->pathname = $destination;
	}
}