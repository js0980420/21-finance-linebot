<?php
/**
 * Migration 順序檢查工具
 * 確保 migration 檔案按照正確的時間順序排列
 */

// 取得 migration 目錄路徑
$migrationPath = __DIR__ . '/../backend/database/migrations';

if (!is_dir($migrationPath)) {
    echo "❌ Migration 目錄不存在: {$migrationPath}\n";
    exit(1);
}

// 讀取所有 migration 檔案
$files = glob($migrationPath . '/*.php');
$migrations = [];

foreach ($files as $file) {
    $filename = basename($file);
    // 提取時間戳 (前14個字符: YYYY_MM_DD_HHMMSS)
    if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)\.php$/', $filename, $matches)) {
        $timestamp = $matches[1];
        $name = $matches[2];
        $migrations[] = [
            'file' => $filename,
            'timestamp' => $timestamp,
            'name' => $name,
            'sort_key' => str_replace('_', '', $timestamp) // 用於排序的純數字
        ];
    }
}

// 按時間戳排序
usort($migrations, function($a, $b) {
    return strcmp($a['sort_key'], $b['sort_key']);
});

echo "🔍 Migration 檔案順序檢查結果:\n";
echo "========================================\n";

$lastTimestamp = '';
$issues = [];

foreach ($migrations as $index => $migration) {
    $status = '✅';
    $currentSort = $migration['sort_key'];

    // 檢查是否有時間戳重複
    if ($lastTimestamp === $currentSort) {
        $status = '❌ 重複時間戳';
        $issues[] = "重複時間戳: {$migration['file']}";
    }

    // 檢查是否有時間戳回跳
    if ($lastTimestamp > $currentSort && $lastTimestamp !== '') {
        $status = '⚠️  時間戳回跳';
        $issues[] = "時間戳回跳: {$migration['file']}";
    }

    printf("%2d. %s %s %s\n",
        $index + 1,
        $status,
        $migration['timestamp'],
        $migration['name']
    );

    $lastTimestamp = $currentSort;
}

echo "\n========================================\n";

if (empty($issues)) {
    echo "🎉 所有 migration 檔案順序正確！\n";
    echo "✅ 總共 " . count($migrations) . " 個 migration 檔案\n";
    echo "✅ 時間戳順序正確\n";
    echo "✅ 沒有重複的時間戳\n";
} else {
    echo "⚠️  發現問題:\n";
    foreach ($issues as $issue) {
        echo "   - {$issue}\n";
    }
    echo "\n💡 建議:\n";
    echo "   - 檢查重複的時間戳\n";
    echo "   - 確認 migration 檔案的創建順序\n";
    echo "   - 如果需要，重新命名檔案以修正順序\n";
}

echo "\n📋 最近的 migration 檔案 (最後5個):\n";
$recent = array_slice($migrations, -5);
foreach ($recent as $migration) {
    echo "   🔸 {$migration['timestamp']} - {$migration['name']}\n";
}

echo "\n📚 使用指南:\n";
echo "   - 新增 migration: php artisan make:migration description\n";
echo "   - 檢查狀態: php artisan migrate:status\n";
echo "   - 執行 migration: php artisan migrate\n";
echo "   - 回滾 migration: php artisan migrate:rollback\n";

exit(empty($issues) ? 0 : 1);