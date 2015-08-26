<?php

namespace Laurentvw\Scrapher\Selectors;

class RegexSelector extends Selector
{
    public function getMatches()
    {
        $matches = array();

        preg_match_all($this->getExpression(), $this->getContent(), $matchLines, PREG_SET_ORDER);

        foreach ($matchLines as $i => $matchLine) {
            foreach ($this->getConfig() as $config) {
                $matches[$i][$config['name']] = $matchLine[$config['id']];
            }
        }

        return $matches;
    }

}
