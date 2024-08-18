<?php

declare(strict_types=1);

namespace Example;

require __DIR__.'/../vendor/autoload.php';

use Example\Example4\Class1;
use Example\Example4\Interface1;
use Temkaa\SimpleContainer\Builder\ConfigBuilder;
use Temkaa\SimpleContainer\Builder\ContainerBuilder;

$config = ConfigBuilder::make()
    ->include(__DIR__.'/Example4/')
    ->bindInterface(Interface1::class, Class1::class)
    ->build();

$container = ContainerBuilder::make()->add($config)->build();

/**
 * object(Example\Example4\Class1)#18 (0) {
 * }
 */
$class = $container->get(Interface1::class);
