<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Model;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Reads domain-to-store mapping configuration from PHP file
 */
class DomainConfigReader
{
    private const CONFIG_FILE = 'nativemind_multidomain.php';

    private ?array $config = null;
    private DirectoryList $directoryList;

    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * Get full configuration array
     */
    public function getConfig(): array
    {
        if ($this->config === null) {
            $this->config = $this->loadConfig();
        }
        return $this->config;
    }

    /**
     * Get store code by domain name
     */
    public function getStoreCodeByDomain(string $domain): ?string
    {
        $config = $this->getConfig();
        $domain = strtolower(trim($domain));

        return $config['domains'][$domain] ?? null;
    }

    /**
     * Get default store code for unknown domains
     */
    public function getDefaultStore(): string
    {
        $config = $this->getConfig();
        return $config['default_store'] ?? 'default';
    }

    /**
     * Get all domain mappings
     */
    public function getDomains(): array
    {
        $config = $this->getConfig();
        return $config['domains'] ?? [];
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        $config = $this->getConfig();
        return (bool) ($config['debug_mode'] ?? false);
    }

    /**
     * Invalidate cached config (call after write)
     */
    public function invalidateCache(): void
    {
        $this->config = null;
    }

    /**
     * Load configuration from file
     */
    private function loadConfig(): array
    {
        $configPath = $this->getConfigPath();

        if (!file_exists($configPath)) {
            return $this->getDefaultConfig();
        }

        try {
            $config = include $configPath;
            return is_array($config) ? $config : $this->getDefaultConfig();
        } catch (\Throwable $e) {
            return $this->getDefaultConfig();
        }
    }

    /**
     * Get path to configuration file
     */
    public function getConfigPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::CONFIG)
            . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'domains' => [],
            'default_store' => 'default',
            'debug_mode' => false,
        ];
    }
}
