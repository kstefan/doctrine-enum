<?php

namespace Enum\Doctrine\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Enum
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Enum extends Annotation
{
    /** @var string @required */
    public $class = null;
}


