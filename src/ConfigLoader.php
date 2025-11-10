<?php declare(strict_types=1);

namespace AutoDoc\Laravel;

use AutoDoc\Config;

/**
 * @phpstan-import-type ConfigArray from Config
 */
class ConfigLoader
{
    public function load(): Config
    {
        /**
         * @var ConfigArray
         */
        $configArray = config('autodoc');

        if (config('autodoc.laravel.autoload_builtin_extensions') ?? true) {
            $configArray['extensions'] = array_unique([
                ...$configArray['extensions'],

                \AutoDoc\Laravel\Extensions\RequestValidate::class,
                \AutoDoc\Laravel\Extensions\ResponseJson::class,
                \AutoDoc\Laravel\Extensions\EloquentModelStaticCall::class,
                \AutoDoc\Laravel\Extensions\ValidationRuleStaticCall::class,
                \AutoDoc\Laravel\Extensions\EloquentModel::class,
                \AutoDoc\Laravel\Extensions\CustomFormRequest::class,
                \AutoDoc\Laravel\Extensions\ResourceCollectionJson::class,
                \AutoDoc\Laravel\Extensions\ResourceJson::class,
                \AutoDoc\Laravel\Extensions\ResourceStaticCall::class,
                \AutoDoc\Laravel\Extensions\RedirectResponse::class,
                \AutoDoc\Laravel\Extensions\LengthAwarePaginatorJson::class,
                \AutoDoc\Laravel\Extensions\RouteParamResolver::class,
            ]);
        }

        return new Config($configArray);
    }
}
