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
 * @property $type string Media type of the entity-body.
 * @property $charset string Charset of the entity-body.
 *
 * @see http://tools.ietf.org/html/rfc2616#section-14.17
 */
class ContentType extends Header
{
	public const VALUE_ALIAS = 'type';

	/**
	 * Defines the `charset` parameter.
	 *
	 * @inheritdoc
	 */
	public function __construct($value = null, array $attributes = [])
	{
		$this->parameters['charset'] = new HeaderParameter('charset');

		parent::__construct($value, $attributes);
	}
}
