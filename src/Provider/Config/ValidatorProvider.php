<?php

declare(strict_types=1);

namespace Temkaa\SimpleContainer\Provider\Config;

use Temkaa\SimpleContainer\Validator\Config\ClassBindingNodeValidator;
use Temkaa\SimpleContainer\Validator\Config\GlobalVariableBindValidator;
use Temkaa\SimpleContainer\Validator\Config\InterfaceBindingNodeValidator;
use Temkaa\SimpleContainer\Validator\Config\ServicesNodeValidator;
use Temkaa\SimpleContainer\Validator\Config\ValidatorInterface;

/**
 * @internal
 */
final class ValidatorProvider
{
    /**
     * @return ValidatorInterface[]
     */
    public function provide(): array
    {
        return [
            new ServicesNodeValidator(),
            new GlobalVariableBindValidator(),
            new ClassBindingNodeValidator(),
            new InterfaceBindingNodeValidator(),
        ];
    }
}
