<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Implementation\Component\Symbol\Icon\Icon;

interface IconFactory
{
    /**
     * Icon für das Plugin „LongEssayAssessment“
     *
     * @param string $label
     * @param string $size
     * @param bool $is_disabled
     * @return Icon
     */
    public function xlas(string $label, string $size = 'small', bool $is_disabled = false): Icon;

    /**
     * Icon für „Autorisierung / Approval“
     *
     * @param string $label
     * @param string $size
     * @param bool $is_disabled
     * @return Icon
     */
    public function appr(string $label, string $size = 'small', bool $is_disabled = false): Icon;

    /**
     * Icon für „Ausschluss“
     *
     * @param string $label
     * @param string $size
     * @param bool $is_disabled
     * @return Icon
     */
    public function disq(string $label, string $size = 'small', bool $is_disabled = false): Icon;

    /**
     * Icon für „Zeitverlängerung“
     *
     * @param string $label
     * @param string $size
     * @param bool $is_disabled
     * @return Icon
     */
    public function time(string $label, string $size = 'small', bool $is_disabled = false): Icon;

    /**
     * Standard-Icon für Notiz
     *
     * @param string $label
     * @param string $size
     * @param bool $is_disabled
     * @return Icon
     */
    public function nots(string $label, string $size = 'small', bool $is_disabled = false): Icon;

    /**
     * Standard-Icon für Benachrichtigung
     *
     * @param string $label
     * @param string $size
     * @param bool $is_disabled
     * @return Icon
     */
    public function nota(string $label, string $size = 'small', bool $is_disabled = false): Icon;
}
