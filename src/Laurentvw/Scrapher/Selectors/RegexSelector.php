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
                if ($config['id'] == 0) {
                    $matches[$i][$config['name']] = $this->getSourceKey();
                    continue;
                }
                if (!isset($matchLine[$config['id']])) {
                    $matches[$i][$config['name']] = null;
                } else {
                    $matches[$i][$config['name']] = $matchLine[$config['id']];
                }
            }
        }

        return $matches;
    }

}
