<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Controller\Adminhtml\Domain;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use NativeMind\MultiDomain\Model\DomainConfigWriter;

/**
 * Delete domain mapping controller
 */
class Delete extends Action
{
    public const ADMIN_RESOURCE = 'NativeMind_MultiDomain::domain_delete';

    private DomainConfigWriter $configWriter;
    private RedirectFactory $redirectFactory;

    public function __construct(
        Context $context,
        DomainConfigWriter $configWriter,
        RedirectFactory $redirectFactory
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->redirectFactory = $redirectFactory;
    }

    /**
     * Execute delete action
     */
    public function execute(): Redirect
    {
        $redirect = $this->redirectFactory->create();
        $domain = $this->getRequest()->getParam('domain');

        if (empty($domain)) {
            $this->messageManager->addErrorMessage(__('Domain parameter is missing.'));
            return $redirect->setPath('*/*/index');
        }

        try {
            $this->configWriter->removeDomain($domain);
            $this->messageManager->addSuccessMessage(__('Domain mapping "%1" has been deleted.', $domain));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error deleting domain: %1', $e->getMessage()));
        }

        return $redirect->setPath('*/*/index');
    }
}
