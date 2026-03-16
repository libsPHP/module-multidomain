<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Block\Adminhtml\Domain\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

/**
 * Back button for domain form
 */
class BackButton implements ButtonProviderInterface
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Get button data
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 10,
        ];
    }

    /**
     * Get URL for back button
     */
    private function getBackUrl(): string
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/index');
    }
}
