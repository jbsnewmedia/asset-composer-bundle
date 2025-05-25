<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Controller;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetComposerController extends AbstractController
{
    private AssetComposer $assetComposer;

    public function __construct(AssetComposer $assetComposer)
    {
        $this->assetComposer = $assetComposer;
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
