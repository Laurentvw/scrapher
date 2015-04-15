<?php namespace Laurentvw\LavaCrawler\Selectors;

class RegexSelector extends Selector {

    public function getMatches()
    {
        preg_match_all('/'.$this->getExpression().'/ms', $this->getContent(), $matchLines, PREG_SET_ORDER);
        
        return $matchLines;
    }
}
