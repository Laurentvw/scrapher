<?php

namespace Laurentvw\Scrapher\Selectors;

abstract class Selector
{
    private $content, $sourceKey, $expression, $config;

    public function __construct($expression, $config)
    {
        $this->expression = $expression;
        $this->config = $config;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setSourceKey($key)
    {
        $this->sourceKey = $key;
    }

    public function getSourceKey()
    {
        return $this->sourceKey;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setExpression($expression)
    {
        $this->expression = $expression;
    }

    public function getExpression()
    {
        return $this->expression;
    }

    abstract public function getMatches();
}
