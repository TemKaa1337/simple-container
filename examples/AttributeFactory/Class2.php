<?php

declare(strict_types=1);

namespace Example\AttributeFactory;

use Temkaa\SimpleContainer\Attribute\Bind\Parameter;
use Temkaa\SimpleContainer\Attribute\Bind\Tagged;

final readonly class Class2
{
    public function __construct(
        private Class3 $class,
        #[Parameter(expression: 'string_var')]
        private string $stringVar,
    ) {
    }

    public function create(#[Parameter(expression: '1')] int $intVar, #[Tagged(tag: 'tag')] array $tagged): Class1
    {
        return new Class1($this->class, $this->stringVar, $intVar, $tagged);
    }
}
