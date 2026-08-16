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

namespace ILIAS\Plugin\LongEssayAssessment\UI\LiveStatusPanel;

class Factory
{
    /**
     * @param string $title
     * @param string $live_data_url
     * @param Property[]  $properties
     * @return Panel
     */
    public function panel(string $title, string $live_data_url, array $properties = []): Panel
    {
        return new Panel($title, $live_data_url, $properties);
    }

    public function property(string $id, string $title, int $initial_value, ?string $filter_url = null, bool $active = false): Property
    {
        return new Property($id, $title, $initial_value, $filter_url, $active);
    }
}
