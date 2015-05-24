<?php

namespace Laurentvw\Scrapher\Exceptions;

class SelectorNotFoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct('No selector was specified using the with() method');
    }
}
