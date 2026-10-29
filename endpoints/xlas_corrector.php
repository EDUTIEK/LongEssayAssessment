<?php
require_once __DIR__ . '/../vendor/composer/vendor/autoload.php';
require_once __DIR__ . '/../artifacts/bootstrap_default.php';

entry_point("ILIAS Legacy Initialisation Adapter");

ilContext::init(ilContext::CONTEXT_REST);

$config = ilLongEssayAssessmentPlugin::getInstance()->dic()->system()->config();
$frontend_url = $config->getFrontendUrl(\Edutiek\AssessmentService\System\Config\Frontend::CORRECTOR);
$backend_url = $config->getSetup()->getBackendUrl();

setcookie(
    'xlasBackendUrl',
    $backend_url,
    [
        'expires' => time() + 60,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => false,
        'sameSite' => 'Strict' // None, Lax, Strict
    ]
);

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Location: ' . $frontend_url);
exit;