<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment;

/**
 * Target for permanent links
 */
enum Jump: string
{
    case WRITER = "writer";
    case CORRECTOR = "corrector";
    case CORRECTOR_ADMIN = "correctoradmin";
}
