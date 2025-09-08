<?php

namespace App\Exceptions;

use Exception;

class InsufficientPointsException extends Exception
{
    protected $message = '點數不足，無法執行此操作';
    
    public function __construct(string $message = null, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message ?? $this->message, $code, $previous);
    }
}