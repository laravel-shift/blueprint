<?php

namespace Blueprint;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

use function array_map;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function trim;

class EnumType extends Type
{
    const ENUM = 'enum';

    protected $values = [];

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $values = array_map(
            function ($val) {
                return "'" . $val . "'";
            },
            $this->values
        );

        return "ENUM(" . implode(", ", $values) . ")";
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (! in_array($value, $this->values)) {
            throw new InvalidArgumentException("Invalid '" . $this->getName() . "' value.");
        }
        return $value;
    }

    public function getName()
    {
        return self::ENUM;
    }

    public static function extractOptions($definition)
    {
        $options = explode(',', preg_replace('/enum\((?P<options>(.*))\)/', '$1', $definition));

        return array_map(
            function ($option) {
                $raw_value = str_replace("''", "'", trim($option, "'"));

                if (! preg_match('/\s/', $raw_value)) {
                    return $raw_value;
                }

                return sprintf('"%s"', $raw_value);
            },
            $options
        );
    }
}
