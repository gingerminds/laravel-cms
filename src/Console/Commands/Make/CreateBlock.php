<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Console\Commands\Make;

use Gingerminds\LaravelCms\Blocks\BlockInterface;
use Gingerminds\LaravelCms\Blocks\BlockRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Scaffolds a new content block (see docs/Blocks.md): the block class in
 * `app/Cms/Blocks` (auto-discovered by `BlockRegistry`, no manual
 * registration needed) and its admin preview view. Only targets the
 * consuming project, never this package itself — a block only belongs
 * package-side if it ships as one of the built-in reference blocks.
 *
 * Two safety checks a hand-written block wouldn't get for free: refuses to
 * overwrite an existing class, and refuses to generate a block whose key
 * would collide with one already registered (`BlockRegistry::discover()`
 * would otherwise let the last one scanned silently win, see BlockRegistry).
 */
class CreateBlock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:cms-block {name : Block class name, e.g. BlockImage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new content block class and its preview view in app/Cms/Blocks';

    public function handle(): int
    {
        $name = $this->argument('name');
        $raw  = trim(is_string($name) ? $name : '');

        if ($raw === '') {
            $this->error('Name is required.');
            return Command::FAILURE;
        }

        $class = Str::studly($raw);

        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $class)) {
            $this->error("\"{$raw}\" can't be turned into a valid class name.");
            return Command::FAILURE;
        }

        $classPath = app_path("Cms/Blocks/{$class}.php");

        if (file_exists($classPath)) {
            $this->error("Block already exists: {$classPath}");
            return Command::FAILURE;
        }

        // The key isn't asked for: it's derived from the class name, same
        // convention as every block shipped in this package (TitleText ->
        // title_text), so there's only one name to pick per block, not two
        // that can drift apart.
        $key = Str::snake($class);

        $collision = $this->findKeyCollision($key);

        if ($collision instanceof BlockInterface) {
            $this->error(
                "Key \"{$key}\" is already used by " . $collision::class . '. ' .
                "Two blocks can't share the same key — pick another class name."
            );
            return Command::FAILURE;
        }

        $kebab = Str::kebab($class);
        $label = Str::headline($class);

        $this->createClassFile($classPath, $class, $key, $label, $kebab);
        $previewPath = $this->createPreviewView($kebab, $key);

        $this->info("Block created: {$classPath}");
        $this->info("Preview view created: {$previewPath}");
        $this->line('');
        $this->line("Next: edit fields() in {$class}.php — it'll show up in the block picker on its own, no registration needed.");

        return Command::SUCCESS;
    }

    /**
     * Checked at generation time, not left to `BlockRegistry`: the class
     * doesn't exist yet at this point, so it can't have been discovered
     * (and therefore can't be excluded from) the scan itself.
     */
    private function findKeyCollision(string $key): ?BlockInterface
    {
        foreach (BlockRegistry::all() as $block) {
            if ($block->key() === $key) {
                return $block;
            }
        }

        return null;
    }

    private function createClassFile(string $path, string $class, string $key, string $label, string $kebab): void
    {
        $stub = $this->stub('block.stub');

        $content = str_replace(
            ['{{class}}', '{{key}}', '{{label}}', '{{kebab}}'],
            [$class, $key, $label, $kebab],
            $stub
        );

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);
    }

    private function createPreviewView(string $kebab, string $key): string
    {
        $path = resource_path("views/cms/blocks/{$kebab}/preview.blade.php");

        if (file_exists($path)) {
            return $path;
        }

        $stub    = $this->stub('block-preview.stub');
        $content = str_replace(['__KEBAB__', '__KEY__'], [$kebab, $key], $stub);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);

        return $path;
    }

    /**
     * A project can override either stub the same way `gingerminds-cms`
     * publishes them: `vendor:publish --tag=gingerminds-stubs` copies them
     * to `stubs/vendor/gingerminds-cms/`, checked here first.
     */
    private function stub(string $name): string
    {
        $published = base_path("stubs/vendor/gingerminds-cms/{$name}");

        $path = file_exists($published) ? $published : __DIR__ . "/../../../../stubs/{$name}";

        return (string) file_get_contents($path);
    }
}
