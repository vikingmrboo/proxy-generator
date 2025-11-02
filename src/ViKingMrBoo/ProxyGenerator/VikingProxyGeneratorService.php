<?php

namespace ViKingMrBoo\ProxyGenerator;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Util\ClassUtils;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Routing\Annotation\Route;

class VikingProxyGeneratorService
{
    /**
     * @var ProxyGenerator
     */
    private $proxyGenerator;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var CacheProvider
     */
    private $cache;

    public function __construct(ProxyGenerator $proxyGenerator, AnnotationReader $annotationReader, CacheProvider $cache)
    {
        $this->proxyGenerator = $proxyGenerator;
        $this->annotationReader = $annotationReader;
        $this->cache = $cache;
    }

    private static function getUsages(ReflectionClass $reflectionClass): string
    {
        if(!$content = file_get_contents($reflectionClass->getFileName())) {
            throw new \RuntimeException("Unable to read file {$reflectionClass->getFileName()}");
        }

        $pattern = '/use\s+(?:function|const\s+)?\S+(?:\s+as\s+\S+\s+)?;/m';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER, 0);

        return implode("\n", $matches[0]);
    }

    public function generateProxyClass($interfaceName, $config)
    {
        $cacheKey = 'proxy_' . $interfaceName;
        if ($this->cache->contains($cacheKey)) {
            return $this->cache->fetch($cacheKey);
        }


        $reflectionClass = new \ReflectionClass($interfaceName);
        $usages = self::getUsages($reflectionClass);
        $methods = $reflectionClass->getMethods();

        $proxyClassName = $interfaceName . 'Proxy';
        $proxyClassCode = $this->generateProxyClassCode($proxyClassName, $usages, $methods, $config);

        file_put_contents($this->proxyGenerator->getProxyFileName($proxyClassName), $proxyClassCode);

        $this->cache->save($cacheKey, $proxyClassName);

        return $proxyClassName;
    }

    private function generateProxyClassCode($proxyClassName, $usages, $methods, $config)
    {
        $methodsCode = '';
        foreach ($methods as $method) {
            $methodsCode .= $this->generateMethodCode($method, $config);
        }

        return <<<PHP
<?php

namespace {$this->proxyGenerator->getProxyNamespace()};

use {$this->proxyGenerator->getProxyNamespace()}\\AbstractClient;
use {$method->getDeclaringClass()->getNamespaceName()}\\{$method->getDeclaringClass()->getShortName()};
{$usages}

class $proxyClassName extends AbstractClient implements {$method->getDeclaringClass()->getShortName()}
{
    $methodsCode
}
PHP;
    }

    private function generateMethodCode(\ReflectionMethod $method, $config)
    {
        $routeAnnotation = $this->annotationReader->getMethodAnnotation($method, Route::class);
        if (!$routeAnnotation) {
            return '';
        }

        $path = $routeAnnotation->getPath();
        $requestMethod = $routeAnnotation->getMethods()[0];
        $name = $routeAnnotation->getName();
        $options = $routeAnnotation->getOptions();
        $parametersMap = [];

        foreach ($method->getParameters() as $parameter) {
            $parametersMap[$parameter->getName()] = $parameter;
        }

        if (preg_match_all('/\{([A-Za-z0-9_]+)}/', $path, $matches)) {
            foreach ($matches[1] as $key => $match) {
                if (!isset($parametersMap[$match])) {
                    continue;
                }

                $parameter = $parametersMap[$match];
                $type = self::detectType($parameter) ?: 'string';

                if (class_exists($type)) {
                    $classMetadata = new \ReflectionClass($type);
                    $arguments = '';
                    $delimiter = '';

                    foreach ($classMetadata->getProperties() as $property) {
                        $propertyName = "{\${$property->getName()}}";

                        if (!$property->isPublic()) {
                            $upCased = ucfirst($property->getName());

                            if ($classMetadata->hasMethod("get{$upCased}")) {
                                $propertyName = "{\${$parameter->getName()}->get{$upCased}()}";
                            } elseif ($classMetadata->hasMethod("is{$upCased}")) {
                                $propertyName = "{\${$parameter->getName()}->is{$upCased}()}";
                            } else {
                                continue;
                            }
                        }

                        $arguments .= "{$delimiter}{$property->getName()}={$propertyName}";
                        $delimiter = '&,';
                    }

                    $path = str_replace($matches[0][$key], $arguments, $path);
                } else {
                    $path = str_replace($matches[0][$key], "\${$parameter->getName()}", $path);
                }
            }
        }

        $timeout = isset($options['timeout']) ? $options['timeout'] : $config['timeout'];

        if (!empty($parametersMap) && in_array($requestMethod, ['POST', 'PUT', 'UPDATE'])) {
            $parameter = end($parametersMap);
            $bodyCode = "\${$parameter->getName()}";
        } else {
            $bodyCode = 'null';
        }

        $responseCode = "return \$this->request('{$requestMethod}', '{$config['url']}{$path}', $bodyCode, ['name' => '{$name}', 'timeout' => '{$timeout}']);";

        $returnType = $method->getReturnType();
        $returnTypeCode = $returnType ? $returnType->getName() : 'mixed';

        $parametersCode = implode(', ', array_map(function (\ReflectionParameter $parameter): string {
            if($type = self::detectType($parameter)) {
                $type = " {$type}";
            }

            return "{$type}{$parameter->getName()}";
        }, $parametersMap));

        return <<<PHP
public function {$method->getName()}($parametersCode): $returnTypeCode
{
    $bodyCode
    $responseCode
}
PHP;
    }

    private static function processMatch(\ReflectionProperty $property, array $matches, int $matchIndex, \ReflectionParameter $parameter): string
    {
        if (!empty($matches[1][$matchIndex])) {
            return "{$matches[1][$matchIndex]}=\${$property->getName()},";
        }

        return "\${$property->getName()},";
    }

    private static function detectType(\ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type) {
            return null;
        }

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            // Обработка объединенных типов (PHP 8+)
            $types = array_map(function($type) {
                return $type->getName();
            }, $type->getTypes());
            return implode('|', $types);
        }

        return (string) $type;
    }
}
