<?php namespace Laurentvw\LavaCrawler;

use \Closure;
use \Illuminate\Support\Facades\Validator;

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
	protected $filter;

	/**
     * Create a new Matcher instance.
     *
     * @param array $matches
     * @param \Closure $filter
     * @return void
     */
	function __construct(array $matches, Closure $filter)
	{
		$this->matches = $matches;
		$this->filter = $filter;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function fetch($data)
	{
		$result = array();

		$dataRules = array();

		foreach ($this->matches as $match)
		{
			// Get the match value, optionally apply a function to it
			if (isset($match['apply']))
			{
				$result[$match['name']] = $this->applyTo($match['apply'], $data[$match['id']]);
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
		$validator = Validator::make($result, $dataRules);
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

	public function applyTo($apply, $value)
	{
		if (empty($apply)) return $value;

		array_walk($apply[1], function(&$value, $index, $match) {
			$value = str_replace(':M', $match, $value);
		}, $value);

		return call_user_func_array($apply[0], $apply[1]);
	}

}