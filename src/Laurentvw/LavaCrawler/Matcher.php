<?php namespace Laurentvw\LavaCrawler;

use \Closure;
use \Illuminate\Validation\Factory as ValidationFactory;
use \Symfony\Component\Translation\Translator;

class Matcher {

	/**
	 * @var array
	 */
	protected $matches = array();

	/**
	 * @var string
	 */
	protected $errors = '';

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
     * @param array $matches
     * @param \Closure $filter
     * @return void
     */
	function __construct($matches = array(), $filter = null)
	{
		$this->setMatches($matches);
		$this->setFilter($filter);

		$translator = new Translator('en');
		$this->validationFactory = new ValidationFactory($translator);
	}

	/**
     * Set the matches
     *
     * @param array $matches
     * @return \Laurentvw\LavaCrawler\Matcher
     */
    public function setMatches(array $matches)
    {
        $this->matches = $matches;

        return $this;
    }

    /**
     * Set the filter to be applied to the matches
     *
     * @param \Closure $filter
     * @return \Laurentvw\LavaCrawler\Matcher
     */
    public function setFilter($filter = null)
    {
        $this->filter = is_callable($filter) ? $filter : null;

        return $this;
    }

	public function getErrors()
	{
		return $this->errors;
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
			$this->errors .= 'Validation failed for: ' . "\r\n";
			foreach ($validator->messages()->getMessages() as $name => $messages)
			{
				foreach ($messages as $message)
				{
					$this->errors .= var_export($result[$name], true) . ': ' . $message . "\r\n";
				}
			}

			$this->errors .= "\r\n";

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