<?php


namespace ellera\commerce\klarna\models;

use craft\commerce\base\RequestResponseInterface;
use GuzzleHttp\Psr7\Response;
use craft\helpers\ArrayHelper;

class KlarnaBaseResponse implements RequestResponseInterface
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
     * Stores the raw response
     *
     * @var Response
     */
    protected $raw_response;

    /**
     * Stores the JSON Decoded Response Object
     *
     * @var object
     */
    protected $response;

    /**
     * Transaction reference
     *
     * @var string
     */
    protected $reference;
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

    /**
     * Generate Headers for API requests
     *
     * @param string $authorization
     * @param array  $additional_headers
     *
     * @return array
     */
    protected function generateHeaders(string $authorization, array $additional_headers = []): array
    {
        $default_headers = [
            'Authorization' => $authorization,
            'Content-Type' => 'application/json'
        ];

        return ArrayHelper::merge($default_headers, $additional_headers);
    }

    /**
     * Return response as PHP array
     *
     * @return string |null
     */
    public function get()
    {
        if(!isset($this->response)) return null;
        return $this->response;
    }
}