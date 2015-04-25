<?php namespace Laurentvw\Scrapher;

use \Closure;
use Laurentvw\Scrapher\Selectors\Selector;

class Matcher {

    /**
     * @var array
     */
    protected $matches = array();

    /**
     * Logs
     *
     * @var array
     */
    protected $logs = array();

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

    /**
     * Get detailed logs of the scraping
     *
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Add a log message
     *
     * @param string $msg
     */
    public function addLog($msg)
    {
        $this->logs[] = $msg;
    }

    /**
     * @param $content
     * @return array
     */
    public function getMatches($content)
    {
        $filteredResults = array();

        $this->getSelector()->setContent($content);

        $matches = $this->getSelector()->getMatches();

        if ($matches)
        {
            foreach ($this->getSelector()->getMatches() as $matchLine)
            {
                $filteredResult = $this->fetch($matchLine);

                if ($filteredResult)
                {
                    $filteredResults[] = $filteredResult;
                }
            }
        }
        else
        {
            $this->addLog('The HTML or Selector expression is broken');
        }

        return $filteredResults;
    }

    /**
     * Fetch the values from a match
     *
     * @param array $matchLine
     * @return array
     */
    private function fetch(array $matchLine)
    {
        $result = array();

        foreach ($this->getSelector()->getConfig() as $match)
        {
            // Get the match value, optionally apply a function to it
            if (isset($match['apply']))
            {
                $result[$match['name']] = $match['apply']($matchLine[$match['name']]);
            }
            else
            {
                $result[$match['name']] = $matchLine[$match['name']];
            }

            // Validate this match
            if (isset($match['validate']))
            {
                if ( ! $match['validate']($matchLine[$match['name']]))
            {
                    $this->addLog('Skipping match because validation failed for ' . $match['name'] . ': '. $matchLine[$match['name']]);

            return false;
        }
            }
        }

        // Filter the data
        if ($this->filter && ! call_user_func($this->filter, $result))
        {
            $this->addLog('Filtering out match: ' . var_export($result, true));

            return false;
        }

        return $result;
    }

}
