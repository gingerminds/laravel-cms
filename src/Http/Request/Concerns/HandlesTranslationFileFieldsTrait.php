<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Concerns;

use Illuminate\Support\Str;

trait HandlesTranslationFileFieldsTrait
{
    /**
     * @return list<string>
     */
    abstract protected function fileFields(): array;

    /**
     * @return array{image?: bool, maxKb?: int}
     */
    protected function fileRuleOptions(string $field): array
    {
        return ['image' => true, 'maxKb' => 5120];
    }

    protected function isFileOrRemoveField(string $field): bool
    {
        return in_array($field, $this->fileFields(), true) || str_ends_with($field, '_remove');
    }

    /**
     * @return string[]
     */
    protected function fileRule(string $key): array
    {
        if (!$this->hasFile($key)) {
            return ['nullable'];
        }

        $field   = str_contains($key, '.') ? Str::afterLast($key, '.') : $key;
        $options = $this->fileRuleOptions($field);

        return ($options['image'] ?? true)
            ? ['file', 'image', 'max:' . ($options['maxKb'] ?? 5120)]
            : ['file', 'max:' . ($options['maxKb'] ?? 5120)];
    }
}
