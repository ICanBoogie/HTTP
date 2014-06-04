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
 * Representation of the `Content-Disposition` header field.
 *
 * <pre>
 * <?php
 *
 * use ICanBoogie\HTTP\ContentDispositionHeader;
 *
 * $cd = new ContentDisposition;
 * $cd->type = attachment;
 * $cd->filename = "Résumé en €.csv";
 *
 * echo $cd; // attachment; filename*=UTF-8''R%C3%A9sum%C3%A9%20en%20%E2%82%AC.csv
 * </pre>
 *
 * @property string $type The `disposition-type` part of the content disposition. Alias to {@link $value}.
 * @property string $filename The `filename-parm` part of the content disposition.
 *
 * @see http://tools.ietf.org/html/rfc2616#section-19.5.1
 * @see http://tools.ietf.org/html/rfc6266
 */
class ContentDispositionHeader extends Header
{
	const VALUE_ALIAS = 'type';

	/**
	 * Defines the `filename` parameter.
	 */
	public function __construct($value=null, array $attributes=[])
	{
		$this->parameters['filename'] = new HeaderParameter('filename');

		parent::__construct($value, $attributes);
	}
}