<?php

namespace Gingerminds\LaravelCms\Resolver;

class ResourceResolver
{
    public static function model(string $resource): string
    {
        return config("gingerminds-cms.resources.{$resource}.model");
    }

    public static function repository(string $resource): string
    {
        return config("gingerminds-cms.resources.{$resource}.repository");
    }

    public static function controller(string $resource): string
    {
        return config("gingerminds-cms.resources.{$resource}.controller");
    }

    public static function provider(string $resource): string
    {
        return config("gingerminds-cms.resources.{$resource}.provider");
    }

    public static function request(string $resource): string
    {
        return config("gingerminds-cms.resources.{$resource}.request");
    }
}
