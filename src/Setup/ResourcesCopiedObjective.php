<?php

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup;
use ILIAS\Setup\Environment;

class ResourcesCopiedObjective implements Setup\Objective
{
    public const ILIAS_ROOT = __DIR__ . "/../../../../../../../../../..";

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
        $root = self::ILIAS_ROOT;
        $source_path = "$root/public/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/resources";
        $dest_path = "$root/public/components/EDUTIEK/LongEssayAssessment";

        foreach (glob("$source_path/*") as $folder) {
            if (is_dir($folder)) {
                mkdir("$dest_path/" . basename($folder) , 0777, true);
                foreach (glob("$folder/*") as $file) {
                    copy($file, $dest_path . '/' . basename($folder) . '/'. basename($file));
                };
            }
        }


        return $environment;
    }

    public function isApplicable(Environment $environment): bool
    {
        return true;
    }
}
