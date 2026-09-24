<?php namespace Siamfesp\Menu\Traits;

/**
 * Gera automaticamente alias/slug únicos para os Models do CMS.
 *
 * Regras:
 * - se o campo estiver vazio, usa o título/nome como base;
 * - se o usuário apagar o alias/slug e salvar, ele é recriado;
 * - se já existir outro registro com o mesmo valor, acrescenta -2, -3 etc.;
 * - preserva URLs existentes quando o campo já está preenchido e sem conflito.
 */
trait AutoSlug
{
    protected function ensureAutomaticSlug(string $column, string $sourceColumn, array $options = []): string
    {
        if (!$this->autoSlugHasColumn($column)) {
            return '';
        }

        $current = $this->autoSlugClean((string) ($this->{$column} ?? ''));
        $source = $this->autoSlugClean((string) ($this->{$sourceColumn} ?? ''));

        $base = $current !== '' ? $current : $source;
        $base = $this->autoSlugMakeBase($base);

        if ($base === '') {
            $base = 'registro';
        }

        $unique = $this->autoSlugMakeUnique($column, $base, $options);
        $this->{$column} = $unique;

        return $unique;
    }

    protected function ensureAutomaticAliasAndSlug(string $aliasColumn, string $slugColumn, string $sourceColumn, array $options = []): void
    {
        $aliasExists = $this->autoSlugHasColumn($aliasColumn);
        $slugExists = $this->autoSlugHasColumn($slugColumn);

        if (!$aliasExists && !$slugExists) {
            return;
        }

        $source = $this->autoSlugClean((string) ($this->{$sourceColumn} ?? ''));
        $alias = $aliasExists ? $this->autoSlugClean((string) ($this->{$aliasColumn} ?? '')) : '';
        $slug = $slugExists ? $this->autoSlugClean((string) ($this->{$slugColumn} ?? '')) : '';

        $base = $alias !== '' ? $alias : ($slug !== '' ? $slug : $source);
        $base = $this->autoSlugMakeBase($base);

        if ($base === '') {
            $base = 'registro';
        }

        if ($aliasExists) {
            $aliasOptions = $options;
            $aliasOptions['maxLength'] = $aliasOptions['maxLength'] ?? 250;
            $this->{$aliasColumn} = $this->autoSlugMakeUnique($aliasColumn, $base, $aliasOptions);
        }

        if ($slugExists) {
            $slugBase = $slug !== '' ? $this->autoSlugMakeBase($slug) : $this->autoSlugMakeBase((string) ($this->{$aliasColumn} ?? $base));
            if ($slugBase === '') {
                $slugBase = $base;
            }

            $slugOptions = $options;
            $slugOptions['maxLength'] = $slugOptions['maxLength'] ?? 250;
            $this->{$slugColumn} = $this->autoSlugMakeUnique($slugColumn, $slugBase, $slugOptions);
        }
    }

    protected function autoSlugMakeUnique(string $column, string $base, array $options = []): string
    {
        $maxLength = (int) ($options['maxLength'] ?? 250);
        if ($maxLength < 10) {
            $maxLength = 250;
        }

        $base = trim($base, '-');
        if ($base === '') {
            $base = 'registro';
        }

        $base = $this->autoSlugLimit($base, $maxLength);
        $candidate = $base;
        $counter = 2;

        while ($this->autoSlugExists($column, $candidate, $options)) {
            $suffix = '-' . $counter;
            $candidate = $this->autoSlugLimit($base, $maxLength - strlen($suffix)) . $suffix;
            $counter++;

            if ($counter > 9999) {
                $suffix = '-' . substr(md5(uniqid('', true)), 0, 8);
                $candidate = $this->autoSlugLimit($base, $maxLength - strlen($suffix)) . $suffix;
                break;
            }
        }

        return $candidate;
    }

    protected function autoSlugExists(string $column, string $value, array $options = []): bool
    {
        try {
            $query = static::query()->where($column, $value);

            $keyName = method_exists($this, 'getKeyName') ? $this->getKeyName() : 'id';
            $key = method_exists($this, 'getKey') ? $this->getKey() : ($this->attributes[$keyName] ?? null);

            if ($key) {
                $query->where($keyName, '<>', $key);
            }

            foreach (($options['scopeColumns'] ?? []) as $scopeColumn) {
                if (!$this->autoSlugHasColumn($scopeColumn)) {
                    continue;
                }

                $scopeValue = $this->{$scopeColumn} ?? null;
                if ($scopeValue === null || $scopeValue === '') {
                    $query->whereNull($scopeColumn);
                } else {
                    $query->where($scopeColumn, $scopeValue);
                }
            }

            return $query->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function autoSlugMakeBase(string $value): string
    {
        $value = $this->autoSlugClean($value);

        if ($value === '') {
            return '';
        }

        try {
            if (class_exists('Illuminate\\Support\\Str')) {
                return trim((string) \Illuminate\Support\Str::slug($value), '-');
            }
        } catch (\Throwable $e) {
        }

        try {
            if (class_exists('Str')) {
                return trim((string) \Str::slug($value), '-');
            }
        } catch (\Throwable $e) {
        }

        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        $value = trim((string) $value, '-');

        return $value;
    }

    protected function autoSlugClean(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim((string) $value);
    }

    protected function autoSlugLimit(string $value, int $limit): string
    {
        $value = trim($value, '-');

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value, 'UTF-8') > $limit) {
                $value = mb_substr($value, 0, $limit, 'UTF-8');
            }
        } elseif (strlen($value) > $limit) {
            $value = substr($value, 0, $limit);
        }

        return trim($value, '-');
    }

    protected function autoSlugHasColumn(string $column): bool
    {
        try {
            return \Schema::hasColumn($this->getTable(), $column);
        } catch (\Throwable $e) {
            return true;
        }
    }
}
