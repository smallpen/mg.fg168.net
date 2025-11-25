<?php

namespace App\Exceptions;

use Exception;

class AgentHasDependenciesException extends Exception
{
    protected $message = '代理有下層關聯，無法刪除';
    
    public function __construct(string $message = null, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message ?? $this->message, $code, $previous);
    }
}