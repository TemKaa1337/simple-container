<?php

declare(strict_types=1);

namespace Example\ConfigDecorator;

final readonly class Class3 implements Interface1
{
    public function __construct(
        public Interface1 $class,
    ) {
    }
}
