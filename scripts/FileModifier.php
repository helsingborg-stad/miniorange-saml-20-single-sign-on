<?php

namespace Municipio\Scripts;

use Composer\Script\Event;

class FileModifier
{
    public static function modify(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();
        $configurations = $extras['file-modifications'] ?? null;

        if (!$configurations || !is_array($configurations)) {
            self::log("No valid file modification configurations found.\n");
            return;
        }

        foreach ($configurations as $config) {
            self::processFileModification($config);
        }
    }

    /**
     * Process file modification based on configuration.
     * 
     * @param array $config
     */
    private static function processFileModification(array $config)
    {
        // Step 1: Validate configuration
        if (!self::validateConfiguration($config)) {
            return;
        }

        $filePath = $config['filePath'];
        $expectedHash = $config['expectedHash'];
        $injectContent = $config['injectContent'];
        $row = $config['row'];

        // Step 2: Validate target file
        if (!self::validateTargetFile($filePath, $expectedHash)) {
            return;
        }

        // Step 3: Create backup
        if (!self::createBackup($filePath)) {
            return;
        }

        // Step 4: Inject content
        if (self::injectContent($filePath, $injectContent, $row)) {
            self::log("File modified successfully: $filePath");
        }
    }

    /**
     * Validate configuration.
     * 
     * @param array $config
     * @return bool
     */
    private static function validateConfiguration(array $config): bool
    {
        $requiredKeys = ['filePath', 'expectedHash', 'injectContent', 'row'];
        foreach ($requiredKeys as $key) {
            if (empty($config[$key])) {
                self::log("Skipping modification: Missing required parameter '$key'.");
                return false;
            }
        }
        return true;
    }

    /**
     * Validate target file.
     * 
     * @param string $filePath
     * @param string $expectedHash
     * @return bool
     */
    private static function validateTargetFile(string $filePath, string $expectedHash): bool
    {
        if (!file_exists($filePath)) {
            self::log("File not found: $filePath");
            return false;
        }

        $currentHash = md5_file($filePath);
        if ($currentHash !== $expectedHash) {
            self::log("File hash mismatch for $filePath. Expected: $expectedHash, Found: $currentHash");
            return false;
        }

        return true;
    }

    /**
     * Create backup of target file.
     * 
     * @param string $filePath
     * @return bool
     */
    private static function createBackup(string $filePath): bool
    {
        $backupFile = $filePath . '.bkup';
        if (!copy($filePath, $backupFile)) {
            self::log("Failed to create backup: $backupFile");
            return false;
        }

        self::log("Backup created: $backupFile");
        return true;
    }

    /**
     * Inject content into target file.
     * 
     * @param string $filePath
     * @param string $injectContent
     * @param int $row
     * @return bool
     */
    private static function injectContent(string $filePath, string $injectContent, int $row): bool
    {
        $fileContents = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($fileContents === false) {
            self::log("Failed to read file: $filePath");
            return false;
        }

        // Ensure the row is within the valid range
        if ($row < 1 || $row > count($fileContents) + 1) {
            self::log("Invalid row number: $row for file: $filePath");
            return false;
        }

        // Insert the content at the specified row
        array_splice($fileContents, $row - 1, 0, $injectContent);

        // Write the updated content back to the file
        $result = file_put_contents($filePath, implode(PHP_EOL, $fileContents));
        if ($result === false) {
            self::log("Failed to write to file: $filePath");
            return false;
        }

        return true;
    }

    /**
     * Log message.
     * 
     * @param string $message
     */
    private static function log(string $message): void
    {
        echo $message . PHP_EOL;
    }
}