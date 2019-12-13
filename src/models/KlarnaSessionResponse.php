<?php


namespace ellera\commerce\klarna\models;

class KlarnaSessionResponse extends KlarnaBaseResponse
{
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
     * Return Session ID if exists
     *
     * @return string|null
     */
    public function getSessionId()
    {
        if(!isset($this->response->session_id)) return null;
        return $this->response->session_id;
    }

    /**
     * Return Auth Token if exists
     *
     * @return string|null
     */
    public function getAuthToken()
    {
        die(var_dump($this->response));
        if(!isset($this->response->authorization_token)) return null;
        return $this->response->authorization_token;
    }

}