<?php

namespace Tests\Application\Mail;

use Hoanvv\Test\TestCase;
use Slim\App;
use \Exception;
use \DateTimeImmutable;
use Hoanvv\App\Mail\RabbitMqConnection;

class MailControllerTest extends TestCase
{
    const senderData = [
        'msg' => 'RabbitMQ_test',
        'delay' => 3
    ];

    public function createApplication(): App
    {
        return (require __DIR__ . '/../../config/bootstrap.php');
    }
    public function setUpData()
    {
        // do something
    }

    /**
     * Simple testcodes for RabbitMQ API
     */

    /**
     * Failed to send msg because RabbitMQ is down
     * 
     * This testcode is for checking the methods that are called inside __construct
     */
    public function testSendMessageFailedBecauseRabbitMqFailed()
    {
        $className = 'Hoanvv\App\Mail\RabbitMqConnection';
        // Get mock, without the constructor being called
        $mock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();

        // set expectations for constructor calls
        $mock->expects(self::once())
            ->method('connect')
            ->with(
                $this->equalTo([]) //if empty $config, [] is default
            );

        // now call the constructor
        $reflectedClass = new \ReflectionClass($className);
        $constructor = $reflectedClass->getConstructor();

        $constructor->invoke($mock);
    }

    /**
     * Send msg successfully to RabbitMQ queue (run server before sending)
     */
    public function testSendMessageSuccess()
    {
        $url = $this->parseToRouteUrl('rabbit.sender');
        $res = $this->post($url, self::senderData);
        $body = $res->getBody();
        $expectedMsg = self::senderData['msg'] . ' ' . (new DateTimeImmutable())->format('Y-m-d');
        $expectedDelay = self::senderData['delay'];

        $actualMsg = $body['data']['message']['msg'];
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertTrue(str_contains($actualMsg, $expectedMsg), json_encode($res->getBody()));
        $this->assertTrue($expectedDelay == $body['data']['message']['delay'], "Invalid delay sent");
    }
}
