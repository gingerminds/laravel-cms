<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Middleware\Api;

use Closure;
use Gingerminds\LaravelCms\Services\Page\PageFilterStore;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectPageFiltersMiddleware
{
    public function __construct(
        private readonly PageFilterStore $filterStore
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
