<?php
/**
 * Backup Manager for MatchDay.ro
 * Handles database export and file backup
 */

require_once(__DIR__ . '/../config/database.php');

class BackupManager {
    private $pdo;
    private $backupDir;
    private $maxBackups = 10; // Keep last 10 backups
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo ?: Database::getInstance();
        $this->backupDir = __DIR__ . '/../data/backups/';
        
        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Get list of tables in database
     */
    private function getTables(): array {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'mysql') {
            $stmt = $this->pdo->query("SHOW TABLES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            // SQLite
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    
    /**
     * Export database to JSON format
     */
    public function exportToJSON(): array {
        $tables = $this->getTables();
        $data = [
            'exported_at' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'tables' => []
        ];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT * FROM `{$table}`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $data['tables'][$table] = $rows;
            } catch (Exception $e) {
                $data['tables'][$table] = ['error' => $e->getMessage()];
            }
        }
        
        return $data;
    }
    
    /**
     * Export database to SQL format
     */
    public function exportToSQL(): string {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $tables = $this->getTables();
        $sql = "-- MatchDay.ro Database Backup\n";
        $sql .= "-- Exported at: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Driver: {$driver}\n\n";
        
        foreach ($tables as $table) {
            $sql .= "-- Table: {$table}\n";
            
            // Get table structure
            if ($driver === 'mysql') {
                $stmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $sql .= $row['Create Table'] . ";\n\n";
            } else {
                // SQLite - get schema
                $stmt = $this->pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name=?");
                $stmt->execute([$table]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $sql .= $row['sql'] . ";\n\n";
                }
            }
            
            // Get data
            $stmt = $this->pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnsList = implode('`, `', $columns);
                
                foreach ($rows as $row) {
                    $values = array_map(function($val) {
                        if ($val === null) return 'NULL';
                        return "'" . addslashes($val) . "'";
                    }, array_values($row));
                    
                    $sql .= "INSERT INTO `{$table}` (`{$columnsList}`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        return $sql;
    }
    
    /**
     * Save backup to file
     */
    public function saveBackup(string $type = 'json'): array {
        $timestamp = date('Y-m-d_H-i-s');
        
        if ($type === 'json') {
            $filename = "backup_{$timestamp}.json";
            $filepath = $this->backupDir . $filename;
            $data = $this->exportToJSON();
            file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $filename = "backup_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;
            $sql = $this->exportToSQL();
            file_put_contents($filepath, $sql);
        }
        
        // Cleanup old backups
        $this->cleanupOldBackups();
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'created_at' => $timestamp
        ];
    }
    
    /**
     * Get list of available backups
     */
    public function getBackups(): array {
        $backups = [];
        
        $files = glob($this->backupDir . 'backup_*.*');
        foreach ($files as $file) {
            $info = pathinfo($file);
            $backups[] = [
                'filename' => $info['basename'],
                'type' => $info['extension'],
                'size' => filesize($file),
                'size_formatted' => $this->formatBytes(filesize($file)),
                'created_at' => filemtime($file),
                'created_at_formatted' => date('d.m.Y H:i:s', filemtime($file))
            ];
        }
        
        // Sort by date descending
        usort($backups, function($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });
        
        return $backups;
    }
    
    /**
     * Download backup file
     */
    public function downloadBackup(string $filename): void {
        $filepath = $this->backupDir . basename($filename);
        
        if (!file_exists($filepath)) {
            throw new Exception('Backup file not found');
        }
        
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        $contentType = $ext === 'json' ? 'application/json' : 'application/sql';
        
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        
        readfile($filepath);
        exit;
    }
    
    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): bool {
        $filepath = $this->backupDir . basename($filename);
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        return unlink($filepath);
    }
    
    /**
     * Cleanup old backups, keep only maxBackups
     */
    private function cleanupOldBackups(): void {
        $backups = $this->getBackups();
        
        if (count($backups) > $this->maxBackups) {
            $toDelete = array_slice($backups, $this->maxBackups);
            foreach ($toDelete as $backup) {
                $this->deleteBackup($backup['filename']);
            }
        }
    }
    
    /**
     * Get backup statistics
     */
    public function getStats(): array {
        $backups = $this->getBackups();
        $totalSize = 0;
        
        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
        }
        
        return [
            'count' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'last_backup' => !empty($backups) ? $backups[0]['created_at_formatted'] : 'Niciun backup'
        ];
    }
    
    /**
     * Restore from JSON backup
     */
    public function restoreFromJSON(string $filename): array {
        $filepath = $this->backupDir . basename($filename);
        
        if (!file_exists($filepath)) {
            throw new Exception('Backup file not found');
        }
        
        $data = json_decode(file_get_contents($filepath), true);
        
        if (!$data || !isset($data['tables'])) {
            throw new Exception('Invalid backup file format');
        }
        
        $restored = [];
        $errors = [];
        
        foreach ($data['tables'] as $table => $rows) {
            if (isset($rows['error'])) {
                $errors[] = "Table {$table}: {$rows['error']}";
                continue;
            }
            
            if (empty($rows)) {
                continue;
            }
            
            try {
                // Clear existing data
                $this->pdo->exec("DELETE FROM `{$table}`");
                
                // Insert rows
                $columns = array_keys($rows[0]);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $columnsList = implode('`, `', $columns);
                
                $stmt = $this->pdo->prepare("INSERT INTO `{$table}` (`{$columnsList}`) VALUES ({$placeholders})");
                
                foreach ($rows as $row) {
                    $stmt->execute(array_values($row));
                }
                
                $restored[] = $table . ' (' . count($rows) . ' rows)';
            } catch (Exception $e) {
                $errors[] = "Table {$table}: " . $e->getMessage();
            }
        }
        
        return [
            'success' => empty($errors),
            'restored' => $restored,
            'errors' => $errors
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Export posts and files to ZIP archive
     */
    public function exportFullBackup(): array {
        $timestamp = date('Y-m-d_H-i-s');
        $zipFilename = "full_backup_{$timestamp}.zip";
        $zipPath = $this->backupDir . $zipFilename;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Could not create ZIP file');
        }
        
        // Add database JSON
        $dbData = $this->exportToJSON();
        $zip->addFromString('database.json', json_encode($dbData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Add SQL backup
        $sqlData = $this->exportToSQL();
        $zip->addFromString('database.sql', $sqlData);
        
        // Add posts folder
        $postsDir = __DIR__ . '/../posts/';
        if (is_dir($postsDir)) {
            $files = glob($postsDir . '*.html');
            foreach ($files as $file) {
                $zip->addFile($file, 'posts/' . basename($file));
            }
        }
        
        // Add uploads folder
        $uploadsDir = __DIR__ . '/../assets/uploads/';
        if (is_dir($uploadsDir)) {
            $this->addFolderToZip($zip, $uploadsDir, 'uploads');
        }
        
        // Add data folder (JSON files)
        $dataDir = __DIR__ . '/../data/';
        $dataFiles = ['editorial-plan.json', 'rate_limits.json'];
        foreach ($dataFiles as $dataFile) {
            if (file_exists($dataDir . $dataFile)) {
                $zip->addFile($dataDir . $dataFile, 'data/' . $dataFile);
            }
        }
        
        // Add comments
        $commentsDir = $dataDir . 'comments/';
        if (is_dir($commentsDir)) {
            $this->addFolderToZip($zip, $commentsDir, 'data/comments');
        }
        
        // Add polls
        $pollsDir = $dataDir . 'polls/';
        if (is_dir($pollsDir)) {
            $this->addFolderToZip($zip, $pollsDir, 'data/polls');
        }
        
        $zip->close();
        
        return [
            'success' => true,
            'filename' => $zipFilename,
            'filepath' => $zipPath,
            'size' => filesize($zipPath),
            'size_formatted' => $this->formatBytes(filesize($zipPath))
        ];
    }
    
    /**
     * Add folder contents to ZIP
     */
    private function addFolderToZip(ZipArchive $zip, string $folder, string $zipFolder): void {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipFolder . '/' . substr($filePath, strlen($folder));
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
