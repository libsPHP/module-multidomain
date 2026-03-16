# NativeMind MultiDomain for Magento 2

Magento 2 module for mapping multiple domains to different Store Views. Manage domain-to-store mappings via Admin Panel without modifying code.

## Features

- Admin UI for CRUD operations on domain mappings (Stores > MultiDomain Mappings)
- Configuration stored in PHP file for maximum performance (no DB queries on page load)
- Magento Plugin architecture — no core file modifications
- Fallback to default Magento behavior for unconfigured domains
- ACL permissions for admin access control
- Debug mode with logging

## Requirements

- Magento 2.4.x
- PHP 8.1+

## Installation

### Manual Installation

1. Copy module to Magento:
   ```bash
   cp -r app/code/NativeMind /path/to/magento/app/code/
   ```

2. Enable and install:
   ```bash
   bin/magento module:enable NativeMind_MultiDomain
   bin/magento setup:upgrade
   bin/magento cache:clean
   ```

### Composer Installation

```bash
composer require nativemind/module-multidomain
bin/magento setup:upgrade
bin/magento cache:clean
```

## Configuration

### Step 1: Configure Base URLs in Magento (Required)

**Important:** This module only handles routing domains to Store Views. You must manually configure Base URLs for each Store View in Magento settings.

Go to **Stores > Configuration > General > Web** and for each Store View set:

- **Base URL:** `https://your-domain.com/`
- **Base Link URL:** `https://your-domain.com/`
- **Secure Base URL:** `https://your-domain.com/`
- **Secure Base Link URL:** `https://your-domain.com/`

The plugin does not modify default URL paths — it only resolves which Store View to load based on the incoming domain.

### Step 2: Add Domain Mappings

1. Go to **Stores > MultiDomain Mappings**
2. Click **Add New Domain**
3. Enter domain (e.g., `ru.example.com`) and select Store View
4. Save

Configuration is stored in `app/etc/nativemind_multidomain.php`:

```php
<?php
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

## How It Works

The module uses a Magento Plugin on `Magento\Store\Model\StoreResolver::getCurrentStoreId()`:

1. Plugin intercepts store resolution
2. Reads `HTTP_HOST` from request
3. Looks up domain in configuration
4. Returns corresponding Store ID or falls back to default Magento behavior

No `index.php` modification required.

## Web Server Configuration

Ensure your web server routes all domains to the same Magento installation.

### Nginx Example

```nginx
server {
    listen 80;
    server_name example.com ru.example.com de.example.com;

    root /var/www/magento/pub;

    # ... standard Magento nginx config ...
}
```

### Apache Example

```apache
<VirtualHost *:80>
    ServerName example.com
    ServerAlias ru.example.com de.example.com

    DocumentRoot /var/www/magento/pub

    # ... standard Magento apache config ...
</VirtualHost>
```

## Troubleshooting

### Domain not resolving to correct Store View

1. Check `app/etc/nativemind_multidomain.php` exists and contains your domain
2. Verify Store View code is correct and store is active
3. Enable debug mode and check `var/log/system.log`:
   ```php
   // In app/etc/nativemind_multidomain.php
   'debug_mode' => true,
   ```
4. Clear cache: `bin/magento cache:clean`

### Permission errors when saving

Ensure `app/etc/` directory is writable by web server:
```bash
chmod 775 app/etc/
chown www-data:www-data app/etc/
```

## Uninstallation

```bash
bin/magento module:disable NativeMind_MultiDomain
bin/magento setup:upgrade
rm -rf app/code/NativeMind/MultiDomain
rm -f app/etc/nativemind_multidomain.php
bin/magento cache:clean
```

## License

This module is licensed under **NativeMindNONC License**.

- **Free for non-commercial use**: Educational institutions, personal learning, non-commercial research
- **Commercial use requires license**: Contact copyright holder for commercial licensing

See [LICENSE](../../../../LICENSE) for full terms.

## Support

For issues and feature requests, please use the GitHub issue tracker.

---

Copyright 2010-2025 NativeMind. All rights reserved.
