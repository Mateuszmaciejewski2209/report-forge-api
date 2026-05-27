<?php

namespace App\Exceptions;

use Exception;

class PlanLimitExceeded extends Exception
{
    public function __construct(
        public readonly string $limitKey,
        string $message = 'Plan limit exceeded.',
    ) {
        parent::__construct($message);
    }
}
