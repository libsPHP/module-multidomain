<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Ui\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Store View options for select/filter
 */
class StoreViewOptions implements OptionSourceInterface
{
    private StoreManagerInterface $storeManager;
    private ?array $options = null;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Get options array
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = [];

            foreach ($this->storeManager->getStores() as $store) {
                $this->options[] = [
                    'value' => $store->getCode(),
                    'label' => sprintf(
                        '%s - %s',
                        $store->getWebsite()->getName(),
                        $store->getName()
                    ),
                ];
            }

            // Sort by label
            usort($this->options, function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
        }

        return $this->options;
    }
}
