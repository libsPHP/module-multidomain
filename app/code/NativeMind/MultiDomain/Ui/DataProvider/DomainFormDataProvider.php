<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;
use NativeMind\MultiDomain\Model\DomainConfigReader;

/**
 * Data provider for domain edit form
 */
class DomainFormDataProvider extends AbstractDataProvider
{
    private DomainConfigReader $configReader;
    private RequestInterface $request;
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        DomainConfigReader $configReader,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->configReader = $configReader;
        $this->request = $request;
    }

    /**
     * Get data for form
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $domain = $this->request->getParam('domain');

        if ($domain) {
            $domains = $this->configReader->getDomains();
            $storeCode = $domains[$domain] ?? '';

            $this->loadedData[$domain] = [
                'domain' => $domain,
                'store_code' => $storeCode,
                'original_domain' => $domain,
            ];
        }

        return $this->loadedData;
    }
}
