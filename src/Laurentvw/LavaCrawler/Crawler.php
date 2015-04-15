<?php namespace Laurentvw\LavaCrawler;

use Laurentvw\LavaCrawler\Selectors\RegexSelector;

class Crawler {

    /**
     * The crawler's urls.
     *
     * @var array
     */
    protected $urls = array();

    /**
     * The expression of the selector
     *
     * @var mixed
     */
    protected $expression;

    /**
     * The crawler's matcher.
     *
     * @var \Laurentvw\LavaCrawler\Matcher
     */
    protected $matcher;

    /**
     * The crawler's interval b/w pages.
     *
     * @var int
     */
    protected $interval = 0;

    /**
     * The number of matches to take
     *
     * @var int
     */
    protected $take = null;

    /**
     * The number of matches to skip
     *
     * @var int
     */
    protected $skip = 0;

    /**
     * The order of the matches
     *
     * @var array
     */
    protected $orderBy;

    /**
     * Verbose output or not
     *
     * @var bool
     */
    protected $verbose = false;

    /**
     * The matches to return
     *
     * @var array
     */
    protected $results = array();

    /**
     * Create a new Crawler instance.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->matcher = new Matcher(new RegexSelector());

        foreach ($config as $item => $value)
        {
            $this->{'set'.ucfirst($item)}($value);
        }
    }

    /**
     * Set an url to crawl
     *
     * @param  string  $url
     * @return Crawler
     */
    public function setUrl($url)
    {
        $this->urls[] = $url;

        return $this;
    }

    /**
     * Set the urls to crawl
     *
     * @param  array  $urls
     * @return Crawler
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
     * Set the expression to match
     *
     * @param  string  $expression
     * @return Crawler
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Set the interval in seconds between page crawls
     *
     * @param  int  $interval
     * @return Crawler
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Set the matches
     *
     * @param  array  $matches
     * @return Crawler
     */
    public function setMatches(array $matches)
    {
        $this->matcher->setMatches($matches);

        return $this;
    }

    /**
     * Set the filter
     *
     * @param  \Closure  $filter
     * @return Crawler
     */
    public function setFilter($filter)
    {
        $this->matcher->setFilter($filter);

        return $this;
    }

    /**
     * Set message output or not
     *
     * @param  bool  $verbose
     * @return Crawler
     */
    public function setVerbose($verbose)
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function getMessages()
    {
        return $this->matcher->getMessages();
    }

    /**
     * Take n-number of matches
     *
     * @param int $n
     * @return Crawler
     */
    public function take($n)
    {
        $this->take = $n;

        return $this;
    }

    /**
     * Skip n-number of matches
     *
     * @param int $n
     * @return Crawler
     */
    public function skip($n)
    {
        $this->skip = $n;

        return $this;
    }

    /**
     * Order the matches
     *
     * @param string $name
     * @param string $order
     * @param string $projection
     * @return Crawler
     */
    public function orderBy($name, $order = 'asc', $projection = null)
    {
        $order = strtolower($order) == 'desc' ? SORT_DESC : SORT_ASC;

        $this->orderBy[] = array($name, $order, $projection);

        return $this;
    }

    /**
     * Get the first match
     *
     * @return array
     */
    public function first()
    {
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
        foreach ($this->urls as $url)
        {
            $page = new Page($url);

            $this->results = $this->matcher->getMatches($page, $this->expression);

            $this->afterCrawl();
        }

        if ($this->results)
        {
            // Order by
            if ($this->orderBy)
            {
                usort($this->results, call_user_func_array('self::make_comparer', $this->orderBy));
            }

            // Skip & Take
            if ($this->skip > 0 || $this->take)
            {
                $this->results = array_slice($this->results, $this->skip, $this->take);
            }
        }
    }

    protected function afterCrawl()
    {
        if ($this->verbose)
        {
            echo $this->getMessages();
            flush();
            $this->matcher->clearMessages();
        }

        if ($this->interval > 0) sleep($this->interval);
    }

    /**
     * This is a callable component of usort.
     *
     * For simple ascending sorts (multiple column included):
     * usort($row, make_comparer('column_name'[, 'other_column_name']);
     *
     * For setting a descending sort
     * usort($rows, make_comparer(array('column_name', SORT_DESC)));
     *
     * To include a function result on a column
     * usort($rows, make_comparer(array('column_name', SORT_ASC, 'function_name')));
     *
     * From stackoverflow.com : user - jon
     * http://stackoverflow.com/questions/96759/how-do-i-sort-a-multidimensional-array-in-php
     * http://stackoverflow.com/users/50079/jon
     * @return type
     */
    public static function make_comparer()
    {
        // Normalize criteria up front so that the comparer finds everything tidy
        $criteria = func_get_args();
        foreach ($criteria as $index => $criterion) {
            $criteria[$index] = is_array($criterion)
                ? array_pad($criterion, 3, null)
                : array($criterion, SORT_ASC, null);
        }

        return function($first, $second) use (&$criteria) {
            foreach ($criteria as $criterion) {
                // How will we compare this round?
                list($column, $sortOrder, $projection) = $criterion;
                $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

                // If a projection was defined project the values now
                if ($projection) {
                    $lhs = call_user_func($projection, $first[$column]);
                    $rhs = call_user_func($projection, $second[$column]);
                }
                else {
                    $lhs = $first[$column];
                    $rhs = $second[$column];
                }

                // Do the actual comparison; do not return if equal
                if ($lhs < $rhs) {
                    return -1 * $sortOrder;
                }
                else if ($lhs > $rhs) {
                    return 1 * $sortOrder;
                }
            }

            return 0; // tiebreakers exhausted, so $first == $second
        };
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
