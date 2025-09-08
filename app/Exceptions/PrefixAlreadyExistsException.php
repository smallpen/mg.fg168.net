<?php

namespace App\Exceptions;

use Exception;

class PrefixAlreadyExistsException extends Exception
{
    protected $message = '前置符號已被使用';
    
    public function __construct(string $message = null, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message ?? $this->message, $code, $previous);
    }
}