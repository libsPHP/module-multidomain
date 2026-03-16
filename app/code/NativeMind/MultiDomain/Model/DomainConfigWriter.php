<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Model;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Exception\FileSystemException;

/**
 * Writes domain-to-store mapping configuration to PHP file
 */
class DomainConfigWriter
{
    private DomainConfigReader $configReader;
    private FileDriver $fileDriver;

    public function __construct(
        DomainConfigReader $configReader,
        FileDriver $fileDriver
    ) {
        $this->configReader = $configReader;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Add or update domain mapping
     *
     * @throws FileSystemException
     */
    public function setDomain(string $domain, string $storeCode): void
    {
        $config = $this->configReader->getConfig();
        $config['domains'][strtolower(trim($domain))] = $storeCode;
        $this->writeConfig($config);
    }

    /**
     * Remove domain mapping
     *
     * @throws FileSystemException
     */
    public function removeDomain(string $domain): void
    {
        $config = $this->configReader->getConfig();
        $domain = strtolower(trim($domain));

        if (isset($config['domains'][$domain])) {
            unset($config['domains'][$domain]);
            $this->writeConfig($config);
        }
    }

    /**
     * Set default store for unknown domains
     *
     * @throws FileSystemException
     */
    public function setDefaultStore(string $storeCode): void
    {
        $config = $this->configReader->getConfig();
        $config['default_store'] = $storeCode;
        $this->writeConfig($config);
    }

    /**
     * Set debug mode
     *
     * @throws FileSystemException
     */
    public function setDebugMode(bool $enabled): void
    {
        $config = $this->configReader->getConfig();
        $config['debug_mode'] = $enabled;
        $this->writeConfig($config);
    }

    /**
     * Write full configuration to file
     *
     * @throws FileSystemException
     */
    public function writeConfig(array $config): void
    {
        $configPath = $this->configReader->getConfigPath();
        $content = $this->generatePhpContent($config);

        $this->fileDriver->filePutContents($configPath, $content);
        $this->configReader->invalidateCache();
    }

    /**
     * Generate PHP file content from config array
     */
    private function generatePhpContent(array $config): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $export = var_export($config, true);

        // Format array for better readability
        $export = preg_replace('/array \(/', '[', $export);
        $export = preg_replace('/\)$/', ']', $export);
        $export = preg_replace('/\)(,?)$/m', ']$1', $export);

        return <<<PHP
<?php
/**
 * NativeMind MultiDomain Configuration
 * Auto-generated file. Do not edit manually.
 * Last updated: {$timestamp}
 */
return {$export};
PHP;
    }
}
