<?php

declare(strict_types=1);

namespace Example\AttributeTaggedIterator;

use Temkaa\SimpleContainer\Attribute\Bind\Tagged;

final readonly class Collector
{
    public function __construct(
        #[Tagged('tag')]
        public array $objects,
    ) {
    }
}
