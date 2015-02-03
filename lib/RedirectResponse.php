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
 * A HTTP response doing a redirect.
 */
class RedirectResponse extends Response
{
	/**
	 * Initializes the `Location` header.
	 *
	 * @param string $url URL to redirect to.
	 * @param int $status Status code (default to 302).
	 * @param array $headers Additional headers.
	 *
	 * @throws \InvalidArgumentException if the provided status code is not a redirect.
	 */
	public function __construct($url, $status = 302, array $headers = [])
	{
		parent::__construct
		(
			function(Response $response) {

				$location = $response->location;
				$title = \ICanBoogie\escape($location);

				echo <<<EOT
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="refresh" content="1;url={$location}" />

	<title>Redirecting to {$title}</title>
</head>
<body>
	Redirecting to <a href="{$location}">{$title}</a>.
</body>
</html>
EOT
; // @codeCoverageIgnore
			},

			$status, [ 'Location' => $url ] + $headers
		);

		if (!$this->status->is_redirect)
		{
			throw new StatusCodeNotValid($this->status->code, "The HTTP status code is not a redirect: {$status}.");
		}
	}
}
