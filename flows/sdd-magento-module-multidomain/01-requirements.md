# Requirements: NativeMind_MultiDomain Magento 2 Module

## Version
v0.2 - Decisions Made

## Problem Statement

Необходимо создать полноценный Magento 2 модуль, который позволит привязывать разные домены (включая поддомены) к различным Store Views. Это позволяет:
- Создавать языковые версии сайта на отдельных доменах (ru.site.com, en.site.com)
- Использовать разные домены для разных регионов/рынков
- Управлять привязками из админки Magento без редактирования кода

### Текущее решение (анализ существующего кода)

Сейчас реализовано через прямую модификацию `index.php`:
1. `index.php` → подключает `my_domains.php`
2. `my_domains.php` → содержит логику определения store code по домену
3. `my_domains_views.php` → содержит массив `$domains2view` с маппингом домен→view

**Преимущества текущего подхода:**
- Быстрая загрузка (PHP array, без БД)
- Fallback на default view при ошибке

**Недостатки:**
- Требуется ручное редактирование PHP файла
- Нет интерфейса в админке
- Сложно масштабировать

---

## User Stories

### US-1: Администратор добавляет новый домен
**As a** Magento administrator
**I want to** добавить привязку домена к Store View через админку
**So that** посетители этого домена видели нужную версию сайта

**Acceptance Criteria:**
- [ ] В админке есть раздел для управления доменами (Stores > NativeMind MultiDomain)
- [ ] Можно добавить новый домен и выбрать Store View из списка
- [ ] После сохранения привязка сразу работает (без очистки кэша)
- [ ] Конфигурация сохраняется в PHP файл (не в БД)

### US-2: Администратор редактирует привязку домена
**As a** Magento administrator
**I want to** изменить Store View для существующего домена
**So that** перенаправить трафик домена на другой view

**Acceptance Criteria:**
- [ ] В списке доменов есть кнопка Edit
- [ ] Можно изменить Store View и сохранить
- [ ] Изменения применяются мгновенно

### US-3: Администратор удаляет привязку домена
**As a** Magento administrator
**I want to** удалить привязку домена
**So that** домен больше не обрабатывался специально (fallback на default)

**Acceptance Criteria:**
- [ ] В списке доменов есть кнопка Delete
- [ ] Требуется подтверждение удаления
- [ ] После удаления домен использует default view

### US-4: Установка backup/default view
**As a** Magento administrator
**I want to** указать default Store View для неизвестных доменов
**So that** сайт работал даже для неконфигурированных доменов

**Acceptance Criteria:**
- [ ] В настройках модуля есть поле "Default Store View"
- [ ] Если домен не найден в маппинге, используется default

### US-5: Быстрая загрузка конфигурации
**As a** system
**I want to** загружать конфигурацию из PHP файла (не из БД)
**So that** не создавать дополнительную нагрузку на БД при каждом запросе

**Acceptance Criteria:**
- [ ] Конфигурация хранится в generated PHP файле
- [ ] Файл содержит PHP array с маппингом домен→view
- [ ] Загрузка происходит через include (максимально быстро)

---

## Functional Requirements

### FR-1: Admin Grid для доменов
- Таблица с колонками: Domain, Store View, Actions
- Сортировка и фильтрация
- Пагинация при большом количестве записей

### FR-2: Add/Edit Form
- Поле Domain (text input с валидацией)
- Dropdown Store View (список всех доступных views)
- Кнопки Save и Cancel

### FR-3: Хранение конфигурации
- PHP файл в директории модуля или var/
- Формат: ассоциативный массив `['domain' => 'store_code']`
- Автоматическая генерация при сохранении из админки

### FR-4: Runtime логика (Magento Plugin)
- Plugin на `Magento\Store\Model\StoreResolver` для определения store по домену
- Без модификации index.php — чистая Magento архитектура
- Загрузка конфигурации из PHP файла при первом обращении

---

## Non-Functional Requirements

### NFR-1: Производительность
- Определение store по домену < 1ms (include PHP array)
- Без запросов к БД на каждый page load

### NFR-2: Совместимость
- Magento 2.4.x
- PHP 8.1+
- Работа с Varnish/Full Page Cache

### NFR-3: Безопасность
- Валидация домена (формат, допустимые символы)
- Только администраторы с правами могут управлять доменами
- Защита от XSS при отображении доменов

---

## Constraints

1. **Название вендора:** NativeMind
2. **Название модуля:** MultiDomain
3. **Хранение:** PHP файл `app/etc/nativemind_multidomain.php` (не БД)
4. **Интеграция:** Magento Plugin system (без модификации index.php)

---

## Non-Goals (Out of Scope)

1. URL rewrites для доменов
2. Редиректы между доменами
3. SSL сертификаты управление
4. DNS настройки
5. Поддержка wildcard доменов (*.domain.com)
6. Поддержка путей в URL (host/folder) — использовать нативный Magento

---

## Decisions Made

### D-1: Хранение конфигурации
**Решение:** `app/etc/nativemind_multidomain.php`

**Обоснование:**
- Стандартное место для runtime конфигов Magento (рядом с env.php, config.php)
- Не удаляется при деплое
- Может контролироваться через git или исключаться

### D-2: Интеграция с Magento
**Решение:** Magento Plugin на StoreResolver

**Обоснование:**
- Чистая Magento архитектура без модификации core файлов
- Работает через стандартный механизм plugins
- index.php остаётся нетронутым

**Точка интеграции:** Plugin на `Magento\Store\Model\StoreResolver::getCurrentStoreId()`

### D-3: Поддержка путей в URL (host/folder)
**Решение:** Out of scope

**Обоснование:**
- Magento нативно поддерживает store code в URL
- Настраивается через Stores > Configuration > Web > URL Options
- Модуль фокусируется только на domain → store view маппинге

---

## References

- Существующий код: `index.php`, `my_domains.php`, `my_domains_views.php`
- Magento Store Views: https://docs.magento.com/user-guide/stores/stores-all-stores.html
