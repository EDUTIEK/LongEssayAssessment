<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir('../../../../../../../');

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_REST);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

$context = new \ILIAS\Plugin\LongEssayTask\Corrector\CorrectorContext();
$service = new \Edutiek\LongEssayService\Corrector\Service($context);
$service->handleRequest();