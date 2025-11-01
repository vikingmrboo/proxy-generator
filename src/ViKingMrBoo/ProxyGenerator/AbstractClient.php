<?php

namespace ViKingMrBoo\ProxyGenerator;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractClient
{
    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(HttpClientInterface $httpClient, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    protected function request($method, $url, ?object $requestModel = null, ?string $responseClass = null, $options = []): ?object
    {
        $clientOptions = $options['client_options'] ?? [];
        $logContext = $options['log_context'] ?? [];

        if (null !== $requestModel) {
            if (!isset($options['content_type'])) {
                throw new \InvalidArgumentException("Missing 'content_type' option in request model");
            }

            $contentType = $options['content_type'];

            if (!$format = self::getFormatByContentType($contentType)) {
                throw new \InvalidArgumentException("Unsupported request model '$contentType'");
            }

            $clientOptions['body'] = $this->serializer->serialize($requestModel, $format);
            $logContext['request_body'] = $requestModel;
        }

        $this->logger->debug("Requesting {$method} {$url}", $logContext);

        try {
            $response = $this->httpClient->request($method, $url, $clientOptions);
            $responseContent = $response->getContent();
            $statusCode = $response->getStatusCode();
            $logContext['response_body'] = $responseContent;
            $logContext['response_code'] = $statusCode;
        } catch (HttpExceptionInterface $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $responseContent = $response->getContent(false);
            $logContext['exception'] = $e;
            $logContext['response_body'] = $responseContent;
            $logContext['response_code'] = $statusCode;

            if (empty($options['skip_errors'])) {
                $this->logger->error("Failed to request {$method} {$url}", $logContext);
                throw $e;
            }

            if (!isset($options['error_model_mapping'])) {
                $this->logger->error("Failed to request {$method} {$url}", $logContext);
                throw new \InvalidArgumentException("Missing 'error_model_mapping' option in request model");
            }

            $mapping = $options['error_model_mapping'];

            if (isset($mapping[$statusCode])) {
                $responseClass = $mapping[$statusCode];
            } elseif (isset($mapping['*'])) {
                $responseClass = $mapping['*'];
            } else {
                $this->logger->error("Failed to request {$method} {$url}", $logContext);
                throw new \InvalidArgumentException("No mapping for response status code '{$statusCode}'");
            }
        }

        if (empty($responseContent)) {
            $this->logger->info("Got response {$method} {$url}", $logContext);
            return null;
        }

        if (null === $responseClass) {
            $this->logger->info("Got response {$method} {$url}", $logContext);
            return null;
        }

        $headers = $response->getHeaders();

        if (isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];

            if (!$format = self::getFormatByContentType($contentType)) {
                $this->logger->info("Got response {$method} {$url}", $logContext);
                throw new \InvalidArgumentException("Unsupported request model '$contentType'");
            }
        } elseif (isset($options['response_format'])) {
            $format = $options['response_format'];
        } else {
            $this->logger->info("Got response {$method} {$url}", $logContext);
            throw new \InvalidArgumentException('Unsupported response format');
        }

        $responseModel = $this->serializer->deserialize($responseContent, $responseClass, $format);
        $logContext['response_body'] = $responseModel;
        $this->logger->info("Got response {$method} {$url}", $logContext);

        return $responseModel;
    }

    private static function getFormatByContentType(string $contentType): ?string
    {
        if ('application/json' === $contentType) {
            return 'json';
        }

        if ('application/xml' === $contentType) {
            return 'xml';
        }

        return null;
    }
}