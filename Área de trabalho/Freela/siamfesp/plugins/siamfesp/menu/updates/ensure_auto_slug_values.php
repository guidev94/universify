<?php namespace Siamfesp\Menu\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;
use Db;

class EnsureAutoSlugValues extends Migration
{
    public function up()
    {
        $this->backfillAliasAndSlug('siamfesp_articles', 'title', 'alias', 'slug', 250);
        $this->backfillSlug('siamfesp_articles_categories', 'title', 'slug', 390);
        $this->backfillSlug('siamfesp_agenda_events', 'title', 'slug', 250);
        $this->backfillSlug('siamfesp_associados', 'name', 'slug', 390);
        $this->backfillSlug('siamfesp_associados_categories', 'name', 'slug', 390);
        $this->backfillSlug('siamfesp_menus', 'title', 'slug', 250);
        $this->backfillSlug('siamfesp_menu_items', 'title', 'slug', 250);
    }

    public function down()
    {
        // Não desfazemos slugs gerados, pois eles passam a ser URLs públicas válidas.
    }

    protected function backfillAliasAndSlug(string $table, string $sourceColumn, string $aliasColumn, string $slugColumn, int $maxLength): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!Schema::hasColumn($table, $sourceColumn)) {
            return;
        }

        $hasAlias = Schema::hasColumn($table, $aliasColumn);
        $hasSlug = Schema::hasColumn($table, $slugColumn);

        if (!$hasAlias && !$hasSlug) {
            return;
        }

        $columns = ['id', $sourceColumn];
        if ($hasAlias) {
            $columns[] = $aliasColumn;
        }
        if ($hasSlug) {
            $columns[] = $slugColumn;
        }

        $seenAlias = [];
        $seenSlug = [];

        Db::table($table)->select($columns)->orderBy('id')->chunk(200, function ($rows) use ($table, $sourceColumn, $aliasColumn, $slugColumn, $maxLength, $hasAlias, $hasSlug, &$seenAlias, &$seenSlug) {
            foreach ($rows as $row) {
                $source = $this->clean((string) ($row->{$sourceColumn} ?? ''));
                $currentAlias = $hasAlias ? $this->clean((string) ($row->{$aliasColumn} ?? '')) : '';
                $currentSlug = $hasSlug ? $this->clean((string) ($row->{$slugColumn} ?? '')) : '';
                $base = $currentAlias !== '' ? $currentAlias : ($currentSlug !== '' ? $currentSlug : $source);
                $base = $this->slugBase($base) ?: 'registro';

                $updates = [];

                if ($hasAlias) {
                    $updates[$aliasColumn] = $this->uniqueValue($table, $aliasColumn, $base, (int) $row->id, $maxLength, $seenAlias);
                }

                if ($hasSlug) {
                    $slugBase = $currentSlug !== '' ? $this->slugBase($currentSlug) : $this->slugBase((string) ($updates[$aliasColumn] ?? $base));
                    $updates[$slugColumn] = $this->uniqueValue($table, $slugColumn, $slugBase ?: $base, (int) $row->id, $maxLength, $seenSlug);
                }

                $changes = [];
                foreach ($updates as $column => $value) {
                    if ($this->clean((string) ($row->{$column} ?? '')) !== $value) {
                        $changes[$column] = $value;
                    }
                }

                if ($changes) {
                    Db::table($table)->where('id', $row->id)->update($changes);
                }
            }
        });
    }

    protected function backfillSlug(string $table, string $sourceColumn, string $slugColumn, int $maxLength): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $sourceColumn) || !Schema::hasColumn($table, $slugColumn)) {
            return;
        }

        $seen = [];

        Db::table($table)->select('id', $sourceColumn, $slugColumn)->orderBy('id')->chunk(200, function ($rows) use ($table, $sourceColumn, $slugColumn, $maxLength, &$seen) {
            foreach ($rows as $row) {
                $current = $this->clean((string) ($row->{$slugColumn} ?? ''));
                $source = $this->clean((string) ($row->{$sourceColumn} ?? ''));
                $base = $this->slugBase($current !== '' ? $current : $source) ?: 'registro';
                $slug = $this->uniqueValue($table, $slugColumn, $base, (int) $row->id, $maxLength, $seen);

                if ($current !== $slug) {
                    Db::table($table)->where('id', $row->id)->update([$slugColumn => $slug]);
                }
            }
        });
    }

    protected function uniqueValue(string $table, string $column, string $base, int $id, int $maxLength, array &$seen): string
    {
        $base = $this->limit(trim($base, '-'), $maxLength) ?: 'registro';
        $candidate = $base;
        $counter = 2;

        while (isset($seen[$candidate])) {
            $suffix = '-' . $counter;
            $candidate = $this->limit($base, $maxLength - strlen($suffix)) . $suffix;
            $counter++;

            if ($counter > 9999) {
                $suffix = '-' . substr(md5($table . $column . $id . microtime(true)), 0, 8);
                $candidate = $this->limit($base, $maxLength - strlen($suffix)) . $suffix;
                break;
            }
        }

        $seen[$candidate] = true;

        return $candidate;
    }

    protected function slugBase(string $value): string
    {
        $value = $this->clean($value);

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

        return trim((string) $value, '-');
    }

    protected function clean(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim((string) $value);
    }

    protected function limit(string $value, int $limit): string
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
}
