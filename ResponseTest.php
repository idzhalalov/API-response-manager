<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Response.php';

use PHPUnit\Framework\TestCase;

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
     * Могу создать ответ 200 с контентом
     */
    public function testCanCreateResponse200()
    {
        $response = new Response200($this->successfulApiResultArray);
        $this->assertTrue($response instanceof Response200);
    }
    
    /**
     * Могу отправить ответ 200 и вернется
     * прерывание, выполнив которое, можно завершить сценарий
     */
    public function testCanCreateExitAfterSending()
    {
        $response = new Response200($this->successfulApiResultArray);
        $exit = $response->send();
        $this->assertTrue($exit instanceof Response200);
    }

    /**
     * Могу отправить ответ 200 с контентом
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
     * Могу создать ответ и дефолтные заголовки
     * будут установлены автоматически
     */
    public function testCanSeeDefaultHeaders()
    {
        $response = new Response200($this->successfulApiResultArray);
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertEquals(3, count($responseResult->headers), 'Current response: ' .var_dump($responseResult->headers));
    }
   
    /**
     * Могу отправить ответ 304 с контентом, при этом контент
     * не вернется клиенту, заголовок длины не вернется
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
     * Могу отправить ответ 500
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
     * Могу установить заголовок строкой
     */
    public function testCanSetHeaderAsString()
    {
	    Response::setHeader('Hello: World');

        $response = new Response200($this->successfulApiResultArray);
        $response->setHeader('ApplicationType: MyTaxiApi.v1');
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertTrue($response instanceof Response200);
        $this->assertEquals('MyTaxiApi.v1', $responseResult->headers['ApplicationType']);
        $this->assertEquals('World', $responseResult->headers['Hello']);
        $this->assertEquals('35', $responseResult->headers['Content-Length']);
    }
    
    /**
     * Могу установить заголовок массивом
     */
    public function testCanSetHeaderAsArray()
    {
        $response = new Response200($this->successfulApiResultArray);
        $headers = ['ApplicationType: MyTaxiApi.v1', 'Hello: World'];
        $response->setHeader($headers);
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertTrue($response instanceof Response200);
        $this->assertEquals('MyTaxiApi.v1', $responseResult->headers['ApplicationType']);
        $this->assertEquals('World', $responseResult->headers['Hello']);
    }

    /**
     * Не могу установить дублирующийся заголовок. Могу переопределить любой заголовок
    */
    public function testCantSetDuplicatedHeaders()
    {
        $response = new Response200($this->successfulApiResultArray);
        $response->setHeader('Content-Type: application/xml');
        
        $response->send();
        
        $responseResult = $response->getResponse();
        $this->assertTrue($response instanceof Response200);
        $this->assertEquals('application/xml', $responseResult->headers['Content-Type']);
        $this->assertNotEquals('application/json', $responseResult->headers['Content-Type']);
    }
}
