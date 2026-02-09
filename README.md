# AssetComposerBundle

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

```twig
{% do addAssetComposer('twbs/bootstrap/dist/css/bootstrap.css') %}
{% do addAssetComposer('components/font-awesome/css/all.css') %}
{% do addAssetComposer('avalynx/avalynx-alert/dist/css/avalynx-alert.css') %}
{% do addAssetComposer('avalynx/avalynx-alert/dist/js/avalynx-alert.js') %}
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

Developed by Juergen Schwind and other contributors.

---

## 🤝 Contributing

Contributions are welcome! If you'd like to contribute, please fork the repository and submit a pull request with your changes or improvements. We're looking for contributions in the following areas:

- Bug fixes
- Feature enhancements
- Documentation improvements

Before submitting your pull request, please ensure your changes are well-documented and follow the existing coding style of the project.

---

## 📫 Contact

If you have any questions, feature requests, or issues, please open an issue on our [GitHub repository](https://github.com/jbsnewmedia/asset-composer-bundle) or submit a pull request.

---

*Always up-to-date. Simple. Composer-native asset management.*
