<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Block\Adminhtml\Domain\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;

/**
 * Delete button for domain form
 */
class DeleteButton implements ButtonProviderInterface
{
    private Context $context;
    private RequestInterface $request;

    public function __construct(
        Context $context,
        RequestInterface $request
    ) {
        $this->context = $context;
        $this->request = $request;
    }

    /**
     * Get button data
     */
    public function getButtonData(): array
    {
        $domain = $this->request->getParam('domain');

        if (!$domain) {
            return [];
        }

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this domain mapping?'),
                $this->getDeleteUrl($domain)
            ),
            'sort_order' => 20,
        ];
    }

    /**
     * Get URL for delete button
     */
    private function getDeleteUrl(string $domain): string
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/delete', ['domain' => $domain]);
    }
}
