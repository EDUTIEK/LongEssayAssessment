<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\Session;

use Edutiek\AssessmentService\System\Session\Storage;
use ilSession;

class SessionAdapter implements Storage
{
    public function get(string $key): mixed
    {
        $data = ilSession::get(__class__);
        return $data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $data = ilSession::get(__class__) ?? [];
        $data[$key] = $value;
        ilSession::set(__class__, $data);
    }
}
