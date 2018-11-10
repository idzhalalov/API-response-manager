<?php

use Unirest\Response as UniResponse;

class ResponseManager
{
    /**
     * Create a particular type of a response
     *
     * @param int   $statusCode HTTP status code
     * @param array $result     Data for send
     *
     * @return Response200 (Response206 | Response304 | Response401 | Response404 | Response405 | Response500)
     *
     * @example ResponseManager::getInstance(500)->send()->finish();
     */
    public static function getInstance($statusCode, $result = [])
    {
        // @todo rid of switch() and create instances dynamically
        switch ($statusCode) {
            case 200:
                return new Response200($result);
            case 206:
                return new Response206($result);
            case 304:
                return new Response304($result);
            case 401:
                return new Response401($result);
            case 404:
                return new Response404($result);
            case 405:
                return new Response405($result);
            case 500:
                return new Response500($result);
            default:
                return new Response200($result);
        }
    }
}

abstract class Response
{
    protected static $headers;
    protected $result;
    protected $protocolVersion;
    protected $statusCode;
    protected $response;
    protected $responseDriver;
    protected $finishLine;

    /**
     * Base class for all types of response
     *
     * @param array $result
     */
    public function __construct(array $result = [])
    {
        $this->result = $result;

        // Set protocol
        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            $this->protocolVersion = $_SERVER['SERVER_PROTOCOL'];
        } else {
            $this->protocolVersion = "HTTP/1.0";
        }

        // Set a response driver
        $this->responseDriver = 'UniResponse';
    }

    /**
     * Append a header to a response
     *
     * @param mixed $header string or array
     *
     * @throws Exception If $header is empty
     */
    public static function setHeader($header)
    {
        if (empty($header)) {
            throw new Exception('An attempt to set an empty header');
        }
        self::$headers .= self::parseHeader($header);
    }

    /**
     * Get the response object
     *
     * @return object Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    protected static function parseHeader($header)
    {
        if (is_array($header)) {
            $result = implode("\r\n", $header);
            $result .= "\r\n";
        } else {
            $result = $header."\r\n";
        }

        return $result;
    }

    /**
     * Return headers
     *
     * @return string All of defined headers
     */
    public static function getDefinedHeaders()
    {
        return self::$headers;
    }

    protected function sendHeaders()
    {
        if (empty($this->response->headers)) {
            return;
        }

        asort($this->response->headers, SORT_NUMERIC);
        foreach ($this->response->headers as $header => $headerValue) {
            if (is_array($headerValue)) {
                $supposedKey = count($headerValue) - 2;
                $headerKey = isset($headerValue[$supposedKey]) ? $supposedKey : 0;
                $headerValue = $headerValue[$headerKey];
                $this->response->headers[$header] = $headerValue;
            }

            $headerValue = ($header) ? $header.': '.$headerValue : $headerValue;
            header($headerValue, true, $this->statusCode);
        }
    }

    /**
     * Return Response with a content and status code ready to send
     *
     * @return object Response Allows to call the finish() method immediately
     *
     * @throws Exception
     *
     * @example <p>
     * $respCallback = $resp->send()->finish();<br />
     * $respCallback();
     * </p>
     */
    public function send()
    {
        if (is_null($this->statusCode) || ! is_int($this->statusCode)) {
            throw new Exception('Response status code is not set');
        }

        if ( ! empty($this->result)) {
            $length = mb_strlen(json_encode($this->result));
            self::setHeader('Content-Length: '.$length);
            self::setHeader('Content-Type: application/json');
            $result = function () {
                echo $this->response->raw_body;
            };
        } else {
            $result = function () {};
        }

        // Send headers
        $clientHeaders = trim(self::$headers);
        $this->response = new UniResponse($this->statusCode,
            json_encode($this->result), $clientHeaders);
        $this->sendHeaders();

        // Send the data
        $result();
        $this->finishLine = function () {
            exit();
        };

        return $this;
    }

    /**
     * Finish the response and execute some additional code
     */
    public function finish()
    {
        $this->finishLine();
    }

    /*
     * The method is intended for safety execute
     * of closure function through the finish() method
     */
    public function __call($method, $args)
    {
        if (is_callable(array($this, $method))) {
            return call_user_func_array($this->$method, $args);
        } else {
            throw new Exception('Call to undefined method '.$method);
        }
    }
}

class Response200 extends Response
{
    public function send()
    {
        $this->statusCode = 200;
        self::setHeader($this->protocolVersion." {$this->statusCode} OK");

        return parent::send();
    }
}

class Response206 extends Response
{
    public function send()
    {
        $this->statusCode = 206;
        self::setHeader($this->protocolVersion
            ." {$this->statusCode} Partial Content");

        return parent::send();
    }
}

class Response304 extends Response
{
    public function send()
    {
        $this->result = null;
        $this->statusCode = 304;
        self::setHeader($this->protocolVersion
            ." {$this->statusCode} Not Modified");

        return parent::send();
    }
}

class Response401 extends Response
{
    public function send()
    {
        $this->statusCode = 401;
        self::setHeader($this->protocolVersion
            ." {$this->statusCode} Unauthorized");

        return parent::send();
    }
}

class Response404 extends Response
{
    public function send()
    {
        $this->statusCode = 404;
        self::setHeader($this->protocolVersion
            ." {$this->statusCode} Not Found");

        return parent::send();
    }
}

class Response405 extends Response
{
    public function send()
    {
        $this->statusCode = 405;
        self::setHeader($this->protocolVersion
            ." {$this->statusCode} Method Not Allowed");

        return parent::send();
    }
}

class Response500 extends Response
{
    public function send()
    {
        $this->result = null;
        $this->statusCode = 500;
        self::setHeader($this->protocolVersion
            ." {$this->statusCode} Internal Server Error");

        return parent::send();
    }
}
