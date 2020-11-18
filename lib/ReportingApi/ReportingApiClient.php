<?php

/**
 * This class connects Inbenta's Authentification and validates/caches a valid token for requests
 * Also gives to the end user several functionalities from the Reporting API
 */

namespace Inbenta\ReportingApi;

use Inbenta\ReportingApi\AuthApiClient as Client; //TEMPORAL UNTIL THE AuthApiClient IS PUBLIC 
//use Inbenta\Auth\AuthApiClient as Client; 
use Inbenta\ApiSignature\SignatureClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client as Guzzle;
use \Exception;

class ReportingApiClient extends Client
{
	private $signature;
	/**
	 * ReportingAPIClient constructor
	 * @param string $key    Reporting API key
	 * @param string $secret Reporting API secret
	 * @param string $signature Reporting API signature
	 */
	function __construct($key, $secret, $signature = null)
	{
		parent::__construct($key, $secret);
		if ($signature) {
			$this->signature = $signature;
		} else {
			throw new Exception("Missing signature key");
		}
	}

	/**
	 * Retrieve a new access token from the API
	 * @return void
	 */
	protected function updateAccessToken()
	{
		parent::updateAccessToken();
		$this->url = $this->methods->reporting;
	}

	/**
	 * Get user questions
	 * @param  array $queryString Query string with all the available filters (env, user_type, source, date_format[iso or unix], date_from [iso or unix], date_to, user_question, log_type, has_matching [boolean], id_content, properties, sort, length, offset)
	 * @return object Endpoint response or guzzle error
	 */
	public function getUserQuestions($queryString = array())
	{
		return $this->getRequest("/events/user_questions", $queryString);
	}
	/**
	 * Get users
	 * @param  array $id event id string
	 * @param  string $queryString Query string with all the available filters (properties and date_format)
	 * @return object Endpoint response or guzzle error
	 */
	public function getUserQuestionsById($id = null, $queryString = array())
	{
		return $this->getRequest("/events/user_questions/" . $id, $queryString);
	}

	/**
	 * Get sessions
	 * @param  array $queryString Query string with all the available filters (env, user_type, source, date_format[iso or unix], date_from [iso or unix], date_to, session_id, log_id, key, value, user_question, id_content, properties, sort, length, offset)
	 * @return object Endpoint response or guzzle error
	 */
	public function getSessions($queryString = array())
	{
		return $this->getRequest("/events/sessions", $queryString);
	}

	/**
	 * Get users
	 * @param  array $id event id string
	 * @param  string $queryString Query string with all the available filters (properties and date_format)
	 * @return object Endpoint response or guzzle error
	 */
	public function getSessionById($id = null, $queryString = array())
	{
		return $this->getRequest("/events/sessions/" . $id, $queryString);
	}

	/**
	 * Get sessions
	 * @param  array $queryString Query string with all the available filters (env, user_type, source, date_format[iso or unix], date_from [iso or unix], date_to, session_id, log_id, key, value, user_question, id_content, properties, sort, length, offset)
	 * @return object Endpoint response or guzzle error
	 */
	public function getClicks($queryString = array())
	{
		return $this->getRequest("/events/clicks", $queryString);
	}
	/**
	 * Get users
	 * @param  array $id event id string
	 * @param  string $queryString Query string with all the available filters (properties and date_format)
	 * @return object Endpoint response or guzzle error
	 */
	public function getClicksById($id = null, $queryString = array())
	{
		return $this->getRequest("/events/clicks/" . $id, $queryString);
	}

	/**
	 * Get sessions
	 * @param  array $queryString Query string with all the available filters (env, user_type, source, date_format[iso or unix], date_from [iso or unix], date_to, session_id, log_id, key, value, user_question, id_content, properties, sort, length, offset)
	 * @return object Endpoint response or guzzle error
	 */
	public function getRatings($queryString = array())
	{
		return $this->getRequest("/events/ratings", $queryString);
	}

	/**
	 * Get users
	 * @param  array $id event id string
	 * @param  string $queryString Query string with all the available filters (properties and date_format)
	 * @return object Endpoint response or guzzle error
	 */
	public function getRatingsById($id = null, $queryString = array())
	{
		return $this->getRequest("/events/ratings/" . $id, $queryString);
	}

	/**
	 * Get sessions
	 * @param  array $queryString Query string with all the available filters (env, user_type, source, date_format[iso or unix], date_from [iso or unix], date_to, session_id, log_id, key, value, user_question, id_content, properties, sort, length, offset)
	 * @return object Endpoint response or guzzle error
	 */
	public function getAggregates($queryString = array())
	{
		return $this->getRequest("/aggregates", $queryString);
	}

	/**
	 * Get users
	 * @param  array $id event id string
	 * @param  string $queryString Query string with all the available filters (properties and date_format)
	 * @return object Endpoint response or guzzle error
	 */
	public function getAggregatesById($id = null, $queryString = array())
	{
		return $this->getRequest("/aggregates/" . $id, $queryString);
	}

	/**
	 * Get sessions
	 * @param  array $queryString Query string with all the available filters (env, user_type, source, date_format[iso or unix], date_from [iso or unix], date_to, session_id, log_id, key, value, user_question, id_content, properties, sort, length, offset)
	 * @return object Endpoint response or guzzle error
	 */
	public function getAggributes($queryString = array())
	{
		return $this->getRequest("/aggributes", $queryString);
	}

	/**
	 * Get users
	 * @param  array $id event id string
	 * @param  string $queryString Query string with all the available filters (properties and date_format)
	 * @return object Endpoint response or guzzle error
	 */
	public function getAggributesById($id = null, $queryString = array())
	{
		return $this->getRequest("/aggributes/" . $id, $queryString);
	}

	/**
	 * Undocumented function
	 *
	 * @param string $endpoint
	 * @param array $query
	 * @return object Endpoint response or guzzle error
	 */
	public function getRequest($endpoint, $query = array())
	{
		$queryString = count($query) ? '?' . http_build_query($query) : '';
		// Headers
		$this->updateAccessToken();

		$headers = array(
			"x-inbenta-key" => $this->key,
			"Authorization" => "Bearer " . $this->accessToken
		);

		$response = $this->callThis($endpoint . $queryString, "GET", $headers);
		return $response;
	}

	/** 
	 *  Performs a request through Guzzle with the specified parameters
	 *  @param string $path - Inbenta API route
	 *  @param string $method - HTTP method
	 *  @param array $headers Request headers
	 *  @param object $params Request parameters
	 *  @return object
	 */
	protected function callThis($path, $method, $headers = array(), $params = array())
	{
		if (in_array('Authorization', $headers)) {
			$this->updateAccessToken();
		}

		$request = new Request(
			$method = 'GET',
			$url = $this->url . '/' . $this->version . $path,
			$headers
		);

		//Sign request
		$signatureClient = new SignatureClient($this->url, $this->signature);
		$request = $signatureClient->signRequest($request);

		$client = new Guzzle();
		try {
			$dataName = (isset($headers['Accept']) && $headers['Accept'] == 'application/json') ?  'json' : 'form_params';
			$response = $client->request(
				$method,
				$this->url . "/" . $this->version . $path,
				[
					'headers' => $request->getHeaders(),
					$dataName => $params,
				]
			);
			if (method_exists($response, 'getBody')) {
				return json_decode($response->getBody()->getContents());
			}
			return (object) ["error" => "Error on connect"];
			
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			return (object) array(
				"error" => $e->getResponse()->getStatusCode(),
				"message" => $e->getResponse()->getBody()->getContents(),
			);
		}
	}
}
