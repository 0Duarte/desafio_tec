<?php

namespace App\Exceptions;

use Exception;

class MerchantCannotTransferException extends Exception
{
	public function __construct($message = 'Merchant cannot transfer funds', $code = 0)
	{
		parent::__construct($message, $code);
	}
}
