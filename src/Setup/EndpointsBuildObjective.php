<?php

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup;
use ILIAS\Setup\Environment;

class EndpointsBuildObjective implements Setup\Objective
{
    public const ILIAS_ROOT = __DIR__ . "/../../../../../../../..";

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
        $public_path = "$root/public";
        $plugin_path = "$public_path/Customizing/plugins/Repository/RepositoryObject/LongEssayAssessment";
        copy("$plugin_path/xlas_rest.php", "$public_path/xlas_rest.php");

        return $environment;
    }

    public function isApplicable(Environment $environment): bool
    {
        return true;
    }
}
