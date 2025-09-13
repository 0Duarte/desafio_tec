<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class TransferFailedException extends Exception
{
	public function __construct($message)
{
    parent::__construct('Failed to transfer: ' . $message);
}
}
