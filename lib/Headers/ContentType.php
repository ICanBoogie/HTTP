<?php

namespace ICanBoogie\HTTP\Headers;

/**
 * Representation of the `Content-Type` header field.
 *
 * <pre>
 * <?php
 *
 * use ICanBoogie\HTTP\Headers\ContentType;
 *
 * $ct = new ContentType;
 * $ct->type = "text/html";
 * $ct->charset = "utf-8";
 * echo $ct;                 // text/html; charset=utf-8
 *
 * $ct = ContentType::from("text/plain; charset=iso-8859-1");
 * echo $ct->type;           // text/plain
 * echo $ct->charset;        // iso-8859-1
 * </pre>
 * @link https://www.rfc-editor.org/rfc/rfc9110#section-8.3
 */
class ContentType extends Header
{
    protected const array PARAMETERS = [ 'charset' ];

    /**
     * Alias to {@see $value}.
     */
    public ?string $type {
        get => $this->value;
        set {
            $this->value = $value;
        }
    }

    public ?string $charset {
        get => $this->parameters['charset']->value;
        set {
            $this->set_parameter('charset', $value);
        }
    }
}
