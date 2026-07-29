<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Validator\MimeTypeValidator;

use function array_shift;
use function explode;
use function implode;
use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strchr;
use function strtolower;
use function trim;

/**
 * A validated media type that preserves custom MIME types and parameters.
 */
final readonly class MediaType implements \Stringable
{
    /**
     * @var non-empty-lowercase-string
     */
    public string $baseType;

    /**
     * @var non-empty-string
     */
    public string $value;

    public Type $type;

    /**
     * @param non-empty-lowercase-string $baseType
     * @param non-empty-string $value
     */
    private function __construct(string $baseType, string $value)
    {
        $this->baseType = $baseType;
        $this->value = $value;
        $this->type = Type::createFromFormat($baseType);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function create(string|Type|self $type): self
    {
        if ($type instanceof self) {
            return $type;
        }

        if ($type instanceof Type) {
            return self::fromString($type->getFormat());
        }

        return self::fromString($type);
    }

    public static function fromString(string $format): self
    {
        $parts = explode(';', trim($format));
        $baseType = MimeTypeValidator::validate(array_shift($parts));
        $parameters = [];

        foreach ($parts as $parameter) {
            $parameter = trim($parameter);

            if ('' === $parameter || !preg_match("/^[A-Za-z0-9!#\\$%&'*+.^_`|~-]+=(?:[^\\s;]+|\"[^\"]*\")$/", $parameter)) {
                throw new InvalidArgumentException(sprintf('The media type parameter "%s" is invalid.', $parameter));
            }

            $attribute = trim((string) strchr($parameter, '=', true));
            $parameterValue = (string) strchr($parameter, '=');
            $parameters[] = strtolower($attribute).$parameterValue;
        }

        $value = $baseType;

        if ([] !== $parameters) {
            $value .= ';'.implode(';', $parameters);
        }

        return new self($baseType, $value);
    }

    /**
     * @return ?non-empty-lowercase-string
     */
    public function getExtension(): ?string
    {
        return $this->type->getExtension();
    }

    public function isText(): bool
    {
        return $this->type->isText()
            || str_starts_with($this->baseType, 'text/')
            || str_ends_with($this->baseType, '+json')
            || str_ends_with($this->baseType, '+xml');
    }
}
