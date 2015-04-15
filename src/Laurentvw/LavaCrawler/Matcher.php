<?php namespace Laurentvw\LavaCrawler;

use \Closure;
use \Illuminate\Validation\Factory as ValidationFactory;
use Laurentvw\LavaCrawler\Selectors\RegexSelector;
use Laurentvw\LavaCrawler\Selectors\Selector;
use \Symfony\Component\Translation\Translator;

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
     * @var \Illuminate\Validation\Factory
     */
    protected $validationFactory;

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

        $translator = new Translator('en');
        $this->validationFactory = new ValidationFactory($translator);
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
        $validator = $this->validationFactory->make($result, $dataRules);
        if ($validator->fails())
        {
            $this->addMessage('Validation failed for: ');

            foreach ($validator->messages()->getMessages() as $name => $messages)
            {
                foreach ($messages as $message)
                {
                    $this->addMessage(var_export($result[$name], true) . ': ' . $message);
                }
            }

            return false;
        }
        // Filter the data
        elseif ($this->filter && ! call_user_func($this->filter, $result))
        {
            return false;
        }

        return $result;
    }

}
