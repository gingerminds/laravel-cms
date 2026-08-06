<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Concerns;

use Gingerminds\LaravelCms\Blocks\BlockFileFieldSync;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMediaManager\Models\File\File;
use Gingerminds\LaravelMultisite\Services\Context\LanguageContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

trait SyncsTranslatableResourceTrait
{
    abstract protected function uploadFolder(): string;

    /**
     * @return list<string>
     */
    abstract protected function resourceFileFields(): array;

    /**
     * @return list<string>
     */
    protected function translationFileFields(): array
    {
        return $this->resourceFileFields();
    }

    /**
     * @return list<int>
     */
    protected function resolveLanguagePreference(): array
    {
        if (! app()->bound(LanguageContext::class)) {
            return [];
        }

        $context = app(LanguageContext::class);

        return array_values(array_filter([
            $context->current()?->id,
            $context->fallback()?->id,
        ]));
    }

    protected function relationName(string $field): string
    {
        return Str::camel($field);
    }

    protected function syncStatus(FormRequestInterface $request, Model $resource): void
    {
        /** @var class-string|null $requestedStatus */
        $requestedStatus = $request->input('status');

        // getAttribute(), not the ->status magic property: PHPStan can't
        // resolve a dynamic property through the bare Model type this
        // trait is written against.
        $status = $resource->getAttribute('status');

        if ($requestedStatus === null || $requestedStatus === get_class($status)) {
            $resource->save();

            return;
        }

        $status->transitionTo($requestedStatus);
    }

    protected function syncResourceFile(FormRequestInterface $request, Model $resource, string $field): void
    {
        /** @var BelongsTo<File, Model> $relation */
        $relation = $resource->{$this->relationName($field)}();

        $uploaded = $request->file($field);

        if ($uploaded !== null) {
            /** @var File|null $old */
            $old = $relation->getResults();

            $file = $this->uploadService->replace($uploaded, $old, $this->uploadFolder());
            $relation->associate($file);

            return;
        }

        if ($request->boolean($field . '_remove')) {
            /** @var File|null $old */
            $old = $relation->getResults();

            if ($old !== null) {
                $this->uploadService->delete($old);
                $relation->dissociate();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function syncTranslationFile(
        FormRequestInterface $request,
        ?Model $translation,
        int|string $languageId,
        string $field,
        array $fields
    ): array {
        $idKey        = $field . '_id';
        $relationName = $this->relationName($field);

        /** @var File|null $old */
        $old = $translation?->{$relationName};

        $uploaded = $request->file("translations.$languageId.$field");

        unset($fields[$field], $fields[$field . '_remove']);

        if ($uploaded !== null) {
            $file           = $this->uploadService->replace($uploaded, $old, $this->uploadFolder());
            $fields[$idKey] = $file->id;

            return $fields;
        }

        if ($request->boolean("translations.$languageId.{$field}_remove") && $old !== null) {
            $this->uploadService->delete($old);
            $fields[$idKey] = null;
        }

        return $fields;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    protected function prepareTranslations(FormRequestInterface $request, Model $resource): array
    {
        /** @var array<int|string, array<string, mixed>> $translations */
        $translations = $request->input('translations', []);

        // getAttribute(), same reasoning as syncStatus() above.
        $existingTranslations = $resource->getAttribute('translations')->keyBy('language_id');

        foreach ($translations as $languageId => $fields) {
            $translation = $existingTranslations->get($languageId);

            foreach ($this->translationFileFields() as $field) {
                $fields = $this->syncTranslationFile($request, $translation, $languageId, $field, $fields);
            }

            BlockFileFieldSync::pruneOrphanedFiles(
                $translation?->content,
                $fields['content'] ?? []
            );

            $fields['site_id'] = $resource->getAttribute('site_id');

            if (array_key_exists('slug', $fields) && $fields['slug'] === '') {
                $fields['slug'] = null;
            }

            $translations[$languageId] = $fields;
        }

        return $translations;
    }
}