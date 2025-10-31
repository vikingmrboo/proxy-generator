# Dynamic Proxy Class Generator for Symfony and Doctrine

![GitHub Actions](https://github.com/vikingmrboo/proxy-generator/actions/workflows/phpunit.yml/badge.svg)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Описание

**Dynamic Proxy Class Generator** — это библиотека для автоматической генерации прокси-классов в Symfony и Doctrine. Она позволяет создавать реализации интерфейсов на основе аннотаций, что упрощает взаимодействие с внешними API.

## Установка

Установите библиотеку через Composer:

```bash
composer require vikingmrboo/proxy-generator
```

Если вы используете Symfony Flex, бандл автоматически зарегистрируется. Если нет, добавьте его в config/bundles.php:

```php
<?php

return [
    // ...
    ViKingMrBoo\ProxyGenerator\ProxyGeneratorBundle::class => ['all' => true],
];
```

## Конфигурация
Создайте конфигурационный файл для библиотеки в config/packages/viking_proxy_generator.yaml:

```yaml
viking_proxy_generator:
    cache_dir: '%kernel.cache_dir%/proxy'
    proxies_dir: '%kernel.cache_dir%/proxies'
    clients:
        example_client:
            url: 'https://api.example.com'
            timeout: 10
```

## Использование

### Шаг 1: Создание интерфейса с аннотацией

Создайте интерфейс, который будет отмечен аннотацией ApiClient и будет содержать методы с аннотацией Route.

Создайте файл src/Api/ExampleApiInterface.php:

```php
<?php

namespace App\Api;

use Symfony\Component\Routing\Annotation\Route;
use ViKingMrBoo\ProxyGenerator\Annotation\ApiClient;

/**
 * @ApiClient("example_client")
 */
interface ExampleApiInterface
{
    /**
     * @Route("/data", methods={"GET"})
     */
    public function getData(): array;

    /**
     * @Route("/data", methods={"POST"})
     */
    public function postData(array $data): array;
}

```


### Шаг 2: Настройка сервисов

Убедитесь, что сервисы правильно настроены и прокси-классы генерируются. Добавьте необходимые сервисы в config/services.yaml:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    App\Api\:
        resource: '../src/Api/*'

    # Регистрация кэша и HTTP клиента
    viking_proxy_generator.cache_provider:
        class: Doctrine\Common\Cache\FilesystemCache
        arguments: ['%viking_proxy_generator.cache_dir%']

    viking_proxy_generator.http_client:
        class: Symfony\Contracts\HttpClient\HttpClientInterface
        factory: [Symfony\Contracts\HttpClient\HttpClientInterface, 'create']

```
### Шаг 3: Использование прокси-класса

Теперь вы можете использовать прокси-класс, который будет автоматически сгенерирован для вашего интерфейса. Внедрите интерфейс в ваш сервис или контроллер.

Создайте контроллер src/Controller/ExampleController.php:

```php
<?php

namespace App\Controller;

use App\Api\ExampleApiInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ExampleController extends AbstractController
{
    private $exampleApi;

    public function __construct(ExampleApiInterface $exampleApi)
    {
        $this->exampleApi = $exampleApi;
    }

    public function getData(): Response
    {
        $data = $this->exampleApi->getData();
        return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']);
    }

    public function postData(array $data): Response
    {
        $response = $this->exampleApi->postData($data);
        return new Response(json_encode($response), 200, ['Content-Type' => 'application/json']);
    }
}
```

### Шаг 4: Проверка генерации прокси-класса

Запустите приложение и убедитесь, что прокси-классы генерируются правильно. Вы можете проверить директорию кэша, указанную в конфигурации (%kernel.cache_dir%/proxies), чтобы увидеть сгенерированные прокси-классы.

### Дополнительные настройки

Если вам нужно настроить дополнительные параметры или добавить больше клиентов, вы можете расширить конфигурацию в config/packages/viking_proxy_generator.yaml:

```yaml
viking_proxy_generator:
    cache_dir: '%kernel.cache_dir%/proxy'
    proxies_dir: '%kernel.cache_dir%/proxies'
    clients:
        example_client:
            url: 'https://api.example.com'
            timeout: 10
        another_client:
            url: 'https://api.another.com'
            timeout: 15
```

Теперь вы можете использовать аннотацию ApiClient с различными именами клиентов для создания прокси-классов для разных внешних API.

# Лицензия
Этот проект лицензирован под лицензией MIT. Подробнее см. файл LICENSE.

# Авторы

ViKingMrBoo - viking@xakep.ru

# Спасибо

Спасибо за использование нашей библиотеки! Если у вас есть вопросы или предложения по улучшению, пожалуйста, создайте issue на GitHub.

