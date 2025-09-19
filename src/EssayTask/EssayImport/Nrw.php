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

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\EssayImport;

use ILIAS\UI\Component\Table\Column\Factory as Column;
use ILIAS\UI\Component\Symbol\Icon\Icon;

class Nrw implements Type
{
    private const FILE_PATTERN = '/^\d+von\d+_(\d+-\d+).pdf$/';

    public function __construct(
        private readonly Import $import,
    )
    {
    }

    public function tableArray(array $files, array $hashes): array
    {
        $pdfs = $this->import->keysBy(fn($f) => $this->import->extract(self::FILE_PATTERN, $f, 1), $files);
        unset($pdfs['']);

        return array_map(function (string $login, string $file) use ($hashes, $pdfs) {
            $problems = $this->import->problems($login, $pdfs, $hashes);
            return [
                'file' => $file,
                'id' => $login,
                'import_possible' => $problems['errors'] === [],
                'comment' => join(', ', array_merge(...array_values($problems))),
                'overwrites' => $problems['overwrites'],
            ];
        }, array_keys($pdfs), array_values($pdfs));
    }

    public function validFilesByLogin(array $files): array
    {
        return $this->import->keysBy(
            fn($file) => $this->import->extract(self::FILE_PATTERN, $file, 1),
            $files
        );
    }

    public function columns(Column $column, Icon $yes, Icon $no): array
    {
        return [
            'file' => $column->text($this->import->txt('essay_import_column_file')),
            'id' => $column->text($this->import->txt('essay_import_column_user')),
            'import_possible' => $column->boolean($this->import->txt('essay_import_column_import_possible'), $yes, $no),
            'comment' => $column->text($this->import->txt('essay_import_column_comment')),
        ];
    }

    public function relevantFiles(array $files): array
    {
        return preg_grep(self::FILE_PATTERN, $files);
    }
}
