<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Block\Adminhtml\Domain\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Save button for domain form
 */
class SaveButton implements ButtonProviderInterface
{
    /**
     * Get button data
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'save'],
                ],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
