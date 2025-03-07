<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Controller;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetComposerController extends AbstractController
{
    public function getAsset(
        string $namespace,
        string $package,
        string $asset,
        AssetComposer $AssetComposer,
        Request $request,
    ): Response {
        return $AssetComposer->getAssetFile($namespace, $package, $asset, (string) $request->query->get('v'));
    }
}
