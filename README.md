LavaCrawler
===========

A PHP library to easily scrape data from web pages


Installation
------------

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "laurentvw/lavacrawler": "1.0.2"
        }
    }


Examples
--------

### Basic Usage

In order to start crawling, you need to set the URL(s) to crawl, the regular expression to match, and the matched data to return.

```php
use \Laurentvw\LavaCrawler\Crawler;

$crawler = new Crawler();

$crawler->setUrls(array(
    'https://www.google.com/',
));

// Match all links on a page
$crawler->setRegex('<a.*?href=(?:"(.*?)"|\'(.*?)\').*?>(.*?)<\/a>');

$crawler->setMatches(array(
    array(
        'name' => 'url',
        'id' => 1, // the second match from the regex (first = 0)
    ),
    array(
        'name' => 'title',
        'id' => 3
    ),
));

$result = $crawler->get();
```

This returns a list of arrays based on the matches that were set.

    array(29) {
      [0] =>
      array(2) {
        'url' =>
        string(34) "https://www.google.be/webhp?tab=ww"
        'title' =>
        string(6) "Zoeken"
      }
      ...
    }


### Retrieving

**Retrieving**
```php
// Return all matches
$result = $crawler->get();

// Return the first match
$result = $crawler->first();

// Return the last match
$result = $crawler->last();
```

**Offset & limit**
```php
// Take the first N matches
$result = $crawler->take(5)->get();

// Skip the first N matches
$result = $crawler->skip(1)->get();

// Take 5 matches starting from the second one.
$result = $crawler->skip(1)->take(5)->get();
```

### Sorting
```php
// Order by title
$result = $crawler->orderBy('title')->get();

// Order by title, then by URL
$result = $crawler->orderBy('title')->orderBy('url', 'desc')->get();

// Custom sorting: For values that do not lend well with sorting, e.g. dates*.
$result = $crawler->orderBy('date', 'desc', 'date_create')->get();
```
* See [date_create](http://php.net/manual/en/function.date-create.php)

### Time interval

You may set a time interval (in seconds) to delay the crawling between pages.
```php
$crawler->setInterval(1); // 1 second
```

### Filtering

You can filter the matched data to refine your result set. Return `true` to keep the match, `false` to filter it out.
```php
$crawler->setFilter(function($matches)
{
    // Return only matches that contain 'Google' in the link title.
    return stristr($matches['title'], 'Google') ? true : false;
});
```

### Mutating

In order to handle inconsistencies or formatting issues, you can alter the matched values to a more desirable value. Altering happens before filtering and sorting the result set. You can do so by using the `apply` index in the matches array with a closure that takes 2 arguments: the matched value and the url of the page.

```php
$crawler->setMatches(array(
    array(
        'name' => 'url',
        'id' => 1,
        // Add domain to URL if it's not present already
        'apply' => function($match, $url)
        {
            if (!stristr($match, 'http')) {
                return $url . trim($match, '/');
            }
            return $match;
        },
    ),
    array(
        'name' => 'title',
        'id' => 3,
        // Remove all html tags inside the link title
        'apply' => function($m) {
            return strip_tags($m);
        },
    ),
    ...
));
```

### Validation

You may validate the matched data to insure that the result set always contains the desired result. Validation happens after optionally mutating the data set with `apply`. To add the validation rules that should be applied to the data, use the `rules` index in the matches array. Matches that fail the validation will be removed from the result set.

```php
$crawler->setMatches(array(
    array(
        'name' => 'url',
        'id' => 1,
        // Make sure it is a valid url
        'rules' => 'url',
    ),
    array(
        'name' => 'title',
        'id' => 3,
        // Example usage of multiple validation rules, seperated by a |.
        'rules' => 'min:1|max:50',
    ),
    ...
));
```

* See [Laravel validation](http://laravel.com/docs/validation#available-validation-rules) for the available validation rules.

About
-----
### Author

Laurent Van Winckel - <http://www.laurentvw.com> - <http://twitter.com/Laurentvw>

### License

LavaCrawler is licensed under the MIT License - see the `LICENSE` file for details
