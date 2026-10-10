<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP\Headers;

use ICanBoogie\HTTP\Headers;
use InvalidArgumentException;
use ValueError;

use function ICanBoogie\remove_accents;
use function mb_convert_encoding;
use function mb_detect_encoding;
use function preg_match;
use function preg_replace;
use function rawurldecode;
use function rawurlencode;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Representation of a header parameter.
 *
 * @see https://www.rfc-editor.org/rfc/rfc8187
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-5.6.6
 * @see https://greenbytes.de/tech/tc2231/#attwithfn2231utf8
 */
class HeaderParameter
{
    /**
     * The charset of the parameter's value.
     */
    public string $charset {
        get => mb_detect_encoding($this->value) ?: 'ISO-8859-1';
    }

    /**
     * Creates a {@see HeaderParameter} instance from the provided source.
     *
     * @throws InvalidArgumentException if the source is not a valid parameter.
     */
    public static function from(mixed $source): self
    {
        if ($source instanceof self) {
            return $source;
        }

        $source = (string) $source;
        $equal_pos = strpos($source, '=');

        if (!$equal_pos) {
            throw new InvalidArgumentException("Expected `attribute=value`, got: $source");
        }

        $attribute = trim(substr($source, 0, $equal_pos));
        $value = trim(substr($source, $equal_pos + 1));
        $language = null;

        if (str_ends_with($attribute, '*')) {
            $attribute = substr($attribute, 0, -1);
            [ $value, $language ] = self::decode_ext_value($value);
        } elseif (strlen($value) >= 2 && $value[0] === '"' && $value[-1] === '"') {
            $value = preg_replace('/\\\\(.)/s', '$1', substr($value, 1, -1));
        }

        if ($attribute === '') {
            throw new InvalidArgumentException("Expected `attribute=value`, got: $source");
        }

        $value = mb_convert_encoding($value, 'UTF-8');

        return new self($attribute, $value, $language);
    }

    /**
     * Decodes an extended value, such as `UTF-8'en'%C2%A3%20rates`.
     *
     * Quotes around the value are tolerated, although RFC 8187 doesn't allow them.
     *
     * @return array{ 0: string, 1: string|null } The value and its language.
     *
     * @throws InvalidArgumentException if the value is malformed or its charset is not supported.
     *
     * @link https://www.rfc-editor.org/rfc/rfc8187#section-3.2
     */
    private static function decode_ext_value(string $value): array
    {
        if (!preg_match('#^([a-zA-Z0-9\-]+)?(\'([a-zA-Z\-]+)?\')?(")?([^"]+)(")?$#', $value, $matches)) {
            throw new InvalidArgumentException("Malformed extended value: $value");
        }

        $charset = $matches[1] ?: 'UTF-8';
        $language = $matches[3] ?: null;

        try {
            $value = mb_convert_encoding(rawurldecode($matches[5]), 'UTF-8', $charset);
        } catch (ValueError $e) {
            throw new InvalidArgumentException("Unsupported charset: $charset", previous: $e);
        }

        return [ $value, $language ];
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
     * Accents are converted using {@see remove_accents()}. Characters that are not
     * in the ASCII range are discarded.
     *
     * @param string $str The string to convert.
     */
    public static function to_ascii(string $str): string
    {
        $str = remove_accents($str);

        return preg_replace('/[^\x20-\x7F]+/', '', $str);
    }

    /**
     *@property-read string $attribute The attribute of the parameter.
     */
    public function __construct(
        public readonly string $attribute,
        public ?string $value = null {
            set {
                $this->assert_is_safe($value);
                $this->value = $value;
            }
        },
        public ?string $language = null {
            set {
                $this->assert_is_safe($value);
                $this->language = $value;
            }
        },
    ) {
    }

    /**
     * @throws InvalidArgumentException if the value contains NUL, CR or LF characters, which would
     * allow header injection.
     */
    private function assert_is_safe(?string $value): void
    {
        if ($value !== null) {
            Headers::assert_value_is_safe($this->attribute, $value);
        }
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
        # @link https://greenbytes.de/tech/tc2231/#encoding-2231-fb
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
     * Note: {@see render()} to render the attribute and value of the parameter.
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
