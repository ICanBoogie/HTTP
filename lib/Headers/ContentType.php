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
 *
 * @property string $type Media type of the entity-body.
 * @property string $charset Charset of the entity-body.
 *
 * @link https://tools.ietf.org/html/rfc2616#section-14.17
 */
class ContentType extends Header
{
    public const string VALUE_ALIAS = 'type';

    /**
     * Defines the `charset` parameter.
     *
     * @inheritdoc
     */
    public function __construct(?string $value = null, array $attributes = [])
    {
        $this->parameters['charset'] = new HeaderParameter('charset');

        parent::__construct($value, $attributes);
    }
}
