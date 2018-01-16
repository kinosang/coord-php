<?php

namespace labs7in0\coord\Exceptions;

class UnknownTypeException extends \Exception
{
    public function __construct(int $type)
    {
        parent::__construct('Type [' . $type . '] unsupported.');
    }
}
