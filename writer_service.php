<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir('../../../../../../../');

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_REST);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

// REST calls from the web app should not write the user session of ILIAS in general
// Session expire is set for specific calls that indicate a user activity
ilSession::enableWebAccessWithoutSession(true);

ilLongEssayAssessmentPlugin::initAutoload();
$context = new \ILIAS\Plugin\LongEssayAssessment\Writer\WriterContext();
$service = new \Edutiek\LongEssayAssessmentService\Writer\Service($context);
$service->handleRequest();