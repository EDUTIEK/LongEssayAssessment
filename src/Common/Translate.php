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

namespace ILIAS\Plugin\LongEssayAssessment\Common;

use ILIAS\Plugin\LongEssayAssessment\Setup\LanguageFileObjective;

class Translate
{
    /**
     * @var array<string, string[]>
     */
    private static ?array $by_lang = null;

    public function txt(string $lang_var): string
    {
        $by_lang = self::$by_lang ??= require_once(LanguageFileObjective::PATH());
        return $by_lang[$lang_var] ?? $lang_var;
    }
}
