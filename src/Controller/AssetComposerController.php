<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Controller;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetComposerController extends AbstractController
{
    public function __construct(private readonly AssetComposer $assetComposer)
    {
    }

    public function getAsset(
        string $namespace,
        string $package,
        string $asset,
        Request $request,
    ): Response {
        return $this->assetComposer->getAssetFile(
            $namespace,
            $package,
            $asset,
            (string) $request->query->get('v')
        );
    }
}
