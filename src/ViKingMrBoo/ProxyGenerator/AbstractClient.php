<?php

namespace ViKingMrBoo\ProxyGenerator;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractClient
{
    protected $httpClient;
    protected $serializer;

    public function __construct(HttpClientInterface $httpClient, SerializerInterface $serializer)
    {
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
    }

    protected function request($method, $url, $body = null, $options = [])
    {
        $response = $this->httpClient->request($method, $url, [
            'body' => $body,
            'timeout' => $options['timeout'],
        ]);

        return $response->getContent();
    }

    protected function serialize($data, $format)
    {
        return $this->serializer->serialize($data, $format);
    }

    protected function deserialize($data, $type, $format)
    {
        return $this->serializer->deserialize($data, $type, $format);
    }
}