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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use ILIAS\Data\ReferenceId;
use ILIAS\StaticURL\Builder\StandardURIBuilder;
use ilTree;
use ilAccessHandler;
use ilObject;
use ilLink;
use ILIAS\StaticURL\Builder\URIBuilder;

class ContextInfoRepo implements \Edutiek\AssessmentService\Assessment\Data\ContextInfoRepo
{
    private array $instances = [];

    public function __construct(
        private readonly ilTree $tree,
        private readonly ilAccessHandler $access,
        private readonly URIBuilder $uri_builder
    ) {
    }

    public function get(int $context_id): ContextInfo
    {
        $node = $this->tree->getParentNodeData($context_id);

        return new ContextInfo(
            $context_id,
            $node['title'] ?? '',
            $node['description'] ?? '',
        );
    }

    public function link(int $ass_id, int $user_id): string
    {
        foreach (ilObject::_getAllReferences($ass_id) as $ref_id) {
            if ($this->access->checkAccessOfUser($user_id, 'read', '', $ref_id)) {
                $url = (string) $this->uri_builder->build('xlas', new ReferenceId($ref_id));
                return str_replace('/xlas_rest.php', '', $url);
            }
        }
        return '';
    }
}
