<?php

namespace ICanBoogie\HTTP\Headers;

/**
 * Representation of the `Content-Disposition` header field.
 *
 * <pre>
 * <?php
 *
 * use ICanBoogie\HTTP\Headers\ContentDisposition;
 *
 * $cd = new ContentDisposition;
 * $cd->type = attachment;
 * $cd->filename = "Résumé en €.csv";
 *
 * echo $cd; // attachment; filename*=UTF-8''R%C3%A9sum%C3%A9%20en%20%E2%82%AC.csv
 * </pre>
 *
 * @property string $type The `disposition-type` part of the content disposition. Alias to {@see $value}.
 * @property string $filename The `filename-parm` part of the content disposition.
 *
 * @link https://tools.ietf.org/html/rfc2616#section-19.5.1
 * @link https://tools.ietf.org/html/rfc6266
 */
class ContentDisposition extends Header
{
    public const string VALUE_ALIAS = 'type';

    /**
     * Defines the `filename` parameter.
     *
     * @inheritdoc
     */
    public function __construct(?string $value = null, array $attributes = [])
    {
        $this->parameters['filename'] = new HeaderParameter('filename');

        parent::__construct($value, $attributes);
    }
}
