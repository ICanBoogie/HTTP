<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\FormattedString;
use ICanBoogie\ToArray;
use Throwable;

use function array_fill_keys;
use function array_intersect_key;
use function basename;
use function class_exists;
use function file_exists;
use function ICanBoogie\format;
use function is_array;
use function is_string;
use function is_uploaded_file;
use function move_uploaded_file;
use function pathinfo;
use function preg_match;
use function rename;
use function strtolower;
use function unlink;

/**
 * Representation of a POST file.
 */
class File implements ToArray, FileOptions
{
    public const true MOVE_OVERWRITE = true;
    public const false MOVE_NO_OVERWRITE = false;

    private const array INITIAL_PROPERTIES = [

        self::OPTION_NAME,
        self::OPTION_TYPE,
        self::OPTION_SIZE,
        self::OPTION_TMP_NAME,
        self::OPTION_ERROR,
        self::OPTION_PATHNAME,

    ];

    /**
     * Creates a {@see File} instance.
     *
     * @param array|string $properties_or_name An array of properties or a file identifier.
     */
    public static function from(array|string $properties_or_name): File
    {
        $properties = [];

        if (is_string($properties_or_name)) {
            $properties = $_FILES[$properties_or_name]
                ?? [ self::OPTION_NAME => basename($properties_or_name) ];
        } elseif (is_array($properties_or_name)) {
            $properties = $properties_or_name;
        }

        $properties = self::filter_initial_properties($properties);

        return new self($properties);
    }

    /**
     * Keeps only initial properties.
     *
     * @param array $properties
     *
     * @return array
     */
    private static function filter_initial_properties(array $properties): array
    {
        return array_intersect_key($properties, array_fill_keys(self::INITIAL_PROPERTIES, true));
    }

    /**
     * Format a string.
     *
     * @param string $format The format of the string.
     * @param array $args The arguments.
     *
     * @return FormattedString|string
     */
    private static function format(
        string $format,
        array $args = [],
    ): string|FormattedString {
        if (class_exists(FormattedString::class)) {
            return new FormattedString($format, $args);
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
    private(set) ?string $name = null;

    public ?string $unsuffixed_name {
        get => $this->name ? basename($this->name, $this->extension) : null;
    }

    /**
     * The MIME type of the file, or `null` if it can't be determined.
     *
     * If the {@see $type} property wasn't defined during construct, the type
     * is guessed from the name or the pathname of the file.
     */
    private(set) ?string $type {
        get {
            if (!empty($this->type)) {
                return $this->type;
            }

            if (!$this->pathname && !$this->tmp_name) {
                return null;
            }

            return $this->type = FileInfo::resolve_type($this->pathname ?: $this->tmp_name);
        }
    }

    /**
     * The size of the file or `false` if it can't be determined.
     *
     * If the {@see $size} property wasn't defined during construct, the size
     * is guessed using the pathname of the file.
     * If the pathname is not available, the method returns `null`.
     */
    private(set) int|false|null $size {
        get {
            if (!empty($this->size)) {
                return $this->size;
            }

            if ($this->pathname) {
                return \filesize($this->pathname);
            }

            return false;
        }
    }

    private $tmp_name;

    /**
     * Error code, one of `UPLOAD_ERR_*`.
     */
    private(set) ?int $error = null;

    /**
     * Returns the message associated with the error.
     */
    public ?FormattedString $error_message {
        get {
            switch ($this->error) {
                case UPLOAD_ERR_OK:
                    return null;

                case UPLOAD_ERR_INI_SIZE:
                    return $this->format("Maximum file size is :size Mb", [
                        ':size' => (int)ini_get('upload_max_filesize'),
                    ]);

                case UPLOAD_ERR_FORM_SIZE:
                    return $this->format("Maximum file size is :size Mb", [
                        ':size' => 'MAX_FILE_SIZE',
                    ]);

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
    }

    /**
     * Whether the file is valid.
     *
     * A file is considered valid if it has no error code, if it has a size,
     * if it has either a temporary name or a pathname and that the file actually exists.
     */
    public bool $is_valid {
        get {
            return !$this->error
                && $this->size
                && ($this->tmp_name || ($this->pathname && file_exists($this->pathname)));
        }
    }

    public ?string $pathname = null {
        get => $this->pathname ?? $this->tmp_name;
    }

    private function __construct(array $properties)
    {
        foreach ($properties as $property => $value) {
            switch ($property) {
                case self::OPTION_NAME:
                    $this->name = $value;
                    break;
                case self::OPTION_TYPE:
                    $this->type = $value;
                    break;
                case self::OPTION_SIZE:
                    $this->size = $value;
                    break;
                case self::OPTION_TMP_NAME:
                    $this->tmp_name = $value;
                    break;
                case self::OPTION_ERROR:
                    $this->error = $value;
                    break;
                case self::OPTION_PATHNAME:
                    $this->pathname = $value;
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown property: $property");
            }
        }

        if (!$this->name && $this->pathname) {
            $this->name = basename($this->pathname);
        }
    }

    /**
     * Returns an array representation of the instance.
     *
     * The following properties are exported:
     *
     * - {@see $name}
     * - {@see $unsuffixed_name}
     * - {@see $extension}
     * - {@see $type}
     * - {@see $size}
     * - {@see $pathname}
     * - {@see $error}
     * - {@see $error_message}
     */
    public function to_array(): array
    {
        $error_message = $this->error_message;

        if ($error_message !== null) {
            $error_message = (string)$error_message;
        }

        return [

            'name' => $this->name,
            'unsuffixed_name' => $this->unsuffixed_name,
            'extension' => $this->extension,
            'type' => $this->type,
            'size' => $this->size,
            'pathname' => $this->pathname,
            'error' => $this->error,
            'error_message' => $error_message,

        ];
    }

    /**
     * The extension of the file, if any.
     *
     * **Note**: The extension includes the dot e.g. ".zip". The extension is always in lower case.
     */
    public ?string $extension {
        get {
            if (!$this->name) {
                return null;
            }

            $extension = pathinfo($this->name, PATHINFO_EXTENSION);

            if (!$extension) {
                return null;
            }

            return '.' . strtolower($extension);
        }
    }

    /**
     * Whether the file was uploaded.
     */
    public bool $is_uploaded
        {
            get => $this->tmp_name && is_uploaded_file($this->tmp_name);
        }

    /**
     * Checks if the file matches a MIME class, a MIME type, or a file extension.
     *
     * @param array|string|null $type The type can be a MIME class (e.g. "image"),
     * a MIME type (e.g. "image/png"), or an extensions (e.g. ".zip"). An array can be used to
     * check if a file matches multiple type e.g. `[ "image", ".mp3" ]`, which matches any type
     * of image or files with the ".mp3" extension.
     *
     * @return bool `true` if the file matches (or `$type` is empty), `false` otherwise.
     */
    public function match(array|string|null $type): bool
    {
        if (!$type) {
            return true;
        }

        if (is_array($type)) {
            return $this->match_multiple($type);
        }

        if ($type[0] === '.') {
            return $type === $this->extension;
        }

        if (!str_contains($type, '/')) {
            return (bool)preg_match('#^' . \preg_quote($type) . '/#', $this->type);
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
        foreach ($type_list as $type) {
            if ($this->match($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Moves the file.
     *
     * @param string $destination Pathname to the destination file.
     * @param bool $overwrite Use {@see MOVE_OVERWRITE} to delete the destination before the file
     * is moved. Defaults to {@see MOVE_NO_OVERWRITE}.
     *
     * @throws Throwable if the file failed to be moved.
     */
    public function move(string $destination, bool $overwrite = self::MOVE_NO_OVERWRITE): void
    {
        if (file_exists($destination)) {
            if (!$overwrite) {
                throw new \Exception("The destination file already exists: $destination.");
            }

            unlink($destination);
        }

        if ($this->pathname) {
            if (!rename($this->pathname, $destination)) {
                throw new \Exception(
                    "Unable to move file to destination: $destination.",
                );  // @codeCoverageIgnore
            }
        }// @codeCoverageIgnoreStart
        elseif (!move_uploaded_file($this->tmp_name, $destination)) {
            throw new \Exception("Unable to move file to destination: $destination.");
        }
        // @codeCoverageIgnoreEnd

        $this->pathname = $destination;
    }
}
