<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Headers;

use ICanBoogie\Accessor\AccessorTrait;

/**
 * Representation of a header parameter.
 *
 * @property-read string $attribute The attribute of the parameter.
 * @property-read string $charset The charset of the parameter's value.
 *
 * @see http://tools.ietf.org/html/rfc2231
 * @see http://tools.ietf.org/html/rfc5987
 * @see http://greenbytes.de/tech/tc2231/#attwithfn2231utf8
 */
class HeaderParameter
{
	use AccessorTrait;

	/**
	 * Token of the parameter.
	 *
	 * @var string
	 */
	protected $attribute;

    /**
     * @return string
     */
	protected function get_attribute()
	{
		return $this->attribute;
	}

	/**
	 * Value of the parameter.
	 *
	 * @var string
	 */
	public $value;

    /**
     * @return string
     */
	protected function get_charset()
	{
		return mb_detect_encoding($this->value) ?: 'ISO-8859-1';
	}

	/**
	 * Language of the value.
	 *
	 * @var string
	 */
	public $language;

	/**
	 * Creates a {@link HeaderParameter} instance from the provided source.
	 *
	 * @param mixed $source
	 *
	 * @return HeaderParameter
	 */
	static public function from($source)
	{
		if ($source instanceof self)
		{
			return $source;
		}

		$equal_pos = strpos($source, '=');
		$language = null;

		if ($source[$equal_pos - 1] === '*')
		{
			$attribute = substr($source, 0, $equal_pos - 1);
			$value = substr($source, $equal_pos + 1);

			preg_match('#^([a-zA-Z0-9\-]+)?(\'([a-z\-]+)?\')?(")?([^"]+)(")?$#', $value, $matches);

			if ($matches[3])
			{
				$language = $matches[3];
			}

			$value = urldecode($matches[5]);

			if ($matches[1] === 'iso-8859-1')
			{
				$value = utf8_encode($value);
			}
		}
		else
		{
			$attribute = substr($source, 0, $equal_pos);
			$value = substr($source, $equal_pos + 1);

			if ($value[0] === '"')
			{
				$value = substr($value, 1, -1);
			}
		}

		$value = mb_convert_encoding($value, 'UTF-8');

		return new static($attribute, $value, $language);
	}

	/**
	 * Checks if the provided string is a token.
	 *
	 * <pre>
	 * token          = 1*<any CHAR except CTLs or separators>
	 * separators     = "(" | ")" | "<" | ">" | "@"
	 *                | "," | ";" | ":" | "\" | <">
	 *                | "/" | "[" | "]" | "?" | "="
	 *                | "{" | "}" | SP | HT
	 * CHAR           = <any US-ASCII character (octets 0 - 127)>
	 * CTL            = <any US-ASCII control character (octets 0 - 31) and DEL (127)>
	 * SP             = <US-ASCII SP, space (32)>
	 * HT             = <US-ASCII HT, horizontal-tab (9)>
	 *</pre>
	 *
	 * @param string $str
	 *
	 * @return boolean `true` if the provided string is a token, `false` otherwise.
	 */
	static public function is_token($str)
	{
		// \x21 = CHAR except 0 - 31 (\x1f) and SP (\x20)
		// \x7e = CHAR except DEL

		return !preg_match('#[^\x21-\x7e]#', $str) && !preg_match('#[\(\)\<\>\@\,\;\:\\\\"\/\[\]\?\=\{\}\x9]#', $str);
	}

	/**
	 * Converts a string to the ASCI charset.
	 *
	 * Accents are converted using {@link \ICanBoogie\remove_accents()}. Characters that are not
	 * in the ASCII range are discarted.
	 *
	 * @param string $str The string to convert.
	 *
	 * @return string
	 */
	static public function to_ascii($str)
	{
		$str = \ICanBoogie\remove_accents($str);
		$str = preg_replace('/[^\x20-\x7F]+/', '', $str);

		return $str;
	}

	/**
	 * Initializes the {@link $attribute}, {@link $value} and {@link $language} properties.
	 *
	 * @param string $attribute
	 * @param string $value
	 * @param string|null $language
	 */
	public function __construct($attribute, $value=null, $language=null)
	{
		$this->attribute = $attribute;
		$this->value = $value;
		$this->language = $language;
	}

	/**
	 * Renders the attribute and value into a string.
	 *
	 * <pre>
	 * A string of text is parsed as a single word if it is quoted using
	 * double-quote marks.
	 *
	 *   quoted-string  = ( <"> *(qdtext | quoted-pair ) <"> )
	 *   qdtext         = <any TEXT except <">>
	 *
	 * The backslash character ("\") MAY be used as a single-character
	 * quoting mechanism only within quoted-string and comment constructs.
	 *
	 *   quoted-pair    = "\" CHAR
	 * </pre>
	 *
	 * @return string
	 */
	public function render()
	{
		$value = $this->value;

		if (!$value)
		{
			return '';
		}

		$attribute = $this->attribute;

		#
		# token
		#

		if (self::is_token($value))
		{
			return "{$attribute}={$value}";
		}

		#
		# quoted string
		#

		$encoding = mb_detect_encoding($value);

		if (($encoding === 'ASCII' || $encoding === 'ISO-8859-1') && strpos($value, '"') === false)
		{
			return "{$attribute}=\"{$value}\"";
		}

		#
		# escaped, with fallback
		#
		# @see http://greenbytes.de/tech/tc2231/#encoding-2231-fb
		#

		if ($encoding !== 'UTF-8')
		{
			$value = mb_convert_encoding($value, 'UTF-8', $encoding);
			$encoding = mb_detect_encoding($value);
		}

		$normalized_value = self::to_ascii($value);
		$normalized_value = str_replace([ '"', ';' ], '', $normalized_value);

		return "{$attribute}=\"{$normalized_value}\"; {$attribute}*=" . $encoding . "'{$this->language}'" . rawurlencode($value);
	}

	/**
	 * Returns the value of the parameter.
	 *
	 * Note: {@link render()} to render the attribute and value of the parameter.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return (string) $this->value;
	}
}
