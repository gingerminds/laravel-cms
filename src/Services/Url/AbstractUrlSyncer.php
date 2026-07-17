<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Url;

use Illuminate\Database\Eloquent\Model;

/**
 * Generic engine behind the "page_urls"-style tables: given an owner model
 * (Page, Product, ...) with translations, it (re)computes one URL row per
 * language and deletes the ones that no longer qualify.
 *
 * Concrete syncers only need to answer a handful of questions about their
 * own model (which URL model/table to write to, what makes a translation
 * "actually translated", how to compose the path, whether the owner is
 * publishable at all) — the row bookkeeping (updateOrCreate + prune stale
 * languages) is identical for every model and lives here once, so a new
 * URL-able resource only ever needs a small concrete subclass, not a copy
 * of this loop.
 *
 * @template TOwner of Model
 * @template TTranslation of Model
 */
abstract class AbstractUrlSyncer
{
    /**
     * Fully qualified class name of the *_urls model (e.g. `PageUrl::class`).
     *
     * @return class-string<Model>
     */
    abstract protected function urlModelClass(): string;

    /**
     * Column on the *_urls table pointing back to the owner (e.g. `page_id`).
     */
    abstract protected function ownerForeignKey(): string;

    /**
     * Whether the owner should have any URL at all right now (e.g. `Page`
     * only keeps URLs while in a `Published` status). Return `true`
     * unconditionally if the owning model has no such concept.
     *
     * @param TOwner $owner
     */
    abstract protected function isPublishable(Model $owner): bool;

    /**
     * Whether a given translation is "complete enough" to deserve a URL row
     * (e.g. `Page` requires a non-empty title).
     *
     * @param TTranslation $translation
     */
    abstract protected function isEligible(Model $translation): bool;

    /**
     * Composes the final `path` column for one translation.
     *
     * @param TOwner $owner
     * @param TTranslation $translation
     */
    abstract protected function resolvePath(Model $owner, Model $translation): string;

    /**
     * Relations to eager-load on the owner before iterating its
     * translations, beyond the translations relation itself (always
     * loaded). Override to add e.g. a category/hierarchy relation needed by
     * `resolvePath()`.
     *
     * @return list<string>
     */
    protected function eagerLoadRelations(): array
    {
        return [];
    }

    /**
     * Extra attributes to store on every URL row alongside `path` (besides
     * the owner foreign key and `language_id`, which are always set).
     * Defaults to denormalizing the owner's own `site_id`, true for every
     * URL-able model so far.
     *
     * @param TOwner $owner
     * @return array<string, mixed>
     */
    protected function extraAttributes(Model $owner): array
    {
        // getAttribute(), not the ->site_id magic property: PHPStan can't
        // resolve dynamic properties through the generic TOwner template,
        // only through Eloquent's own typed accessor methods.
        return ['site_id' => $owner->getAttribute('site_id')];
    }

    /**
     * Name of the relation on the owner exposing its translations.
     */
    protected function translationsRelationName(): string
    {
        return 'translations';
    }

    /**
     * Recomputes every language's URL row for one owner instance.
     *
     * @param TOwner $owner
     */
    public function sync(Model $owner): void
    {
        $owner->load(array_merge(
            [$this->translationsRelationName()],
            $this->eagerLoadRelations(),
        ));

        /** @var class-string<Model> $urlModelClass */
        $urlModelClass = $this->urlModelClass();
        $foreignKey    = $this->ownerForeignKey();

        // getKey()/getAttribute(), not ->id/->language_id: same PHPStan
        // reasoning as extraAttributes() above, applied to every dynamic
        // property read on the generic TOwner/TTranslation templates below.
        $ownerId = $owner->getKey();

        if (!$this->isPublishable($owner)) {
            $urlModelClass::query()->where($foreignKey, $ownerId)->delete();

            return;
        }

        $translatedLanguageIds = [];

        foreach ($owner->{$this->translationsRelationName()} as $translation) {
            /** @var TTranslation $translation */
            if (!$this->isEligible($translation)) {
                continue;
            }

            $languageId = $translation->getAttribute('language_id');

            $urlModelClass::query()->updateOrCreate(
                [$foreignKey => $ownerId, 'language_id' => $languageId],
                array_merge(
                    $this->extraAttributes($owner),
                    ['path' => $this->resolvePath($owner, $translation)],
                ),
            );

            $translatedLanguageIds[] = $languageId;
        }

        // Authoritative, not just additive: a language that no longer
        // qualifies (e.g. its slug/title was cleared out after having one)
        // must lose its stale row too, not just skip getting a new one.
        $urlModelClass::query()
            ->where($foreignKey, $ownerId)
            ->whereNotIn('language_id', $translatedLanguageIds)
            ->delete();
    }
}
