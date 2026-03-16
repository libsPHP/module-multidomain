# Specifications: NativeMind_MultiDomain

## Version
v0.1 - Draft

## Overview

Magento 2 модуль для маппинга доменов на Store Views через Magento Plugin system с хранением конфигурации в PHP файле.

---

## Module Structure

```
app/code/NativeMind/MultiDomain/
├── registration.php
├── composer.json
├── etc/
│   ├── module.xml
│   ├── di.xml
│   ├── acl.xml
│   ├── adminhtml/
│   │   ├── menu.xml
│   │   ├── routes.xml
│   │   └── system.xml
│   └── config.xml
├── Plugin/
│   └── StoreResolverPlugin.php
├── Model/
│   ├── DomainConfig.php
│   ├── DomainConfigReader.php
│   └── DomainConfigWriter.php
├── Api/
│   └── DomainConfigInterface.php
├── Controller/
│   └── Adminhtml/
│       └── Domain/
│           ├── Index.php
│           ├── NewAction.php
│           ├── Edit.php
│           ├── Save.php
│           └── Delete.php
├── Block/
│   └── Adminhtml/
│       └── Domain/
│           └── Edit/
│               └── BackButton.php
│               └── DeleteButton.php
│               └── SaveButton.php
├── Ui/
│   └── Component/
│       └── Listing/
│           └── Column/
│               └── Actions.php
├── view/
│   └── adminhtml/
│       ├── layout/
│       │   ├── nativemind_multidomain_domain_index.xml
│       │   └── nativemind_multidomain_domain_edit.xml
│       └── ui_component/
│           ├── nativemind_domain_listing.xml
│           └── nativemind_domain_form.xml
└── i18n/
    └── en_US.csv
```

---

## Configuration File Format

### Location
`app/etc/nativemind_multidomain.php`

### Format
```php
<?php
/**
 * NativeMind MultiDomain Configuration
 * Auto-generated. Do not edit manually.
 * Last updated: 2026-03-16 10:30:00
 */
return [
    'domains' => [
        'example.com' => 'default',
        'ru.example.com' => 'view_ru',
        'de.example.com' => 'view_de',
    ],
    'default_store' => 'default',
    'debug_mode' => false,
];
```

### Fallback
Если файл не существует или пустой — используется стандартное поведение Magento (определение store по URL или default).

---

## Components Specification

### 1. Plugin: StoreResolverPlugin

**File:** `Plugin/StoreResolverPlugin.php`

**Target:** `Magento\Store\Model\StoreResolver::getCurrentStoreId`

**Type:** Around plugin

```php
<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Plugin;

use Magento\Store\Model\StoreResolver;
use Magento\Store\Api\StoreRepositoryInterface;
use NativeMind\MultiDomain\Model\DomainConfigReader;
use Psr\Log\LoggerInterface;

class StoreResolverPlugin
{
    private DomainConfigReader $configReader;
    private StoreRepositoryInterface $storeRepository;
    private LoggerInterface $logger;

    public function __construct(
        DomainConfigReader $configReader,
        StoreRepositoryInterface $storeRepository,
        LoggerInterface $logger
    ) {
        $this->configReader = $configReader;
        $this->storeRepository = $storeRepository;
        $this->logger = $logger;
    }

    public function aroundGetCurrentStoreId(
        StoreResolver $subject,
        callable $proceed
    ): string {
        $host = $this->getHost();
        $storeCode = $this->configReader->getStoreCodeByDomain($host);

        if ($storeCode !== null) {
            try {
                $store = $this->storeRepository->get($storeCode);
                if ($store->isActive()) {
                    return (string) $store->getId();
                }
            } catch (\Exception $e) {
                $this->logger->warning(
                    "MultiDomain: Store '{$storeCode}' for domain '{$host}' not found",
                    ['exception' => $e]
                );
            }
        }

        return $proceed();
    }

    private function getHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // Remove port if present
        return strtolower(explode(':', $host)[0]);
    }
}
```

**Behavior:**
1. Получает текущий HTTP_HOST
2. Ищет в конфигурации соответствующий store code
3. Если найден и store активен — возвращает его ID
4. Если не найден или ошибка — передаёт управление оригинальному методу

---

### 2. Model: DomainConfigReader

**File:** `Model/DomainConfigReader.php`

```php
<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Model;

use Magento\Framework\App\Filesystem\DirectoryList;

class DomainConfigReader
{
    private const CONFIG_FILE = 'nativemind_multidomain.php';

    private ?array $config = null;
    private DirectoryList $directoryList;

    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    public function getConfig(): array
    {
        if ($this->config === null) {
            $this->config = $this->loadConfig();
        }
        return $this->config;
    }

    public function getStoreCodeByDomain(string $domain): ?string
    {
        $config = $this->getConfig();
        $domain = strtolower($domain);

        return $config['domains'][$domain] ?? null;
    }

    public function getDefaultStore(): string
    {
        $config = $this->getConfig();
        return $config['default_store'] ?? 'default';
    }

    public function getDomains(): array
    {
        $config = $this->getConfig();
        return $config['domains'] ?? [];
    }

    public function isDebugMode(): bool
    {
        $config = $this->getConfig();
        return (bool) ($config['debug_mode'] ?? false);
    }

    public function invalidateCache(): void
    {
        $this->config = null;
    }

    private function loadConfig(): array
    {
        $configPath = $this->getConfigPath();

        if (!file_exists($configPath)) {
            return $this->getDefaultConfig();
        }

        try {
            $config = include $configPath;
            return is_array($config) ? $config : $this->getDefaultConfig();
        } catch (\Throwable $e) {
            return $this->getDefaultConfig();
        }
    }

    private function getConfigPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::CONFIG)
            . '/' . self::CONFIG_FILE;
    }

    private function getDefaultConfig(): array
    {
        return [
            'domains' => [],
            'default_store' => 'default',
            'debug_mode' => false,
        ];
    }
}
```

---

### 3. Model: DomainConfigWriter

**File:** `Model/DomainConfigWriter.php`

```php
<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

class DomainConfigWriter
{
    private const CONFIG_FILE = 'nativemind_multidomain.php';

    private DirectoryList $directoryList;
    private FileDriver $fileDriver;
    private DomainConfigReader $configReader;

    public function __construct(
        DirectoryList $directoryList,
        FileDriver $fileDriver,
        DomainConfigReader $configReader
    ) {
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->configReader = $configReader;
    }

    /**
     * Add or update domain mapping
     */
    public function setDomain(string $domain, string $storeCode): void
    {
        $config = $this->configReader->getConfig();
        $config['domains'][strtolower($domain)] = $storeCode;
        $this->writeConfig($config);
    }

    /**
     * Remove domain mapping
     */
    public function removeDomain(string $domain): void
    {
        $config = $this->configReader->getConfig();
        unset($config['domains'][strtolower($domain)]);
        $this->writeConfig($config);
    }

    /**
     * Set default store
     */
    public function setDefaultStore(string $storeCode): void
    {
        $config = $this->configReader->getConfig();
        $config['default_store'] = $storeCode;
        $this->writeConfig($config);
    }

    /**
     * Set debug mode
     */
    public function setDebugMode(bool $enabled): void
    {
        $config = $this->configReader->getConfig();
        $config['debug_mode'] = $enabled;
        $this->writeConfig($config);
    }

    /**
     * Write full config
     */
    public function writeConfig(array $config): void
    {
        $configPath = $this->getConfigPath();
        $content = $this->generatePhpContent($config);

        $this->fileDriver->filePutContents($configPath, $content);
        $this->configReader->invalidateCache();
    }

    private function getConfigPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::CONFIG)
            . '/' . self::CONFIG_FILE;
    }

    private function generatePhpContent(array $config): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $export = var_export($config, true);

        return <<<PHP
<?php
/**
 * NativeMind MultiDomain Configuration
 * Auto-generated. Do not edit manually.
 * Last updated: {$timestamp}
 */
return {$export};
PHP;
    }
}
```

---

### 4. Model: DomainConfig (Data Model)

**File:** `Model/DomainConfig.php`

```php
<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Model;

use Magento\Framework\DataObject;

class DomainConfig extends DataObject
{
    public function getDomain(): string
    {
        return (string) $this->getData('domain');
    }

    public function setDomain(string $domain): self
    {
        return $this->setData('domain', strtolower($domain));
    }

    public function getStoreCode(): string
    {
        return (string) $this->getData('store_code');
    }

    public function setStoreCode(string $storeCode): self
    {
        return $this->setData('store_code', $storeCode);
    }
}
```

---

### 5. Admin Controllers

#### Index Controller (Grid)
**File:** `Controller/Adminhtml/Domain/Index.php`

```php
<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Controller\Adminhtml\Domain;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

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

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('NativeMind_MultiDomain::domain');
        $resultPage->getConfig()->getTitle()->prepend(__('Domain Mappings'));
        return $resultPage;
    }
}
```

#### Save Controller
**File:** `Controller/Adminhtml/Domain/Save.php`

```php
<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Controller\Adminhtml\Domain;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use NativeMind\MultiDomain\Model\DomainConfigWriter;
use NativeMind\MultiDomain\Model\DomainValidator;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'NativeMind_MultiDomain::domain_save';

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

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $redirect = $this->redirectFactory->create();

        try {
            $domain = $this->validateDomain($data['domain'] ?? '');
            $storeCode = $data['store_code'] ?? '';

            if (empty($storeCode)) {
                throw new \InvalidArgumentException('Store code is required');
            }

            $this->configWriter->setDomain($domain, $storeCode);
            $this->messageManager->addSuccessMessage(__('Domain mapping saved.'));

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['domain' => $data['original_domain'] ?? '']);
        }

        return $redirect->setPath('*/*/index');
    }

    private function validateDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));

        if (empty($domain)) {
            throw new \InvalidArgumentException('Domain is required');
        }

        // Basic domain validation
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/', $domain)) {
            throw new \InvalidArgumentException('Invalid domain format');
        }

        return $domain;
    }
}
```

#### Delete Controller
**File:** `Controller/Adminhtml/Domain/Delete.php`

```php
<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Controller\Adminhtml\Domain;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use NativeMind\MultiDomain\Model\DomainConfigWriter;

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

    public function execute()
    {
        $domain = $this->getRequest()->getParam('domain');
        $redirect = $this->redirectFactory->create();

        try {
            if (empty($domain)) {
                throw new \InvalidArgumentException('Domain is required');
            }

            $this->configWriter->removeDomain($domain);
            $this->messageManager->addSuccessMessage(__('Domain mapping deleted.'));

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $redirect->setPath('*/*/index');
    }
}
```

---

### 6. Configuration Files

#### etc/module.xml
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="NativeMind_MultiDomain" setup_version="1.0.0">
        <sequence>
            <module name="Magento_Store"/>
            <module name="Magento_Backend"/>
        </sequence>
    </module>
</config>
```

#### etc/di.xml
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/di.xsd">

    <type name="Magento\Store\Model\StoreResolver">
        <plugin name="nativemind_multidomain_store_resolver"
                type="NativeMind\MultiDomain\Plugin\StoreResolverPlugin"
                sortOrder="10"/>
    </type>

</config>
```

#### etc/acl.xml
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
    <acl>
        <resources>
            <resource id="Magento_Backend::admin">
                <resource id="Magento_Backend::stores">
                    <resource id="NativeMind_MultiDomain::domain"
                              title="MultiDomain Mappings"
                              sortOrder="100">
                        <resource id="NativeMind_MultiDomain::domain_save"
                                  title="Save Domain Mapping"/>
                        <resource id="NativeMind_MultiDomain::domain_delete"
                                  title="Delete Domain Mapping"/>
                    </resource>
                </resource>
            </resource>
        </resources>
    </acl>
</config>
```

#### etc/adminhtml/menu.xml
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Backend:etc/menu.xsd">
    <menu>
        <add id="NativeMind_MultiDomain::domain"
             title="MultiDomain Mappings"
             module="NativeMind_MultiDomain"
             sortOrder="100"
             parent="Magento_Backend::stores"
             action="nativemind_multidomain/domain/index"
             resource="NativeMind_MultiDomain::domain"/>
    </menu>
</config>
```

#### etc/adminhtml/routes.xml
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
    <router id="admin">
        <route id="nativemind_multidomain" frontName="nativemind_multidomain">
            <module name="NativeMind_MultiDomain" before="Magento_Backend"/>
        </route>
    </router>
</config>
```

---

### 7. UI Components

#### Grid: nativemind_domain_listing.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">

    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">nativemind_domain_listing.nativemind_domain_listing_data_source</item>
        </item>
    </argument>

    <settings>
        <buttons>
            <button name="add">
                <url path="*/*/edit"/>
                <class>primary</class>
                <label translate="true">Add New Domain</label>
            </button>
        </buttons>
        <spinner>nativemind_domain_columns</spinner>
        <deps>
            <dep>nativemind_domain_listing.nativemind_domain_listing_data_source</dep>
        </deps>
    </settings>

    <dataSource name="nativemind_domain_listing_data_source" component="Magento_Ui/js/grid/provider">
        <settings>
            <storageConfig>
                <param name="dataScope" xsi:type="string">filters.store_id</param>
            </storageConfig>
            <updateUrl path="mui/index/render"/>
        </settings>
        <dataProvider class="NativeMind\MultiDomain\Ui\DataProvider\DomainDataProvider"
                      name="nativemind_domain_listing_data_source">
            <settings>
                <requestFieldName>domain</requestFieldName>
                <primaryFieldName>domain</primaryFieldName>
            </settings>
        </dataProvider>
    </dataSource>

    <listingToolbar name="listing_top">
        <settings>
            <sticky>true</sticky>
        </settings>
        <filters name="listing_filters"/>
        <paging name="listing_paging"/>
    </listingToolbar>

    <columns name="nativemind_domain_columns">
        <column name="domain">
            <settings>
                <filter>text</filter>
                <label translate="true">Domain</label>
            </settings>
        </column>
        <column name="store_code">
            <settings>
                <filter>text</filter>
                <label translate="true">Store View</label>
            </settings>
        </column>
        <actionsColumn name="actions"
                       class="NativeMind\MultiDomain\Ui\Component\Listing\Column\Actions">
            <settings>
                <indexField>domain</indexField>
            </settings>
        </actionsColumn>
    </columns>
</listing>
```

#### Form: nativemind_domain_form.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">

    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">nativemind_domain_form.nativemind_domain_form_data_source</item>
        </item>
        <item name="template" xsi:type="string">templates/form/collapsible</item>
    </argument>

    <settings>
        <buttons>
            <button name="back" class="NativeMind\MultiDomain\Block\Adminhtml\Domain\Edit\BackButton"/>
            <button name="delete" class="NativeMind\MultiDomain\Block\Adminhtml\Domain\Edit\DeleteButton"/>
            <button name="save" class="NativeMind\MultiDomain\Block\Adminhtml\Domain\Edit\SaveButton"/>
        </buttons>
        <namespace>nativemind_domain_form</namespace>
        <dataScope>data</dataScope>
        <deps>
            <dep>nativemind_domain_form.nativemind_domain_form_data_source</dep>
        </deps>
    </settings>

    <dataSource name="nativemind_domain_form_data_source">
        <argument name="data" xsi:type="array">
            <item name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">Magento_Ui/js/form/provider</item>
            </item>
        </argument>
        <dataProvider class="NativeMind\MultiDomain\Ui\DataProvider\DomainFormDataProvider"
                      name="nativemind_domain_form_data_source">
            <settings>
                <requestFieldName>domain</requestFieldName>
                <primaryFieldName>domain</primaryFieldName>
            </settings>
        </dataProvider>
    </dataSource>

    <fieldset name="general">
        <settings>
            <label translate="true">Domain Mapping</label>
        </settings>

        <field name="domain" formElement="input">
            <settings>
                <dataType>text</dataType>
                <label translate="true">Domain</label>
                <validation>
                    <rule name="required-entry" xsi:type="boolean">true</rule>
                </validation>
            </settings>
        </field>

        <field name="store_code" formElement="select">
            <settings>
                <dataType>text</dataType>
                <label translate="true">Store View</label>
                <validation>
                    <rule name="required-entry" xsi:type="boolean">true</rule>
                </validation>
            </settings>
            <formElements>
                <select>
                    <settings>
                        <options class="NativeMind\MultiDomain\Ui\Source\StoreViewOptions"/>
                    </settings>
                </select>
            </formElements>
        </field>

        <field name="original_domain" formElement="hidden">
            <settings>
                <dataType>text</dataType>
            </settings>
        </field>
    </fieldset>
</form>
```

---

### 8. Data Providers

#### DomainDataProvider (Grid)

```php
<?php
declare(strict_types=1);

namespace NativeMind\MultiDomain\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use NativeMind\MultiDomain\Model\DomainConfigReader;
use Magento\Framework\Api\FilterBuilder;

class DomainDataProvider extends AbstractDataProvider
{
    private DomainConfigReader $configReader;
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        DomainConfigReader $configReader,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->configReader = $configReader;
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $domains = $this->configReader->getDomains();
        $items = [];

        foreach ($domains as $domain => $storeCode) {
            $items[] = [
                'domain' => $domain,
                'store_code' => $storeCode,
            ];
        }

        $this->loadedData = [
            'totalRecords' => count($items),
            'items' => $items,
        ];

        return $this->loadedData;
    }
}
```

---

## Edge Cases & Error Handling

### EC-1: Несуществующий Store Code
- Plugin проверяет существование store через StoreRepository
- Если store не найден — логируется warning и используется fallback

### EC-2: Конфигурационный файл повреждён/отсутствует
- DomainConfigReader возвращает default конфиг
- Сайт продолжает работать со стандартным Magento поведением

### EC-3: Дублирующийся домен
- При сохранении домен перезаписывается (upsert логика)
- Старая привязка заменяется новой

### EC-4: Невалидный формат домена
- Валидация в Save controller
- Regex проверка формата домена
- Показывается сообщение об ошибке

### EC-5: Нет прав на запись в app/etc/
- FileSystemException при попытке записи
- Показывается сообщение администратору

### EC-6: Store View деактивирован
- Plugin проверяет `$store->isActive()`
- Неактивные store views пропускаются

---

## Security Considerations

1. **ACL:** Отдельные permissions для view/save/delete
2. **Domain Validation:** Regex проверка, lowercase, trim
3. **XSS:** Использование Magento escaping в UI
4. **CSRF:** Стандартная Magento form_key protection
5. **File Permissions:** app/etc/ должен быть writable для web server

---

## Performance Considerations

1. **Config Caching:** DomainConfigReader кэширует конфиг в памяти
2. **No DB Queries:** Весь runtime без обращений к БД
3. **Plugin SortOrder:** 10 — выполняется рано, до других plugins
4. **File Include:** Нативный PHP include — максимальная скорость

---

## Testing Strategy

### Unit Tests
- DomainConfigReader: чтение конфига, fallback
- DomainConfigWriter: запись, форматирование
- StoreResolverPlugin: определение store по домену

### Integration Tests
- Полный цикл: добавление домена через админку → резолвинг store

### Manual Tests
- Добавление/редактирование/удаление доменов
- Проверка резолвинга с разных доменов
- Проверка fallback при ошибках

---

## Dependencies

- `Magento_Store` — StoreResolver, StoreRepository
- `Magento_Backend` — Admin controllers, ACL
- `Magento_Ui` — Grid/Form components

---

## Migration from Current Solution

1. Установить модуль
2. Перенести маппинги из `my_domains_views.php` в админку
3. Удалить модификации из `index.php`
4. Удалить `my_domains.php`, `my_domains_views.php`
