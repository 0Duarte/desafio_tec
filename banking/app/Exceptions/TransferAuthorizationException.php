<?php

namespace App\Exceptions;

use Exception;

class TransferAuthorizationException extends Exception
{
	public function __construct($message = 'Failed to authorize transfer', $code = 0)
	{
		parent::__construct($message, $code);
	}
}
