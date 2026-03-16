<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Plugin;

use Magento\Store\Model\StoreResolver;
use Magento\Store\Api\StoreRepositoryInterface;
use NativeMind\MultiDomain\Model\DomainConfigReader;
use Psr\Log\LoggerInterface;

/**
 * Plugin to resolve store based on domain name
 */
class StoreResolverPlugin
{
    private DomainConfigReader $configReader;
    private StoreRepositoryInterface $storeRepository;
    private LoggerInterface $logger;

    public function __construct(
        DomainConfigReader $configReader,
        StoreRepositoryInterface $storeRepository,
        LoggerInterface $logger
    ) {
        $this->configReader = $configReader;
        $this->storeRepository = $storeRepository;
        $this->logger = $logger;
    }

    /**
     * Intercept store resolution to check domain mapping
     *
     * @param StoreResolver $subject
     * @param callable $proceed
     * @return string Store ID
     */
    public function aroundGetCurrentStoreId(
        StoreResolver $subject,
        callable $proceed
    ): string {
        $host = $this->getHost();
        $storeCode = $this->configReader->getStoreCodeByDomain($host);

        if ($storeCode !== null) {
            try {
                $store = $this->storeRepository->get($storeCode);

                if ($store->isActive()) {
                    if ($this->configReader->isDebugMode()) {
                        $this->logger->debug(
                            "MultiDomain: Resolved domain '{$host}' to store '{$storeCode}'"
                        );
                    }
                    return (string) $store->getId();
                }

                $this->logger->warning(
                    "MultiDomain: Store '{$storeCode}' for domain '{$host}' is not active"
                );
            } catch (\Exception $e) {
                $this->logger->warning(
                    "MultiDomain: Store '{$storeCode}' for domain '{$host}' not found",
                    ['exception' => $e->getMessage()]
                );
            }
        }

        // Fallback to default Magento behavior
        return $proceed();
    }

    /**
     * Get current HTTP host without port
     */
    private function getHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';

        // Remove port if present (e.g., localhost:8080 -> localhost)
        $host = strtolower(explode(':', $host)[0]);

        return trim($host);
    }
}
