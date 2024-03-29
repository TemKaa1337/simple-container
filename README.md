### This is a simple DI Container implementation.

##### Usage
```php
<?php

declare(strict_types=1);

use Temkaa\SimpleContainer\Container;

$container = new Container($config, $env);
$container->compile();

/** @var ClassName $object */
$object = $container->get(ClassName::class);

// or if you have the class which has alias (from Alias attribute) then you can get its instance by alias
$object = $container->get('class_alias');

// or if you have registered interface implementation in config you can get class which implements interface by calling
$object = $container->get(InterfaceName::class);
```

##### Env variables example:
```php
<?php

declare(strict_types=1);

$env = [
    'variable_name_1' => 'variable_value_1',
    'variable_name_2' => 'variable_value_2',
];
```

```php
<?php

declare(strict_types=1);

// if you are using Symfony\Component\Dotenv package then you will likely should do something like:
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->loadEnv('/path/to/env/.env');

$env = $_ENV;

// or:

(new Dotenv())->usePutEnv()->loadEnv('/path/to/env/.env');

$env = getenv();

// otherwise you can just fill key => value array with variable names and values (they are all must be strings!).
```

##### Container config example:
```php
<?php

declare(strict_types=1);

$config = [
    // always leave it as it is in example below
    'config_dir' => __DIR__,
    'services' => [
        // this section includes files that will be autowired by container
        // you can pass either filename or directory
        // please note that class paths must be relative to `config_dir` path
        'include' => [
            '/../some/path/ClassName.php',
            '/../some/path/'
        ],
        // this section includes files that will be excluded from autowiring by container
        'exclude' => [
            '/../some/path/ClassName.php',
            '/../some/path/'
        ],
    ],
    'interface_bindings' => [
        // here you can bind interface implementation to a concrete class 
        SomeInterface::class => SomeClass::class,
    ],
    'class_bindings' => [
        SomeClass::class => [
            // in `bind` section you can bind value to a variable in class constructor
            'bind' => [
                '$variableFirst' => 'variableValue',
                // you can also bind env variable value to a variable in class constructor
                '$variableSecond' => 'env(ENV_VARIABLE)',
                // you can also bind `glued` env variables
                '$variableThird' => 'env(ENV_VARIABLE_1)_env(ENV_VARIABLE_2)',
                // this line will automatically pass an array of objects which have 
                // 'tag_name' tag
                '$variableFourth' => '!tagged tag_name'
            ],
            'tags' => [
                'tag_1', 'tag_2', 'tag_3',
            ],
        ]
    ],
];
```

### Container attributes example:
```php
<?php

declare(strict_types=1);

namespace App;

use Temkaa\SimpleContainer\Attribute\Alias;
use Temkaa\SimpleContainer\Attribute\Bind\Parameter;
use Temkaa\SimpleContainer\Attribute\Bind\Tagged;
use Temkaa\SimpleContainer\Attribute\Tag

#[Tag(name: 'tag_name')]
#[Alias(name: 'class_alias')]
class Example
{
    public function __construct(
        #[Tagged(tag: 'any_tag_name')]
        private readonly iterable $tagged,
        #[Parameter(expression: 'env(INT_VARIABLE)')]
        private readonly int $age,
    ) {}
}

```

##### Important notes:
- Please note that all classes for now are singletons, option with instantiating classes multiple times will be added later.

##### Here are some TODOs:
- Refactoring src + refactoring tests
- Add env var resolving circular detection
- Allow tagged iterator to be caster to specific class collection
- Add Decorator (both from attribute and config)
- Add singleton option (both from attribute and config)
- Add reflection caching
- remove array $env and replace with env getting everywhere
- env var processors (Enum, ...)

- automatic release tag drafter
- automated git actions tests passing

