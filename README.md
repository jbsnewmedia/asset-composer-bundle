# AssetComposerBundle

[![Packagist Version](https://img.shields.io/packagist/v/jbsnewmedia/asset-composer-bundle)](https://packagist.org/packages/jbsnewmedia/asset-composer-bundle)
[![Packagist Downloads](https://img.shields.io/packagist/dt/jbsnewmedia/asset-composer-bundle)](https://packagist.org/packages/jbsnewmedia/asset-composer-bundle)
[![PHP Version Require](https://img.shields.io/packagist/php-v/jbsnewmedia/asset-composer-bundle)](https://packagist.org/packages/jbsnewmedia/asset-composer-bundle)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E6.4%20%7C%20%5E7.0-673ab7?logo=symfony)](https://symfony.com)
[![License](https://img.shields.io/packagist/l/jbsnewmedia/asset-composer-bundle)](https://packagist.org/packages/jbsnewmedia/asset-composer-bundle)
[![Tests](https://github.com/jbsnewmedia/asset-composer-bundle/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/jbsnewmedia/asset-composer-bundle/actions/workflows/tests.yml)
[![PHP CS Fixer](https://img.shields.io/badge/php--cs--fixer-checked-brightgreen)](https://github.com/jbsnewmedia/asset-composer-bundle/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/phpstan-analysed-brightgreen)](https://github.com/jbsnewmedia/asset-composer-bundle/actions/workflows/tests.yml)
[![Rector](https://img.shields.io/badge/rector-checked-brightgreen)](https://github.com/jbsnewmedia/asset-composer-bundle/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/jbsnewmedia/asset-composer-bundle/branch/main/graph/badge.svg)](https://codecov.io/gh/jbsnewmedia/asset-composer-bundle)

**AssetComposerBundle** is a Symfony bundle that helps you manage and serve assets directly from the `vendor` directory. It ensures all files are kept up-to-date by leveraging file modification timestamps for cache busting.

## 🚀 Features

- Manage CSS/JS assets via Composer packages
- Automatic versioning using file timestamps
- Simple integration with Twig templates
- Supports production/dev-only assets via `assetcomposer.json`
- Symfony 6 & 7 compatible

---

## ⚙️ Requirements

- PHP 8.1 or higher
- Symfony Framework 6.4 or 7.0

---

## 📦 Installation

Use [Composer](https://getcomposer.org/) to install the bundle:

```bash
composer require jbsnewmedia/asset-composer-bundle
````

---

## 📋 Usage

### 1. Install Asset Packages via Composer

```bash
composer require twbs/bootstrap
composer require components/font-awesome
composer require avalynx/avalynx-alert
```

### 2. Register Assets in Twig Templates

#### Assets from Composer Packages

```twig
{% do addAssetComposer('twbs/bootstrap/dist/css/bootstrap.css') %}
{% do addAssetComposer('components/font-awesome/css/all.css') %}
{% do addAssetComposer('avalynx/avalynx-alert/dist/css/avalynx-alert.css') %}
{% do addAssetComposer('avalynx/avalynx-alert/dist/js/avalynx-alert.js') %}
```

#### Local Assets

You can also use local assets stored in your project's `assets/` directory. Use the namespace `app` and the package `assets`:

```twig
{% do addAssetComposer('app/assets/css/custom.css') %}
{% do addAssetComposer('app/assets/js/custom.js') %}
```

The files must be located locally at `%kernel.project_dir%/assets/css/custom.css` and `%kernel.project_dir%/assets/js/custom.js` respectively.

Example file structure:
```text
your-project/
├── assets/
│   ├── css/
│   │   └── custom.css
│   └── js/
│       └── custom.js
├── composer.json
└── ...
```

### 3. Render in Layout

```twig
<!DOCTYPE html>
<html>
<head>
    {% block stylesheets %}
        {{ renderAssetComposerStylesheets() }}
    {% endblock %}
</head>
<body>
    {% block body %}{% endblock %}
    
    {% block javascripts %}
        {{ renderAssetComposerJavascripts() }}
    {% endblock %}
    
    {{ renderAssetComposerJavascripts('bottom') }}
</body>
</html>
```

---

## 📁 File Structure

```
config/
├── routes.yaml
├── services.yaml
src/
├── Controller/
│   └── AssetComposerController.php
├── DependencyInjection/
│   ├── AssetComposerExtension.php
│   └── Configuration.php
├── Service/
│   └── AssetComposer.php
├── Twig/
│   └── AssetComposerExtension.php
├── AssetComposerBundle.php
```

---

## 🧰 Configuration (Optional)

You can create an `assetcomposer.json` file in your asset packages to define which files should be exposed:

```json
{
  "name": "library-name",
  "files": [
    "dist/css/styles.css",
    "dist/js/scripts.js"
  ],
  "files-dev": [
    "src/css/dev-styles.css",
    "src/js/dev-scripts.js"
  ]
}
```

---

## 🔧 Development Tools

To maintain code quality, this project uses:

* **PHP-CS-Fixer (ECS)**: `composer bin-ecs`
* **PHPStan**: `composer bin-phpstan`
* **Rector**: `composer bin-rector`

Install and update tools using:

```bash
composer bin-ecs-install
composer bin-phpstan-install
composer bin-rector-install
composer bin-phpunit-install
```

---

## 🧪 Testing & QA

All code adheres to modern PHP standards. Use the provided scripts to analyze and refactor the codebase:

```bash
composer bin-phpstan       # Static analysis
composer bin-ecs           # Coding standards (check)
composer bin-ecs-fix       # Coding standards (fix)
composer bin-rector        # Code transformation (dry-run)
composer bin-rector-process # Code transformation (apply)
composer test              # Run tests
composer test-coverage     # Run tests with coverage
```

---

## 📜 License

This bundle is licensed under the MIT license. See the [LICENSE](LICENSE) file for more details.

Developed by Jürgen Schwind and other contributors.

---

## 🤝 Contributing

Contributions are welcome! If you'd like to contribute, please contact us or fork the repository and submit a pull request with your changes or improvements.

---

## 📫 Contact

If you have any questions, feature requests, or issues, please open an issue on our [GitHub repository](https://github.com/jbsnewmedia/asset-composer-bundle) or submit a pull request.

---

*Always up-to-date. Simple. Composer-native asset management.*
