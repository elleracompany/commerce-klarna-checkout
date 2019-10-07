<?php


namespace ellera\commerce\klarna\models;

class KlarnaHppSessionResponse extends KlarnaBaseResponse
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
     * Return QR Code if exists
     *
     * @return string|null
     */
    public function getQrCodeUrl()
    {
        if(!isset($this->response->qr_code_url)) return null;
        return $this->response->qr_code_url;
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
     * Return Session URL if exists
     *
     * @return string|null
     */
    public function getSessionUrl()
    {
        if(!isset($this->response->session_url)) return null;
        return $this->response->session_url;
    }

    /**
     * Return distribution URL if exists
     *
     * @return string|null
     */
    public function getDistributionUrl()
    {
        if(!isset($this->response->distribution_url)) return null;
        return $this->response->distribution_url;
    }
}