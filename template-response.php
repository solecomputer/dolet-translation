<?php
declare(strict_types=1);
namespace DoletWP;

if (!\defined('ABSPATH')) {
    exit;
}

$sole_translation_template_owner = \apply_filters('sole_translation_template_response_owner', null);
if (!$sole_translation_template_owner instanceof Routines_TemplateResponseInterface
    || $sole_translation_template_owner->hasStartedTemplateResponse()) {
    return;
}
$sole_translation_template_owner->prepareTemplateResponse();
                                                                                 
global $template;
$template = $sole_translation_template_owner->getSelectedTemplate();
if (!\ob_start([$sole_translation_template_owner, 'identityBuffer'])) {
    $sole_translation_template_owner->reportTemplateBufferFailure();
    include $sole_translation_template_owner->getSelectedTemplate();
    return;
}
try {
    $sole_translation_template_owner->startTemplateResponse();
                                                                                   
    include $sole_translation_template_owner->getSelectedTemplate();
    $sole_translation_template_owner->finalizeResponse();
} finally {
    $sole_translation_template_owner->clearTemplateResponse();
                                                                             
    if ($sole_translation_template_owner->ownsTemplateBuffer()) {
        \ob_end_flush();
    }
}
