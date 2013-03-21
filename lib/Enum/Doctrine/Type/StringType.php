<?php

namespace Enum\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType as DBALStringType;
use Enum\EnumInterface;

class StringType extends DBALStringType
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof EnumInterface) {
            return $value->getValue();
        }

        return $value;
    }
}
