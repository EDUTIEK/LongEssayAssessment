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

class LanguageFileObjective extends BuildArtifactObjective
{
    private const CSV_FILE = __DIR__ . '/../../translations.csv';

    public function getArtifactName(): string
    {
        return 'long_essay_assessment_lang_files';
    }

    public function build(): Artifact
    {
        return new ArrayArtifact($this->readCsv(self::CSV_FILE));
    }

    /**
     * @return array<string, string[]>
     */
    private function readCsv(string $filename): array
    {
        $file = fopen($filename, 'rb');
        if(!$file){
            return [];
        }
        $ret = $this->readCsvFile($file);
        fclose($file);
        return $ret;
    }

    /**
     * @param resource $file
     * @return array<string, string[]>
     */
    private function readCsvFile($file): array
    {
        $header = fgetcsv($file);
        $id = key(array_filter($header, fn($v) => $v === 'id'));
        $by_lang = [];
        while (($row = fgetcsv($file))) {
            $id_field = $row[$id];
            foreach ($row as $i => $val){
                if ($i !== $id) {
                    $by_lang[$header[$i]][$id_field] = $val;
                }
            }
        }

        return $by_lang;
    }
}
