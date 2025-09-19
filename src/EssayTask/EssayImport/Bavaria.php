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
use Closure;
use ILIAS\UI\Component\Symbol\Icon\Icon;

class Bavaria implements Type
{
    private const FILE_PATTERN = '/^[[:alnum:]]+-\d+_([[:alpha:]]-\d+)_.*.pdf$/';
    private const PROTOCOL_FILE_NAME = 'Abgabe-Protokoll.csv';
        // Values are lang vars.
    private const CSV_MAP = [
        'id' => 'by_csv_id',
        'alloc_time' => 'by_csv_alloc_time',
        'used_time' => 'by_csv_used_time',
        'logged_in' => 'by_csv_logged_in',
        'given_up' => 'by_csv_given_up',
        'pdf_hash' => 'by_csv_pdf_hash',
    ];

    public function __construct(
        private readonly Import $import,
    )
    {
    }

    public function tableArray(array $files, array $hashes): array
    {
        $protocol = $this->readProtocol();
        $pdfs = $this->import->keysBy(fn($f) => $this->import->extract(self::FILE_PATTERN, $f, 1), $files);
        unset($pdfs['']);

        return array_map(function (array $row) use ($hashes, $pdfs) {
            $login = $this->loginName($row);
            $problems = $this->import->problems($this->loginName($row), $pdfs, $hashes);
            $hash_ok = $row['pdf_hash'] === ($hashes[$pdfs[$login] ?? false] ?? false);
            return [
                'file' => $pdfs[$login] ?? null,
                'user' => $login,
                'id' => $row['id'],
                'hash_ok' => $hash_ok,
                'import_possible' => $hash_ok && $problems['errors'] == [],
                'comment' => join(', ', array_merge(...array_values($problems))),
                'overwrites' => $problems['overwrites'],
            ];
            }, $protocol);
    }

    public function validFilesByLogin(array $files): array
    {
        $files = array_filter(
            $this->tableArray($files, $this->import->buildPdfHashes($files)),
            fn(array $row) => $row['hash_ok']//  && $row['error'] === ''
        );

        return array_combine(array_map($this->loginName(...), $files), array_column($files, 'file'));
    }

    public function columns(Column $column, Icon $yes, Icon $no): array
    {
        return [
            'file' => $column->text($this->import->txt('essay_import_column_file')),
            'user' => $column->text($this->import->txt('essay_import_column_user')),
            'id' => $column->text($this->import->txt('essay_import_column_id')),
            'hash_ok' => $column->boolean($this->import->txt('essay_import_column_hash_ok'), $yes, $no),
            'import_possible' => $column->boolean($this->import->txt('essay_import_column_import_possible'), $yes, $no),
            'comment' => $column->text($this->import->txt('essay_import_column_comment')),
        ];
    }

    public function relevantFiles(array $files): array
    {
        return array_filter($files, fn($file) => $file === self::PROTOCOL_FILE_NAME || preg_match(self::FILE_PATTERN, $file));
    }

    private function readProtocol(): array
    {
        $csv = $this->import->openFile(self::PROTOCOL_FILE_NAME);
        $x = fgets($csv); // Skip Description
        $by_row = $this->csvRow(fgetcsv($csv)); // Header

        $array = iterator_to_array($this->import->iterator(fn() => fgetcsv($csv), false));
        fclose($csv);
        return array_map($by_row, $array);
    }

    private function csvRow(array $header): Closure
    {
        $txts = array_flip(array_map($this->import->txt(...), self::CSV_MAP));

        $header = array_map(fn($key) => $txts[$key] ?? 'unknown', $header);
        return fn(array $row) => array_combine($header, $row);
    }

    private function loginName(array $row): string
    {
        return str_replace(' ', '-', $row['id']);
    }
}
