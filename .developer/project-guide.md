# Developer Guide

This document contains development-specific configurations and troubleshooting tips for the AssetComposerBundle project.

## Composer Installation

If you encounter a `PHP Fatal error: Allowed memory size` during installation, use the following command to bypass memory limits:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

## PHPStan Configuration

To enable clickable file links in PHPStan's output within PhpStorm, ensure your `phpstan.neon` includes the following:

```neon
includes:
    - phpstan-global.neon

parameters:
    editorUrl: 'phpstorm://open?file=%relFile&line=%line'
    editorUrlTitle: '{{editorPath}}/%%relFile%%:%%line%%'
```

### Setup `{{editorPath}}`
1. Open your project in **PhpStorm**.
2. Right-click on the project root directory.
3. Select `Copy Path/Reference` > `Absolute Path`.
4. Replace `{{editorPath}}` in your local configuration with this path.
