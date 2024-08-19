<?php

declare(strict_types=1);

namespace Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Temkaa\SimpleContainer\Builder\ContainerBuilder;
use Temkaa\SimpleContainer\Exception\Config\EntryNotFoundException;
use Temkaa\SimpleContainer\Exception\UnresolvableArgumentException;
use Temkaa\SimpleContainer\Model\Config\Decorator;
use Tests\Helper\Service\ClassBuilder;
use Tests\Helper\Service\ClassGenerator;
use Tests\Integration\Container\AbstractContainerTestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @psalm-suppress ArgumentTypeCoercion, InternalClass, InternalMethod
 */
final class DecoratorTest extends AbstractContainerTestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithDecoratorByInterfaceWhereDecoratorContainsMultipleConstructorArguments(): void
    {
        $interfaceName1 = ClassGenerator::getClassName();
        $interfaceName2 = ClassGenerator::getClassName();
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        $className3 = ClassGenerator::getClassName();
        $className4 = ClassGenerator::getClassName();
        $collectorClassName = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$collectorClassName.php")
                    ->setName($collectorClassName)
                    ->setHasConstructor(true)
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency1,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1,
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$interfaceName1.php")
                    ->setName($interfaceName1)
                    ->setPrefix('interface'),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1.'::class',
                            0,
                            '$inner',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            self::ATTRIBUTE_PARAMETER_SIGNATURE,
                            'env(ENV_VAR_1)',
                        ),
                        'public readonly string $arg,',
                        sprintf(
                            'public readonly %s $inner,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1,
                        ),
                        sprintf(self::ATTRIBUTE_TAGGED_SIGNATURE, 'Interface2'),
                        'public readonly iterable $dependency,',
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$interfaceName2.php")
                    ->setName($interfaceName2)
                    ->setAttributes([sprintf(self::ATTRIBUTE_TAG_SIGNATURE, 'Interface2')])
                    ->setPrefix('interface'),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className3.php")
                    ->setName($className3)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName2])
                    ->setAttributes([sprintf(self::ATTRIBUTE_AUTOWIRE_SIGNATURE, 'true', 'false')]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className4.php")
                    ->setName($className4)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName2])
                    ->setAttributes([sprintf(self::ATTRIBUTE_AUTOWIRE_SIGNATURE, 'true', 'false')]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$collectorClassName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$interfaceName1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$interfaceName2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className3.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className4.php",
        ];
        $config = $this->generateConfig(
            includedPaths: $files,
            interfaceBindings: [
                self::GENERATED_CLASS_NAMESPACE.$interfaceName1 => self::GENERATED_CLASS_NAMESPACE.$className1,
            ],
        );

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$interfaceName1);
        $collector = $container->get(self::GENERATED_CLASS_NAMESPACE.$collectorClassName);
        $class2 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className2);

        self::assertSame($class2, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$collectorClassName, $collector);

        self::assertSame($class2, $collector->dependency1);

        self::assertEquals('test_one', $class2->arg);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $class2->inner);

        self::assertCount(2, $class2->dependency);

        /** @psalm-suppress PossiblyInvalidArrayAccess, UndefinedInterfaceMethod */
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $class2->dependency[0]);

        /** @psalm-suppress PossiblyInvalidArrayAccess, UndefinedInterfaceMethod */
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $class2->dependency[1]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithDecoratorFromAttribute(): void
    {
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1.'::class',
                            0,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1,
                        ),
                    ]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
        ];
        $config = $this->generateConfig(includedPaths: $files);

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);
        $decorator = $container->get(self::GENERATED_CLASS_NAMESPACE.$className2);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorator);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $decorated->dependency);
        self::assertSame($decorated, $decorator);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithDecoratorFromConfig(): void
    {
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1,
                        ),
                    ]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
        ];
        $config = $this->generateConfig(
            includedPaths: $files,
            classBindings: [
                $this->generateClassConfig(
                    className: self::GENERATED_CLASS_NAMESPACE.$className2,
                    decorates: new Decorator(
                        self::GENERATED_CLASS_NAMESPACE.$className1,
                        signature: '$dependency',
                    ),
                ),
            ],
        );

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);
        $decorator = $container->get(self::GENERATED_CLASS_NAMESPACE.$className2);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorator);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $decorated->dependency);
        self::assertSame($decorated, $decorator);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithDecoratorTypeHintedAsObject(): void
    {
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1.'::class',
                            0,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments(['public readonly object $dependency']),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
        ];
        $config = $this->generateConfig(includedPaths: $files);

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);
        $decorator = $container->get(self::GENERATED_CLASS_NAMESPACE.$className2);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorator);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $decorated->dependency);
        self::assertSame($decorated, $decorator);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithDecoratorWithoutDecoratedServiceInjected(): void
    {
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1.'::class',
                            0,
                            'dependency',
                        ),
                    ]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
        ];
        $config = $this->generateConfig(includedPaths: $files);

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);
        $decorator = $container->get(self::GENERATED_CLASS_NAMESPACE.$className2);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorator);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithMultipleDecoratorsByClass(): void
    {
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        $className3 = ClassGenerator::getClassName();
        $className4 = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1.'::class',
                            3,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1,
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className3.php")
                    ->setName($className3)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1.'::class',
                            2,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className2,
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className4.php")
                    ->setName($className4)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1.'::class',
                            1,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className3,
                        ),
                    ]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className4.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className3.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
        ];
        $config = $this->generateConfig(includedPaths: $files);

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);

        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className3,
            $container->get(self::GENERATED_CLASS_NAMESPACE.$className3),
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $container->get(self::GENERATED_CLASS_NAMESPACE.$className2),
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className4,
            $container->get(self::GENERATED_CLASS_NAMESPACE.$className4),
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $decorated->dependency);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorated->dependency->dependency);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $decorated->dependency->dependency->dependency,
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithMultipleDecoratorsByClassWithOneDecoratedPropertyDeclared(): void
    {
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        $className3 = ClassGenerator::getClassName();
        $className4 = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1.'::class',
                            3,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $propertyName',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1,
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className3.php")
                    ->setName($className3)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1.'::class',
                            2,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $otherPropertyName',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className2,
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className4.php")
                    ->setName($className4)
                    ->setHasConstructor(true)
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $lastPropertyName',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className3,
                        ),
                    ]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className4.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className3.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
        ];
        $config = $this->generateConfig(
            includedPaths: $files,
            classBindings: [
                $this->generateClassConfig(
                    className: self::GENERATED_CLASS_NAMESPACE.$className4,
                    decorates: new Decorator(
                        id: self::GENERATED_CLASS_NAMESPACE.$className1,
                        priority: 1,
                    ),
                ),
            ],
        );

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);

        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className3,
            $container->get(self::GENERATED_CLASS_NAMESPACE.$className3),
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $container->get(self::GENERATED_CLASS_NAMESPACE.$className2),
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className4,
            $container->get(self::GENERATED_CLASS_NAMESPACE.$className4),
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $decorated->lastPropertyName);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $decorated->lastPropertyName->otherPropertyName,
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $decorated->lastPropertyName->otherPropertyName->propertyName,
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithMultipleDecoratorsByInterface(): void
    {
        $interfaceName = ClassGenerator::getClassName();
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        $className3 = ClassGenerator::getClassName();
        $className4 = ClassGenerator::getClassName();
        $collectorClassName = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$collectorClassName.php")
                    ->setName($collectorClassName)
                    ->setHasConstructor(true)
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency1,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                        sprintf(
                            'public readonly %s $dependency2,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className4,
                        ),
                        sprintf(
                            'public readonly %s $dependency3,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className3,
                        ),
                        sprintf(
                            'public readonly %s $dependency4,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className2,
                        ),
                        sprintf(
                            'public readonly %s $dependency5,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1,
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$interfaceName.php")
                    ->setName($interfaceName)
                    ->setPrefix('interface'),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            3,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dep',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className3.php")
                    ->setName($className3)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            2,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $class',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className4.php")
                    ->setName($className4)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            1,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $property',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$interfaceName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className4.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$collectorClassName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className3.php",
        ];
        $config = $this->generateConfig(
            includedPaths: $files,
            interfaceBindings: [
                self::GENERATED_CLASS_NAMESPACE.$interfaceName => self::GENERATED_CLASS_NAMESPACE.$className1,
            ],
        );

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$interfaceName);
        $collector = $container->get(self::GENERATED_CLASS_NAMESPACE.$collectorClassName);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$collectorClassName, $collector);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $collector->dependency1);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency1->property);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $collector->dependency1->property->class,
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency1->property->class->dep,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $collector->dependency2);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency2->property);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $collector->dependency2->property->class,
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency2->property->class->dep,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency3);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $collector->dependency3->class);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency3->class->dep,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $collector->dependency4);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $collector->dependency4->dep);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $collector->dependency5);

        $class4 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className4);
        $class3 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className3);
        $class2 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className2);
        $class1 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $class4);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $class3);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $class2);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $class1);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $class2->dep);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $class3->class);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $class4->property);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $decorated->property);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorated->property->class);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $decorated->property->class->dep,
        );

        self::assertSame($decorated, $collector->dependency1);
        self::assertSame($decorated, $collector->dependency2);
        self::assertSame($class4, $collector->dependency2);
        self::assertSame($class3, $collector->dependency3);
        self::assertSame($class2, $collector->dependency4);
        self::assertSame($class1, $collector->dependency5);
        self::assertSame($decorated->property, $collector->dependency3);
        self::assertSame($decorated->property->class, $collector->dependency4);
        self::assertSame($decorated->property->class->dep, $collector->dependency5);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithMultipleDecoratorsByInterfaceDeclaredAsNonSingletons(): void
    {
        $interfaceName = ClassGenerator::getClassName();
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        $className3 = ClassGenerator::getClassName();
        $className4 = ClassGenerator::getClassName();
        $collectorClassName = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$collectorClassName.php")
                    ->setName($collectorClassName)
                    ->setHasConstructor(true)
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency1,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                        sprintf(
                            'public readonly %s $dependency2,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className4,
                        ),
                        sprintf(
                            'public readonly %s $dependency3,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className3,
                        ),
                        sprintf(
                            'public readonly %s $dependency4,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className2,
                        ),
                        sprintf(
                            'public readonly %s $dependency5,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1,
                        ),
                    ])
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_AUTOWIRE_SIGNATURE,
                            'true',
                            'false',
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$interfaceName.php")
                    ->setName($interfaceName)
                    ->setPrefix('interface')
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_AUTOWIRE_SIGNATURE,
                            'true',
                            'false',
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName])
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_AUTOWIRE_SIGNATURE,
                            'true',
                            'false',
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            3,
                            'dependency',
                        ),
                        sprintf(
                            self::ATTRIBUTE_AUTOWIRE_SIGNATURE,
                            'true',
                            'false',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className3.php")
                    ->setName($className3)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            2,
                            'dependency',
                        ),
                        sprintf(
                            self::ATTRIBUTE_AUTOWIRE_SIGNATURE,
                            'true',
                            'false',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className4.php")
                    ->setName($className4)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            1,
                            'dependency',
                        ),
                        sprintf(
                            self::ATTRIBUTE_AUTOWIRE_SIGNATURE,
                            'true',
                            'false',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$collectorClassName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$interfaceName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className4.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className3.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
        ];
        $config = $this->generateConfig(
            includedPaths: $files,
            interfaceBindings: [
                self::GENERATED_CLASS_NAMESPACE.$interfaceName => self::GENERATED_CLASS_NAMESPACE.$className1,
            ],
        );

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$interfaceName);
        $collector = $container->get(self::GENERATED_CLASS_NAMESPACE.$collectorClassName);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$collectorClassName, $collector);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $collector->dependency1);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency1->dependency);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $collector->dependency1->dependency->dependency,
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency1->dependency->dependency->dependency,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $collector->dependency2);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency2->dependency);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $collector->dependency2->dependency->dependency,
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency2->dependency->dependency->dependency,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency3);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $collector->dependency3->dependency);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency3->dependency->dependency,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $collector->dependency4);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $collector->dependency4->dependency);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $collector->dependency5);

        $class4 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className4);
        $class3 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className3);
        $class2 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className2);
        $class1 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $class4);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $class3);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $class2);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $class1);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $class2->dependency);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $class3->dependency);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $class4->dependency);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $decorated->dependency);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorated->dependency->dependency);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $decorated->dependency->dependency->dependency,
        );

        self::assertNotSame($decorated, $collector->dependency1);
        self::assertNotSame($decorated, $collector->dependency2);
        self::assertNotSame($class4, $collector->dependency2);
        self::assertNotSame($class3, $collector->dependency3);
        self::assertNotSame($class2, $collector->dependency4);
        self::assertNotSame($class1, $collector->dependency5);
        self::assertNotSame($decorated->dependency, $collector->dependency3);
        self::assertNotSame($decorated->dependency->dependency, $collector->dependency4);
        self::assertNotSame($decorated->dependency->dependency->dependency, $collector->dependency5);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testCompilesWithMultipleDecoratorsByInterfaceWhichIsNotBoundedToClass(): void
    {
        $interfaceName = ClassGenerator::getClassName();
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        $className3 = ClassGenerator::getClassName();
        $className4 = ClassGenerator::getClassName();
        $collectorClassName = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$collectorClassName.php")
                    ->setName($collectorClassName)
                    ->setHasConstructor(true)
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency1,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                        sprintf(
                            'public readonly %s $dependency2,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className4,
                        ),
                        sprintf(
                            'public readonly %s $dependency3,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className3,
                        ),
                        sprintf(
                            'public readonly %s $dependency4,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className2,
                        ),
                        sprintf(
                            'public readonly %s $dependency5,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1,
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$interfaceName.php")
                    ->setName($interfaceName)
                    ->setPrefix('interface'),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            3,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dep',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className3.php")
                    ->setName($className3)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            2,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $class',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className4.php")
                    ->setName($className4)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            1,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $property',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$interfaceName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className4.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$collectorClassName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className3.php",
        ];
        $config = $this->generateConfig(includedPaths: $files);

        $container = (new ContainerBuilder())->add($config)->build();

        $decorated = $container->get(self::GENERATED_CLASS_NAMESPACE.$interfaceName);
        $collector = $container->get(self::GENERATED_CLASS_NAMESPACE.$collectorClassName);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$collectorClassName, $collector);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $collector->dependency1);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency1->property);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $collector->dependency1->property->class,
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency1->property->class->dep,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $collector->dependency2);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency2->property);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className2,
            $collector->dependency2->property->class,
        );
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency2->property->class->dep,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $collector->dependency3);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $collector->dependency3->class);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $collector->dependency3->class->dep,
        );

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $collector->dependency4);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $collector->dependency4->dep);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $collector->dependency5);

        $class4 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className4);
        $class3 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className3);
        $class2 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className2);
        $class1 = $container->get(self::GENERATED_CLASS_NAMESPACE.$className1);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $class4);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $class3);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $class2);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $class1);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className1, $class2->dep);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $class3->class);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $class4->property);

        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className4, $decorated);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className3, $decorated->property);
        self::assertInstanceOf(self::GENERATED_CLASS_NAMESPACE.$className2, $decorated->property->class);
        self::assertInstanceOf(
            self::GENERATED_CLASS_NAMESPACE.$className1,
            $decorated->property->class->dep,
        );

        self::assertSame($decorated, $collector->dependency1);
        self::assertSame($decorated, $collector->dependency2);
        self::assertSame($class4, $collector->dependency2);
        self::assertSame($class3, $collector->dependency3);
        self::assertSame($class2, $collector->dependency4);
        self::assertSame($class1, $collector->dependency5);
        self::assertSame($decorated->property, $collector->dependency3);
        self::assertSame($decorated->property->class, $collector->dependency4);
        self::assertSame($decorated->property->class->dep, $collector->dependency5);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testDoesNotCompileWithMultipleDecoratorsByInterfaceWhichIsNotBoundedToClassAndMultipleImplementingClasses(
    ): void
    {
        $interfaceName = ClassGenerator::getClassName();
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        $className3 = ClassGenerator::getClassName();
        $className4 = ClassGenerator::getClassName();
        $className5 = ClassGenerator::getClassName();
        $collectorClassName = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$collectorClassName.php")
                    ->setName($collectorClassName)
                    ->setHasConstructor(true)
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dependency1,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                        sprintf(
                            'public readonly %s $dependency2,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className4,
                        ),
                        sprintf(
                            'public readonly %s $dependency3,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className3,
                        ),
                        sprintf(
                            'public readonly %s $dependency4,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className2,
                        ),
                        sprintf(
                            'public readonly %s $dependency5,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$className1,
                        ),
                    ]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$interfaceName.php")
                    ->setName($interfaceName)
                    ->setPrefix('interface'),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className3.php")
                    ->setName($className3)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            3,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $dep',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className4.php")
                    ->setName($className4)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            2,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $class',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className5.php")
                    ->setName($className5)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName.'::class',
                            1,
                            'dependency',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            'public readonly %s $property',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$interfaceName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className4.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$collectorClassName.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className3.php",
        ];
        $config = $this->generateConfig(includedPaths: $files);

        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Could not find interface implementation for "%s".',
                self::GENERATED_CLASS_NAMESPACE.$interfaceName,
            ),
        );
        (new ContainerBuilder())->add($config)->build();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testDoesNotCompileWithNonMatchingDecoratorSignatureWithMultipleConstructorArgumentsFromAttribute(
    ): void
    {
        $interfaceName1 = ClassGenerator::getClassName();
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$interfaceName1.php")
                    ->setName($interfaceName1)
                    ->setPrefix('interface'),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setAttributes([
                        sprintf(
                            self::ATTRIBUTE_DECORATES_SIGNATURE,
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1.'::class',
                            0,
                            '$inner',
                        ),
                    ])
                    ->setConstructorArguments([
                        sprintf(
                            self::ATTRIBUTE_PARAMETER_SIGNATURE,
                            'env(ENV_VAR_1)',
                        ),
                        'public readonly string $arg,',
                        sprintf(
                            'public readonly %s $decorated,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$interfaceName1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
        ];
        $config = $this->generateConfig(
            includedPaths: $files,
            interfaceBindings: [
                self::GENERATED_CLASS_NAMESPACE.$interfaceName1 => self::GENERATED_CLASS_NAMESPACE.$className1,
            ],
        );

        $this->expectException(UnresolvableArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Could not resolve decorated class in class "%s" as it does not have argument named "inner".',
                self::GENERATED_CLASS_NAMESPACE.$className2,
            ),
        );

        (new ContainerBuilder())->add($config)->build();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testDoesNotCompileWithNonMatchingDecoratorSignatureWithMultipleConstructorArgumentsFromConfig(
    ): void
    {
        $interfaceName1 = ClassGenerator::getClassName();
        $className1 = ClassGenerator::getClassName();
        $className2 = ClassGenerator::getClassName();
        (new ClassGenerator())
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$interfaceName1.php")
                    ->setName($interfaceName1)
                    ->setPrefix('interface'),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className1.php")
                    ->setName($className1)
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1]),
            )
            ->addBuilder(
                (new ClassBuilder())
                    ->setAbsolutePath(realpath(__DIR__.self::GENERATED_CLASS_STUB_PATH)."/$className2.php")
                    ->setName($className2)
                    ->setHasConstructor(true)
                    ->setConstructorArguments([
                        sprintf(
                            self::ATTRIBUTE_PARAMETER_SIGNATURE,
                            'env(ENV_VAR_1)',
                        ),
                        'public readonly string $arg,',
                        sprintf(
                            'public readonly %s $decorated,',
                            self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1,
                        ),
                    ])
                    ->setInterfaceImplementations([self::GENERATED_CLASS_ABSOLUTE_NAMESPACE.$interfaceName1]),
            )
            ->generate();

        $files = [
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$interfaceName1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className1.php",
            __DIR__.self::GENERATED_CLASS_STUB_PATH."$className2.php",
        ];
        $config = $this->generateConfig(
            includedPaths: $files,
            interfaceBindings: [
                self::GENERATED_CLASS_NAMESPACE.$interfaceName1 => self::GENERATED_CLASS_NAMESPACE.$className1,
            ],
            classBindings: [
                $this->generateClassConfig(
                    className: self::GENERATED_CLASS_NAMESPACE.$className2,
                    decorates: new Decorator(id: self::GENERATED_CLASS_NAMESPACE.$interfaceName1),
                ),
            ],
        );

        $this->expectException(UnresolvableArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Could not resolve decorated class in class "%s" as it does not have argument named "inner".',
                self::GENERATED_CLASS_NAMESPACE.$className2,
            ),
        );

        (new ContainerBuilder())->add($config)->build();
    }
}