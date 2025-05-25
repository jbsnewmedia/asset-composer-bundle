<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Twig;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Twig extension for managing and rendering assets
 */
class AssetComposerExtension extends AbstractExtension
{
    /**
     * Stores assets organized by position and type
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private array $assets = [];

    public function __construct(private AssetComposer $assetComposer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('addAssetComposer', [$this, 'addAssetComposer']),
            new TwigFunction('removeAssetComposer', [$this, 'removeAssetComposer']),
            new TwigFunction('renderAssetComposerStylesheets', [$this, 'renderStylesheets']),
            new TwigFunction('renderAssetComposerJavascripts', [$this, 'renderJavascripts']),
            new TwigFunction('getAssetComposerFile', [$this, 'getAssetComposerFile']),
        ];
    }

    /**
     * Add an asset to be managed by the asset composer
     *
     * @throws \InvalidArgumentException If the asset type is invalid
     */
    public function addAssetComposer(string $assetFilename, string $position = 'all'): void
    {
        $assetInfo = pathinfo($assetFilename);
        if (!isset($assetInfo['extension'])) {
            throw new \InvalidArgumentException('Invalid asset: missing file extension');
        }

        $extension = strtolower($assetInfo['extension']);

        if ($extension === 'css') {
            $this->assets[$position]['css'][$assetFilename] = $assetFilename;
        } elseif ($extension === 'js') {
            $this->assets[$position]['js'][$assetFilename] = $assetFilename;
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid asset type: %s', $extension));
        }
    }

    /**
     * Remove an asset from being managed by the asset composer
     *
     * @throws \InvalidArgumentException If the asset type is invalid
     */
    public function removeAssetComposer(string $assetFilename, string $position = 'all'): void
    {
        $assetInfo = pathinfo($assetFilename);
        if (!isset($assetInfo['extension'])) {
            throw new \InvalidArgumentException('Invalid asset: missing file extension');
        }

        $extension = strtolower($assetInfo['extension']);

        if ($extension === 'css') {
            unset($this->assets[$position]['css'][$assetFilename]);
        } elseif ($extension === 'js') {
            unset($this->assets[$position]['js'][$assetFilename]);
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid asset type: %s', $extension));
        }
    }

    /**
     * Render stylesheet link tags for the given position
     */
    public function renderStylesheets(string $position = 'all'): Markup
    {
        $stylesheets = '';

        if (isset($this->assets[$position]['css']) && !empty($this->assets[$position]['css'])) {
            foreach ($this->assets[$position]['css'] as $assetFilename) {
                $stylesheets .= sprintf(
                    '<link rel="stylesheet" href="%s">',
                    htmlspecialchars($this->assetComposer->getAssetFileName($assetFilename), ENT_QUOTES)
                );
            }
        }

        return new Markup($stylesheets, 'UTF-8');
    }

    /**
     * Render JavaScript script tags for the given position
     */
    public function renderJavascripts(string $position = 'all'): Markup
    {
        $javascripts = '';

        if (isset($this->assets[$position]['js']) && !empty($this->assets[$position]['js'])) {
            foreach ($this->assets[$position]['js'] as $assetFilename) {
                $javascripts .= sprintf(
                    '<script src="%s"></script>',
                    htmlspecialchars($this->assetComposer->getAssetFileName($assetFilename), ENT_QUOTES)
                );
            }
        }

        return new Markup($javascripts, 'UTF-8');
    }

    /**
     * Get the URL for an asset file with versioning
     */
    public function getAssetComposerFile(string $assetFilename): string
    {
        return $this->assetComposer->getAssetFileName($assetFilename);
    }
}
