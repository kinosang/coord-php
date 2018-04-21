<?php

namespace labs7in0\coord\Exceptions;

class ExactException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Out of iterations.');
    }
}
