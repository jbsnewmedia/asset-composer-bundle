<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle;

use JBSNewMedia\AssetComposerBundle\DependencyInjection\AssetComposerExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class AssetComposerBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new AssetComposerExtension();
        }

        if (false === $this->extension) {
            return null;
        }

        return $this->extension;
    }
}
