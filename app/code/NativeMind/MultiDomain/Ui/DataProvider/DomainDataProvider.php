<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use NativeMind\MultiDomain\Model\DomainConfigReader;

/**
 * Data provider for domain listing grid
 */
class DomainDataProvider extends AbstractDataProvider
{
    private DomainConfigReader $configReader;
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        DomainConfigReader $configReader,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->configReader = $configReader;
    }

    /**
     * Get data for grid
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $domains = $this->configReader->getDomains();
        $items = [];

        foreach ($domains as $domain => $storeCode) {
            $items[] = [
                'domain' => $domain,
                'store_code' => $storeCode,
            ];
        }

        $this->loadedData = [
            'totalRecords' => count($items),
            'items' => $items,
        ];

        return $this->loadedData;
    }

    /**
     * Add filter - not applicable for config-based storage
     */
    public function addFilter(Filter $filter): void
    {
        // Filtering handled client-side for config-based data
    }
}
