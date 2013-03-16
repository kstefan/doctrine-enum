<?php

namespace Enum\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Enum\EnumInterface;

class StringEnumType extends StringType
{
    public function getName()
    {
        return 'string_enum';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof EnumInterface) {
            return $value->getValue();
        }

        return $value;
    }
}
