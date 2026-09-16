<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Concerns;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMultisite\Models\Trait\SyncsTranslationsInterface;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Database\Eloquent\Model;

trait UpdatesPublishableCmsResourceTrait
{
    /**
     * @param  list<string>  $fileFields
     * @param  (callable(): mixed)|null  $syncExtraRelations
     * @param  list<string>  $fillExcept
     */
    protected function updatePublishableResource(
        FormRequestInterface $request,
        Model&SyncsTranslationsInterface $resourceModel,
        array $fileFields,
        ?callable $syncExtraRelations = null,
        array $fillExcept = [],
    ): void {
        $resourceModel->fill($request->except([...$fillExcept, 'status']));
        $resourceModel->setAttribute('site_id', app(SiteContext::class)->site()?->id);

        foreach ($fileFields as $field) {
            $this->syncResourceFile($request, $resourceModel, $field);
        }

        $this->syncStatus($request, $resourceModel);

        if ($syncExtraRelations !== null) {
            $syncExtraRelations();
        }

        $resourceModel->syncTranslations(
            $this->prepareTranslations($request, $resourceModel)
        );
    }
}
