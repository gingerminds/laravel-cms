<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Middleware\Api;

use Closure;
use Gingerminds\LaravelCms\Services\Filters\FilterStoreInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Merges a resource's computed `filters` (see `FilterStoreInterface`) into
 * its collection endpoint's JSON response body. A concrete subclass only
 * needs to type-hint its own `FilterStoreInterface` implementation in its
 * constructor — one per resource, since each is bound as its own singleton
 * (see `LaravelCmsServiceProvider::register()`) — and forward it here.
 */
abstract class AbstractInjectFiltersMiddleware
{
    public function __construct(
        protected readonly FilterStoreInterface $filterStore
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($this->filterStore->isEmpty()) {
            return $response;
        }

        $content = json_decode((string) $response->getContent(), true);

        if (!is_array($content)) {
            return $response;
        }

        // Only keep filter entries that have at least one option.
        // All entries share the {type, options} shape.
        $filters = array_filter(
            $this->filterStore->get(),
            static fn (array $entry): bool => ! empty($entry['options'])
        );

        // json format returns a plain array — wrap it so we can add filters alongside
        if (array_is_list($content)) {
            $content = ['member' => $content];
        }

        $content['filters'] = $filters;

        $response->setContent((string) json_encode($content, JSON_UNESCAPED_UNICODE));

        return $response;
    }
}
