<?php


namespace ellera\commerce\klarna\klarna;

use craft\commerce\base\RequestResponseInterface;
use ellera\commerce\klarna\gateways\Base;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use craft\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

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
    public $body = [];

    /**
     * Request Headers
     * @var array
     */
    public $headers = [];

    /**
     * Stores the JSON Response
     *
     * @var object
     */
    protected $response;

    /**
     * Stores the JSON Decoded Response Object
     *
     * @var object
     */
    protected $decoded_response;

    /**
     * Transaction reference
     *
     * @var string
     */
    protected $reference;

    /**
     * Gateway
     *
     * @var Base
     */
    protected $gateway;

    /**
     * HTTP Client
     *
     * @var Client
     */
    protected $client;

    /**
     * KlarnaResponse constructor.
     * @param Base $gateway
     */
    public function __construct(Base $gateway)
    {
        // Save the Gateway
        $this->gateway = $gateway;

        // Create a HTTP Client
        $this->client = new Client(['base_uri' => $gateway->getApiUrl()]);
    }

    /**
     * Returns whether or not the payment was successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return 200 <= $this->response->getStatusCode() && $this->response->getStatusCode() < 300;
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
        return true;
    }

    /**
     * Returns the redirect method to use, if any.
     *
     * @return string
     */
    public function getRedirectMethod(): string
    {
        return 'GET';
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
        return $this->response->redirect_url;
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
        return $this->response->getStatusCode();
    }

    /**
     * Returns the data.
     *
     * @return mixed
     */
    public function getRawResponse()
    {
        return $this->response;
    }

    /**
     * @return object
     */
    public function getData()
    {
        return $this->getDecodedResponse();
    }

    /**
     * Returns the gateway message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->response->getReasonPhrase();
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

    /**
     * Generate Headers for API requests
     *
     * @param string $authorization
     * @param array  $additional_headers
     *
     * @return array
     */
    protected function generateHeaders(): array
    {
        $authorization = 'Basic ' . base64_encode($this->gateway->getApiId() . ':' . $this->gateway->getApiPassword());
        $default_headers = [
            'Authorization' => $authorization,
            'Content-Type' => 'application/json'
        ];

        return ArrayHelper::merge($default_headers, $this->headers);
    }

    /**
     * Returns the decoded response as a PHP Object
     *
     * @return object
     */
    public function getDecodedResponse()
    {
        if(!$this->decoded_response) $this->decoded_response = json_decode($this->response->getBody());
        return $this->decoded_response;
    }

    /**
     * Execute a GET request to Klarna
     *
     * @throws InvalidConfigException
     * @throws \yii\base\ErrorException
     */
    protected function get()
    {
        try {
            $request = [
                'headers' => $this->generateHeaders()
            ];
            if(!empty($this->body)) $request['json'] = $this->body;

            $response = $this->client->get(
                $this->endpoint,
                $request
            );
        } catch (ClientException $e) {
            $this->gateway->log($e->getCode() . ': ' . $e->getResponse()->getBody()->getContents());
            throw new InvalidConfigException('Something went wrong when communicating with Klarna. See logs for more information.');
        }

        $this->response = $response;
    }

    /**
     * Execute a POST request to Klarna
     *
     * @throws InvalidConfigException
     * @throws \yii\base\ErrorException
     */
    protected function post()
    {
        try {
            $request = [
                'headers' => $this->generateHeaders()
            ];
            if(!empty($this->body)) $request['json'] = $this->body;

            $response = $this->client->post(
                $this->endpoint,
                $request
            );
        } catch (ClientException $e) {
            $this->gateway->log($e->getCode() . ': ' . $e->getResponse()->getBody()->getContents());
            throw new InvalidConfigException('Something went wrong when communicating with Klarna. See logs for more information.');
        }

        $this->response = $response;
    }
}