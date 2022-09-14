<?php

namespace Hoanvv\Test;

use Hoanvv\App\Factory\DatabaseFactory;
use Exception;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use ReflectionClass;
use ReflectionException;
use Slim\App;
use Slim\Interfaces\RouteParserInterface;

abstract class TestCase extends PHPUnit_TestCase
{
    use MakeHttpRequest;

    protected App $app;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->refreshApplication();
    }

    /**
     * @param string $class
     * @param array $methods
     * @param bool $useOriginalConstructor
     * @return object
     * @throws ReflectionException
     */
    public function prepareMock(string $class, array $methods, bool $useOriginalConstructor = false): object
    {
        if ($useOriginalConstructor) {
            return $this->prepareMockUsingOriginalConstructor($class, $methods);
        }

        return $this->prepareMockDisableOriginalConstructor($class, $methods);
    }

    /**
     * @param string $className
     * @param array $methods
     * @return object
     * @throws ReflectionException
     */
    public function prepareMockDisableOriginalConstructor(string $className, array $methods): object
    {
        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $class
     * @param array $methods
     * @return object
     * @throws ReflectionException
     */
    public function prepareMockUsingOriginalConstructor(string $class, array $methods): object
    {
        return $this->getMockBuilder(get_class(new $class()))
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Set a given protected property of a given class instance to a given value.
     *
     * Note: Please use this methods only for static 'mocking' or with other hard reasons!
     *       For the most possible non static usages there exist other solutions.
     *
     * @param object $classInstance Instance of the class of which the property will be set
     * @param string $property Name of the property to be set
     * @param mixed $value Value to which the property will be set
     * @throws ReflectionException
     */
    protected function setProtectedClassProperty(object $classInstance, string $property, $value): void
    {
        $className = get_class($classInstance);

        $reflectionClass = new ReflectionClass($className);

        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($classInstance, $value);
    }

    /**
     * Get a given protected property of a given class instance.
     *
     * Note: Please use this methods only for static 'mocking' or with other hard reasons!
     *       For the most possible non static usages there exist other solutions.
     * @param object $classInstance Instance of the class of which the property will be set
     * @param string $property Name of the property to be retrieved
     *
     * @return mixed
     * @throws ReflectionException
     */
    protected function getProtectedClassProperty(object $classInstance, string $property)
    {
        $className = get_class($classInstance);

        $reflectionClass = new ReflectionClass($className);

        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($classInstance);
    }

    public function createAccessToken(array $data = []): string
    {
        $data = array_merge($data, [
            'user' => 'test',
            'password' => 'test',
        ]);
        $res = $this->post('/v1/auth/login', $data);
        $this->assertResponseStatus($res, 200);
        $body = $res->getBody();
        $token = $body['token'] ?? '';

        return 'Bearer ' . $token;
    }

    public function createAuthorizationHeader(): array
    {
        // Create access token with account: test/test
        $accessToken = $this->createAccessToken();

        return ['HTTP_AUTHORIZATION' => $accessToken];
    }

    /**
     * Creates the application.
     *
     * Needs to be implemented by subclasses.
     */
    abstract public function createApplication(): App;

    public function assertResponseStatus(TestResponse $response, int $statusCode): void
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    public function getRouteParser(): RouteParserInterface
    {
        return $this->app->getRouteCollector()->getRouteParser();
    }

    public function parseToRouteUrl(string $name, $data = [], $query = []): string
    {
        return $this->getRouteParser()->urlFor($name, $data, $query);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws Exception
     */
    public function getDatabaseFactory(): DatabaseFactory
    {
        $container = $this->app->getContainer();
        if (empty($container)) {
            throw new Exception('Container was not defined');
        }

        return $container->get(DatabaseFactory::class);
    }

    /**
     * Refresh the application instance.
     */
    protected function refreshApplication(): void
    {
        $this->app = $this->createApplication();
    }

    /**
     * @param TestResponse $res
     */
    public function assertUnauthorizedResponse(TestResponse $res): void
    {
        $this->assertErrorResponse($res, 'Unauthorized.', 401);
    }

    public function assertErrorResponse(TestResponse $res, string $message, int $code): void
    {
        $this->assertEquals($code, $res->getStatusCode());
        $body = $res->getBody();
        $this->assertIsArray($body);
        $this->assertArrayHasKey('code', $body);
        $this->assertArrayHasKey('message', $body);
        $this->assertEquals($code, $body['code']);
        $this->assertSame($message, $body['message']);

        if (function_exists('IS_DEBUG') && IS_DEBUG()) {
            $this->assertArrayHasKey('exception', $body);
            $this->assertIsArray($body['exception']);
            $this->assertGreaterThan(0, count($body['exception']));
            $this->assertArrayHasKey(0, $body['exception']);
            $this->assertIsArray($body['exception'][0]);
            $this->assertArrayHasKey('type', $body['exception'][0]);
            $this->assertArrayHasKey('code', $body['exception'][0]);
            $this->assertArrayHasKey('message', $body['exception'][0]);
            $this->assertArrayHasKey('file', $body['exception'][0]);
            $this->assertArrayHasKey('line', $body['exception'][0]);
            $this->assertEquals($code, $body['exception'][0]['code']);
            $this->assertSame($message, $body['exception'][0]['message']);
        }
    }
}
