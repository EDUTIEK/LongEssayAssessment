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

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Example;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Column;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use DateTimeImmutable;

#[Table(name: 'dummy')]
class Dummy
{
    // Infer db type from php type.
    private string $foo = 'fofof';
    #[Key]
    private int $bar = 3484;
    private float $baz = 4.0;
    private DateTimeImmutable $some_day;

    #[Column(type: 'integer')] // Specify db type explicitly.
    #[Sequence] // Define sequence.
    private string $hej;

    #[Column(name: 'hu')] // Specify db field name explicitly.
    private string $ho = 'huhuhu';

    public function getSomeDay() : DateTimeImmutable
    {
        return $this->some_day;
    }

    public function setSomeDay(DateTimeImmutable $some_day) : Dummy
    {
        $this->some_day = $some_day;
        return $this;
    }

    public function getHej() : string
    {
        return $this->hej;
    }

    public function setHej(string $hej) : Dummy
    {
        $this->hej = $hej;
        return $this;
    }

    public function getHo() : string
    {
        return $this->ho;
    }

    public function setHo(string $ho) : Dummy
    {
        $this->ho = $ho;
        return $this;
    }


}
