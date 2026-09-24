<?php namespace Siamfesp\Pdf\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddDocumentsThemeForeignKey extends Migration
{
    protected $table = 'siamfesp_estudos_documents';
    protected $foreignKey = 'siamfesp_estudos_documents_theme_id_foreign';

    public function up()
    {
        if (!Schema::hasTable($this->table) || !Schema::hasColumn($this->table, 'theme_id')) {
            return;
        }

        // Normaliza dados órfãos antes de aplicar a FK
        $existingThemeIds = [];
        if (Schema::hasTable('siamfesp_estudos_themes')) {
            $existingThemeIds = 
                \Db::table('siamfesp_estudos_themes')
                    ->pluck('id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->all();
        }

        \Db::table($this->table)
            ->whereNotNull('theme_id')
            ->when(!empty($existingThemeIds), function ($query) use ($existingThemeIds) {
                $query->whereNotIn('theme_id', $existingThemeIds);
            }, function ($query) {
                $query->whereRaw('1 = 1');
            })
            ->update(['theme_id' => null]);

        Schema::table($this->table, function (Blueprint $table) {
            if (!$this->hasForeignKey($this->table, $this->foreignKey)) {
                $table->foreign('theme_id', $this->foreignKey)
                    ->references('id')
                    ->on('siamfesp_estudos_themes')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if ($this->hasForeignKey($this->table, $this->foreignKey)) {
                $table->dropForeign($this->foreignKey);
            }
        });
    }

    protected function hasForeignKey($tableName, $foreignKeyName)
    {
        $databaseName = \Db::getDatabaseName();

        return \Db::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $foreignKeyName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
}
