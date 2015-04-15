<?php namespace Laurentvw\LavaCrawler;

use \Closure;
use Laurentvw\LavaCrawler\Selectors\RegexSelector;
use Laurentvw\LavaCrawler\Selectors\Selector;
use Valitron\Validator;

class Matcher {

    /**
     * @var array
     */
    protected $matches = array();

    /**
     * A log
     *
     * @var string
     */
    protected $messages = '';

    /**
     * @var Selector
     */
    protected $selector = null;

    /**
     * @var \Closure
     */
    protected $filter = null;

    /**
     * Create a new Matcher instance.
     *
     * @param Selector $selector
     * @param \Closure $filter
     */
    function __construct(Selector $selector, $filter = null)
    {
        $this->setSelector($selector);
        $this->setFilter($filter);
    }

    /**
     * Set the matches
     *
     * @param array $matches
     * @return Matcher
     */
    public function setMatches(array $matches)
    {
        $this->matches = $matches;

        return $this;
    }

    /**
     * Set a match
     *
     * @param $match
     * @return Matcher
     */
    public function setMatch($match)
    {
        $this->matches[] = $match;

        return $this;
    }

    /**
     * Set the selector
     *
     * @param Selector $selector
     * @return Matcher
     */
    public function setSelector(Selector $selector)
    {
        $this->selector = $selector;

        return $this;
    }

    /**
     * Get the selector
     *
     * @return Selector
     */
    public function getSelector()
    {
        if ( ! $this->selector) {
            return new RegexSelector();
        }
        
        return $this->selector;
    }

    /**
     * Set the filter to be applied to the matches
     *
     * @param \Closure $filter
     * @return Matcher
     */
    public function setFilter($filter = null)
    {
        $this->filter = is_callable($filter) ? $filter : null;

        return $this;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function addMessage($msg, $newLines = 1)
    {
        $this->messages .= $msg;

        for ($i = 0; $i < $newLines; $i++)
        {
            $this->messages .= "<br><br>\r\n";
        }
    }

    public function clearMessages()
    {
        $this->messages = '';
    }

    /**
     * @param $page
     * @param $selectorExpression
     * @return array
     */
    public function getMatches($page, $selectorExpression)
    {
        $filteredResults = array();

        $this->addMessage('Crawling ' . $page->getURL());
        $this->addMessage('');

        $this->getSelector()->setContent($page->getHTML());
        $this->getSelector()->setExpression($selectorExpression);

        $matches = $this->getSelector()->getMatches();

        if ($matches)
        {
            foreach ($matches as $matchLine)
            {
                $filteredResult = $this->fetch($matchLine, $page->getURL());

                if ($filteredResult)
                {
                    $filteredResults[] = $filteredResult;
                }
            }
        }
        else
        {
            $this->addMessage('HTML/Regex is broken on ' . $page->getURL());
        }

        return $filteredResults;
    }

    /**
     * Fetch the values from a match
     *
     * @param array $data
     * @param string $url
     * @return array
     */
    public function fetch(array $data, $url = '')
    {
        $result = array();
        $dataRules = array();

        foreach ($this->matches as $match)
        {
            // Get the match value, optionally apply a function to it
            if (isset($match['apply']))
            {
                $result[$match['name']] = $match['apply']($data[$match['id']], $url);
            }
            else
            {
                $result[$match['name']] = $data[$match['id']];
            }

            // Get the validation rules for this match
            if (isset($match['rules']))
            {
                $dataRules[$match['name']] = $match['rules'];
            }
        }

        // Validate the data
        $validator = new Validator($result);
        $dataRules = $this->formatRulesForValidator($dataRules);
        $validator->rules($dataRules);

        if ( ! $validator->validate())
        {
            foreach ($validator->errors() as $errorField => $errors)
            {
                $this->addMessage('Skipping match because validation failed for ' . $errorField . ' (' . $errors[0] . '):');
                $this->addMessage(var_export($result, true));
                $this->addMessage('');
            }

            return false;
        }
        // Filter the data
        elseif ($this->filter && ! call_user_func($this->filter, $result))
        {
            $this->addMessage('Filtering out match:');
            $this->addMessage(var_export($result, true));
            $this->addMessage('');

            return false;
        }

        return $result;
    }

    private function formatRulesForValidator($dataRules)
    {
        $rulesRes = array();
        foreach ($dataRules as $fieldRule => $rules) {
            $rulesArr = explode('|', $rules);
            foreach ($rulesArr as $rule) {
                $ruleArr = explode(':', $rule);
                $ruleVals = array();
                $ruleVals[] = $fieldRule;
                if (isset($ruleArr[1])) {
                    $ruleVal = explode(',', $ruleArr[1]);
                    if (count($ruleVal) < 2) {
                        $ruleVals[] = $ruleVal[0];
                    } else {
                        $ruleVals[] = $ruleVal;
                    }
                }
                $rulesRes[$ruleArr[0]][] = $ruleVals;
            }
        }
        return $rulesRes;
    }

}
