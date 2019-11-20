<?php


namespace ellera\commerce\klarna\models;

use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\Transaction;
use Klarna\Rest\HostedPaymentPage\Sessions as HPPSession;
use Klarna\Rest\Payments\Sessions;

class KlarnaHPPResponse implements RequestResponseInterface
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
     * Stores the hpp session
     *
     * @var HPPSession
     */
    protected $hpp_session;

    /**
     * Stores the session request
     *
     * @var Sessions
     */
    protected $session;

    /**
     * Stores the session data
     *
     * @var array
     */
    protected $session_data;

    /**
     * Stores the JSON Decoded Response Object
     *
     * @var object
     */
    protected $response;

    /**
     * Transaction
     *
     * @var Transaction
     */
    protected $transaction;

    public function __construct(Transaction $transaction, KlarnaBasePaymentForm $form, Sessions $session, HPPSession $hpp)
    {
        $this->hpp_session = $hpp;
        $this->session = $session;
        $this->session_data = $this->hpp_session->create($form->getHppSessionRequestBody('https://api.klarna.com/payments/v1/sessions/' .  $session->getId()));
        $this->transaction = $transaction;
    }
    /**
     * Returns whether or not the payment was successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return !empty($this->session_data['redirect_url']) && is_string($this->session_data['redirect_url']);
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
        return $this->session_data['redirect_url'];
    }

    /**
     * Returns the transaction reference.
     *
     * @return string
     */
    public function getTransactionReference(): string
    {
        return $this->session->getId();
    }

    /**
     * Returns the response code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return 200;
    }

    /**
     * Returns the data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->session_data;
    }

    /**
     * Returns the gateway message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return '';
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
}