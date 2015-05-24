<?php

namespace Laurentvw\Scrapher;

use Laurentvw\Scrapher\Exceptions\ContentNotFoundException;
use Laurentvw\Scrapher\Exceptions\SelectorNotFoundException;
use Laurentvw\Scrapher\Selectors\Selector;

class Scrapher
{
    /**
     * The scraper's contents.
     *
     * @var array
     */
    protected $contents = [];

    /**
     * The crawler's matcher.
     *
     * @var \Laurentvw\Scrapher\Matcher
     */
    protected $matcher;

    /**
     * The number of matches to take.
     *
     * @var int
     */
    protected $take;

    /**
     * The number of matches to skip.
     *
     * @var int
     */
    protected $skip = 0;

    /**
     * The order of the matches.
     *
     * @var array
     */
    protected $orderBy;

    /**
     * Create a new Scrapher instance.
     *
     * You may optionally pass the contents or URLs to scrape.
     *
     * @param string|array|null $contents
     */
    public function __construct($contents = null)
    {
        if ($contents) {
            if (!is_array($contents)) {
                $contents = [$contents];
            }

            foreach ($contents as $content) {
                if (substr($content, 0, 4) == 'http') {
                    $this->addUrl($content);
                } else {
                    $this->addContent($content);
                }
            }
        }
    }

    /**
     * Add URL to scrape.
     *
     * @param string $url
     *
     * @return Scrapher
     */
    public function addUrl($url)
    {
        $page = new Page($url);
        $this->addContent($page->getHTML());

        return $this;
    }

    /**
     * Add URLs to scrape.
     *
     * @param array $urls
     *
     * @return Scrapher
     */
    public function addUrls(array $urls)
    {
        foreach ($urls as $url) {
            $this->addUrl($url);
        }

        return $this;
    }

    /**
     * Add content to scrape.
     *
     * @param string $content
     *
     * @return Scrapher
     */
    public function addContent($content)
    {
        $this->contents[] = $content;

        return $this;
    }

    /**
     * Add contents to scrape.
     *
     * @param array $contents
     *
     * @return Scrapher
     */
    public function addContents(array $contents)
    {
        foreach ($contents as $content) {
            $this->addContent($content);
        }

        return $this;
    }

    /**
     * Set the type of selector to use.
     *
     * @param Selector $selector
     *
     * @return Scrapher
     */
    public function with(Selector $selector)
    {
        $this->matcher = new Matcher($selector);

        return $this;
    }

    /**
     * Filter the resulting matches.
     *
     * @param \Closure $filter
     *
     * @return Scrapher
     */
    public function filter($filter)
    {
        $this->getMatcher()->setFilter($filter);

        return $this;
    }

    /**
     * Take n-number of matches.
     *
     * @param int $n
     *
     * @return Scrapher
     */
    public function take($n)
    {
        $this->take = $n;

        return $this;
    }
    /**
     * Skip n-number of matches.
     *
     * @param int $n
     *
     * @return Scrapher
     */
    public function skip($n)
    {
        $this->skip = $n;

        return $this;
    }
    /**
     * Order the matches.
     *
     * @param string $name
     * @param string $order
     * @param string $projection
     *
     * @return Scrapher
     */
    public function orderBy($name, $order = 'asc', $projection = null)
    {
        $order = strtolower($order) == 'desc' ? SORT_DESC : SORT_ASC;
        $this->orderBy[] = [$name, $order, $projection];

        return $this;
    }

    /**
     * Get all the matches.
     *
     * @return array
     */
    public function get()
    {
        return $this->scrape();
    }

    /**
     * Get the first match.
     *
     * @return array
     */
    public function first()
    {
        $results = $this->scrape();

        return current($results);
    }

    /**
     * Get the last match.
     *
     * @return array
     */
    public function last()
    {
        $results = $this->scrape();

        return end($results);
    }

    /**
     * Count the number of matches.
     *
     * @return array
     */
    public function count()
    {
        $results = $this->scrape();

        return count($results);
    }

    /**
     * Get detailed logs of the scraping.
     *
     * @return array
     */
    public function getLogs()
    {
        return $this->getMatcher()->getLogs();
    }

    /**
     * The matcher.
     *
     * @throws SelectorNotFoundException
     *
     * @return Matcher
     */
    protected function getMatcher()
    {
        if (!$this->matcher) {
            throw new SelectorNotFoundException();
        }

        return $this->matcher;
    }

    /**
     * The actual scraping.
     *
     * @throws ContentNotFoundException
     *
     * @return array
     */
    protected function scrape()
    {
        if (!$this->contents) {
            throw new ContentNotFoundException();
        }

        $results = [];

        foreach ($this->contents as $content) {
            $results = array_merge($results, $this->getMatcher()->getMatches($content));
        }

        if ($results) {
            // Order by
            if ($this->orderBy) {
                usort($results, call_user_func_array('self::makeComparer', $this->orderBy));
            }
            // Skip & Take
            if ($this->skip > 0 || $this->take) {
                $results = array_slice($results, $this->skip, $this->take);
            }
        }

        $this->clear();

        return $results;
    }

    /**
     * Clear the scraping configuration.
     *
     * This allows us to scrape the same contents again, but with a different selector
     */
    protected function clear()
    {
        $this->take = null;
        $this->orderBy = null;
        $this->skip = 0;
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
     *
     * @return int
     */
    private static function makeComparer()
    {
        // Normalize criteria up front so that the comparer finds everything tidy
        $criteria = func_get_args();
        foreach ($criteria as $index => $criterion) {
            $criteria[$index] = is_array($criterion)
                ? array_pad($criterion, 3, null)
                : [$criterion, SORT_ASC, null];
        }

        return function ($first, $second) use (&$criteria) {
            foreach ($criteria as $criterion) {
                // How will we compare this round?
                list($column, $sortOrder, $projection) = $criterion;
                $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;
                // If a projection was defined project the values now
                if ($projection) {
                    $lhs = call_user_func($projection, $first[$column]);
                    $rhs = call_user_func($projection, $second[$column]);
                } else {
                    $lhs = $first[$column];
                    $rhs = $second[$column];
                }
                // Do the actual comparison; do not return if equal
                if ($lhs < $rhs) {
                    return -1 * $sortOrder;
                } elseif ($lhs > $rhs) {
                    return 1 * $sortOrder;
                }
            }

            return 0; // tiebreakers exhausted, so $first == $second
        };
    }
}
