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

use function ICanBoogie\remove_accents;
use function mb_convert_encoding;
use function mb_detect_encoding;
use function preg_match;
use function preg_replace;
use function rawurlencode;
use function str_replace;
use function strpos;
use function substr;
use function urldecode;

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
    /**
     * @uses get_attribute
     * @uses get_charset
     */
    use AccessorTrait;

    protected function get_attribute(): string
    {
        return $this->attribute;
    }

    protected function get_charset(): string
    {
        return mb_detect_encoding($this->value) ?: 'ISO-8859-1';
    }

    /**
     * Creates a {@link HeaderParameter} instance from the provided source.
     */
    public static function from(mixed $source): self
    {
        if ($source instanceof self) {
            return $source;
        }

        $equal_pos = strpos($source, '=');
        $language = null;

        if ($source[$equal_pos - 1] === '*') {
            $attribute = substr($source, 0, $equal_pos - 1);
            $value = substr($source, $equal_pos + 1);

            preg_match('#^([a-zA-Z0-9\-]+)?(\'([a-z\-]+)?\')?(")?([^"]+)(")?$#', $value, $matches);

            if ($matches[3]) {
                $language = $matches[3];
            }

            $value = urldecode($matches[5]);
            $value = mb_convert_encoding($value, 'UTF-8', $matches[1]);
        } else {
            $attribute = substr($source, 0, $equal_pos);
            $value = substr($source, $equal_pos + 1);

            if ($value[0] === '"') {
                $value = substr($value, 1, -1);
            }
        }

        $value = mb_convert_encoding($value, 'UTF-8');

        return new self($attribute, $value, $language);
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
     */
    public static function is_token(string $str): bool
    {
        // \x21 = CHAR except 0 - 31 (\x1f) and SP (\x20)
        // \x7e = CHAR except DEL

        return !preg_match('#[^\x21-\x7e]#', $str)
            && !preg_match('#[\(\)\<\>\@\,\;\:\\\\"\/\[\]\?\=\{\}\x9]#', $str);
    }

    /**
     * Converts a string to the ASCII charset.
     *
     * Accents are converted using {@link remove_accents()}. Characters that are not
     * in the ASCII range are discarded.
     *
     * @param string $str The string to convert.
     */
    public static function to_ascii(string $str): string
    {
        $str = remove_accents($str);

        return preg_replace('/[^\x20-\x7F]+/', '', $str);
    }

    public function __construct(
        protected string $attribute,
        public ?string $value = null,
        public ?string $language = null
    ) {
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
     */
    public function render(): string
    {
        $value = $this->value;

        if (!$value) {
            return '';
        }

        $attribute = $this->attribute;

        #
        # token
        #

        if (self::is_token($value)) {
            return "$attribute=$value";
        }

        #
        # quoted string
        #

        $encoding = mb_detect_encoding($value);

        if (($encoding === 'ASCII' || $encoding === 'ISO-8859-1') && !str_contains($value, '"')) {
            return "$attribute=\"$value\"";
        }

        #
        # escaped, with fallback
        #
        # @see http://greenbytes.de/tech/tc2231/#encoding-2231-fb
        #

        if ($encoding !== 'UTF-8') {
            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
            $encoding = mb_detect_encoding($value);
        }

        $normalized_value = self::to_ascii($value);
        $normalized_value = str_replace([ '"', ';' ], '', $normalized_value);

        return "$attribute=\"$normalized_value\"; {$attribute}*="
            . $encoding . "'$this->language'" . rawurlencode($value);
    }

    /**
     * Returns the value of the parameter.
     *
     * Note: {@link render()} to render the attribute and value of the parameter.
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
