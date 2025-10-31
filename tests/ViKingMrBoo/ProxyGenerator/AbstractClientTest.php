<?php

namespace Tests\ViKingMrBoo\ProxyGenerator;

use ViKingMrBoo\ProxyGenerator\AbstractClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use PHPUnit\Framework\TestCase;

class AbstractClientTest extends TestCase
{
    private $httpClient;
    private $serializer;
    private $abstractClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->abstractClient = new class($this->httpClient, $this->serializer) extends AbstractClient {};
    }

    public function testRequest()
    {
        $method = 'GET';
        $url = 'https://my.test.host.com/api/v1/info/list?page=123';
        $body = null;
        $options = ['options' => ['timeout' => '2s'], 'name' => 'test-info-list'];

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn('{"page": 123}');

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with($method, $url, ['body' => $body, 'timeout' => $options['options']['timeout']])
            ->willReturn($responseMock);

        $response = $this->abstractClient->request($method, $url, $body, $options);

        $this->assertEquals('{"page": 123}', $response);
    }

    public function testSerialize()
    {
        $data = ['page' => 123];
        $format = 'json';
        $serializedData = '{"page":123}';

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($data, $format)
            ->willReturn($serializedData);

        $result = $this->abstractClient->serialize($data, $format);

        $this->assertEquals($serializedData, $result);
    }

    public function testDeserialize()
    {
        $data = '{"page":123}';
        $type = 'App\Client\Test\Model\Filters';
        $format = 'json';
        $deserializedData = new \App\Client\Test\Model\Filters();
        $deserializedData->setPage(123);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($data, $type, $format)
            ->willReturn($deserializedData);

        $result = $this->abstractClient->deserialize($data, $type, $format);

        $this->assertEquals($deserializedData, $result);
    }
}