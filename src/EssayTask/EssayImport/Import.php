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

use Generator;

interface Import
{
    /**
     * @param array<string, string> $pdfs Filenames by login
     * @param array<string, string> $hashes Hashes by filename
     *
     * @return array{errors: string[], overwrites: string[]}
     */
    public function problems(string $login, array $pdfs, array $hashes): array;

    /**
     * @return resource
     */
    public function openFile(string $id);
    public function txt(string $lang_var): string;
    public function buildPdfHashes(array $pdfs): array;

    /**
     * @template A
     * @template B
     *
     * @param callable(A): B $proc
     * @param A[] $array
     * @return array<B, A>
     */
    public function keysBy(callable $proc, array $array): array;
    public function extract(string $pattern, string $subject, int $sub_match): ?string;
    public function iterator(callable $proc, $end = false): Generator;
}
