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
        $currentPath = '';

        if (('app' === $namespace) && ('assets' === $package)) {
            $vendorDir = $this->projectDir.'/assets/';
        } else {
            foreach ($this->paths as $path) {
                $currentPath = $path;
                $vendorDir = $this->projectDir.$path.$namespace.'/'.$package.'/';
                if (is_dir($vendorDir)) {
                    break;
                }
            }
        }

        if (!is_dir($vendorDir)) {
            throw new BadRequestHttpException('Vendor directory not found');
        }

        if (str_contains($asset, '..')) {
            throw new BadRequestHttpException('Vendor directory traversal detected');
        }

        $vendorFile = $vendorDir.$asset;

        if (!file_exists($vendorFile)) {
            throw new BadRequestHttpException('Asset file not found');
        }

        $realVendorFilePath = realpath($vendorFile);
        $realVendorDir = realpath($vendorDir);
        if (
            false === $realVendorFilePath
            || false === $realVendorDir
            || !str_starts_with($realVendorFilePath, $realVendorDir)
        ) {
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
                if (!isset($vendorProtectJson['files']) || !is_array($vendorProtectJson['files'])) {
                    throw new BadRequestHttpException('Invalid asset composer file: missing files array');
                }

                if (!in_array($asset, $vendorProtectJson['files'], true)) {
                    throw new BadRequestHttpException('Asset not allowed in production environment');
                }
            } else {
                if (!isset($vendorProtectJson['files']) || !is_array($vendorProtectJson['files'])) {
                    throw new BadRequestHttpException('Invalid asset composer file: missing files array');
                }

                if (isset($vendorProtectJson['files-dev']) && is_array($vendorProtectJson['files-dev'])) {
                    if (!in_array($asset, $vendorProtectJson['files'], true)
                        && !in_array($asset, $vendorProtectJson['files-dev'], true)) {
                        throw new BadRequestHttpException('Asset not allowed in development environment');
                    }
                } else {
                    if (!in_array($asset, $vendorProtectJson['files'], true)) {
                        throw new BadRequestHttpException('Asset not allowed');
                    }
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

        $content = $this->setUrlVersions($content, $this->projectDir.$currentPath, $vendorFile, $baseUrlPart);

        $response = new Response($content);
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', strtotime('+10 years')));
        $response->headers->set('Cache-Control', 'max-age=315360000, public');
        $response->headers->set('Pragma', 'cache');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $fileMTime));
        $response->headers->set('Content-Type', $this->contentTypes[$fileType]);

        return $response;
    }

    private function setUrlVersions(string $content, string $vendorDir, string $vendorFile, string $baseUrlPart): string
    {
        if (!str_ends_with($vendorFile, '.css') && !str_ends_with($vendorFile, '.js')) {
            return $content;
        }

        $urlRegex = '/url\((["\']?)([^"\')]+)(["\']?)\)/i';
        $matches = [];
        preg_match_all($urlRegex, $content, $matches, PREG_SET_ORDER);

        $dirname = dirname(realpath($vendorFile) ?: $vendorFile).DIRECTORY_SEPARATOR;
        $matchesNew = [];

        foreach ($matches as $match) {
            $url = $match[2];

            if (str_starts_with($url, 'data:')
                || str_starts_with($url, 'http://')
                || str_starts_with($url, 'https://')) {
                continue;
            }

            if (!isset($matchesNew[$url])) {
                $matchesNew[$url] = $match;
            }
        }

        foreach ($matchesNew as $match) {
            $url = $match[2];
            $file = $dirname.$url;

            $resolvedFile = realpath($file);

            if (false === $resolvedFile || !file_exists($resolvedFile)) {
                continue;
            }

            $baseUrlPart = str_replace($vendorDir, '', $resolvedFile);
            $mtime = filemtime($resolvedFile) ?: time();
            $v = md5($baseUrlPart.'#'.$this->appSecret.'#'.(string) $mtime);

            $cleanUrl = $match[0];
            $newUrl = str_replace($url, $url.'?v='.$v, $cleanUrl);
            $content = str_replace($cleanUrl, $newUrl, $content);
        }

        return $content;
    }

    /**
     * Get the URL for an asset file with versioning.
     *
     * @throws BadRequestHttpException If the asset cannot be found or accessed
     */
    public function getAssetFileName(string $asset): string
    {
        $assetParts = explode('/', $asset);
        if (count($assetParts) < 3) {
            throw new BadRequestHttpException('Asset not found (invalid asset path)');
        }

        $namespace = $assetParts[0];
        $package = $assetParts[1];
        $assetPath = implode('/', array_slice($assetParts, 2));

        if (('app' === $namespace) && ('assets' === $package)) {
            $vendorFile = $this->projectDir.'/assets/'.$assetPath;
        } else {
            $vendorFile = '';
            $found = false;

            foreach ($this->paths as $path) {
                $candidateFile = $this->projectDir.$path.$asset;
                if (is_file($candidateFile)) {
                    $vendorFile = $candidateFile;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new BadRequestHttpException('Asset not found: '.$asset);
            }

            $realVendorFilePath = realpath($vendorFile);
            $realProjectDir = realpath($this->projectDir);

            if (false === $realVendorFilePath
                || false === $realProjectDir
                || !str_starts_with($realVendorFilePath, $realProjectDir)) {
                throw new BadRequestHttpException('Security violation: attempted directory traversal');
            }
        }

        if (!file_exists($vendorFile)) {
            throw new BadRequestHttpException('Asset file not found: '.str_replace($this->projectDir.'/', '', $vendorFile));
        }

        $baseUrlPart = $namespace.'/'.$package.'/'.$assetPath;
        $baseUrl = $this->router->generate('jbs_new_media_assets_composer', [
            'namespace' => $namespace,
            'package' => $package,
            'asset' => $assetPath,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $fileMTime = filemtime($vendorFile);
        if (false === $fileMTime) {
            throw new BadRequestHttpException('Unable to get the file modification time');
        }

        return $baseUrl.'?v='.md5($baseUrlPart.'#'.$this->appSecret.'#'.(string) $fileMTime);
    }
}
