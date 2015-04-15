<?php namespace Laurentvw\LavaCrawler\Selectors;

abstract class Selector {

    private $content, $expression;

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
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
