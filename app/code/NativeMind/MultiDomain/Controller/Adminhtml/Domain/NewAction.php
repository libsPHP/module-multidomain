<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Controller\Adminhtml\Domain;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;

/**
 * New domain action - forwards to Edit
 */
class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'NativeMind_MultiDomain::domain_save';

    private ForwardFactory $resultForwardFactory;

    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Forward to edit action
     */
    public function execute(): Forward
    {
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
