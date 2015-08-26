<?php

namespace Laurentvw\Scrapher\Exceptions;

class MatchIdNotFoundException extends \Exception
{
    public function __construct($id)
    {
        parent::__construct('The match with ID '.$id.' does not exist in the selector');
    }
}
