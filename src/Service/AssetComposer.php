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
     * @param string[] $paths
     */
    public function __construct(
        protected string $projectDir,
        protected UrlGeneratorInterface $router,
        protected string $environment,
        protected string $appSecret,
        protected array $paths = [],
        protected bool $useRelativePath = true,
    ) {
        if ([] === $this->paths) {
            $this->paths = ['/vendor/'];
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
        $rootDirForVersioning = '';

        if ('app' === $namespace && 'assets' === $package) {
            $vendorDir = rtrim($this->projectDir, '/').'/assets/';
            $rootDirForVersioning = $vendorDir;
        } else {
            foreach ($this->paths as $path) {
                $candidateDir = rtrim($this->projectDir, '/').$path.$namespace.'/'.$package.'/';
                if (is_dir($candidateDir)) {
                    $vendorDir = $candidateDir;
                    $rootDirForVersioning = rtrim($this->projectDir, '/').$path;
                    break;
                }
            }
        }

        if ('' === $vendorDir || !is_dir($vendorDir)) {
            throw new BadRequestHttpException('Vendor directory not found');
        }

        if (str_contains($asset, '..')) {
            throw new BadRequestHttpException('Vendor directory traversal detected');
        }

        $vendorFile = $vendorDir.$asset;

        if (!is_file($vendorFile)) {
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
        if (is_file($vendorProtectFile)) {
            $vendorProtectContent = file_get_contents($vendorProtectFile);
            if (false === $vendorProtectContent) {
                throw new BadRequestHttpException('Unable to read the asset composer file');
            }

            $vendorProtectJson = json_decode($vendorProtectContent, true);
            if (!is_array($vendorProtectJson)) {
                throw new BadRequestHttpException('Invalid asset composer file');
            }

            if (!isset($vendorProtectJson['files']) || !is_array($vendorProtectJson['files'])) {
                throw new BadRequestHttpException('Invalid asset composer file: missing files array');
            }

            if ('prod' === $this->environment) {
                if (!in_array($asset, $vendorProtectJson['files'], true)) {
                    throw new BadRequestHttpException('Asset not allowed in production environment');
                }
            } else {
                if (isset($vendorProtectJson['files-dev']) && is_array($vendorProtectJson['files-dev'])) {
                    if (
                        !in_array($asset, $vendorProtectJson['files'], true)
                        && !in_array($asset, $vendorProtectJson['files-dev'], true)
                    ) {
                        throw new BadRequestHttpException('Asset not allowed in development environment');
                    }
                } else {
                    if (!in_array($asset, $vendorProtectJson['files'], true)) {
                        throw new BadRequestHttpException('Asset not allowed');
                    }
                }
            }
        }

        $fileMTime = (int) filemtime($vendorFile);

        $baseUrlPart = $namespace.'/'.$package.'/'.$asset;
        $vNew = md5($baseUrlPart.'#'.$this->appSecret.'#'.(string) $fileMTime);
        if ('' === $v || $v !== $vNew) {
            throw new BadRequestHttpException('Invalid asset version');
        }

        $fileType = pathinfo($vendorFile, PATHINFO_EXTENSION);
        if (!isset($this->contentTypes[$fileType])) {
            throw new BadRequestHttpException('Invalid content type');
        }

        $content = file_get_contents($vendorFile);
        if (false === $content) {
            throw new BadRequestHttpException('Unable to read the asset file');
        }

        $content = $this->setUrlVersions(
            $content,
            $rootDirForVersioning,
            $vendorFile,
            $namespace,
            $package
        );

        $response = new Response($content);
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', strtotime('+10 years')));
        $response->headers->set('Cache-Control', 'max-age=315360000, public');
        $response->headers->set('Pragma', 'cache');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $fileMTime));
        $response->headers->set('Content-Type', $this->contentTypes[$fileType]);

        return $response;
    }

    private function setUrlVersions(
        string $content,
        string $rootDirForVersioning,
        string $vendorFile,
        string $namespace,
        string $package,
    ): string {
        if (!str_ends_with($vendorFile, '.css') && !str_ends_with($vendorFile, '.js')) {
            return $content;
        }

        $urlRegex = '/url\((["\']?)([^"\')]+)(["\']?)\)/i';
        $matches = [];
        preg_match_all($urlRegex, $content, $matches, PREG_SET_ORDER);

        $dirname = dirname(realpath($vendorFile) ?: $vendorFile).DIRECTORY_SEPARATOR;
        $uniqueMatchesByUrl = [];

        foreach ($matches as $match) {
            $url = $match[2];

            if (
                str_starts_with($url, 'data:')
                || str_starts_with($url, 'http://')
                || str_starts_with($url, 'https://')
            ) {
                continue;
            }

            $uniqueMatchesByUrl[$url] = $match;
        }

        $rootDirForVersioningReal = realpath($rootDirForVersioning);
        if (false === $rootDirForVersioningReal) {
            return $content;
        }
        $rootDirForVersioningReal = rtrim($rootDirForVersioningReal, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        foreach ($uniqueMatchesByUrl as $match) {
            $url = $match[2];

            $fileCandidate = $dirname.$url;
            $parsedPath = (string) parse_url($fileCandidate, PHP_URL_PATH);
            $resolvedFile = realpath($parsedPath);

            if (false === $resolvedFile || !is_file($resolvedFile)) {
                continue;
            }

            $mtime = (int) filemtime($resolvedFile);

            $relativeToRoot = str_replace($rootDirForVersioningReal, '', $resolvedFile);

            if ('app' === $namespace && 'assets' === $package) {
                $baseUrlPartNew = 'app/assets/'.$relativeToRoot;
            } else {
                $baseUrlPartNew = $relativeToRoot;
            }

            $v = md5($baseUrlPartNew.'#'.$this->appSecret.'#'.(string) $mtime);

            $cleanUrl = $match[0];
            if (str_contains($cleanUrl, '?')) {
                $newUrl = str_replace($url, $url.'&v='.$v, $cleanUrl);
            } else {
                $newUrl = str_replace($url, $url.'?v='.$v, $cleanUrl);
            }

            $content = str_replace($cleanUrl, $newUrl, $content);
        }

        return $content;
    }

    public function getAssetFileName(string $asset): string
    {
        $assetParts = explode('/', $asset);
        if (count($assetParts) < 3) {
            throw new BadRequestHttpException('Asset not found (invalid asset path)');
        }

        $namespace = $assetParts[0];
        $package = $assetParts[1];
        $assetPath = implode('/', array_slice($assetParts, 2));

        if ('app' === $namespace && 'assets' === $package) {
            $vendorFile = rtrim($this->projectDir, '/').'/assets/'.$assetPath;
        } else {
            $vendorFile = '';
            $found = false;

            foreach ($this->paths as $path) {
                $candidateFile = rtrim($this->projectDir, '/').$path.$asset;
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

            if (
                false === $realVendorFilePath
                || false === $realProjectDir
                || !str_starts_with($realVendorFilePath, $realProjectDir)
            ) {
                throw new BadRequestHttpException('Security violation: attempted directory traversal');
            }
        }

        if (!is_file($vendorFile)) {
            throw new BadRequestHttpException('Asset file not found: '.str_replace(rtrim($this->projectDir, '/').'/', '', $vendorFile));
        }

        $baseUrlPart = $namespace.'/'.$package.'/'.$assetPath;
        $referenceType = $this->useRelativePath
            ? UrlGeneratorInterface::RELATIVE_PATH
            : UrlGeneratorInterface::ABSOLUTE_URL;

        $baseUrl = $this->router->generate('jbs_new_media_assets_composer', [
            'namespace' => $namespace,
            'package' => $package,
            'asset' => $assetPath,
        ], $referenceType);

        $fileMTime = (int) filemtime($vendorFile);

        return $baseUrl.'?v='.md5($baseUrlPart.'#'.$this->appSecret.'#'.(string) $fileMTime);
    }
}
