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
 * @link https://www.rfc-editor.org/rfc/rfc6266
 */
class ContentDisposition extends Header
{
    protected const array PARAMETERS = [ 'filename' ];

    /**
     * Alias to {@see $value}.
     */
    public ?string $type {
        get => $this->value;
        set {
            $this->value = $value;
        }
    }

    public ?string $filename {
        get => $this->parameters['filename']->value;
        set {
            $this->set_parameter('filename', $value);
        }
    }
}
