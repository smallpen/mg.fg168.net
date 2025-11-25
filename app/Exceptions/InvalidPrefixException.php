<?php

namespace App\Exceptions;

use Exception;

class InvalidPrefixException extends Exception
{
    protected $message = '前置符號格式不正確';
    
    public function __construct(string $message = null, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message ?? $this->message, $code, $previous);
    }
}