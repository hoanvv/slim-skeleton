<?php

namespace Hoanvv\App\Test;

use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Uri;
use Slim\Psr7\Environment;

trait MakeHttpRequest
{
    public function get(
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): TestResponse {
        return $this->request('get', $path, $data, $optionalHeaders, $cookies);
    }

    public function post(
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): TestResponse {
        return $this->request('post', $path, $data, $optionalHeaders, $cookies);
    }

    public function patch(
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): TestResponse {
        return $this->request('patch', $path, $data, $optionalHeaders, $cookies);
    }

    public function put(
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): TestResponse {
        return $this->request('put', $path, $data, $optionalHeaders, $cookies);
    }

    public function delete(
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): TestResponse {
        return $this->request('delete', $path, $data, $optionalHeaders, $cookies);
    }

    public function head(
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): TestResponse {
        return $this->request('head', $path, $data, $optionalHeaders, $cookies);
    }

    public function options(
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): TestResponse {
        return $this->request('options', $path, $data, $optionalHeaders, $cookies);
    }

    public function request(
        string $method,
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): TestResponse {
        // Create request
        $request = $this->create($method, $path, $data, $optionalHeaders, $cookies);
        // Process request
        $response = $this->app->handle($request);
        // Return the application output.
        return new TestResponse($response);
    }

    protected function create(
        string $method,
        string $path,
        array $data = [],
        array $optionalHeaders = [],
        array $cookies = []
    ): Request {
        $method = strtoupper($method);
        $options = array(
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $path
        );

        if ($method === 'GET') {
            $query = $options['QUERY_STRING'] = http_build_query($data);
        } else {
            $params = json_encode($data);
        }

        $uri = new Uri('', '', 80, $path, $query ?? '');
        $server = Environment::mock(array_merge($options, $optionalHeaders));
        $headers = new Headers();

        foreach ($optionalHeaders as $name => $value) {
            $headers->addHeader($name, $value);
        }

        $handle = fopen('php://temp', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($handle);

        if (isset($params)) {
            $headers->setHeader('Content-Type', 'application/json;charset=utf8');
            $stream->write($params);
        }

        return new Request($method, $uri, $headers, $cookies, $server, $stream);
    }
}
