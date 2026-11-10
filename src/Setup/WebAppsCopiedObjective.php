<?php

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup;
use ILIAS\Setup\Environment;

class WebAppsCopiedObjective implements Setup\Objective
{
    private const ILIAS_ROOT = __DIR__ . "/../../../../../../../../../..";

    public function getHash(): string
    {
        return hash("sha256", self::class);
    }

    public function getLabel(): string
    {
        return "The LongEssayAssessment web apps are copied to their public destination";
    }

    public function isNotable(): bool
    {
        return true;
    }

    public function getPreconditions(Environment $environment): array
    {
        return [];
    }

    /**
     * Copy the web apps to the public directory.
     *
     * Built files are taken from each app's dist directory. The destination
     * under public/ comes from apps.config.json when that file exists, otherwise
     * from apps.config.default.json. The corrector's public config.json gets
     * the configured backendUrl.
     */
    public function achieve(Environment $environment): Environment
    {
        $plugin = self::ILIAS_ROOT . '/public/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment';
        $public = self::ILIAS_ROOT . '/public';
        $config = $this->readAppsConfig($plugin);

        $sources = [
            'corrector' => $plugin . '/vendor/edutiek/assessment-service/node_modules/assessment-corrector/dist',
            'writer' => $plugin . '/vendor/edutiek/assessment-service/node_modules/assessment-writer/dist',
        ];

        foreach ($sources as $app => $source) {
            $public_path = $config[$app]['publicPath'] ?? null;
            if (!is_string($public_path) || $public_path === '') {
                throw new Setup\UnachievableException(
                    "Missing publicPath for '$app' in the LongEssayAssessment apps config."
                );
            }
            if (!is_dir($source)) {
                throw new Setup\UnachievableException("Web app '$app' was not found at '$source'.");
            }
            $this->copy($source, $public, $public_path);
        }

        $backend_url = $config['corrector']['backendUrl'] ?? null;
        if (!is_string($backend_url) || $backend_url === '') {
            throw new Setup\UnachievableException(
                "Missing backendUrl for the corrector in the LongEssayAssessment apps config."
            );
        }
        $this->writeCorrectorBackendUrl($public . '/' . $config['corrector']['publicPath'] . '/config.json', $backend_url);

        return $environment;
    }

    /**
     * Prefer a local apps.config.json over apps.config.default.json
     * @return array<string, mixed>
     */
    private function readAppsConfig(string $plugin): array
    {
        $local = $plugin . '/apps.config.json';
        $default = $plugin . '/apps.config.default.json';
        $path = is_file($local) ? $local : $default;

        if (!is_file($path)) {
            throw new Setup\UnachievableException("LongEssayAssessment apps config was not found.");
        }

        $config = json_decode((string) file_get_contents($path), true);
        if (!is_array($config)) {
            throw new Setup\UnachievableException("LongEssayAssessment apps config '$path' is not valid JSON.");
        }

        return $config;
    }

    /**
     * Replace backendUrl in the public copy of the corrector config
     */
    private function writeCorrectorBackendUrl(string $config_file, string $backend_url): void
    {
        if (!is_file($config_file)) {
            throw new Setup\UnachievableException("Corrector config was not copied to '$config_file'.");
        }

        $config = json_decode((string) file_get_contents($config_file), true);
        if (!is_array($config)) {
            throw new Setup\UnachievableException("Corrector config '$config_file' is not valid JSON.");
        }

        $config['backendUrl'] = $backend_url;
        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false || file_put_contents($config_file, $encoded . "\n") === false) {
            throw new Setup\UnachievableException("Could not write corrector config '$config_file'.");
        }
    }

    /**
     * Copy a directory recursively
     * @param string $source    absolute path of the source file or directory
     * @param string $dest      absolute path of destination parent directory
     * @param string $name      new name of the source within the destination parent
     */
    private function copy(string $source, string $dest, string $name)
    {
        $target = $dest . '/' . $name;

        if (is_dir($source)) {
            if (!is_dir($target)) {
                mkdir($target, 0777, true);
            }
            foreach (glob("$source/*") as $content) {
                $this->copy($content, $target, basename($content));
            };
        }
        if (is_file($source)) {
            copy($source, $target);
        }
    }

    public function isApplicable(Environment $environment): bool
    {
        return true;
    }
}
