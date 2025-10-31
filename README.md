# proxy-generator
Dynamic proxy class generator for Symfony and Doctrine

# Использование в проекте
Теперь можно использовать прокси-классы в проекте.

services.yaml:

```yaml
services:
    App\Client\Test\TestInterface:
        tags:
            - { name: mega_coder.proxy, interface: 'App\Client\Test\TestInterface', config: '%clients.test%' }
```

config/services.yaml:

```yaml
parameters:
    clients:
        test:
            url: "https://my.test.host.com"
            timeout: 10s
```
Теперь при внедрении интерфейса App\Client\Test\TestInterface будет автоматически создан и внедрен прокси-класс с нужной логикой.
