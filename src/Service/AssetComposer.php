<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssetComposer
{
    /**
     * @var array<string, string>
     */
    protected array $contentTypes = [];

    /**
     * @param string[] $paths Array of paths
     */
    public function __construct(
        protected string $projectDir,
        protected UrlGeneratorInterface $router,
        protected string $environment,
        protected string $appSecret,
        protected array $paths = [],
    ) {
        if ([] === $this->paths) {
            $this->paths = [
                '/vendor/',
            ];
        } else {
            $this->paths[] = '/vendor/';
        }

        $this->contentTypes = [
            'csv' => 'text/csv',
            'css' => 'text/css',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'eot' => 'font/eot',
            'gif' => 'image/gif',
            'gz' => 'application/gzip',
            'html' => 'text/html',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'oga' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'otf' => 'font/otf',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'rar' => 'application/vnd.rar',
            'svg' => 'image/svg+xml',
            'tar' => 'application/x-tar',
            'ttf' => 'font/ttf',
            'txt' => 'text/plain',
            'wav' => 'audio/wav',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
        ];
    }

    public function getAssetFile(string $namespace, string $package, string $asset, string $v): Response
    {
        $vendorDir = '';
        if (('app' === $namespace) && ('assets' === $package)) {
            $vendorDir = $this->projectDir.'/assets/';
        } else {
            foreach ($this->paths as $path) {
                $vendorDir = $this->projectDir.$path.$namespace.'/'.$package.'/';
                if (is_dir($vendorDir)) {
                    break;
                }
            }
        }

        if (!is_dir($vendorDir)) {
            throw new BadRequestHttpException('Vendor directory not found');
        }

        $vendorFile = $vendorDir.$asset;
        $realVendorFilePath = realpath($vendorFile);
        if (false === $realVendorFilePath || !str_starts_with($realVendorFilePath, $vendorDir)) {
            throw new BadRequestHttpException('Vendor directory traversal detected');
        }

        $vendorProtectFile = $vendorDir.'assetscomposer.json';
        if (file_exists($vendorProtectFile)) {
            $vendorProtectContent = file_get_contents($vendorProtectFile);
            if (false === $vendorProtectContent) {
                throw new BadRequestHttpException('Unable to read the asset composer file');
            }

            $vendorProtectJson = json_decode($vendorProtectContent, true);
            if (!is_array($vendorProtectJson)) {
                throw new BadRequestHttpException('Invalid asset composer file');
            }

            if ('prod' === $this->environment) {
                if ((!isset($vendorProtectJson['files'])) || (!is_array($vendorProtectJson['files']))) {
                    throw new BadRequestHttpException('Invalid asset composer file');
                }

                if (!in_array($asset, $vendorProtectJson['files'], true)) {
                    throw new BadRequestHttpException('Asset not allowed');
                }
            } else {
                if ((!isset($vendorProtectJson['files'])) || (!is_array($vendorProtectJson['files'])) || (!isset($vendorProtectJson['files-dev'])) || (!is_array($vendorProtectJson['files-dev']))) {
                    throw new BadRequestHttpException('Invalid asset composer file');
                }

                if ((!in_array($asset, $vendorProtectJson['files'], true)) && (!in_array($asset, $vendorProtectJson['files-dev'], true))) {
                    throw new BadRequestHttpException('Asset not allowed');
                }
            }
        }

        $fileMTime = filemtime($vendorFile);
        if (false === $fileMTime) {
            throw new BadRequestHttpException('Unable to get the file modification time');
        }

        $baseUrlPart = $namespace.'/'.$package.'/'.$asset;
        $vNew = md5($baseUrlPart.'#'.$this->appSecret.'#'.(string) $fileMTime);
        if (('' === $v) || ($v !== $vNew)) {
            throw new BadRequestHttpException('Invalid asset version');
        }

        $fileType = pathinfo($vendorFile, PATHINFO_EXTENSION);
        $content = file_get_contents($vendorFile);
        if (false === $content) {
            throw new BadRequestHttpException('Unable to read the asset file');
        }

        if (!isset($this->contentTypes[$fileType])) {
            throw new BadRequestHttpException('Invalid content type');
        }

        $response = new Response($content);
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', strtotime('+10 years')));
        $response->headers->set('Cache-Control', 'max-age=315360000, public');
        $response->headers->set('Pragma', 'cache');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $fileMTime));
        $response->headers->set('Content-Type', $this->contentTypes[$fileType]);

        return $response;
    }

    public function getAssetFileName(string $asset): string
    {
        $assetParts = explode('/', $asset);
        if (count($assetParts) < 3) {
            throw new BadRequestHttpException('Asset not found (invalid asset path)');
        }

        if (('app' === $assetParts[0]) && ('assets' === $assetParts[1])) {
            $vendorFile = $this->projectDir.'/assets/'.implode('/', array_slice($assetParts, 2));
        } else {
            $vendorFile = '';
            foreach ($this->paths as $path) {
                $vendorFile = $this->projectDir.$path.$asset;
                if (is_file($vendorFile)) {
                    break;
                }
            }

            $realVendorFilePath = realpath($vendorFile);
            if (false === $realVendorFilePath || !str_starts_with($realVendorFilePath, $this->projectDir)) {
                throw new BadRequestHttpException('Asset not found ('.str_replace($this->projectDir.'/', '', $vendorFile).')');
            }
        }

        if (!file_exists($vendorFile)) {
            throw new BadRequestHttpException('Asset not found ('.str_replace($this->projectDir.'/', '', $vendorFile).')');
        }

        $baseUrlPart = $assetParts[0].'/'.$assetParts[1].'/'.implode('/', array_slice($assetParts, 2));
        $baseUrl = $this->router->generate('jbs_new_media_assets_composer', [
            'namespace' => $assetParts[0],
            'package' => $assetParts[1],
            'asset' => implode('/', array_slice($assetParts, 2)),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $fileMTime = filemtime($vendorFile);
        if (false === $fileMTime) {
            throw new BadRequestHttpException('Unable to get the file modification time');
        }

        return $baseUrl.'?v='.md5($baseUrlPart.'#'.$this->appSecret.'#'.(string) $fileMTime);
    }
}
