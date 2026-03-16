<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\UrlInterface;

/**
 * Actions column for domain grid
 */
class Actions extends Column
{
    private const URL_PATH_EDIT = 'nativemind_multidomain/domain/edit';
    private const URL_PATH_DELETE = 'nativemind_multidomain/domain/delete';

    private UrlInterface $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare data source with actions
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['domain'])) {
                    $domain = $item['domain'];

                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                self::URL_PATH_EDIT,
                                ['domain' => $domain]
                            ),
                            'label' => __('Edit'),
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                self::URL_PATH_DELETE,
                                ['domain' => $domain]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete Domain'),
                                'message' => __('Are you sure you want to delete the mapping for "%1"?', $domain),
                            ],
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
