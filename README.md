# AssetComposerBundle

A Symfony bundle to help you manage your assets directly from the `vendor` directory, ensuring all files are always up-to-date using their filetime version.

## Requirements

- PHP 8.0 or higher
- Symfony Framework Bundle 6.0 or higher

## Installation

To install AssetComposerBundle, you need to use [Composer](https://getcomposer.org/). Run the following command in your project root:

```bash
composer require jbsnewmedia/asset-composer-bundle
```

## Usage

This bundle allows you to include assets from the `vendor` directory in your Symfony project. It ensures that all files are always current by using their last modified time as a version number. This way, you can be sure that the assets are always up-to-date, even if you update your dependencies.

### Adding Assets example 

To add assets from the `vendor` directory to your project, simply use Composer to manage your dependencies. This method ensures that all assets are always up-to-date, as Composer will handle the downloading and updating of the required packages for you. Here’s how you can include assets in your Symfony project:

**Install the asset packages via Composer**:

For example, to include Bootstrap, FontAwesome, and AvalynxAlert in your project, run the following commands:

```bash
composer require twbs/bootstrap
composer require components/font-awesome
composer require avalynx/avalynx-alert
```

This will download the asset packages into the `vendor` directory of your Symfony project.

**Include assets in your twig templates**:

Once the assets are installed, you can include them in your twig templates using the `addAssetComposer` function. This function registers the assets for inclusion in the HTML output. Here is an example of how to use it in your base template:

```twig
{% extends 'base.html.twig' %}

{% do addAssetComposer('twbs/bootstrap/dist/css/bootstrap.css') %}
{% do addAssetComposer('components/font-awesome/css/all.css') %}
{% do addAssetComposer('avalynx/avalynx-alert/dist/css/avalynx-alert.css') %}
{% do addAssetComposer('avalynx/avalynx-alert/dist/js/avalynx-alert.js') %}
```

In the example above, the CSS and JS files from the specified packages are added to the asset pipeline, ensuring they are included in the final HTML output.

**Render the assets**:

Finally, you need to render the stylesheets and javaScripts in your HTML. This can be done using the `renderAssetComposerStylesheets` and `renderAssetComposerJavascripts` functions. Here’s how to include them in your HTML structure:

```twig
<!DOCTYPE html>
<html lang="en">
   <head>
       <meta charset="UTF-8">
       <title>{% block title %}Welcome!{% endblock %}</title>
       <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>⚫️</text><text y=%221.3em%22 x=%220.2em%22 font-size=%2276%22 fill=%22%23fff%22>sf</text></svg>">
       {% block stylesheets %}
           {{ renderAssetComposerStylesheets() }}
       {% endblock %}

       {% block javascripts %}
           {{ renderAssetComposerJavascripts() }}
       {% endblock %}
   </head>
   <body>
       {% block body %}{% endblock %}

       {{ renderAssetComposerJavascripts('bottom') }}
   </body>
</html>
```

By following these steps, you can seamlessly manage and include assets from the `vendor` directory in your Symfony project, ensuring they are always up-to-date.

### File Structure Overview

- **config**
  - `routes.yaml`: Routing configuration. (moved to `config/routes/asset_composer.yaml`)
  - `services.yaml`: Symfony service configuration.
- **src**
  - **Controller**
    - `AssetComposerController.php`: Controller to manage asset routes.
  - **DependencyInjection**
    - `AssetComposerExtension.php`: Dependency injection configuration.
    - `Configuration.php`: Configuration settings for the bundle.
  - **Service**
    - `AssetComposer.php`: Core functionality for handling assets.
  - **Twig**
    - `AssetComposerBundle.php`: The main bundle class.
- `composer.json` Composer configuration file for the bundle.

## Autoloading

The bundle uses PSR-4 autoloading, as defined in `composer.json`:

```json
"autoload": {
    "psr-4": {
        "JBSNewMedia\\AssetComposerBundle\\": "src/"
    }
}
```

Ensure your application's autoloader is updated by running:

```bash
composer dump-autoload
```

## Using assetcomposer.json in other libraries

The `assetcomposer.json` file allows you to specify which files should be included in the project. Using the `files` and `files-dev` keys, you can define which files are allowed to be loaded in the production and development environments, respectively. This JSON file can be included by other libraries and tools to ensure that only the desired assets are used.

The `assetcomposer.json` file should be placed in the root directory of the library or tool, where the `composer.json` file is also located, and it should have the following structure:

```json
{
    "name": "Library name",
    "files": [
        "dist/css/library.css",
        "dist/js/library.js"
    ],
    "files-dev": [
        "src/css/library.css",
        "src/js/library.js"
    ]
}
```

## License

This bundle is licensed under the MIT license. See the [LICENSE](LICENSE) file for more details.

Developed by Juergen Schwind and other contributors.

## Contributing

Contributions are welcome! If you'd like to contribute, please fork the repository and submit a pull request with your changes or improvements. We're looking for contributions in the following areas:

- Bug fixes
- Feature enhancements
- Documentation improvements

Before submitting your pull request, please ensure your changes are well-documented and follow the existing coding style of the project.

## Contact

If you have any questions, feature requests, or issues, please open an issue on our [GitHub repository](https://github.com/jbsnewmedia/asset-composer-bundle) or submit a pull request.

We'd love to hear from you!
