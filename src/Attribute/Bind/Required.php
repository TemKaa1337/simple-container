<?php

declare(strict_types=1);

namespace Temkaa\Container\Attribute\Bind;

use Attribute;

/**
 * @psalm-api
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Required
{
}
