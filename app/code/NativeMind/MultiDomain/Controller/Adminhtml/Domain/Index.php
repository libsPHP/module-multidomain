<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Controller\Adminhtml\Domain;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;

/**
 * Domain list grid controller
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'NativeMind_MultiDomain::domain';

    private PageFactory $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute action
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('NativeMind_MultiDomain::domain');
        $resultPage->getConfig()->getTitle()->prepend(__('Domain Mappings'));

        return $resultPage;
    }
}
