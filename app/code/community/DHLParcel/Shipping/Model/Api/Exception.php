<?php

class DHLParcel_Shipping_Model_Api_Exception extends Exception
{
    protected $request;
    protected $response;
    protected $rawResponse;
    protected $formatedResponse;

    /**
     * DHLParcel_Shipping_Model_Api_Exception constructor.
     * @param string $message
     * @param $request
     * @param $response
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message, $request, $response, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->request = $request;
    }

    public function getRawResponse()
    {
        return Zend_Http_Response::extractBody($this->response);
    }

    /**
     * @return mixed
     */
    public function getFormatedResponse()
    {
        return json_decode($this->getRawResponse(), true);
    }
}
