<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Console\Commands\Search;

use Gingerminds\LaravelCms\Services\Search\SearchIndexer;
use Illuminate\Console\Command;

class ReindexSearchCommand extends Command
{
    /** @var string */
    protected $signature = 'search:reindex
        {--type=* : Only reindex these registered search resource keys (repeatable). Omit to reindex every type.}';

    /** @var string */
    protected $description = 'Rebuild the cross-content search_index table for every '
        . '(or a subset of) the searchable content types';

    public function handle(SearchIndexer $indexer): int
    {
        $registered = array_keys((array) config('gingerminds-cms.search_resources', []));
        /** @var list<string> $requested */
        $requested = $this->option('type');

        foreach (array_diff($requested, $registered) as $unknown) {
            $this->warn("Unknown search resource \"{$unknown}\", skipping. Registered: " . implode(', ', $registered));
        }

        $types = $requested === [] ? $registered : array_intersect($requested, $registered);

        foreach ($types as $type) {
            $this->info("Reindexing \"{$type}\"...");
            $indexer->reindexType($type);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
