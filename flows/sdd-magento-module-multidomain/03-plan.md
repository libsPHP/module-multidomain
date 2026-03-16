# Implementation Plan: NativeMind_MultiDomain

## Version
v0.1 - Draft

## Overview

Пошаговый план создания Magento 2 модуля для маппинга доменов на Store Views.

**Estimated Complexity:** Medium
**Total Tasks:** 12
**Dependencies:** Magento 2.4.x, PHP 8.1+

---

## Task Breakdown

### Phase 1: Module Foundation

#### Task 1.1: Create module structure
**Complexity:** Low
**Files to create:**
- `app/code/NativeMind/MultiDomain/registration.php`
- `app/code/NativeMind/MultiDomain/composer.json`
- `app/code/NativeMind/MultiDomain/etc/module.xml`

**Acceptance:** `bin/magento module:status` показывает NativeMind_MultiDomain

---

#### Task 1.2: Create DI configuration
**Complexity:** Low
**Files to create:**
- `app/code/NativeMind/MultiDomain/etc/di.xml`

**Content:** Plugin registration для StoreResolver

**Acceptance:** Файл валиден (no XML errors при setup:upgrade)

---

### Phase 2: Core Logic

#### Task 2.1: Create DomainConfigReader
**Complexity:** Medium
**Files to create:**
- `app/code/NativeMind/MultiDomain/Model/DomainConfigReader.php`

**Methods:**
- `getConfig(): array`
- `getStoreCodeByDomain(string $domain): ?string`
- `getDomains(): array`
- `getDefaultStore(): string`
- `invalidateCache(): void`

**Acceptance:** Класс читает конфиг из `app/etc/nativemind_multidomain.php`

---

#### Task 2.2: Create DomainConfigWriter
**Complexity:** Medium
**Files to create:**
- `app/code/NativeMind/MultiDomain/Model/DomainConfigWriter.php`

**Methods:**
- `setDomain(string $domain, string $storeCode): void`
- `removeDomain(string $domain): void`
- `writeConfig(array $config): void`

**Acceptance:** Создаёт/обновляет PHP файл с конфигурацией

---

#### Task 2.3: Create StoreResolverPlugin
**Complexity:** Medium
**Files to create:**
- `app/code/NativeMind/MultiDomain/Plugin/StoreResolverPlugin.php`

**Logic:**
1. Получить HTTP_HOST
2. Найти store code в конфиге
3. Вернуть store ID или proceed()

**Acceptance:** При запросе с настроенного домена возвращается правильный store

---

### Phase 3: Admin ACL & Menu

#### Task 3.1: Create ACL configuration
**Complexity:** Low
**Files to create:**
- `app/code/NativeMind/MultiDomain/etc/acl.xml`

**Resources:**
- `NativeMind_MultiDomain::domain` (view)
- `NativeMind_MultiDomain::domain_save`
- `NativeMind_MultiDomain::domain_delete`

**Acceptance:** ACL ресурсы видны в System > Permissions > User Roles

---

#### Task 3.2: Create admin menu
**Complexity:** Low
**Files to create:**
- `app/code/NativeMind/MultiDomain/etc/adminhtml/menu.xml`
- `app/code/NativeMind/MultiDomain/etc/adminhtml/routes.xml`

**Acceptance:** Пункт меню появляется в Stores > MultiDomain Mappings

---

### Phase 4: Admin Controllers

#### Task 4.1: Create Index controller (Grid)
**Complexity:** Low
**Files to create:**
- `app/code/NativeMind/MultiDomain/Controller/Adminhtml/Domain/Index.php`

**Acceptance:** URL `/admin/nativemind_multidomain/domain/index` открывается

---

#### Task 4.2: Create Edit controller (Form)
**Complexity:** Low
**Files to create:**
- `app/code/NativeMind/MultiDomain/Controller/Adminhtml/Domain/Edit.php`
- `app/code/NativeMind/MultiDomain/Controller/Adminhtml/Domain/NewAction.php`

**Acceptance:** Форма открывается для нового и существующего домена

---

#### Task 4.3: Create Save controller
**Complexity:** Medium
**Files to create:**
- `app/code/NativeMind/MultiDomain/Controller/Adminhtml/Domain/Save.php`

**Logic:**
1. Валидация домена (regex)
2. Валидация store code
3. Вызов DomainConfigWriter
4. Redirect с success/error message

**Acceptance:** Домен сохраняется в конфиг файл

---

#### Task 4.4: Create Delete controller
**Complexity:** Low
**Files to create:**
- `app/code/NativeMind/MultiDomain/Controller/Adminhtml/Domain/Delete.php`

**Acceptance:** Домен удаляется из конфига

---

### Phase 5: Admin UI Components

#### Task 5.1: Create Grid UI Component
**Complexity:** Medium
**Files to create:**
- `app/code/NativeMind/MultiDomain/view/adminhtml/ui_component/nativemind_domain_listing.xml`
- `app/code/NativeMind/MultiDomain/view/adminhtml/layout/nativemind_multidomain_domain_index.xml`
- `app/code/NativeMind/MultiDomain/Ui/DataProvider/DomainDataProvider.php`
- `app/code/NativeMind/MultiDomain/Ui/Component/Listing/Column/Actions.php`

**Columns:** Domain, Store View, Actions

**Acceptance:** Grid отображает список доменов из конфига

---

#### Task 5.2: Create Form UI Component
**Complexity:** Medium
**Files to create:**
- `app/code/NativeMind/MultiDomain/view/adminhtml/ui_component/nativemind_domain_form.xml`
- `app/code/NativeMind/MultiDomain/view/adminhtml/layout/nativemind_multidomain_domain_edit.xml`
- `app/code/NativeMind/MultiDomain/Ui/DataProvider/DomainFormDataProvider.php`
- `app/code/NativeMind/MultiDomain/Ui/Source/StoreViewOptions.php`
- `app/code/NativeMind/MultiDomain/Block/Adminhtml/Domain/Edit/BackButton.php`
- `app/code/NativeMind/MultiDomain/Block/Adminhtml/Domain/Edit/SaveButton.php`
- `app/code/NativeMind/MultiDomain/Block/Adminhtml/Domain/Edit/DeleteButton.php`

**Fields:** Domain (input), Store View (select)

**Acceptance:** Форма работает для add/edit

---

## Execution Order

```
1.1 Module structure
 ↓
1.2 DI configuration
 ↓
2.1 DomainConfigReader ─────┐
 ↓                          │
2.2 DomainConfigWriter ─────┤
 ↓                          │
2.3 StoreResolverPlugin ←───┘
 ↓
3.1 ACL ────────┐
 ↓              │
3.2 Admin menu ←┘
 ↓
4.1 Index controller
 ↓
4.2 Edit controller
 ↓
4.3 Save controller ←── depends on 2.2
 ↓
4.4 Delete controller ←── depends on 2.2
 ↓
5.1 Grid UI ←── depends on 2.1
 ↓
5.2 Form UI
 ↓
✓ Complete
```

---

## File Summary

| Category | Files | Count |
|----------|-------|-------|
| Module base | registration.php, composer.json, etc/*.xml | 5 |
| Models | DomainConfigReader, DomainConfigWriter | 2 |
| Plugin | StoreResolverPlugin | 1 |
| Controllers | Index, Edit, NewAction, Save, Delete | 5 |
| UI DataProviders | DomainDataProvider, DomainFormDataProvider | 2 |
| UI Sources | StoreViewOptions | 1 |
| UI Blocks | BackButton, SaveButton, DeleteButton | 3 |
| UI Actions | Actions column | 1 |
| Layouts | index.xml, edit.xml | 2 |
| UI Components | listing.xml, form.xml | 2 |
| **Total** | | **24** |

---

## Rollback Plan

1. `bin/magento module:disable NativeMind_MultiDomain`
2. `bin/magento setup:upgrade`
3. Remove `app/code/NativeMind/MultiDomain/`
4. Remove `app/etc/nativemind_multidomain.php`
5. Restore original `index.php` if needed (не требуется при plugin подходе)

---

## Post-Implementation

1. **Миграция данных:** Перенести домены из `my_domains_views.php` через админку
2. **Cleanup:** Удалить старые файлы (`my_domains.php`, `my_domains_views.php`)
3. **Testing:** Проверить все домены с разных браузеров/режимов
4. **Documentation:** README для модуля

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Plugin не срабатывает | Low | High | Проверить sortOrder, debug logging |
| Права на app/etc/ | Medium | Medium | Документация, fallback на var/ |
| Cache issues | Medium | Low | Invalidate после записи конфига |
| Store не найден | Low | Low | Graceful fallback, logging |
