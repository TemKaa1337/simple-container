<?php

declare(strict_types=1);

namespace Temkaa\SimpleContainer\Validator\Config;

use Temkaa\SimpleContainer\Model\Container\Config;
use Temkaa\SimpleContainer\Util\ExpressionParser;

/**
 * @internal
 */
final class GlobalVariableBindValidator implements ValidatorInterface
{
    // TODO: reuse from class config variable validation?
    public function validate(Config $config): void
    {
        $expressionParser = new ExpressionParser();
        foreach ($config->getBoundedVariables() as $variableValue) {
            $expressionParser->parse($variableValue);
        }
    }
}
