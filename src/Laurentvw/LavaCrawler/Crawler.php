<?php namespace Laurentvw\LavaCrawler;

abstract class Crawler {

    /**
     * The crawler's urls.
     *
     * @var array
     */
    protected $urls = array();

    /**
     * The regular expression used for crawling
     *
     * @var string
     */
    protected $regex;

    /**
     * The crawler's matcher.
     *
     * @var \Laurentvw\LavaCrawler\Matcher
     */
    protected $matcher;

    /**
     * The number of matches to take
     *
     * @var int
     */
    protected $take;

    /**
     * Return the last match
     *
     * @var bool
     */
    protected $last = false;

    /**
     * Return the first match
     *
     * @var bool
     */
    protected $first = false;

    protected $message = '';

    /**
     * The matches to return
     *
     * @var array
     */
    protected $results = array();

    /**
     * Create a new Crawler instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $urls = array(), $regex = '', array $matchData = array(), $filter = null)
    {
        $this->setUrls($urls);
        $this->setRegex($regex);
        $this->setMatcher($matchData, $filter);
    }

    /**
     * Set the urls to crawl
     *
     * @param  array  $urls
     * @return \Laurentvw\LavaCrawler\Crawler
     */
    public function setUrls(array $urls)
    {
        foreach ($urls as $url)
        {
            $this->urls[] = $url;
        }

        return $this;
    }

    /**
     * Set the regex to match
     *
     * @param  string  $regex
     * @return \Laurentvw\LavaCrawler\Crawler
     */
    public function setRegex($regex)
    {
        $this->regex = $regex;

        return $this;
    }

    /**
     * Set the regex to match
     *
     * @param  string  $regex
     * @return \Laurentvw\LavaCrawler\Crawler
     */
    public function setMatcher($matchData, $filter)
    {
        $this->matcher = new Matcher($matchData, $filter); // DepInj???!

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Take n-number of matches
     *
     * @return \Laurentvw\LavaCrawler\Crawler
     */
    public function take($n)
    {
        $this->take = $n;

        return $this;
    }

    /**
     * Get the first match
     *
     * @return array
     */
    public function first()
    {
        $this->first = true;

        $this->crawl();

        return current($this->results);
    }

    /**
     * Get the last match
     *
     * @return array
     */
    public function last()
    {
        $this->last = true;

        $this->crawl();

        return end($this->results);
    }

    /**
     * Get the matches
     *
     * @return array
     */
    public function get()
    {
        $this->crawl();

        return $this->results;
    }

    /**
     * The actual crawling
     *
     * @return void
     */
    public function crawl()
    {
        $i = 0;

        foreach ($this->urls as $url)
        {
            $page = new Page($url);
            $html = $page->getHTML();

            if (preg_match_all('/'.$this->regex.'/ms', $html, $matchLines, PREG_SET_ORDER))
            {
                foreach ($matchLines as $matchLine)
                {
                    if ($this->results[$i] = $this->matcher->fetch($matchLine, $url))
                    {
                        if ($this->first || ($this->take && $this->take >= $i+1))
                        {
                            break;
                        }

                        $i++;
                    }
                    else
                    {
                        // Remove this match from the data set
                        unset($this->results[$i]);
                        
                        if ($this->matcher->getErrors())
                        {
                            $this->message .= 'On ' . $url . "\r\n";
                            $this->message .= $this->matcher->getErrors();
                        }
                    }
                }
            }
            else
            {
                $this->message .= 'HTML is broken on ' . $url . '!' . "\r\n\r\n";
            }
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array(array($instance, $method), $parameters);
    }
}