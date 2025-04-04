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

    /**
     * todo: use non-conflicting names
     */
    public function achieve(Environment $environment): Environment
    {
        $root = self::ILIAS_ROOT;
        $public_path = "$root/public";
        $plugin_path = "$public_path/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment";
        copy("$plugin_path/resources/images/icon_xlas.svg", "$public_path/assets/images/standard/icon_xlas.svg");
        copy("$plugin_path/resources/images/icon_appr.svg", "$public_path/assets/images/standard/icon_appr.svg");
        copy("$plugin_path/resources/images/icon_disq.svg", "$public_path/assets/images/standard/icon_disq.svg");
        copy("$plugin_path/resources/images/icon_nota.svg", "$public_path/assets/images/standard/icon_nota.svg");
        copy("$plugin_path/resources/images/icon_nots.svg", "$public_path/assets/images/standard/icon_nots.svg");
        copy("$plugin_path/resources/images/icon_time.svg", "$public_path/assets/images/standard/icon_time.svg");


        return $environment;
    }

    public function isApplicable(Environment $environment): bool
    {
        return true;
    }
}
