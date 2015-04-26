<?php namespace Laurentvw\Scrapher\Exceptions;

class ContentNotFoundException extends \Exception {

    public function __construct()
    {
        parent::__construct('No URL or content was found in order to start scraping');
    }
}
