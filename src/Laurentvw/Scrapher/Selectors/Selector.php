<?php namespace Laurentvw\Scrapher\Selectors;

abstract class Selector {

    private $content, $expression, $data;

    public function __construct($expression, $data)
    {
        $this->expression = $expression;
        $this->data = $data;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
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
