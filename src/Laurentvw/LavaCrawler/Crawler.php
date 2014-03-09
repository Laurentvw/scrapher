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

	protected $message = '';

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
     * @return \LavaCrawl\Crawler
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
     * @return \LavaCrawl\Crawler
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
     * @return \LavaCrawl\Crawler
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

	public function getData()
	{
		$matches = array();
		$i = 0;

		foreach ($this->urls as $url)
		{
			$page = new Page($url);
			$html = $page->getHTML();

			if (preg_match_all('/'.$this->regex.'/ms', $html, $matchLines, PREG_SET_ORDER))
			{
				foreach ($matchLines as $matchLine)
				{
					if ($matches[$i] = $this->matcher->fetch($matchLine))
					{
						$i++;
					}
					else
					{
						// Remove this match from the data set
						unset($matches[$i]);
						$this->message .= 'On ' . $url . "\r\n";
						$this->message .= $this->matcher->getErrors();
					}
				}
			}
			else
			{
				$this->message .= 'HTML is broken on ' . $url . '!' . "\r\n\r\n";
			}
		}

		return $matches;
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