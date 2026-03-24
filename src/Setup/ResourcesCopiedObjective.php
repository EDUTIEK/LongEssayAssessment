<?php

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup;
use ILIAS\Setup\Environment;

class ResourcesCopiedObjective implements Setup\Objective
{
    private const ILIAS_ROOT = __DIR__ . "/../../../../../../../../../..";

    public function getHash(): string
    {
        return hash("sha256", self::class);
    }

    public function getLabel(): string
    {
        return "The public folder is populated with EDUTIEK assets.";
    }

    public function isNotable(): bool
    {
        return true;
    }

    public function getPreconditions(Environment $environment): array
    {
        return [
            new \ILIAS\Component\Setup\PublicAssetsBuildObjective(new \ILIAS\Component\Resource\PublicAssetManager(), [])
        ];
    }

    public function achieve(Environment $environment): Environment
    {
        $source = self::ILIAS_ROOT . '/public/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/resources';
        $dest = self::ILIAS_ROOT . '/public/components/EDUTIEK';
        $this->copy($source, $dest, "LongEssayAssessment");

        return $environment;
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
