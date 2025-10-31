<?php

namespace ViKingMrBoo\ProxyGenerator\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
class ApiClient extends Annotation
{
    /**
     * @var string
     */
    public $value;
}