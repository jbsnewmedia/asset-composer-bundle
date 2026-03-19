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

**AssetComposerBundle** ist ein Symfony-Bundle, das Dir hilft, Assets direkt aus dem `vendor`-Verzeichnis zu verwalten und bereitzustellen. Es stellt sicher, dass alle Dateien auf dem neuesten Stand bleiben, indem es Dateiänderungszeitstempel für das Cache-Busting nutzt.

## 🚀 Funktionen

- Verwalten von CSS/JS-Assets über Composer-Pakete
- Automatische Versionierung durch Datei-Zeitstempel
- Einfache Integration in Twig-Templates
- Unterstützt Produktions-/Nur-Dev-Assets über `assetcomposer.json`
- Kompatibel mit Symfony 6 & 7

---

## ⚙️ Voraussetzungen

- PHP 8.1 oder höher
- Symfony Framework 6.4 oder 7.0

---

## 📦 Installation

Verwende [Composer](https://getcomposer.org/), um das Bundle zu installieren:

```bash
composer require jbsnewmedia/asset-composer-bundle
```

---

## 📋 Verwendung

### 1. Asset-Pakete über Composer installieren

```bash
composer require twbs/bootstrap
composer require components/font-awesome
composer require avalynx/avalynx-alert
```

### 2. Assets in Twig-Templates registrieren

#### Assets aus Composer-Paketen

```twig
{% do addAssetComposer('twbs/bootstrap/dist/css/bootstrap.css') %}
{% do addAssetComposer('components/font-awesome/css/all.css') %}
{% do addAssetComposer('avalynx/avalynx-alert/dist/css/avalynx-alert.css') %}
{% do addAssetComposer('avalynx/avalynx-alert/dist/js/avalynx-alert.js') %}
```

#### Lokale Assets

Du kannst auch lokale Assets verwenden, die im Verzeichnis `assets/` Deines Projekts liegen. Nutze dafür den Namespace `app` und das Paket `assets`:

```twig
{% do addAssetComposer('app/assets/css/custom.css') %}
{% do addAssetComposer('app/assets/js/custom.js') %}
```

Dabei müssen die Dateien lokal unter `%kernel.project_dir%/assets/css/custom.css` bzw. `%kernel.project_dir%/assets/js/custom.js` liegen.

Beispielhafte Dateistruktur:
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

### 3. Im Layout ausgeben

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

## 📁 Dateistruktur

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

## 🧰 Konfiguration (Optional)

Du kannst eine `assetcomposer.json`-Datei in Deinen Asset-Paketen erstellen, um festzulegen, welche Dateien freigegeben werden sollen:

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

## 🔧 Entwicklungswerkzeuge

Um die Codequalität aufrechtzuerhalten, verwendet dieses Projekt:

* **PHP-CS-Fixer (ECS)**: `composer bin-ecs`
* **PHPStan**: `composer bin-phpstan`
* **Rector**: `composer bin-rector`

Installiere und aktualisiere die Werkzeuge mit:

```bash
composer bin-ecs-install
composer bin-phpstan-install
composer bin-rector-install
composer bin-phpunit-install
```

---

## 🧪 Tests & QA

Der gesamte Code entspricht modernen PHP-Standards. Verwende die bereitgestellten Skripte, um die Codebasis zu analysieren und zu refaktorieren:

```bash
composer bin-phpstan       # Statische Analyse
composer bin-ecs           # Coding-Standards (prüfen)
composer bin-ecs-fix       # Coding-Standards (beheben)
composer bin-rector        # Code-Transformation (Dry-Run)
composer bin-rector-process # Code-Transformation (anwenden)
composer test              # Tests ausführen
composer test-coverage     # Tests mit Coverage ausführen
```

---

## 📜 Lizenz

Dieses Bundle ist unter der MIT-Lizenz lizenziert. Weitere Details findest Du in der Datei [LICENSE](LICENSE).

Entwickelt von Jürgen Schwind und weiteren Mitwirkenden.

---

## 🤝 Mitwirken

Beiträge sind willkommen! Wenn Du etwas beitragen möchtest, kontaktiere uns oder erstelle einen Fork des Repositories und sende einen Pull-Request mit Deinen Änderungen oder Verbesserungen.

---

## 📫 Kontakt

Wenn Du Fragen, Funktionswünsche oder Probleme hast, eröffne bitte ein Issue in unserem [GitHub-Repository](https://github.com/jbsnewmedia/asset-composer-bundle) oder sende einen Pull-Request.

---

*Immer aktuell. Einfach. Composer-natives Asset-Management.*
