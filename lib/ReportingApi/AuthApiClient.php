<?php

/*
 * This class connects Inbenta's Authentification and validates/caches a valid token for requests
 */

namespace Inbenta\ReportingApi;

use GuzzleHttp\Client as Guzzle;
use \Exception;

class AuthApiClient
{
	protected $productUrl;
	protected $key;
	protected $secret;
	protected $accessToken;
	protected $ttl;
	protected $methods;
	protected $cachePath;
	protected $version;
	protected $product;
	const     TOKEN_REFRESH_OFFSET  = 180;       // Time in seconds before access-token-expiration when it should be refreshed
	const     AUTH_URL = "https://api.inbenta.io";

	/**
	 * BaseAPIClient constructor
	 * @param string $api_key    Case Management API key
	 * @param string $api_secret Case Management API secret
	 * @param string $version SDK version to be used - default 1
	 */
	function __construct($api_key, $api_secret, $version = '1')
	{
		$this->version = 'v' . $version;
		$this->key = $api_key;
		$this->secret = $api_secret;
		$this->cachePath = rtrim(sys_get_temp_dir(), '/') . '/';
		$this->cachedAccessTokenFile = $this->cachePath . "cached-accesstoken-" . preg_replace("/[^A-Za-z0-9 ]/", '', $this->key);
	}

	/**
	 * Check the cached access token and get a new one if it is expired
	 * @return void
	 */
	protected function updateAccessToken()
	{
		// Check if the current token is valid
		if (!$this->validAccessToken()) {
			// If not valid, try to get it from cache
			$this->getAccessTokenFromCache();
			// If there isn't a cached token or it is expired, get a new one from API
			if (!$this->validAccessToken()) {
				$this->getAccessTokenFromAPI();
			} elseif (($this->ttl - self::TOKEN_REFRESH_OFFSET) <= time()) {
				// Refresh access token before it expires (during the token_refresh_offset)
				$this->refreshAccessToken();
			}
		}
	}

	/**
	 * Check if the current access token is not expired
	 * @return boolean True if current accessToken is not expired.
	 */
	protected function validAccessToken()
	{
		return !is_null($this->accessToken) && !is_null($this->ttl) && $this->ttl > time();
	}

	/**
	 * Get the accessToken information from cache
	 * @return void
	 */
	protected function getAccessTokenFromCache()
	{
		$cachedAccessToken          = file_exists($this->cachedAccessTokenFile) ? json_decode(file_get_contents($this->cachedAccessTokenFile)) : null;
		$cachedAccessTokenExpired   = is_object($cachedAccessToken) && isset($cachedAccessToken->expiration) ? $cachedAccessToken->expiration < time() : true;

		if (is_object($cachedAccessToken) && !empty($cachedAccessToken) && !$cachedAccessTokenExpired) {
			$this->accessToken = $cachedAccessToken->accessToken;
			$this->ttl         = $cachedAccessToken->expiration;
			$this->methods     = $cachedAccessToken->apis;
		}
	}

	/**
	 * Retrieve a new access token from the API
	 * @return void
	 */
	protected function getAccessTokenFromAPI()
	{
		$headers = array(
			'x-inbenta-key' => $this->key,
			'Content-Type' => 'application/x-www-form-urlencoded'
		);
		$params = array('secret' => $this->secret);

		$accessInfo = $this->call($this->getAuthUrl("/auth"), "POST", $headers, $params);



		// Verify success response from API
		if (!isset($accessInfo->accessToken) || isset($accessInfo->messsage) && $accessInfo->message == 'Unauthorized') {
			throw new Exception("Invalid key/secret");
		}
		$this->accessToken  = $accessInfo->accessToken;
		$this->ttl          = $accessInfo->expiration;
		$this->methods      = $accessInfo->apis;

		// Store token information to cache file
		file_put_contents($this->cachedAccessTokenFile, json_encode($accessInfo));
	}

	/**
	 * Exchange the current -valid- access token for a new one before it expires
	 * @return void
	 */
	protected function refreshAccessToken()
	{
		$headers = array(
			"x-inbenta-key" => $this->key,
			"Authorization" => "Bearer " . $this->accessToken
		);
		$accessInfo = $this->call($this->getAuthUrl("/refreshToken"), "POST", $headers);

		// Verify success response from API
		if (!isset($accessInfo->accessToken) || isset($accessInfo->messsage) && $accessInfo->message == 'Unauthorized') {
			throw new Exception("Invalid key/secret");
		}
		$this->accessToken  = $accessInfo->accessToken;
		$this->ttl          = $accessInfo->expiration;
		// Set the API methods in the $accessInfo data from cache because the /refresToken endpoint does not return this data
		$accessInfo->apis   = $this->methods;

		// Store token information to cache file
		file_put_contents($this->cachedAccessTokenFile, json_encode($accessInfo));
	}

	/**
	 * Get full Auth URL
	 * 
	 * @return String Auth URL
	 */
	protected function getAuthUrl($path)
	{
		return self::AUTH_URL . '/' . $this->version . $path;
	}

	/**
	 * Get full Product URL
	 * 
	 * @return String Product URL
	 */
	protected function getProductUrl($path)
	{
		return $this->productUrl . '/' . $this->version . $path;
	}

	/**
	 * Process rate limit headers and proceed as needed
	 * TODO
	 * @param $headers Request Headers
	 */
	// protected function checkRateLimits($headers) {

	// }

	/** 
	 *  Performs a request through Guzzle with the specified parameters
	 *  @param $path - string - Inbenta API route
	 *  @param $method - string - HTTP method
	 *  @param $headers - array - Request headers
	 *  @param $params - object - Request parameters
	 *  @return object
	 */
	protected function call($url, $method, $headers = array(), $params = array())
	{
		if (array_key_exists('Authorization', $headers)) {
			self::updateAccessToken();
			$headers['Authorization'] = "Bearer " . $this->accessToken;
		}

		$client = new Guzzle();

		try {
			$dataName = (isset($headers['Accept']) && $headers['Accept'] == 'application/json') ?  'json' : 'form_params';
			$response = $client->request(
				$method,
				$url,
				[
					'headers' => $headers,
					$dataName => $params
				]
			);

			return json_decode($response->getBody()->getContents());
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			return (object) array(
				"error" => $e->getResponse()->getStatusCode(),
				"message" => $e->getResponse()->getBody()->getContents(),
			);
		}
	}
}
