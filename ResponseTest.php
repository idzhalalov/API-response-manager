<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Response.php';

use PHPUnit\Framework\TestCase;

class ResponseForTests extends Response
{
    protected $statusCode = 206;

    public function finish()
    {
        throw new RuntimeException('Exit');
    }
}

class ResponseTest extends TestCase
{
    protected $successfulApiResultArray;
    protected $successfulJsonResponse;

    protected function setUp()
    {
        $this->successfulApiResultArray = ['result' => 'success', 'message' => 'ok'];
        $this->successfulJsonResponse = json_encode($this->successfulApiResultArray);
    }

    protected function tearDown()
    {
    }
    
    /**
     * Can create a response of status 200
     */
    public function testCanCreateResponse200()
    {
        $response = new Response200($this->successfulApiResultArray);
        $this->assertTrue($response instanceof Response200);
    }
    
    /**
     * Can invoke finish() method and finish the program
     */
    public function testCanCreateExitAfterSending()
    {
        $response = new ResponseForTests($this->successfulApiResultArray);
        $response = $response->send();

        $this->expectException(RuntimeException::class);
        $response->finish();
    }

    /**
     * Can send a response with 200 code and content
     */
    public function testCanSendResponse200()
    {
        $response = new Response200($this->successfulApiResultArray);
        
        ob_start();
        $response->send();
        $resultString = ob_get_clean();
        $resultJson = $resultString;
        
        $this->assertJson($resultJson);
        $this->assertJsonStringEqualsJsonString($this->successfulJsonResponse, $resultJson);
    }

    /**
     * Can create a response which is has default headers
     */
    public function testCanSeeDefaultHeaders()
    {
        $response = new Response200($this->successfulApiResultArray);
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertEquals(3, count($responseResult->headers), 'Current response: ' .var_dump($responseResult->headers));
    }
   
    /**
     * Can create response with a status code 304 and content
     * however Content-length will be equal 0
     */
    public function testCanSendResponse304()
    {
        $response = new Response304($this->successfulApiResultArray);
        $response->send();
        
        $headers = $response->getDefinedHeaders();
        $this->assertContains('HTTP/1.0 304 Not Modified', $headers, 'Current headers: ' . $headers);
        
        $responseResult = $response->getResponse();
        $this->assertTrue($response instanceof Response304);
        $this->assertEquals('HTTP/1.0 304 Not Modified', $responseResult->headers['0'], 'Current response is: ' .var_dump($responseResult));
        $this->assertArrayNotHasKey('Content-Length', $responseResult->headers);
    }
   
    /**
     * Can create and send a response with 500 code
     */
    public function testCanSendResponse500()
    {
        $response = new Response500($this->successfulApiResultArray);
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertTrue($response instanceof Response500);
        $this->assertEquals('HTTP/1.0 500 Internal Server Error', $responseResult->headers['0'], 'Current response is: ' .var_dump($responseResult));
        $this->assertArrayNotHasKey('Content-Length', $responseResult->headers);
    }
    
    /**
     * Can set a header using a string
     */
    public function testCanSetHeaderAsString()
    {
	    Response::setHeader('Hello: World');

        $response = new Response200($this->successfulApiResultArray);
        $response->setHeader('ApplicationType: SomeAPI.v1');
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertTrue($response instanceof Response200);
        $this->assertEquals('SomeAPI.v1', $responseResult->headers['ApplicationType']);
        $this->assertEquals('World', $responseResult->headers['Hello']);
        $this->assertEquals('35', $responseResult->headers['Content-Length']);
    }
    
    /**
     * Can set a header using an array
     */
    public function testCanSetHeaderAsArray()
    {
        $response = new Response200($this->successfulApiResultArray);
        $headers = ['ApplicationType: SomeAPI.v1', 'Hello: World'];
        $response->setHeader($headers);
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertTrue($response instanceof Response200);
        $this->assertEquals('SomeAPI.v1', $responseResult->headers['ApplicationType']);
        $this->assertEquals('World', $responseResult->headers['Hello']);
    }

    /**
     * Can't set a duplicated header but can redefine any header
    */
    public function testCantSetDuplicatedHeaders()
    {
        $response = new Response200($this->successfulApiResultArray);
        $response->setHeader('Content-Type: application/xml');
        
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertTrue($response instanceof Response200);
        $this->assertEquals('application/xml', $responseResult->headers['Content-Type']);
    }
}
