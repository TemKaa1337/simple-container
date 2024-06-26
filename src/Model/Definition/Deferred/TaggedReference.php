<?php

declare(strict_types=1);

namespace Temkaa\SimpleContainer\Model\Definition\Deferred;

use Temkaa\SimpleContainer\Model\Definition\ReferenceInterface;

/**
 * @psalm-api
 *
 * @internal
 */
final readonly class TaggedReference implements ReferenceInterface
{
    public function __construct(
        public string $tag,
    ) {
    }

    public function getId(): string
    {
        return $this->tag;
    }
}
