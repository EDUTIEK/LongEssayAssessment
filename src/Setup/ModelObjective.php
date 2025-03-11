<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup\Artifact\BuildArtifactObjective;
use ILIAS\Setup\Artifact;
use ILIAS\Setup\Artifact\ArrayArtifact;
use ILIAS\Setup\AbstractOfFinder;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

class ModelObjective extends BuildArtifactObjective
{
    public function getArtifactName(): string
    {
        return 'long_essay_assessment_models';
    }

    public function build(): Artifact
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $finder = new class() extends AbstractOfFinder{
            public function findModels(): array
            {
                return iterator_to_array($this->genericGetMatchingClassNames(fn($r) => (
                    preg_match('/^ILIAS\\\\Plugin\\\\LongEssayAssessment\\\\.*\\\\Data\\\\/', $r->getName()) &&
                    Generate::findName(Table::class, $r->getAttributes())
                )));
            }
        };

        $classes = $finder->findModels();

        return new ArrayArtifact(array_combine(
            $classes,
            array_map(Generate::readModelFromClass(...), $classes)
        ));
    }
}
