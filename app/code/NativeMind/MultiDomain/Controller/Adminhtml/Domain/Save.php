<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Controller\Adminhtml\Domain;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use NativeMind\MultiDomain\Model\DomainConfigWriter;
use Magento\Framework\Exception\LocalizedException;

/**
 * Save domain mapping controller
 */
class Save extends Action
{
    public const ADMIN_RESOURCE = 'NativeMind_MultiDomain::domain_save';

    private DomainConfigWriter $configWriter;
    private RedirectFactory $redirectFactory;
    private StoreRepositoryInterface $storeRepository;

    public function __construct(
        Context $context,
        DomainConfigWriter $configWriter,
        RedirectFactory $redirectFactory,
        StoreRepositoryInterface $storeRepository
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->redirectFactory = $redirectFactory;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Execute save action
     */
    public function execute(): Redirect
    {
        $redirect = $this->redirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (empty($data)) {
            $this->messageManager->addErrorMessage(__('No data to save.'));
            return $redirect->setPath('*/*/index');
        }

        try {
            $domain = $this->validateDomain($data['domain'] ?? '');
            $storeCode = $this->validateStoreCode($data['store_code'] ?? '');
            $originalDomain = $data['original_domain'] ?? '';

            // If editing and domain changed, remove old mapping
            if ($originalDomain && $originalDomain !== $domain) {
                $this->configWriter->removeDomain($originalDomain);
            }

            $this->configWriter->setDomain($domain, $storeCode);
            $this->messageManager->addSuccessMessage(__('Domain mapping has been saved.'));

            // Check if "Save and Continue"
            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['domain' => $domain]);
            }

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['domain' => $data['original_domain'] ?? '']);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while saving: %1', $e->getMessage()));
            return $redirect->setPath('*/*/edit', ['domain' => $data['original_domain'] ?? '']);
        }

        return $redirect->setPath('*/*/index');
    }

    /**
     * Validate domain format
     *
     * @throws LocalizedException
     */
    private function validateDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));

        if (empty($domain)) {
            throw new LocalizedException(__('Domain is required.'));
        }

        // Basic domain validation regex
        $pattern = '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/';

        if (!preg_match($pattern, $domain)) {
            throw new LocalizedException(
                __('Invalid domain format. Use format like "example.com" or "sub.example.com".')
            );
        }

        if (strlen($domain) > 253) {
            throw new LocalizedException(__('Domain name is too long (max 253 characters).'));
        }

        return $domain;
    }

    /**
     * Validate store code exists
     *
     * @throws LocalizedException
     */
    private function validateStoreCode(string $storeCode): string
    {
        if (empty($storeCode)) {
            throw new LocalizedException(__('Store View is required.'));
        }

        try {
            $this->storeRepository->get($storeCode);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Store View "%1" does not exist.', $storeCode));
        }

        return $storeCode;
    }
}
