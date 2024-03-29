<?php

declare(strict_types=1);

namespace Temkaa\SimpleContainer\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

final class ClassNotFoundException extends RuntimeException implements ContainerExceptionInterface
{
    public function __construct(string $class)
    {
        parent::__construct(sprintf('Class "%s" is not found.', $class));
    }
}
