Scrapher
===========

Scrapher is a PHP library to easily scrape data from web pages.


Getting Started
---------------

### Installation

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "laurentvw/scrapher": "2.*"
        }
    }

*For the people still using v1.0 ("LavaCrawler"), you can find the documentation is here: <https://github.com/Laurentvw/scrapher/tree/v1.0.2>*


### Basic Usage

In order to start scraping, you need to set the URL(s) or HTML to scrape, and a type of selector to use (for example a regex selector, together with the data you wish to match).

```php
use \Laurentvw\Scrapher\Scrapher;
use \Laurentvw\Scrapher\Selectors\RegexSelector;

$url = 'https://www.google.com/';
$scrapher = new Scrapher($url);

// Match all links on a page
$regex = '/<a.*?href=(?:"(.*?)").*?>(.*?)<\/a>/ms';

$matchConfig = array(
    array(
        'name' => 'url',
        'id' => 1, // the first match (.*?) from the regex
    ),
    array(
        'name' => 'title',
        'id' => 2, // the second match (.*?) from the regex
    ),
);

$matches = $scrapher->with(new RegexSelector($regex, $matchConfig));

$results = $matches->get();
```

This returns a list of arrays based on the match configuration that was set.

    array(29) {
      [0] =>
      array(2) {
        'url' =>
        string(34) "https://www.google.com/webhp?tab=ww"
        'title' =>
        string(6) "Search"
      }
      ...
    }
    
Documentation
-------------

### Instantiating

When creating an instance of Scrapher, you may optionally pass one or more URLs.

Passing multiple URLs can be useful when you want to scrape the same data on different pages. For example when content is separated by pagination.

```php
$scrapher = new Scrapher($url);
$scrapher = new Scrapher(array($url, $url2));
```

If you prefer to fetch the page yourself using a dedicated client/library, you may also simply pass the actual content of a page. This can also be handy if you want to scrape other content besides just web pages (e.g. local files).

```php
$scrapher = new Scrapher($content);
$scrapher = new Scrapher(array($content, $content2));
```

In some cases, you may want to add (read: append) URLs or contents on the fly.

```php
$scrapher->addUrl($url);
$scrapher->addUrls(array($url, $url2));
$scrapher->addContent($content);
$scrapher->addContents(array($content, $content2));
```

### Matching data using a Selector

Before retrieving or sorting the matched data, you need to choose a selector to match the data you want.

At the moment, Scrapher offers 1 selector out of the box, **RegexSelector**, which let's you select data using regular expressions.

A Selector takes an expression and a match configuration as its arguments.

For example, to match all links and their link name, you could do:

```php
$regExpression = '/<a.*?href=(?:"(.*?)").*?>(.*?)<\/a>/ms';

$matchConfig = array(
    array(
        // The "name" key let's you name the data you're looking for,
        // and will be used when retrieving the matched data
        'name' => 'url',
        // The "id" key is an identifier used during the regular expression search.
        // The id 1 corresponds to the first match in the regular expression, matching the URL.
        'id' => 1,
    ),
    array(
        'name' => 'title',
        'id' => 2,
    ),
);

$matches = $scrapher->with(new RegexSelector($regExpression, $matchConfig));
```

Note that the kind of value passed to the "id" key may vary depending on what selector you're using, and can virtually be anything. You can think of the "id" key as the glue between the given expression and its selector.

_**RegexSelector** uses <http://php.net/manual/en/function.preg-match-all.php> under the hood._

For your convenience, when using Regex, a match with `'id' => 0` will return the URL of the crawled page.

### Retrieving & Sorting

Once you've specified a selector using the **with** method, you can start retrieving and/or sorting the data.

**Retrieving**
```php
// Return all matches
$results = $matches->get();

// Return all matches with a subset of the data (either use multiple arguments or an array for more than one column)
$results = $matches->get('title');

// Return the first match
$result = $matches->first();

// Return the last match
$result = $matches->last();

// Count the number of matches
$numberOfMatches = $matches->count();
```

**Offset & limit**
```php
// Take the first N matches
$results = $matches->take(5)->get();

// Skip the first N matches
$results = $matches->skip(1)->get();

// Take 5 matches starting from the second one.
$results = $matches->skip(1)->take(5)->get();
```

**Sorting**
```php
// Order by title
$results = $matches->orderBy('title')->get();

// Order by title, then by URL
$results = $matches->orderBy('title')->orderBy('url', 'desc')->get();

// Custom sorting: For values that do not lend well with sorting, e.g. dates*.
$results = $matches->orderBy('date', 'desc', 'date_create')->get();

// Simply reverse the order of the results
$results = $matches->reverse()->get();
```
* See [date_create](http://php.net/manual/en/function.date-create.php)


### Filtering

You can filter the matched data to refine your result set. Return `true` to keep the match, `false` to filter it out.
```php
$matches->filter(function($match) {
    // Return only matches that contain 'Google' in the link title.
    return stristr($match['title'], 'Google') ? true : false;
});
```

### Mutating

In order to handle inconsistencies or formatting issues, you can alter the matched values to a more desirable value. Altering happens before filtering and sorting the result set. You can do so by using the `apply` index in the match configuration array with a closure that takes 2 arguments: the matched value and the URL of the crawled page.

```php
$matchConfig = array(
    array(
        'name' => 'url',
        'id' => 1,
        // Add domain to relative URLs
        'apply' => function($match, $sourceUrl)
        {
            if (!stristr($match, 'http')) {
                return $sourceUrl . trim($match, '/');
            }
            return $match;
        },
    ),
    array(
        'name' => 'title',
        'id' => 2,
        // Remove all html tags inside the link title
        'apply' => function($match) {
            return strip_tags($match);
        },
    ),
    ...
);
```

### Validation

You may validate the matched data to insure that the result set always contains the desired result. Validation happens after optionally mutating the data set with `apply`. To add the validation rules that should be applied to the data, use the `validate` index in the match configuration array with a closure that takes 2 arguments: the matched value and the URL of the crawled page. The closure should return `true` if the validation succeeded, and `false` if the validation failed. Matches that fail the validation will be removed from the result set.

```php
$matchConfig = array(
    array(
        'name' => 'url',
        'id' => 1,
        // Make sure it is a valid url
        'validate' => function($match) {
            return filter_var($match, FILTER_VALIDATE_URL);
        },
    ),
    array(
        'name' => 'title',
        'id' => 2,
        // We only want titles that are between 1 and 50 characters long.
        'validate' => function($match) {
            return strlen($match) >= 1 && strlen($match) <= 50;
        },
    ),
    ...
);
```

* To make validation easier, we recommend using <https://github.com/Respect/Validation> in your project.

### Logging

If you wish to see the matches that were filtered out, or removed due to failed validation, you can use the `getLogs` method, which returns an array of message logs.

```php
$logs = $matches->getLogs();
```

### Did you know?

**All methods are chainable**

```php
$scrapher = new Scrapher();
$scrapher->addUrl($url)->with($regexSelector)->filter(...)->orderBy('title')->skip(1)->take(5)->get();
```

Only the methods `get`, `first`, `last`, `count` and `getLogs` will cause the chaining to end, as they all return a certain result.

**You can scrape different data from one page**

Suppose you're scraping a page, and you want to get all H2 titles, as well as all links on the page. You can do so without having to re-instantiate Scrapher.

```php
$scrapher = new Scrapher($url);
$h2Titles = $scrapher->with($h2RegexSelector)->get();
$links = $scrapher->with($linksRegexSelector)->get();
```

About
-----
### Author

Laurent Van Winckel - <http://www.laurentvw.com> - <http://twitter.com/Laurentvw>

### License

Scrapher is licensed under the MIT License - see the `LICENSE` file for details

### Contributing

Contributions to Laurentvw\Scrapher are always welcome. You make our lives
easier by sending us your contributions through
[GitHub pull requests](http://help.github.com/pull-requests).

You may also [create an issue](https://github.com/Laurentvw/scrapher/issues) to report bugs or request new features.
