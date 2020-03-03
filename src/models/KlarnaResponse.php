<?php


namespace ellera\commerce\klarna\models;

use craft\commerce\base\RequestResponseInterface;
use yii\helpers\ArrayHelper;

class KlarnaResponse implements RequestResponseInterface
{
	/**
	 * Klarna API Endpoint
	 *
	 * @var string
	 */
	public $endpoint;

	/**
	 * Klarna Request Body
	 *
	 * @var array
	 */
	public $body;


	/**
	 * Stores the raw json response
	 *
	 * @var string
	 */
	private $raw_response;

	/**
	 * Stores the JSON Decoded Response Object
	 *
	 * @var object
	 */
	private $response;

	/**
	 * Transaction reference
	 *
	 * @var string
	 */
	private $reference;

	/**
	 * KlarnaResponse constructor.
	 *
	 * @param string $method
	 * @param string $url
	 * @param string $endpoint
	 * @param string $username
	 * @param string $password
	 * @param array  $body
	 * @param array  $headers
	 *
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function __construct(string $method, string $url, string $endpoint, string $username, string $password, array $body = [], array $headers = [])
	{
		// Create the Guzzle HTTP Client
		$client = new \GuzzleHttp\Client(['base_uri' => $url]);

		$this->endpoint = $endpoint;
		$this->body = $body;


		// Generate Authorization Header
		$credentials = $username . ':' . $password;

		$authorization = 'Basic ' . base64_encode( $credentials );

		// Build the request
		$request = [
			'headers' => $this->generateHeaders($authorization, $headers),
			'json' => $body
		];

		// Send the request
		$this->raw_response = $client->request($method, $endpoint, $request);
		$this->response = json_decode($this->raw_response->getBody());
		if(isset($this->response->order_id)) $this->setTransactionReference($this->response->order_id);
	}

	/**
	 * Generate Headers for API requests
	 *
	 * @param string $authorization
	 * @param array  $additional_headers
	 *
	 * @return array
	 */
	private function generateHeaders(string $authorization, array $additional_headers = []): array
	{
		$default_headers = [
			'Authorization' => $authorization,
			'Content-Type' => 'application/json'
		];

		return ArrayHelper::merge($default_headers, $additional_headers);
	}

	/**
	 * Returns whether or not the payment was successful.
	 *
	 * @return bool
	 */
	public function isSuccessful(): bool
	{
		return 200 <= $this->raw_response->getStatusCode() && $this->raw_response->getStatusCode() < 300;
	}

	/**
	 * Returns whether or not the payment is being processed by gateway.
	 *
	 * @return bool
	 */
	public function isProcessing(): bool
	{
		return false;
	}

	/**
	 * Returns whether or not the user needs to be redirected.
	 *
	 * @return bool
	 */
	public function isRedirect(): bool
	{
		return false;
	}

	/**
	 * Returns the redirect method to use, if any.
	 *
	 * @return string
	 */
	public function getRedirectMethod(): string
	{

	}

	/**
	 * Returns the redirect data provided.
	 *
	 * @return array
	 */
	public function getRedirectData(): array
	{

	}

	/**
	 * Returns the redirect URL to use, if any.
	 *
	 * @return string
	 */
	public function getRedirectUrl(): string
	{

	}

	/**
	 * Returns the transaction reference.
	 *
	 * @return string
	 */
	public function getTransactionReference(): string
	{
		return $this->reference;
	}

	/**
	 * @param $reference
	 *
	 * @return string
	 */
	public function setTransactionReference(string $reference): string
	{
		$this->reference = $reference;
		return $this->reference;
	}

	/**
	 * Returns the response code.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->raw_response->getStatusCode();
	}

	/**
	 * Returns the data.
	 *
	 * @return mixed
	 */
	public function getData()
	{
		return $this->response;
	}

	/**
	 * Returns the gateway message.
	 *
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->raw_response->getReasonPhrase();
	}

	/**
	 * Perform the redirect.
	 *
	 * @return mixed
	 */
	public function redirect()
	{
		return null;
	}

	public function get()
	{
		return $this->response;
	}
}