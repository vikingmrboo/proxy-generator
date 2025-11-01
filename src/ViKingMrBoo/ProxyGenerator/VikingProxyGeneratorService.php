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
    private $proxyGenerator;
    private $annotationReader;
    private $cache;

    public function __construct(ProxyGenerator $proxyGenerator, AnnotationReader $annotationReader, CacheProvider $cache)
    {
        $this->proxyGenerator = $proxyGenerator;
        $this->annotationReader = $annotationReader;
        $this->cache = $cache;
    }

    public function generateProxyClass($interfaceName, $config)
    {
        $cacheKey = 'proxy_' . $interfaceName;
        if ($this->cache->contains($cacheKey)) {
            return $this->cache->fetch($cacheKey);
        }

        $reflectionClass = new ReflectionClass($interfaceName);
        $methods = $reflectionClass->getMethods();

        $proxyClassName = $interfaceName . 'Proxy';
        $proxyClassCode = $this->generateProxyClassCode($proxyClassName, $methods, $config);

        file_put_contents($this->proxyGenerator->getProxyNamespace() . '/' . $proxyClassName . '.php', $proxyClassCode);

        $this->cache->save($cacheKey, $proxyClassName);

        return $proxyClassName;
    }

    private function generateProxyClassCode($proxyClassName, $methods, $config)
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

class $proxyClassName extends AbstractClient implements {$method->getDeclaringClass()->getShortName()}
{
    $methodsCode
}
PHP;
    }

    private function generateMethodCode(ReflectionMethod $method, $config)
    {
        $routeAnnotation = $this->annotationReader->getMethodAnnotation($method, Route::class);
        if (!$routeAnnotation) {
            return '';
        }

        $path = $routeAnnotation->getPath();
        $methods = $routeAnnotation->getMethods();
        $name = $routeAnnotation->getName();
        $options = $routeAnnotation->getOptions();

        $timeout = isset($options['timeout']) ? $options['timeout'] : $config['timeout'];

        $parametersCode = '';
        foreach ($method->getParameters() as $parameter) {
            $parametersCode .= '$' . $parameter->getName() . ', ';
        }
        $parametersCode = rtrim($parametersCode, ', ');

        $bodyCode = '';
        if (in_array('POST', $methods)) {
            $bodyCode = '$body = $this->serialize($' . $method->getParameters()[0]->getName() . ', \'json\');';
        }

        $responseCode = '$response = $this->request("' . implode('|', $methods) . '", "' . $config['url'] . $path . '", ' . ($bodyCode ? '$body' : 'null') . ', ["timeout" => "' . $timeout . '", "name" => "' . $name . '"]);';

        $returnType = $method->getReturnType();
        $returnTypeCode = $returnType ? $returnType->getName() : 'mixed';

        return <<<PHP
public function {$method->getName()}($parametersCode): $returnTypeCode
{
    $bodyCode
    $responseCode
    \$model = \$this->deserialize(\$response, {$returnTypeCode}::class);
    return \$model;
}
PHP;
    }
}
