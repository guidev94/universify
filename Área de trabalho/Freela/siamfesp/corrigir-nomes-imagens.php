<?php
/**
 * Script para corrigir nomes de arquivos da pasta /images no OctoberCMS/SIAMFESP.
 *
 * O que faz:
 * 1) Procura arquivos e pastas dentro de /images com espaço no nome.
 * 2) Renomeia trocando espaços por underline "_".
 * 3) Atualiza referências no banco da tabela siamfesp_artigos_articles:
 *    - images_json
 *    - image_intro_path
 *    - image_fulltext_path
 *    - introtext
 *    - fulltext
 *    - content_html
 *
 * Segurança:
 * - Por padrão roda em modo teste e não altera nada.
 * - Para executar de verdade, acesse: /corrigir-nomes-imagens.php?executar=1
 * - Depois de executar, apague este arquivo do servidor.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

$basePath = __DIR__;
$imagesDir = $basePath . DIRECTORY_SEPARATOR . 'images';
$execute = isset($_GET['executar']) && $_GET['executar'] == '1';

header('Content-Type: text/plain; charset=utf-8');

echo "Correção de nomes de imagens - SIAMFESP\n";
echo "Modo: " . ($execute ? "EXECUTANDO ALTERAÇÕES" : "TESTE / SIMULAÇÃO") . "\n";
echo "Pasta analisada: {$imagesDir}\n\n";

if (!is_dir($imagesDir)) {
    echo "ERRO: pasta /images não encontrada na raiz onde este arquivo foi colocado.\n";
    echo "Coloque este arquivo na raiz do OctoberCMS, junto das pastas app, plugins, themes e images.\n";
    exit;
}

function normalizeFileNamePart($name)
{
    $name = str_replace(' ', '_', $name);
    $name = preg_replace('/_+/', '_', $name);
    return $name;
}

function normalizeRelativePath($path)
{
    $path = str_replace('\\\\', '/', $path);
    $path = str_replace('\\', '/', $path);
    $parts = explode('/', $path);
    $parts = array_map('normalizeFileNamePart', $parts);
    return implode('/', $parts);
}

function publicPathFromAbsolute($absolute, $basePath)
{
    $relative = substr($absolute, strlen($basePath));
    $relative = str_replace('\\', '/', $relative);
    $relative = ltrim($relative, '/');
    return $relative;
}

/**
 * Primeiro monta uma lista de arquivos/pastas, do mais profundo para o mais raso.
 * Assim evita problema ao renomear pasta antes dos arquivos internos.
 */
$items = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($imagesDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($iterator as $item) {
    $oldAbsolute = $item->getPathname();
    $oldRelative = publicPathFromAbsolute($oldAbsolute, $basePath);

    if (strpos($oldRelative, ' ') === false) {
        continue;
    }

    $newRelative = normalizeRelativePath($oldRelative);
    $newAbsolute = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $newRelative);

    if ($oldAbsolute === $newAbsolute) {
        continue;
    }

    $items[] = [
        'old_absolute' => $oldAbsolute,
        'new_absolute' => $newAbsolute,
        'old_relative' => $oldRelative,
        'new_relative' => $newRelative,
        'is_dir' => $item->isDir(),
    ];
}

$map = [];

if (!$items) {
    echo "Nenhum arquivo ou pasta com espaço foi encontrado dentro de /images.\n";
} else {
    echo "Arquivos/pastas encontrados para renomear:\n\n";
}

foreach ($items as $row) {
    echo "- {$row['old_relative']}\n";
    echo "  => {$row['new_relative']}\n";

    $map[$row['old_relative']] = $row['new_relative'];
    $map['/' . $row['old_relative']] = '/' . $row['new_relative'];

    // Também cobre casos sem o prefixo images/ por segurança.
    if (strpos($row['old_relative'], 'images/') === 0) {
        $map[substr($row['old_relative'], 7)] = substr($row['new_relative'], 7);
    }

    if ($execute) {
        if (file_exists($row['new_absolute'])) {
            echo "  AVISO: destino já existe. Não renomeado para evitar sobrescrever.\n";
            continue;
        }

        $parent = dirname($row['new_absolute']);
        if (!is_dir($parent)) {
            mkdir($parent, 0755, true);
        }

        if (@rename($row['old_absolute'], $row['new_absolute'])) {
            echo "  OK renomeado.\n";
        } else {
            echo "  ERRO ao renomear. Verifique permissões da pasta/arquivo.\n";
        }
    }
}

if (!$map) {
    echo "\nNada para atualizar no banco.\n";
    exit;
}

echo "\nReferências que serão atualizadas no banco/conteúdo:\n\n";
foreach ($map as $old => $new) {
    if ($old === $new) {
        continue;
    }
    echo "{$old} => {$new}\n";
}

// Tenta carregar o OctoberCMS/Laravel para atualizar o banco.
$autoload = $basePath . '/vendor/autoload.php';
$appFile = $basePath . '/bootstrap/app.php';

if (!file_exists($autoload) || !file_exists($appFile)) {
    echo "\nAVISO: não foi possível carregar o OctoberCMS. Arquivos renomeados, mas banco não atualizado.\n";
    echo "vendor/autoload.php ou bootstrap/app.php não encontrado.\n";
    exit;
}

require_once $autoload;
$app = require_once $appFile;

try {
    if (method_exists($app, 'make')) {
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
    }

    $db = $app->make('db');
    $schema = $app->make('db')->connection()->getSchemaBuilder();

    $table = 'siamfesp_artigos_articles';
    if (!$schema->hasTable($table)) {
        echo "\nAVISO: tabela {$table} não encontrada. Banco não atualizado.\n";
        exit;
    }

    $columns = ['images_json', 'image_intro_path', 'image_fulltext_path', 'introtext', 'fulltext', 'content_html'];
    $existingColumns = [];
    foreach ($columns as $column) {
        if ($schema->hasColumn($table, $column)) {
            $existingColumns[] = $column;
        }
    }

    if (!$existingColumns) {
        echo "\nAVISO: nenhuma coluna esperada foi encontrada em {$table}.\n";
        exit;
    }

    echo "\nColunas encontradas para atualização: " . implode(', ', $existingColumns) . "\n";

    if (!$execute) {
        echo "\nSIMULAÇÃO FINALIZADA. Nenhum arquivo ou registro foi alterado.\n";
        echo "Para executar de verdade, acesse este mesmo arquivo com ?executar=1\n";
        echo "Exemplo: https://seusite.com.br/corrigir-nomes-imagens.php?executar=1\n";
        exit;
    }

    $rows = $db->table($table)->select(array_merge(['id'], $existingColumns))->get();
    $updated = 0;

    foreach ($rows as $row) {
        $data = [];

        foreach ($existingColumns as $column) {
            $value = isset($row->$column) ? (string) $row->$column : '';
            if ($value === '') {
                continue;
            }

            $newValue = $value;
            foreach ($map as $old => $new) {
                $newValue = str_replace($old, $new, $newValue);
                $newValue = str_replace(rawurlencode($old), rawurlencode($new), $newValue);
                $newValue = str_replace(str_replace(' ', '%20', $old), str_replace(' ', '_', $new), $newValue);
            }

            if ($newValue !== $value) {
                $data[$column] = $newValue;
            }
        }

        if ($data) {
            $db->table($table)->where('id', $row->id)->update($data);
            $updated++;
            echo "Artigo ID {$row->id} atualizado.\n";
        }
    }

    echo "\nFINALIZADO. Registros atualizados: {$updated}\n";
    echo "IMPORTANTE: apague este arquivo do servidor agora.\n";
    echo "Depois limpe o cache do OctoberCMS.\n";
} catch (Throwable $e) {
    echo "\nERRO ao atualizar banco: " . $e->getMessage() . "\n";
    echo "Os arquivos podem ter sido renomeados. Confira manualmente antes de rodar novamente.\n";
}
