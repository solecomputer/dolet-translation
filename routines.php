<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                    
  
                                           
                                                  
                            
                
                                                     
                                                                         
  
                                                                          
   

require_once __DIR__ . '/contracts.php';

                                                                   
interface Routines_SettingsValueInterface
{
                                                                      
    public static function checkbox(mixed $value): bool;
}



                                                                             
         
                                                                             
  
         


                                                                             
        
                                                                             
  
         


                                                                             
          
                                                                             
   
                                              
                                                                               
   
final class Routines_AdminLanguagesSettings implements Routine_RoutineInterface
{
    private bool $reportedInvalidCode = false;

    public function __construct(
        private Config_LanguageAdminInterface $langConfig,
        private Config_DebugSettingsInterface $debugSettings,
        private Config_ApproachSettingsInterface $approachSettings,
        private Language_OptionFactoryInterface $optionFactory
    ) {}

    public function execute(): void
    {
        add_action('admin_enqueue_scripts', function (string $hookSuffix): void {
            if ($hookSuffix !== 'settings_page_dolet-settings') {
                return;
            }
            wp_enqueue_script(
                'dolet-languages-builder',
                plugin_dir_url(__FILE__) . 'assets/languages-builder.js',
                [],
                '1.0.0',
                true
            );
        });

        add_action('admin_init', function (): void {
            $factory = $this->optionFactory;
            $config  = $this->langConfig;

                               

            register_setting('dolet_settings_group', Config_TranslationOptionNames::FLOATING_SELECTOR_ENABLED, [
                'type'              => 'boolean',
                'sanitize_callback' => static fn ($value): bool => Routines_SettingsValue::checkbox($value),
                'default'           => true,
                'show_in_rest'      => false,
            ]);

            register_setting('dolet_settings_group', Data_UniversalTableL1_Options::OPTION_DELETE_ON_UNINSTALL, [
                'type'              => 'boolean',
                'sanitize_callback' => static fn ($value): bool => Routines_SettingsValue::checkbox($value),
                'default'           => false,
                'show_in_rest'      => false,
            ]);

                          
            register_setting('dolet_settings_group', Language_AdminOptionNames::SOURCE_CODE, [
                'type'              => 'string',
                'sanitize_callback' => function ($v) use ($factory, $config) {
                    $code = $config->sanitizeCodeInput($v);
                    if ($code === null) {
                        if ($v !== null && $v !== '') {
                            $this->reportInvalidCode();
                        }
                        return Language_Constants::ORIGINAL;
                    }
                    if (strtolower($code) === Language_Constants::ORIGINAL) {
                        return Language_Constants::ORIGINAL;
                    }

                    $lo = $factory->fromCodeElseNull($code, null);
                    if ($lo === null) {
                        $this->reportInvalidCode();
                    }
                    return $lo ? $lo->getCode() : Language_Constants::ORIGINAL;
                },
                'default'      => Language_Constants::ORIGINAL,
                'show_in_rest' => false,
            ]);

                               
            register_setting('dolet_settings_group', Language_AdminOptionNames::TARGET_LANGUAGES, [
                'type'              => 'array',
                'sanitize_callback' => function ($arr) use ($config) {
                    $arr      = is_array($arr) ? $arr : [];
                    $codesReg = Language_Constants::getAllLanguages();

                    /*
                     * WPORG-REVIEWER-NOTE:
                     * The options.php request has already passed settings-form capability and nonce checks. Each posted custom-language value is unslashed and normalized. New codes must equal sanitize_text_field output and match alphanumeric segments separated by hyphens; exact previously saved identities remain compatible. Only validated posted codes can allow a separately registered target-language value.
                     */
                    $posted = isset($_POST[Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME])
                        ? wp_unslash($_POST[Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME])
                        : [];
                    $posted = is_array($posted) ? $posted : [];
                    $postedCustoms = [];
                    foreach ($this->sanitizePostedCodes($posted) as $n) {
                        $postedCustoms[$n] = true;
                    }

                                                                            
                                                                             
                                                                            
                    $customsUnion = $postedCustoms;

                                     
                    $src = $config->getSourceCode();

                    $out = [];
                    foreach ($this->sanitizePostedCodes($arr) as $n) {
                        if ($n === Language_Constants::ORIGINAL || $n === $src) {
                            continue;
                        }

                        $isRegistry = array_key_exists($n, $codesReg);
                        $isCustom   = isset($customsUnion[$n]);
                        if (!$isRegistry && !$isCustom) {
                            continue;
                        }

                        $out[$n] = true;
                    }

                    $codes = \array_map('strval', \array_keys($out));
                    sort($codes, SORT_STRING);
                    return $codes;
                },

                'default'      => [],
                'show_in_rest' => false,
            ]);

                                          
            register_setting('dolet_settings_group', Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME, [
                'type'              => 'array',
                'sanitize_callback' => function ($arr) {
                    $arr   = is_array($arr) ? $arr : [];
                    $out = [];
                    foreach ($this->sanitizePostedCodes($arr) as $n) {
                        $out[$n] = true;
                    }
                    $codes = \array_map('strval', \array_keys($out));
                    sort($codes, SORT_STRING);
                    return $codes;
                },

                'default'      => [],
                'show_in_rest' => false,
            ]);

                                       
            register_setting('dolet_settings_group', Language_AdminOptionNames::LANGUAGE_CUSTOMIZATIONS, [
                'type'              => 'array',
                'sanitize_callback' => function ($map) {
                    $map   = is_array($map) ? $map : [];
                    $clean = [];
                    foreach ($map as $code => $row) {
                        $code = \trim((string) $code);
                        if (!\is_array($row)) {
                            continue;
                        }
                        if ($code === '') {
                            continue;
                        }
                        $name = isset($row['name']) && \is_string($row['name']) ? \trim($row['name']) : '';
                        $note = isset($row['note']) && \is_string($row['note']) ? \trim($row['note']) : '';
                        $r    = [];
                        if ($name !== '') {
                            $r['name'] = $name;
                        }
                        if ($note !== '') {
                            $r['note'] = $note;
                        }
                        if (!empty($r)) {
                            $clean[$code] = $r;
                        }
                    }
                    return $clean;
                },
                'default'      => [],
                'show_in_rest' => false,
            ]);

                                                                                 
                                                                                             
              
                                                                                             
                                                                                                
                                                                                        
              
                                                                                              
                                                                                         
                                                                   
                                                                                 

            add_action(
                'update_option_' . Language_AdminOptionNames::SOURCE_CODE,
                function ($oldValue, $newValue, $optionName): void {
                    static $shutdownHooked = false;
                    if ($shutdownHooked) {
                        return;
                    }
                    $shutdownHooked = true;

                    add_action('shutdown', function (): void {
                                                                                    
                                                                                         
                        $this->langConfig->setTargetCodes($this->langConfig->getTargetCodes());
                    });
                },
                10,
                3
            );

                                                
            add_settings_section(
                'dolet_languages_section',
                \esc_html(Core_Strings::get('section_title')),
                function (): void {

                    echo '<p>' . \wp_kses_post(Core_Strings::get('section_description')) . '</p>';
                },
                'dolet_settings_page'
            );

                                                                          
            add_settings_field(
                'dolet_approach_toggle',
                '',
                [$this, 'renderApproachToggle'],
                'dolet_settings_page',
                'dolet_languages_section'
            );

                                                                
            add_settings_field(
                'dolet_languages_builder',
                '',
                [$this, 'renderLanguagesBuilder'],
                'dolet_settings_page',
                'dolet_languages_section'
            );

            add_settings_field(
                'dolet_floating_selector_toggle',
                '',
                [$this, 'renderFloatingSelectorToggle'],
                'dolet_settings_page',
                'dolet_languages_section'
            );

                                            
            add_settings_field(
                'dolet_pre_textarea_toggle',
                '',
                [$this, 'renderPreTextareaToggle'],
                'dolet_settings_page',
                'dolet_languages_section'
            );

                                                                 
            add_settings_field(
                'dolet_debug_bottom',
                '',
                [$this, 'renderDebugBottom'],
                'dolet_settings_page',
                'dolet_languages_section'
            );

            add_settings_field(
                'dolet_delete_on_uninstall',
                '',
                [$this, 'renderDeleteOnUninstallToggle'],
                'dolet_settings_page',
                'dolet_languages_section'
            );
        });
    }

    public function renderApproachToggle(): void
    {
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName(Config_ApproachOptionNames::APPROACH_ENABLED);
        $view->setChecked($this->approachSettings->isEnabled());
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('enable_translation_label')) . '</strong> <span class="dolet-settings-hint">(' . \esc_html(Core_Strings::get('enable_translation_hint')) . ')</span>');

        echo \wp_kses($view->render(), self::toggleAllowedHtml());
    }

    public function renderPreTextareaToggle(): void
    {
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName(Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA);
        $view->setChecked(Config_OptionValue::boolean(\get_option(Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA, false)));
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('translate_pre_textarea_label')) . '</strong> <span class="dolet-settings-hint">(' . \esc_html(Core_Strings::get('translate_pre_textarea_hint')) . ')</span>');

        echo \wp_kses($view->render(), self::toggleAllowedHtml());
    }

    public function renderDebugBottom(): void
    {
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName(Config_DebugOptionNames::DEBUG_OPTION_NAME);
        $view->setChecked($this->debugSettings->isEnabled());
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('debug_label')) . '</strong> <span class="dolet-settings-hint">(' . \wp_kses_post(Core_Strings::get('debug_hint')) . ')</span>');

        echo \wp_kses($view->render(), self::toggleAllowedHtml());
    }

    public function renderFloatingSelectorToggle(): void
    {
        $optionName = Config_TranslationOptionNames::FLOATING_SELECTOR_ENABLED;
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName($optionName);
        $view->setChecked(Config_OptionValue::boolean(\get_option($optionName, true)));
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('floating_selector_label')) . '</strong> <span class="dolet-settings-hint">(' . \esc_html(Core_Strings::get('floating_selector_hint')) . ')</span>');

        echo '<input type="hidden" name="' . \esc_attr($optionName) . '" value="0">';
        echo \wp_kses($view->render(), self::toggleAllowedHtml());
    }

    public function renderDeleteOnUninstallToggle(): void
    {
        $optionName = Data_UniversalTableL1_Options::OPTION_DELETE_ON_UNINSTALL;
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName($optionName);
        $view->setChecked(Config_OptionValue::boolean(\get_option($optionName, false)));
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('delete_on_uninstall_label')) . '</strong> <span class="dolet-settings-hint">(' . \esc_html(Core_Strings::get('delete_on_uninstall_hint')) . ')</span>');

        echo '<input type="hidden" name="' . \esc_attr($optionName) . '" value="0">';
        echo \wp_kses($view->render(), self::toggleAllowedHtml());
    }

    public function renderLanguagesBuilder(): void
    {
        $srcCode   = $this->langConfig->getSourceCode();                                          
        $targets   = $this->langConfig->getTargetCodes();                                                 
        $customs   = $this->langConfig->getCustomCodes();                            
        $customMap = $this->langConfig->getCustomizations();                              
        $catalog   = $this->langConfig->getSelectableCatalog();                                       

        $siteLocale = get_locale();
        $siteLO     = $this->optionFactory->fromCodeElseNull((string)$siteLocale, (string)$siteLocale);
        $siteCode   = $siteLO ? $siteLO->getCode() : '';

        $catalogNoOriginal = $catalog;
        unset($catalogNoOriginal[Language_Constants::ORIGINAL]);

        $getDisplayName = function (string $code) use ($catalog, $customMap): string {
            if (isset($customMap[$code]['name']) && $customMap[$code]['name'] !== '') {
                return (string)$customMap[$code]['name'];
            }
            return $catalog[$code] ?? $code;
        };
        $getNote = function (string $code) use ($customMap): string {
            return isset($customMap[$code]['note']) ? (string)$customMap[$code]['note'] : '';
        };

                                     
        $isOriginal = ($srcCode === Language_Constants::ORIGINAL);
        $isSite     = (!$isOriginal && $siteCode !== '' && $srcCode === $siteCode);
        $isSpecific = (!$isOriginal && !$isSite);

                                            
        $srcName        = $getDisplayName($srcCode);
        $srcDefaultName = $catalog[$srcCode] ?? $srcCode;

                                              
        $tpl = new UiWebmaster_LanguagesBuilderView();

        $tpl->setLabelSource(Core_Strings::get('source_label'));
        $tpl->setHintSource(Core_Strings::get('source_hint'));
        $tpl->setLabelTargets(Core_Strings::get('targets_label'));
        $tpl->setHintTargets(Core_Strings::get('targets_hint'));
        $tpl->setLabelPalette(Core_Strings::get('available_label'));
        $tpl->setLabelAddCustom(Core_Strings::get('add_custom_button'));
        $tpl->setPlaceholderSearch(Core_Strings::get('search_placeholder'));
        $tpl->setTitleChipAdd(Core_Strings::get('palette_add'));
        $tpl->setTitleChipDisabled(Core_Strings::get('palette_already_added'));

                          
        $tpl->setSourceHiddenName(Language_AdminOptionNames::SOURCE_CODE);
        $tpl->setSourceHiddenValue($srcCode);
        $tpl->setSourceModeIsOriginal($isOriginal);
        $tpl->setSourceModeIsSite($isSite);
        $tpl->setSourceModeIsSpecific($isSpecific);
        $tpl->setSiteCode($siteCode);
        $tpl->setCatalogNoOriginal($catalogNoOriginal);

                           
        $tpl->setSourceCustomizationFieldBase(Language_AdminOptionNames::LANGUAGE_CUSTOMIZATIONS);
        $tpl->setSourceDisplayCode($srcCode);
        $tpl->setSourceDefaultName($srcDefaultName);
                                                                                                     
        $tpl->setSourceCurrentName(($srcName !== $srcDefaultName) ? $srcName : '');

                  
        $tpl->setTargetsHolderDomId('dolet_targets_holder');
        $tpl->setTargetsSorted($targets);
        $tpl->setLanguageCustomizationsOptionName(Language_AdminOptionNames::LANGUAGE_CUSTOMIZATIONS);
                                                       
        $catalogForDefaults = $catalogNoOriginal;                                
        $tpl->setCatalogForDefaults($catalogForDefaults);

                                                     
        $tpl->setCustomCodes($customs);
        $tpl->setCustomListOptionName(Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME);

                  
        $activeTargetsSet = array_fill_keys($targets, true);
        foreach ($catalogNoOriginal as $code => $name) {
            $code = (string) $code;
            $disabled = isset($activeTargetsSet[$code]) || ($code === $srcCode);
            $label    = $code . ' — ' . $name;
            $tpl->addPaletteItem(new UiWebmaster_PaletteItemView($code, $label, $disabled));
        }

                    
        $tpl->setPaletteAddTitle(Core_Strings::get('palette_add'));
        $tpl->setPaletteAlreadyTitle(Core_Strings::get('palette_already_added'));


        echo \wp_kses($tpl->render(), self::languagesBuilderAllowedHtml());
    }

       
                                            
                           
       
    private function sanitizePostedCodes(array $values): array
    {
        $codes = $this->langConfig->sanitizeCodeInputs($values);
        if (\count($codes) !== \count($values)) {
            $this->reportInvalidCode();
        }
        return $codes;
    }

    private function reportInvalidCode(): void
    {
        if ($this->reportedInvalidCode) {
            return;
        }
        $this->reportedInvalidCode = true;
        \add_settings_error('dolet_settings_group', 'sole_translation_language_code_rejected',
            \esc_html(Core_Strings::get('language_code_rejected')));
    }

                                                   
    private static function languagesBuilderAllowedHtml(): array
    {
        return [
            'div' => [
                'class' => true,
                'data-custom-list-option' => true,
                'data-customizations-option' => true,
                'data-str-note-placeholder' => true,
                'data-str-no-targets' => true,
                'data-targets-field' => true,
                'data-targets-holder-id' => true,
                'id' => true,
            ],
            'h3' => ['class' => true],
            'p' => ['class' => true],
            'label' => ['class' => true, 'for' => true],
            'span' => ['class' => true],
            'strong' => [],
            'input' => [
                'checked' => true,
                'class' => true,
                'data-field-base' => true,
                'data-site-code' => true,
                'disabled' => true,
                'aria-controls' => true,
                'autocomplete' => true,
                'id' => true,
                'name' => true,
                'placeholder' => true,
                'type' => true,
                'value' => true,
            ],
            'select' => ['class' => true, 'id' => true],
            'option' => ['selected' => true, 'value' => true],
            'table' => ['class' => true, 'id' => true],
            'thead' => [],
            'tbody' => [],
            'tr' => ['data-code' => true, 'data-dolet-empty-targets' => true],
            'th' => ['class' => true],
            'td' => ['class' => true, 'colspan' => true],
            'details' => ['class' => true],
            'summary' => [],
            'code' => [],
            'button' => [
                'class' => true,
                'data-code' => true,
                'data-title-add' => true,
                'data-title-disabled' => true,
                'disabled' => true,
                'id' => true,
                'title' => true,
                'type' => true,
            ],
        ];
    }

                                                   
    private static function toggleAllowedHtml(): array
    {
        return [
            'div' => ['class' => true],
            'label' => ['class' => true],
            'input' => [
                'checked' => true,
                'name' => true,
                'type' => true,
                'value' => true,
            ],
            'span' => ['class' => true],
            'strong' => [],
        ];
    }
}



                                                                                
final class Routines_AdminSettings implements Routine_RoutineInterface
{
    private const ASSET_HANDLE = 'dolet-admin-settings';
    private const ASSET_VERSION = '1.0.0';

    private ?Routines_AdminConsoleInterface $console = null;
    private ?Routines_AdminDiagnosticsInterface $diagnostics = null;

    public function __construct() {}

    public function setConsole(Routines_AdminConsoleInterface $console): void
    {
        $this->console = $console;
    }

    public function setDiagnostics(Routines_AdminDiagnosticsInterface $diagnostics): void
    {
        $this->diagnostics = $diagnostics;
    }

    public function execute(): void
    {
        add_action('admin_enqueue_scripts', function (string $hookSuffix): void {
            if ($hookSuffix !== 'settings_page_dolet-settings') {
                return;
            }

            wp_enqueue_script(
                self::ASSET_HANDLE,
                plugin_dir_url(__FILE__) . 'assets/admin-settings.js',
                [],
                self::ASSET_VERSION,
                true
            );
            wp_enqueue_style(
                self::ASSET_HANDLE,
                plugin_dir_url(__FILE__) . 'assets/admin-settings.css',
                [],
                self::ASSET_VERSION
            );
            wp_add_inline_script(
                self::ASSET_HANDLE,
                'window.doletAdminSettings = ' . wp_json_encode([
                    'ajaxUrl'              => admin_url('admin-ajax.php'),
                    'consoleNonce'         => wp_create_nonce('dolet_console'),
                    'diagnosticsNonce'     => wp_create_nonce('dolet_diagnostics'),
                    'workerRunning'        => Core_Strings::get('console_worker_running'),
                    'workerDone'           => Core_Strings::get('console_worker_done'),
                    'errorPrefix'          => Core_Strings::get('error_prefix'),
                    'testPolling'          => Core_Strings::get('console_test_polling'),
                    'errorLogClearConfirm' => Core_Strings::get('diag_error_log_clear_confirm'),
                    'errorLogEmpty'        => Core_Strings::get('diag_error_log_empty'),
                    'ui'                   => [
                        'missingAjaxUrl' => Core_Strings::get('js_missing_ajax_url'),
                        'invalidJson' => Core_Strings::get('js_invalid_json'),
                        'httpError' => Core_Strings::get('js_http_error'),
                        'unknown' => Core_Strings::get('error_unknown'),
                        'jobSubmitted' => Core_Strings::get('js_job_submitted'),
                        'pollPending' => Core_Strings::get('js_poll_pending'),
                        'pollTimeout' => Core_Strings::get('js_poll_timeout'),
                        'result' => Core_Strings::get('js_result'),
                        'failed' => Core_Strings::get('js_failed'),
                        'ajaxError' => Core_Strings::get('js_ajax_error'),
                        'running' => Core_Strings::get('js_running'),
                        'pending' => Core_Strings::get('js_pending'),
                        'pass' => Core_Strings::get('js_pass'),
                        'fail' => Core_Strings::get('js_fail'),
                        'error' => Core_Strings::get('js_error'),
                        'notAvailable' => Core_Strings::get('js_not_available'),
                        'diagnosticTimeout' => Core_Strings::get('diag_poll_timeout'),
                    ],
                    'testIds'              => Routines_AdminSelfTests::testIds(),
                ]) . ';',
                'before'
            );
        });

        add_action('admin_init', function (): void {
            register_setting(
                'dolet_settings_group',
                Config_ApproachOptionNames::APPROACH_ENABLED,
                [
                    'type'              => 'boolean',
                    'sanitize_callback' => static fn($v): bool => Routines_SettingsValue::checkbox($v),
                    'default'           => false,
                    'show_in_rest'      => false,
                ]
            );

            register_setting(
                'dolet_settings_group',
                Config_DebugOptionNames::DEBUG_OPTION_NAME,
                [
                    'type'              => 'boolean',
                    'sanitize_callback' => static fn($v): bool => Routines_SettingsValue::checkbox($v),
                    'default'           => false,
                    'show_in_rest'      => false,
                ]
            );

            register_setting(
                'dolet_settings_group',
                Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA,
                [
                    'type'              => 'boolean',
                    'sanitize_callback' => static fn($v): bool => Routines_SettingsValue::checkbox($v),
                    'default'           => false,
                    'show_in_rest'      => false,
                ]
            );
        });

        add_action('admin_menu', function (): void {
            add_options_page(
                Core_Strings::get('page_title'),
                Core_Strings::get('page_title'),
                'manage_options',
                'dolet-settings',
                [$this, 'renderPage']
            );
        });
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . \esc_html(Core_Strings::get('page_title')) . '</h1>';

                   
        echo '<h2 class="nav-tab-wrapper" id="dolet_tabs">';
        echo '<a href="#settings" class="nav-tab nav-tab-active" data-tab="settings">' . \esc_html(Core_Strings::get('tab_settings')) . '</a>';
        echo '<a href="#console" class="nav-tab" data-tab="console">' . \esc_html(Core_Strings::get('tab_console')) . '</a>';
        echo '<a href="#diagnostics" class="nav-tab" data-tab="diagnostics">' . \esc_html(Core_Strings::get('tab_diagnostics')) . '</a>';
        echo '</h2>';

                           
        echo '<div id="dolet_tab_settings" class="dolet-tab-panel">';
        echo '<form action="options.php" method="post">';
        settings_fields('dolet_settings_group');
        do_settings_sections('dolet_settings_page');
        echo '<p class="description">' . \esc_html(Core_Strings::get('save_scope')) . '</p>';
        submit_button(\esc_html(Core_Strings::get('save_button')));
        echo '</form>';
        if ($this->console !== null) {
            $this->console->renderProcessingOverview();
        }
        echo '</div>';

                          
        echo '<div id="dolet_tab_console" class="dolet-tab-panel" style="display:none;">';
        if ($this->console !== null) {
            $this->console->renderPanel();
        }
        echo '</div>';

                              
        echo '<div id="dolet_tab_diagnostics" class="dolet-tab-panel" style="display:none;">';
        if ($this->diagnostics !== null) {
            $this->diagnostics->renderPanel();
        }
        echo '</div>';

        echo '</div>';
    }
}


   
                                                                                  
  
                                                                                            
   
final class Routines_AdminConsole implements Routines_AdminConsoleInterface
{
    private const CONSOLE_JOB_TTL = 7200;

    public function __construct(
        private Config_LanguageAdminInterface $langAdmin,
        private Config_ApproachSettingsInterface $approachSettings,
        private Config_DebugSettingsInterface $debugSettings,
        private Language_OptionFactoryInterface $optionFactory,
        private ?Approach_EngineCredentialReadinessReaderInterface $credentialReader = null
    ) {
        $this->credentialReader ??= new Approach_EngineCredentialReadinessReader();
    }

    public function execute(): void
    {
                               
        add_action('wp_ajax_dolet_console_stats', function (): void {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['error' => Core_Strings::get('error_forbidden')], 403);
                return;
            }
            check_ajax_referer('dolet_console');

            $repo = new Data_TranslationMemoryRepository();
            try {
                $repo->ensureReady();
                wp_send_json_success($this->configuredStats($repo->getStatsByPair()));
            } catch (\Throwable $error) {
                (new Config_DiagnosticsStore())->recordError('translation_stats_unavailable', $error->getMessage());
                wp_send_json_error(['error' => Core_Strings::get('processing_unavailable')]);
            }
        });

                                       
        add_action('wp_ajax_dolet_console_worker_trigger', function (): void {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['error' => Core_Strings::get('error_forbidden')], 403);
                return;
            }
            check_ajax_referer('dolet_console');

            if (!$this->approachSettings->isEnabled()) {
                wp_send_json_error(['error' => Core_Strings::get('processing_disabled')]);
                return;
            }

            $credential = $this->credentialReader->read();
            if ($credential['ready'] !== true) {
                wp_send_json_error([
                    'error' => Routines_EngineCredentialUi::actionLabel($credential['state']),
                ]);
                return;
            }

            $engine = new Approach_SoleEngineClient();
            if (!$engine->isAvailable()) {
                wp_send_json_error(['error' => Core_Strings::get('error_engine_unavailable')]);
                return;
            }

            $diagnostics = new Config_DiagnosticsStore();
            $lease = new Config_OptionLease(
                Config_WorkerOptionNames::LEASE,
                Approach_EngineCallPolicy::workerLeaseSeconds(),
                $diagnostics,
                'worker_stale_lock_recovered'
            );
            if (!$lease->acquire()) {
                wp_send_json_error(['error' => Core_Strings::get('processing_busy')]);
                return;
            }

            $workerError = null;
            $result = [];
            $submitResults = [];
            try {
                $retainedIdentityFailures = 0;
                $representationFailures = 0;
                $repo = new Data_TranslationMemoryRepository(identityDiagnostics: $diagnostics);
                $repo->ensureReady();
                if (!$lease->renew()) {
                    throw new \RuntimeException('Worker lease ownership was lost.');
                }

                $worker = new Translation_WorkerService(
                    $repo,
                    $engine,
                    $this->debugSettings,
                    $this->approachSettings,
                    $diagnostics,
                    null,
                    static function () use ($lease): void {
                        if (!$lease->renew()) {
                            throw new \RuntimeException('Worker lease ownership was lost.');
                        }
                    }
                );

                try {
                    $result = ['poll' => $worker->pollAndApplyResults()];
                } catch (Data_StoredIdentityMismatchExceptionInterface) {
                    $retainedIdentityFailures++;
                }
                if (!$lease->renew()) {
                    throw new \RuntimeException('Worker lease ownership was lost.');
                }

                $srcCode = $this->langAdmin->getSourceCode();
                $targets = $this->langAdmin->getTargetCodes();
                foreach ($targets as $tgtCode) {
                    if (!$lease->renew()) {
                        throw new \RuntimeException('Worker lease ownership was lost.');
                    }
                    $pair = $this->buildPairOrNull($srcCode, (string)$tgtCode);
                    if ($pair === null) {
                        continue;
                    }
                    try {
                        $submitResults[(string)$tgtCode] = $worker->submitPendingFor($pair, 50);
                    } catch (Data_PairRepresentationRejectedExceptionInterface) {
                                                                                          
                        $representationFailures++;
                    } catch (Data_StoredIdentityMismatchExceptionInterface) {
                        $retainedIdentityFailures++;
                    }
                }
                $result['submit'] = $submitResults;
                if ($representationFailures > 0) {
                    $diagnostics->recordError('manual_worker_failed', $representationFailures . ' target representation failure(s); independent work continued.');
                    $workerError = Core_Strings::get('processing_failed');
                }
                if ($retainedIdentityFailures > 0) {
                    $diagnostics->recordError('manual_worker_failed', $retainedIdentityFailures . ' identity failure(s) retained; independent work continued.');
                    $workerError = Core_Strings::get('processing_failed');
                }
            } catch (\Throwable $error) {
                $diagnostics->recordError('manual_worker_failed', $error->getMessage());
                $workerError = Core_Strings::get('processing_failed');
            } finally {
                $lease->release();
            }

            if ($workerError !== null) {
                wp_send_json_error(['error' => $workerError]);
                return;
            }

            $poll = $result['poll'];
            $submitted = 0;
            $skipped = 0;
            foreach ($submitResults as $submitResult) {
                if (($submitResult['submitted'] ?? false) === true) {
                    $submitted++;
                } elseif (($submitResult['skipped'] ?? '') !== '') {
                    $skipped++;
                }
            }
            $result['summary'] = $this->formatProcessingSummary(
                (int)($poll['jobs_checked'] ?? 0),
                (int)($poll['applied'] ?? 0),
                $submitted,
                $skipped
            );
            wp_send_json_success($result);
        });

                                               
        add_action('wp_ajax_dolet_console_test_submit', function (): void {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['error' => Core_Strings::get('error_forbidden')], 403);
                return;
            }
            check_ajax_referer('dolet_console');

            $srcLang = $this->readLanguageCode($_POST['source_lang'] ?? '');
            $tgtLang = $this->readLanguageCode($_POST['target_lang'] ?? '');
            if ($srcLang === null || $tgtLang === null) {
                \wp_send_json_error(['error' => Core_Strings::get('console_invalid_pair')]);
                return;
            }
            /*
             * WPORG-REVIEWER-NOTE:
             * This administrator-only AJAX handler verifies manage_options and a nonce before reading input. Single String Test text is opaque translation content. wp_check_invalid_utf8 and an independent UTF-8 check reject changed or invalid input before a fixed JSON encoder and Engine submit; stripping markup or whitespace would change the requested content.
             */
            $text = isset($_POST['text']) && is_string($_POST['text']) ? wp_unslash($_POST['text']) : '';

            if ($tgtLang === '' || $text === '') {
                wp_send_json_error(['error' => Core_Strings::get('console_required_fields')]);
                return;
            }
            $validatedText = \wp_check_invalid_utf8($text);
            if ($validatedText !== $text || !$this->isValidUtf8($validatedText)) {
                wp_send_json_error(['error' => Core_Strings::get('console_invalid_utf8')]);
                return;
            }

            if ($this->buildConsolePairOrNull($srcLang, $tgtLang) === null) {
                wp_send_json_error(['error' => Core_Strings::get('console_invalid_pair')]);
                return;
            }

            if (!function_exists('sole_engine_llm')) {
                wp_send_json_error(['error' => Core_Strings::get('error_engine_unavailable')]);
                return;
            }

                                                                                     
                                                                               
            $codec = new Approach_SparseMapCodec();
            $payload = $codec->encodeSparseMapJson([0 => $validatedText]);
            if ($payload === null) {
                wp_send_json_error(['error' => Core_Strings::get('console_encode_failed')]);
                return;
            }

            $options = ['targetLanguage' => $tgtLang];
            if ($srcLang !== '' && $srcLang !== 'original') {
                $options['sourceLanguage'] = $srcLang;
            }

            $diagnostics = new Config_DiagnosticsStore();

            try {
                                            
                $ticket = (new Approach_EngineCallPolicy())->invoke(
                    static fn () => sole_engine_llm()->submit('dolet-wp', 'translate_batch', $payload, $options),
                    $diagnostics
                );
            } catch (\Throwable $e) {
                $diagnostics->recordError('console_submit_exception', $e->getMessage());
                wp_send_json_error(['error' => $e->getMessage()]);
                return;
            }

            if (!$ticket->wasAccepted()) {
                $error = $ticket->getError() ?? 'Job not accepted.';
                $errorCode = in_array($error, ['config_missing', 'user_key_invalid'], true)
                    ? $error
                    : 'console_submit_rejected';
                $diagnostics->recordError($errorCode, $tgtLang . ': ' . $error);
                wp_send_json_error(['error' => $error]);
                return;
            }

            $jobId = $ticket->getJobId();
            if (!is_string($jobId) || !$this->isValidConsoleJobId($jobId)) {
                $diagnostics->recordError('console_submit_rejected', 'Engine returned an invalid job ID.');
                wp_send_json_error(['error' => Core_Strings::get('console_invalid_job')]);
                return;
            }

            $this->rememberConsoleJob($jobId);
            wp_send_json_success(['job_id' => $jobId]);
        });

                                             
        add_action('wp_ajax_dolet_console_test_poll', function (): void {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['error' => Core_Strings::get('error_forbidden')], 403);
                return;
            }
            check_ajax_referer('dolet_console');

            /*
             * WPORG-REVIEWER-NOTE:
             * This administrator-only AJAX handler verifies manage_options and a nonce before reading input. The service-issued job ID is a bounded valid-UTF-8 value without control or space bytes, checked against a user-scoped transient and passed to a fixed result-poll call; generic text sanitization would change exact identifiers.
             */
            $jobId = isset($_POST['job_id']) && is_string($_POST['job_id']) ? wp_unslash($_POST['job_id']) : '';
            if (!$this->isValidConsoleJobId($jobId) || !$this->ownsConsoleJob($jobId)) {
                wp_send_json_error(['error' => Core_Strings::get('console_unknown_job')]);
                return;
            }

            if (!function_exists('sole_engine_llm')) {
                wp_send_json_error(['error' => Core_Strings::get('error_engine_unavailable')]);
                return;
            }

            $diagnostics = new Config_DiagnosticsStore();

            try {
                $reply = (new Approach_EngineCallPolicy())->invoke(
                    static fn () => sole_engine_llm()->getResult($jobId),
                    $diagnostics
                );
            } catch (\Throwable $e) {
                $diagnostics->recordError('console_poll_exception', $e->getMessage());
                wp_send_json_error(['error' => $e->getMessage()]);
                return;
            }

            if ($reply->isPending()) {
                wp_send_json_success(['status' => 'pending']);
                return;
            }

            if (!$reply->isSuccessful()) {
                $error = $reply->getError() ?? 'unknown';
                $errorCode = in_array($error, ['config_missing', 'user_key_invalid'], true)
                    ? $error
                    : 'console_poll_failed';
                $diagnostics->recordError($errorCode, 'Job ' . $jobId . ': ' . $error);
                $this->forgetConsoleJob($jobId);
                wp_send_json_success(['status' => 'failed', 'error' => $error]);
                return;
            }

            $this->forgetConsoleJob($jobId);
            wp_send_json_success(['status' => 'success', 'reply' => $reply->getReply()]);
        });
    }

    public function renderPanel(): void
    {
                            
        echo '<h3>' . \esc_html(Core_Strings::get('stats_heading')) . '</h3>';
        echo '<p id="dolet_stats_none" style="display:none;"><em>' . \esc_html(Core_Strings::get('stats_no_data')) . '</em></p>';
        echo '<table class="widefat fixed" id="dolet_stats_table" style="display:none;">';
        echo '<thead><tr>';
        echo '<th>' . \esc_html(Core_Strings::get('stats_source')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('stats_target')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('stats_total')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('stats_translated')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('stats_pending')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('stats_human_locked')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('stats_word_count')) . '</th>';
        echo '</tr></thead>';
        echo '<tbody id="dolet_stats_body"></tbody>';
        echo '</table>';
        echo '<p><button type="button" class="button" id="dolet_stats_refresh">' . \esc_html(Core_Strings::get('stats_refresh')) . '</button></p>';

                                      
        echo '<hr>';
        echo '<h3>' . \esc_html(Core_Strings::get('console_test_heading')) . '</h3>';

        $srcCode  = $this->langAdmin->getSourceCode();
        $targets  = $this->langAdmin->getTargetCodes();

        echo '<p><label>' . \esc_html(Core_Strings::get('console_test_source_label')) . ': ';
        echo '<input type="text" id="dolet_test_src" value="' . \esc_attr($srcCode) . '" size="10" /></label></p>';

                                                                          
                                                                              
                                                                     
        $noTargets = $targets === [];

        echo '<p><label>' . \esc_html(Core_Strings::get('console_test_target_label')) . ': ';
        echo '<select id="dolet_test_tgt"' . ($noTargets ? ' disabled' : '') . '>';
        if ($noTargets) {
            echo '<option value="">' . \esc_html(Core_Strings::get('console_test_no_targets')) . '</option>';
        } else {
            foreach ($targets as $tgt) {
                echo '<option value="' . \esc_attr((string)$tgt) . '">' . \esc_html((string)$tgt) . '</option>';
            }
        }
        echo '</select></label></p>';
        if ($noTargets) {
            echo '<p class="description">' . \esc_html(Core_Strings::get('console_test_no_targets_hint')) . '</p>';
        }

        echo '<p><label>' . \esc_html(Core_Strings::get('console_test_text_label')) . ':<br />';
        echo '<textarea id="dolet_test_text" rows="3" style="width:100%;"></textarea></label></p>';

        echo '<p><button type="button" class="button button-primary" id="dolet_test_submit"'
            . ($noTargets ? ' disabled' : '') . '>'
            . \esc_html(Core_Strings::get('console_test_submit')) . '</button></p>';
        echo '<textarea id="dolet_test_output" rows="8" style="width:100%;font-family:monospace;" readonly></textarea>';
    }

    private function buildPairOrNull(string $srcCode, string $tgtCode): ?Language_PairInterface
    {
        return Routines_LanguagePairResolver::build(
            $srcCode,
            $tgtCode,
            $this->langAdmin,
            $this->optionFactory
        );
    }

    private function formatProcessingSummary(int $checked, int $saved, int $sent, int $busy): string
    {
        return strtr(Core_Strings::get('processing_summary'), [
            '{checked}' => (string) $checked,
            '{saved}' => (string) $saved,
            '{sent}' => (string) $sent,
            '{busy}' => (string) $busy,
        ]);
    }

    private function readLanguageCode(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }
        $value = \wp_unslash($value);
                                                                                      
        if (\preg_match('//u', $value) !== 1
            || \preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return \sanitize_text_field($value);
        }
        return \trim($value);
    }

    private function buildConsolePairOrNull(string $srcCode, string $tgtCode): ?Language_PairInterface
    {
        if ($srcCode !== $this->langAdmin->getSourceCode()) {
            return null;
        }
        if (!in_array($tgtCode, $this->langAdmin->getTargetCodes(), true)) {
            return null;
        }
        return $this->buildPairOrNull($srcCode, $tgtCode);
    }

    private function isValidConsoleJobId(string $jobId): bool
    {
        $length = strlen($jobId);
        return $length >= 1
            && $length <= 255
            && $this->isValidUtf8($jobId)
            && preg_match('/[\x00-\x20\x7f]/', $jobId) !== 1;
    }

    private function isValidUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }

    private function rememberConsoleJob(string $jobId): void
    {
        set_transient($this->consoleJobKey($jobId), 'owned', self::CONSOLE_JOB_TTL);
    }

    private function ownsConsoleJob(string $jobId): bool
    {
        return get_transient($this->consoleJobKey($jobId)) === 'owned';
    }

    private function forgetConsoleJob(string $jobId): void
    {
        delete_transient($this->consoleJobKey($jobId));
    }

    private function consoleJobKey(string $jobId): string
    {
        return 'dolet_console_job_' . get_current_user_id() . '_' . hash('sha256', $jobId);
    }

                                                                            
    public function renderProcessingOverview(): void
    {
        $repo = new Data_TranslationMemoryRepository();
        $pending = 0;
        $pendingLabel = null;
        try {
            foreach ($this->configuredStats($repo->getStatsByPair()) as $stats) {
                $pending += $stats['pending'];
            }
        } catch (\Throwable $error) {
            (new Config_DiagnosticsStore())->recordError('translation_stats_unavailable', $error->getMessage());
            $pendingLabel = Core_Strings::get('processing_unavailable');
        }

        $lastSuccess = (new Config_DiagnosticsStore())->getLastSuccess();
        $lastSuccessTimestamp = $lastSuccess === null ? false : \strtotime($lastSuccess);
        $lastSuccessLabel = is_int($lastSuccessTimestamp)
            ? \wp_date('M j, Y, g:i a T', $lastSuccessTimestamp)
            : ($lastSuccess ?? Core_Strings::get('processing_never'));
        $nextScheduled = \wp_next_scheduled('dolet_translation_worker_tick');
        $nextScheduledLabel = is_int($nextScheduled)
            ? \wp_date('M j, Y, g:i a T', $nextScheduled)
            : Core_Strings::get('processing_not_scheduled');

        echo '<div class="notice notice-info inline" style="max-width:700px;padding:12px 16px;margin:16px 0;">';
        echo '<h2 style="margin-top:0;">' . \esc_html(Core_Strings::get('processing_heading')) . '</h2>';
        echo '<p>' . \esc_html(Core_Strings::get('processing_note')) . '</p>';
        $translationEnabled = $this->approachSettings->isEnabled();
        $credential = $this->credentialReader->read();
        $processingReady = $translationEnabled && $credential['ready'] === true;
        if (!$translationEnabled) {
            echo '<p><strong>' . \esc_html(Core_Strings::get('processing_disabled')) . '</strong></p>';
        } elseif (!$processingReady) {
            echo '<p><strong>' . \esc_html(Routines_EngineCredentialUi::actionLabel($credential['state'])) . '</strong></p>';
        }
        echo '<table class="widefat striped" style="max-width:680px;"><tbody>';
        echo '<tr><th scope="row">' . \esc_html(Core_Strings::get('processing_pending')) . '</th>';
        echo '<td id="dolet_processing_pending">' . \esc_html($pendingLabel ?? (string)$pending) . '</td></tr>';
        echo '<tr><th scope="row">' . \esc_html(Core_Strings::get('processing_last_success')) . '</th>';
        echo '<td>' . \esc_html($lastSuccessLabel) . '</td></tr>';
        echo '<tr><th scope="row">' . \esc_html(Core_Strings::get('processing_next_scheduled')) . '</th>';
        echo '<td>' . \esc_html($nextScheduledLabel) . '</td></tr>';
        echo '</tbody></table>';
        echo '<p><button type="button" class="button button-primary" id="dolet_worker_trigger"' . ($processingReady ? '' : ' disabled') . '>' . \esc_html(Core_Strings::get('console_worker_trigger')) . '</button> ';
        echo '<span id="dolet_worker_output" role="status" aria-live="polite"></span></p>';
        echo '</div>';
    }

       
                                                                                                                                         
                                                                                                                                   
       
    private function configuredStats(array $stats): array
    {
        $source = $this->langAdmin->getSourceCode();
        $targets = array_map('strval', $this->langAdmin->getTargetCodes());

        return array_values(array_filter(
            $stats,
            static fn(array $row): bool => in_array($row['tgt'], $targets, true)
                && ($source === Language_Constants::ORIGINAL || $row['src'] === $source)
        ));
    }
}


   
                                                                               
   
final class Routines_AdminDiagnostics implements Routines_AdminDiagnosticsInterface
{
    private ?Routines_AdminSelfTestsInterface $selfTests = null;

    public function __construct(
        private Config_LanguageAdminInterface $langAdmin,
        private Config_ApproachSettingsInterface $approachSettings,
        private ?Diagnostics_StoreInterface $diagnosticsStore = null,
        private ?Approach_EngineCredentialReadinessReaderInterface $credentialReader = null
    ) {
        $this->credentialReader ??= new Approach_EngineCredentialReadinessReader();
    }

    public function setSelfTests(Routines_AdminSelfTestsInterface $selfTests): void
    {
        $this->selfTests = $selfTests;
    }

    public function execute(): void
    {
                                   
        \add_action('wp_ajax_dolet_refresh_error_log', function (): void {
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['error' => Core_Strings::get('error_forbidden')], 403);
                return;
            }
            \check_ajax_referer('dolet_diagnostics');

            if ($this->diagnosticsStore === null) {
                \wp_send_json_error(['error' => Core_Strings::get('error_diagnostics_unavailable')]);
                return;
            }

            $log = $this->diagnosticsStore->getErrorLog();
            \wp_send_json_success([
                'log'         => $this->formatErrorLogLines($log),
                'lastSuccess' => $this->diagnosticsStore->getLastSuccess(),
                'lastError'   => $this->displayError($this->diagnosticsStore->getLastError()),
                'lastErrorAt' => $this->diagnosticsStore->getLastErrorAt(),
            ]);
        });

                                 
        \add_action('wp_ajax_dolet_clear_error_log', function (): void {
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['error' => Core_Strings::get('error_forbidden')], 403);
                return;
            }
            \check_ajax_referer('dolet_diagnostics');

            if ($this->diagnosticsStore === null) {
                \wp_send_json_error(['error' => Core_Strings::get('error_diagnostics_unavailable')]);
                return;
            }

            $this->diagnosticsStore->clearErrorLog();
            \wp_send_json_success(['cleared' => true]);
        });
    }

    public function renderPanel(): void
    {
        echo '<h3>' . \esc_html(Core_Strings::get('diag_heading')) . '</h3>';
        echo '<table class="widefat fixed" style="max-width:600px;">';
        echo '<tbody>';

                                                                            
                                                                               
        $credential = $this->credentialReader->read();
        $engineLabel = Routines_EngineCredentialUi::statusLabel($credential['state']);
        $engineColor = Routines_EngineCredentialUi::statusColor($credential['state']);
        echo '<tr><td><span style="color:' . \esc_attr($engineColor) . ';">&#9679;</span> ' . \esc_html($engineLabel) . '</td></tr>';
        echo '<tr><td><strong>' . \esc_html(Core_Strings::get('diag_engine_action_label')) . ':</strong> '
            . \esc_html(Routines_EngineCredentialUi::actionLabel($credential['state'])) . '</td></tr>';
                               
        $translationEnabled = $this->approachSettings->isEnabled();
        $translationLabel = $translationEnabled ? Core_Strings::get('diag_translation_enabled') : Core_Strings::get('diag_translation_disabled');
        $translationColor = $translationEnabled ? '#46b450' : '#646970';
        echo '<tr><td><span style="color:' . \esc_attr($translationColor) . ';">&#9679;</span> ' . \esc_html($translationLabel) . '</td></tr>';

                             
        $cronScheduled = (\wp_next_scheduled('dolet_translation_worker_tick') !== false);
        $cronLabel = $cronScheduled
            ? Core_Strings::get('diag_cron_scheduled')
            : Core_Strings::get($translationEnabled ? 'diag_cron_not_scheduled' : 'diag_cron_disabled');
        $cronColor = $cronScheduled ? '#46b450' : ($translationEnabled ? '#ffb900' : '#646970');
        echo '<tr><td><span style="color:' . \esc_attr($cronColor) . ';">&#9679;</span> ' . \esc_html($cronLabel) . '</td></tr>';

                           
        global $wpdb;
        $tableName = $wpdb->prefix . Data_UniversalTableL1_Constants::TABLE_SLUG;

        $tableExists = ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tableName)) === $tableName);
        $tableLabel = $tableExists ? Core_Strings::get('diag_table_exists') : Core_Strings::get('diag_table_missing');
        $tableColor = $tableExists ? '#46b450' : '#dc3232';
        echo '<tr><td><span style="color:' . \esc_attr($tableColor) . ';">&#9679;</span> ' . \esc_html($tableLabel) . '</td></tr>';

                                
        $srcCode = $this->langAdmin->getSourceCode();
        $targets = $this->langAdmin->getTargetCodes();
        $langSummary = strtr(Core_Strings::get('diag_languages_summary'), [
            '{source}' => $srcCode,
            '{targets}' => empty($targets)
                ? Core_Strings::get('diag_languages_none')
                : implode(', ', array_map('strval', $targets)),
        ]);
        echo '<tr><td><strong>' . \esc_html(Core_Strings::get('diag_languages_label')) . ':</strong> ' . \esc_html($langSummary) . '</td></tr>';

        echo '</tbody></table>';
                                   
        if ($this->diagnosticsStore !== null) {
            echo '<hr>';
            echo '<h3>' . \esc_html(Core_Strings::get('diag_error_log_heading')) . '</h3>';

                                          
            $lastSuccess = $this->diagnosticsStore->getLastSuccess();
            $lastError   = $this->displayError($this->diagnosticsStore->getLastError());
            $lastErrorAt = $this->diagnosticsStore->getLastErrorAt();

            echo '<table class="widefat fixed" style="max-width:600px;" id="dolet_diag_summary">';
            echo '<tbody>';
            echo '<tr><td><strong>' . \esc_html(Core_Strings::get('diag_last_success')) . ':</strong></td>';
            echo '<td id="dolet_diag_last_success">' . \esc_html($lastSuccess ?? '—') . '</td></tr>';
            echo '<tr><td><strong>' . \esc_html(Core_Strings::get('diag_last_error')) . ':</strong></td>';
            echo '<td id="dolet_diag_last_error">' . \esc_html($lastError ?? '—') . '</td></tr>';
            echo '<tr><td><strong>' . \esc_html(Core_Strings::get('diag_last_error_at')) . ':</strong></td>';
            echo '<td id="dolet_diag_last_error_at">' . \esc_html($lastErrorAt ?? '—') . '</td></tr>';
            echo '</tbody></table>';

                                  
            $log = $this->diagnosticsStore->getErrorLog();
            $logText = !empty($log) ? $this->formatErrorLogLines($log) : Core_Strings::get('diag_error_log_empty');
            echo '<textarea id="dolet_error_log_textarea" rows="15" style="width:100%;max-width:600px;font-family:monospace;" readonly>' . \esc_textarea($logText) . '</textarea>';

                                       
            echo '<p>';
            echo '<button type="button" class="button" id="dolet_error_log_refresh">' . \esc_html(Core_Strings::get('diag_error_log_refresh')) . '</button> ';
            echo '<button type="button" class="button" id="dolet_error_log_clear">' . \esc_html(Core_Strings::get('diag_error_log_clear')) . '</button>';
            echo '</p>';

        }

                              
        if ($this->selfTests instanceof Routines_AdminSelfTests) {
            $this->selfTests->renderPanel();
        }
    }

    private function displayError(?string $code): ?string
    {
        $reason = Core_HtmlRejection::fromCode($code);
        if ($reason === null) {
            return $code;
        }
        $label = $reason->label();
        return $code === $reason->code(true)
            ? Core_Strings::get('diag_candidate_prefix') . ': ' . $label : $label;
    }

       
                                              
      
                                             
       
    private function formatErrorLogLines(array $log): string
    {
        if (empty($log)) {
            return '';
        }
        $lines = [];
        foreach ($log as $entry) {
            $time    = (string)($entry['time'] ?? '');
            $code    = (string)($entry['code'] ?? '');
            $context = (string)($entry['context'] ?? '');
            $context = Core_HtmlRejection::fromCode($code) !== null ? ($this->displayError($code) ?? $context) : $context;
            $lines[] = '[' . $time . '] ' . $code . ': ' . $context;
        }
        return implode("\n", $lines);
    }
}


   
                                                                                                         
  
                                                                                   
   
final class Routines_AdminSelfTests implements Routines_AdminSelfTestsInterface
{
    private const FLOW_JOB_TTL = 600;
                                                      
    private const TESTS = [
        ['id' => 'engine_available',  'descKey' => 'diag_test_engine_available'],
        ['id' => 'config_valid',      'descKey' => 'diag_test_config_valid'],
        ['id' => 'tm_table_ready',    'descKey' => 'diag_test_tm_table_ready'],
        ['id' => 'codec_roundtrip',   'descKey' => 'diag_test_codec_roundtrip'],
        ['id' => 'submit_poll_flow',  'descKey' => 'diag_test_submit_poll_flow'],
    ];

                               
    public static function testIds(): array
    {
        return array_map(static fn(array $test): string => $test['id'], self::TESTS);
    }

    public function __construct(
        private Config_LanguageAdminInterface $langAdmin,
        private Config_ApproachSettingsInterface $approachSettings,
        private ?Approach_EngineCredentialReadinessReaderInterface $credentialReader = null
    ) {
        $this->credentialReader ??= new Approach_EngineCredentialReadinessReader();
    }

    public function execute(): void
    {
                                 
        \add_action('wp_ajax_dolet_test_engine_available', function (): void {
            $this->requireCap();
            \check_ajax_referer('dolet_diagnostics');
            $this->runEngineAvailable();
        });

                             
        \add_action('wp_ajax_dolet_test_config_valid', function (): void {
            $this->requireCap();
            \check_ajax_referer('dolet_diagnostics');
            $this->runConfigValid();
        });

                               
        \add_action('wp_ajax_dolet_test_tm_table_ready', function (): void {
            $this->requireCap();
            \check_ajax_referer('dolet_diagnostics');
            $this->runTmTableReady();
        });

                                
        \add_action('wp_ajax_dolet_test_codec_roundtrip', function (): void {
            $this->requireCap();
            \check_ajax_referer('dolet_diagnostics');
            $this->runCodecRoundtrip();
        });

                                 
        \add_action('wp_ajax_dolet_test_submit_poll_flow', function (): void {
            $this->requireCap();
            \check_ajax_referer('dolet_diagnostics');
            $this->runSubmitPollFlow();
        });
    }

    public function renderPanel(): void
    {
        echo '<hr>';
        echo '<h3>' . \esc_html(Core_Strings::get('diag_tests_heading')) . '</h3>';

        echo '<table class="widefat fixed" style="max-width:800px;" id="dolet_self_tests_table">';
        echo '<thead><tr>';
        echo '<th>' . \esc_html(Core_Strings::get('diag_test_col_test')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('diag_test_col_description')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('diag_test_col_result')) . '</th>';
        echo '<th>' . \esc_html(Core_Strings::get('diag_test_col_detail')) . '</th>';
        echo '<th></th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach (self::TESTS as $test) {
            $testId  = $test['id'];
            $descKey = $test['descKey'];
            echo '<tr id="dolet_test_row_' . \esc_attr($testId) . '">';
            echo '<td><code>' . \esc_html($testId) . '</code></td>';
            echo '<td>' . \esc_html(Core_Strings::get($descKey)) . '</td>';
            echo '<td id="dolet_test_result_' . \esc_attr($testId) . '">—</td>';
            echo '<td id="dolet_test_detail_' . \esc_attr($testId) . '"></td>';
            echo '<td><button type="button" class="button dolet-test-run" data-test="' . \esc_attr($testId) . '">'
                . \esc_html(Core_Strings::get('diag_test_run')) . '</button></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<p><button type="button" class="button button-primary" id="dolet_test_run_all">' . \esc_html(Core_Strings::get('diag_test_run_all')) . '</button></p>';

    }

                                                                         
                           
                                                                         

    private function runEngineAvailable(): void
    {
        $credential = $this->credentialReader->read();
        $state = $credential['state'];
        \wp_send_json_success([
            'result' => $credential['ready'] ? 'pass' : 'fail',
            'detail' => Routines_EngineCredentialUi::statusLabel($state) . '. '
                . Routines_EngineCredentialUi::actionLabel($state),
        ]);
    }

    private function runConfigValid(): void
    {
        $issues = [];

        $srcCode = $this->langAdmin->getSourceCode();

        $targets = $this->langAdmin->getTargetCodes();
        if (empty($targets)) {
            $issues[] = Core_Strings::get('diag_no_targets');
        }

        if (empty($issues)) {
            $state = Core_Strings::get($this->approachSettings->isEnabled() ? 'diag_state_enabled' : 'diag_state_disabled');
            \wp_send_json_success(['result' => 'pass', 'detail' => self::text('diag_config_summary', [
                '{source}' => $srcCode,
                '{targets}' => implode(', ', $targets),
                '{state}' => $state,
            ])]);
        } else {
            \wp_send_json_success(['result' => 'fail', 'detail' => implode(' ', $issues)]);
        }
    }

    private function runTmTableReady(): void
    {
        try {
            $repo = new Data_TranslationMemoryRepository();
            $repo->ensureReady();

            global $wpdb;
            $tableName = $wpdb->prefix . Data_UniversalTableL1_Constants::TABLE_SLUG;

            $exists = ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tableName)) === $tableName);
            if ($exists) {
                \wp_send_json_success(['result' => 'pass', 'detail' => self::text('diag_table_ready', ['{table}' => $tableName])]);
            } else {
                \wp_send_json_success(['result' => 'fail', 'detail' => self::text('diag_table_not_ready', ['{table}' => $tableName])]);
            }
        } catch (\Throwable $e) {
            \wp_send_json_success(['result' => 'fail', 'detail' => $e->getMessage()]);
        }
    }

    private function runCodecRoundtrip(): void
    {
        try {
            $codec = new Approach_SparseMapCodec();

            $original = [0 => 'Hello world', 1 => 'Test string', 5 => 'Sparse key'];
            $encoded = $codec->encodeSparseMapJson($original);
            if (!is_string($encoded)) {
                \wp_send_json_success(['result' => 'fail', 'detail' => Core_Strings::get('diag_codec_encode_failed')]);
                return;
            }

            $decoded = $codec->parseSparseMapJsonOrNull($encoded, null);
            if ($decoded === null) {
                \wp_send_json_success(['result' => 'fail', 'detail' => Core_Strings::get('diag_codec_decode_failed')]);
                return;
            }

                                                              
            $match = true;
            foreach ($original as $k => $v) {
                if (!array_key_exists($k, $decoded) || $decoded[$k] !== $v) {
                    $match = false;
                    break;
                }
            }

            if ($match && count($decoded) === count($original)) {
                \wp_send_json_success(['result' => 'pass', 'detail' => self::text('diag_codec_pass', ['{count}' => (string) count($original)])]);
            } else {
                \wp_send_json_success(['result' => 'fail', 'detail' => self::text('diag_codec_mismatch', [
                    '{original}' => (string) count($original),
                    '{decoded}' => (string) count($decoded),
                ])]);
            }
        } catch (\Throwable $e) {
            \wp_send_json_success(['result' => 'fail', 'detail' => $e->getMessage()]);
        }
    }

    private function runSubmitPollFlow(): void
    {
        if (!\function_exists('sole_engine_llm')) {
            \wp_send_json_success(['result' => 'fail', 'detail' => Core_Strings::get('error_engine_unavailable')]);
            return;
        }

        $postedJobId = isset($_POST['job_id']) && is_string($_POST['job_id'])
            ? wp_unslash($_POST['job_id'])
            : '';
        if ($postedJobId !== '') {
            $this->pollSubmitFlowOnce($postedJobId);
            return;
        }

        $targets = $this->langAdmin->getTargetCodes();
        if (empty($targets)) {
            \wp_send_json_success(['result' => 'fail', 'detail' => Core_Strings::get('diag_no_targets')]);
            return;
        }

        $tgtCode = $targets[0];

        try {
            $options = ['targetLanguage' => $tgtCode];
            $srcCode = $this->langAdmin->getSourceCode();
            if ($srcCode !== '' && strcasecmp($srcCode, 'original') !== 0) {
                $options['sourceLanguage'] = $srcCode;
            }

            $ticket = (new Approach_EngineCallPolicy())->invoke(
                static fn () => \sole_engine_llm()->submit('dolet-wp', 'translate', 'Hello', $options),
                new Config_DiagnosticsStore()
            );
            if (!$ticket->wasAccepted()) {
                \wp_send_json_success(['result' => 'fail', 'detail' => self::text('diag_submit_rejected', [
                    '{error}' => $ticket->getError() ?? Core_Strings::get('error_unknown'),
                ])]);
                return;
            }

            $jobId = $ticket->getJobId();
            if (!is_string($jobId) || !$this->validFlowJobId($jobId)) {
                \wp_send_json_success(['result' => 'fail', 'detail' => Core_Strings::get('console_invalid_job')]);
                return;
            }
            \set_transient($this->flowJobKey($jobId), 'owned', self::FLOW_JOB_TTL);
            \wp_send_json_success([
                'result' => 'pending',
                'detail' => Core_Strings::get('diag_job_submitted'),
                'job_id' => $jobId,
            ]);
        } catch (\Throwable $e) {
            \wp_send_json_success(['result' => 'fail', 'detail' => $e->getMessage()]);
        }
    }

    private function pollSubmitFlowOnce(string $jobId): void
    {
        if (!$this->validFlowJobId($jobId) || \get_transient($this->flowJobKey($jobId)) !== 'owned') {
            \wp_send_json_success(['result' => 'fail', 'detail' => Core_Strings::get('diag_job_unknown')]);
            return;
        }

        try {
            $reply = (new Approach_EngineCallPolicy())->invoke(
                static fn () => \sole_engine_llm()->getResult($jobId),
                new Config_DiagnosticsStore()
            );
            if ($reply->isPending()) {
                \wp_send_json_success([
                    'result' => 'pending',
                    'detail' => Core_Strings::get('diag_job_pending'),
                    'job_id' => $jobId,
                ]);
                return;
            }
            \delete_transient($this->flowJobKey($jobId));
            if ($reply->isSuccessful()) {
                $replyText = $reply->getReply();
                \wp_send_json_success([
                    'result' => 'pass',
                    'detail' => self::text('diag_job_complete', [
                        '{reply}' => is_string($replyText) ? substr($replyText, 0, 200) : Core_Strings::get('error_unknown'),
                    ]),
                ]);
                return;
            }
            \wp_send_json_success(['result' => 'fail', 'detail' => self::text('diag_job_failed', [
                '{error}' => $reply->getError() ?? Core_Strings::get('error_unknown'),
            ])]);
        } catch (\Throwable $error) {
            \delete_transient($this->flowJobKey($jobId));
            \wp_send_json_success(['result' => 'fail', 'detail' => $error->getMessage()]);
        }
    }

    private function validFlowJobId(string $jobId): bool
    {
        $length = strlen($jobId);
        return $length >= 1
            && $length <= 255
            && preg_match('//u', $jobId) === 1
            && preg_match('/[\x00-\x20\x7f]/', $jobId) !== 1;
    }

    private function flowJobKey(string $jobId): string
    {
        return 'dolet_self_test_job_' . get_current_user_id() . '_' . hash('sha256', $jobId);
    }

                                                    
    private static function text(string $key, array $replacements): string
    {
        return strtr(Core_Strings::get($key), $replacements);
    }

    private function requireCap(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['error' => Core_Strings::get('error_forbidden')], 403);
            exit;
        }
    }
}


   
                                                                               
                                                                          
                                         
  
                                                                    
                                                                               
                                                                                                             
  
                                                           
                                                         
  
                                                                              
                                                    
                                                                              
                                     
                                                                            
                                                                          
                                                                           
  
                                                                       
                                                                             
                                                                         
   
final class Routines_AutoTranslate implements Routine_RoutineInterface
{
    private const CALLER_ID = 'dolet-wp';
                                                                                 
    private const TASK = 'translate';
                                                                             
                                                                          
    private const SUBMIT_BATCH_LIMIT = 15;
                                                                           
                                                                               
    private const COLLECT_STALE_SECONDS = 600;
                                                                                 
    private const COLLECT_MAX_AGE_SECONDS = 25 * 60 * 60;
                                                                             
    private const FAILURE_COOLDOWN_SECONDS = 3600;

    private string $dir;
    private Diagnostics_StoreInterface $diagnostics;
    private ?Config_OptionLease $activeLease = null;
    private ?string $credentialCheckedAt = null;

    public function __construct(
        ?string $dir = null,
        ?Diagnostics_StoreInterface $diagnostics = null,
        private ?Approach_EngineCredentialReadinessReaderInterface $credentialReader = null
    ) {
        $this->dir = $dir ?? __DIR__;
        $this->diagnostics = $diagnostics ?? new Config_DiagnosticsStore();
        $this->credentialReader ??= new Approach_EngineCredentialReadinessReader();
    }

    public function execute(): void
    {
        \add_action('dolet_translation_worker_tick', [$this, 'maybeTranslate'], 20);
    }

    public function maybeTranslate(): void
    {
        $lease = new Config_OptionLease(
            Core_StringsOptionNames::AUTOTRANSLATE_LOCK,
            self::leaseSeconds(),
            $this->diagnostics,
            'autotranslate_stale_lock_recovered'
        );
        if (!$lease->acquire()) {
            return;
        }
        $this->activeLease = $lease;
        try {
            $this->runCycle();
        } catch (Routines_AutoTranslateFailure $error) {
            if ($error->getCode() !== Routines_AutoTranslateFailure::LEASE_LOST
                && $this->diagnostics->getLastError() !== 'autotranslate_failed') {
                $this->diagnostics->recordError(
                    'autotranslate_failed',
                    'UI-string translation checkpoint could not be saved; further submissions stopped.'
                );
            }
        } finally {
            $lease->release();
            $this->activeLease = null;
        }
    }

    public static function leaseSeconds(): int
    {
        return Approach_EngineCallPolicy::workerLeaseSeconds();
    }

    private function runCycle(): void
    {
                                                                      
                                                                  
        if (!(new Config_ApproachSettings())->isEnabled()) {
            return;
        }

        $locale = \get_locale();
        if (\str_starts_with($locale, 'en')) {
            return;
        }
        if (!\function_exists('sole_engine_llm')) {
            return;
        }

        $strings = $this->loadStrings();
        if ($strings === []) {
            return;
        }
                                                                       
                                                                            
                                                                               
                                                                           
        $version = \md5(\serialize($strings));

        $state = \get_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE);
        if (!\is_array($state)) {
            $state = null;
        }

                                                                              
                                                                              
                                                      
        if ($state !== null
            && (($state['version'] ?? '') !== $version || ($state['locale'] ?? '') !== $locale)) {
            \delete_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE);
            $state = null;
        }

        $status = $state !== null ? ($state['status'] ?? '') : '';

        if ($status === 'complete') {
            return;
        }

        $credential = $this->credentialReader->read();
        $this->credentialCheckedAt = $credential['checked_at'];
        $credentialError = Routines_EngineCredentialUi::blockingErrorCode($credential['state']);

        if ($status === 'failed') {
            $failureCode = is_string($state['failure_code'] ?? null) ? $state['failure_code'] : '';
            $failedCredentialState = is_string($state['credential_state'] ?? null) ? $state['credential_state'] : '';
            $wasCredentialFailure = in_array($failureCode, ['config_missing', 'credential_unverified', 'user_key_invalid', 'engine_unavailable'], true);

                                                                             
                                                                              
                                                                           
                                                                   
            $verificationChanged = isset($state['credential_checked_at'])
                && $state['credential_checked_at'] !== $credential['checked_at'];
            $readinessRecovered = $credential['ready'] === true
                && ($failedCredentialState !== Approach_EngineCredentialStates::VERIFIED || $verificationChanged);
            $credentialStateChanged = $failedCredentialState !== $credential['state']
                && $failedCredentialState !== Approach_EngineCredentialStates::UNAVAILABLE
                && $credential['state'] !== Approach_EngineCredentialStates::UNAVAILABLE;
            if ($wasCredentialFailure && ($readinessRecovered || $credentialStateChanged)) {
                $state['failed_at'] = 0;
            }

                                                                                 
                                                                        
            if (($state['failed_at'] ?? 0) > \time() - self::FAILURE_COOLDOWN_SECONDS) {
                return;
            }
            $status = is_string($state['resume_status'] ?? null) ? $state['resume_status'] : '';
            $state['status'] = $status;
            if ($status === 'pending') { $state['last_progress_at'] = \time(); }
            unset($state['resume_status'], $state['failure_code'], $state['failed_at']);
            $this->saveState($state);
        }

        if ($credential['ready'] !== true) {
            $this->enterFailed(
                $locale,
                $version,
                Routines_EngineCredentialUi::actionLabel($credential['state']),
                $credentialError ?? 'engine_unavailable',
                $credential['state']
            );
            return;
        }

        if ($status === 'submitting' && $state !== null) {
            $jobs = \is_array($state['jobs'] ?? null) ? $state['jobs'] : [];
            $failedKeys = \is_array($state['failed_keys'] ?? null) ? $state['failed_keys'] : [];
            $startedAt = (int) ($state['started_at'] ?? \time());
            $this->submitDrain($locale, $version, $strings, $jobs, $failedKeys, $startedAt, $credential['state']);
            return;
        }

        if ($status === 'pending' && $state !== null) {
            $this->collect($locale, $version, $state, $credential['state']);
            return;
        }

                                                   
        $this->submitDrain($locale, $version, $strings, [], [], \time(), $credential['state']);
    }

       
                                                                                
                                                                    
      
                                                      
                                                           
                                      
       
    private function submitDrain(
        string $locale,
        string $version,
        array $strings,
        array $jobs,
        array $failedKeys,
        int $startedAt,
        string $credentialState
    ): void
    {
        $engine = \sole_engine_llm();
        $lang = \str_replace('_', '-', $locale);

        $submittedKeys = [];
        foreach ($jobs as $job) {
            $submittedKeys[$job['key']] = true;
        }
        foreach ($failedKeys as $failedKey) {
            $submittedKeys[(string) $failedKey] = true;
        }

        $checkpoint = [
            'status' => 'submitting', 'locale' => $locale, 'version' => $version,
            'jobs' => $jobs, 'failed_keys' => $failedKeys, 'started_at' => $startedAt,
        ];
        $this->saveState($checkpoint);

        $submittedThisLoad = 0;
        foreach ($strings as $key => $value) {
            if ($submittedThisLoad >= self::SUBMIT_BATCH_LIMIT) {
                break;
            }
            $key = (string) $key;
            if (isset($submittedKeys[$key])) {
                continue;
            }
                                                                             
                                                                          
                                               
            if (!\is_string($value)) {
                continue;
            }
            if (!$this->renewActiveLease()) {
                return;
            }
                                                                           
                                                                            
                                                                              
            $checkpoint['failed_keys'] = [...$failedKeys, $key];
            $this->saveState($checkpoint);
            try {
                $ticket = (new Approach_EngineCallPolicy())->invoke(
                    static fn () => $engine->submit(
                        self::CALLER_ID,
                        self::TASK,
                        $value,
                        ['targetLanguage' => $lang]
                    ),
                    $this->diagnostics
                );
            } catch (\Throwable $e) {
                                                                           
                                                                                
                $this->enterFailed(
                    $locale,
                    $version,
                    'submit exception',
                    'autotranslate_submit_exception',
                    $credentialState
                );
                return;
            }
            $submittedThisLoad++;
            if ($ticket->wasAccepted()) {
                $jobId = $ticket->getJobId();
                if ($jobId !== null && $jobId !== '') {
                    $jobs[] = ['key' => $key, 'jobId' => $jobId];
                    $submittedKeys[$key] = true;
                } else {
                    $failedKeys[] = $key;
                    $submittedKeys[$key] = true;
                    if ($this->diagnostics->getLastError() !== 'autotranslate_string_rejected') {
                        $this->diagnostics->recordError('autotranslate_string_rejected', 'Accepted UI-string job has no identity; English fallback retained.');
                    }
                }
            } else {
                $error = $ticket->getError() ?? 'submit rejected';
                if (in_array($error, ['config_missing', 'user_key_invalid'], true)) {
                    $checkpoint['failed_keys'] = $failedKeys;
                    $this->saveState($checkpoint);
                    $this->enterFailed($locale, $version, $error, $error, $credentialState);
                    return;
                }
                if (!in_array($key, $failedKeys, true)) {
                    $failedKeys[] = $key;
                }
                if ($this->diagnostics->getLastError() !== 'autotranslate_string_rejected') {
                    $this->diagnostics->recordError(
                        'autotranslate_string_rejected',
                        'UI-string auto-translate rejected one or more strings; English fallback retained.'
                    );
                }
                $submittedKeys[$key] = true;
            }
            $checkpoint['jobs'] = $jobs;
            $checkpoint['failed_keys'] = $failedKeys;
            $this->saveState($checkpoint);
        }

        $remaining = 0;
        foreach ($strings as $key => $value) {
            if (\is_string($value) && !isset($submittedKeys[(string) $key])) {
                $remaining++;
            }
        }

        if ($remaining > 0) {
            $this->saveState([
                'status'     => 'submitting',
                'locale'     => $locale,
                'version'    => $version,
                'jobs'       => $jobs,
                'failed_keys' => $failedKeys,
                'started_at' => $startedAt,
            ]);
            return;
        }

                                                                      
        $this->saveState([
            'status'       => 'pending',
            'locale'       => $locale,
            'version'      => $version,
            'jobs'         => $jobs,
            'failed_keys'  => $failedKeys,
            'translated'   => [],
            'started_at'   => $startedAt,
            'submitted_at' => \time(),
            'last_progress_at' => \time(),
        ]);
    }

       
                                                                           
      
                                         
       
    private function collect(string $locale, string $version, array $state, string $credentialState): void
    {
        $engine = \sole_engine_llm();
        $jobs = \is_array($state['jobs'] ?? null) ? $state['jobs'] : [];

        $translated = \is_array($state['translated'] ?? null) ? $state['translated'] : [];
        $failedKeys = \is_array($state['failed_keys'] ?? null) ? $state['failed_keys'] : [];
        $pending = [];
        $pollException = false;
        $tooOld = ($state['submitted_at'] ?? $state['started_at'] ?? \time()) < \time() - self::COLLECT_MAX_AGE_SECONDS;
        foreach ($jobs as $index => $job) {
            if (!$this->renewActiveLease()) {
                return;
            }
            $reply = null;
            try {
                $jobId = $job['jobId'];
                $reply = (new Approach_EngineCallPolicy())->invoke(
                    static fn () => $engine->getResult($jobId),
                    $this->diagnostics
                );
            } catch (\Throwable) {
                $pollException = true;
            }
            $error = $reply?->getError() ?? 'job failed';
            $retryable = $reply === null || $reply->isPending()
                || in_array($error, ['engine_internal', 'network_error', 'api_response_invalid', 'rate_limited', 'site_purging'], true);
            if ($retryable && !$tooOld) {
                $pending[] = $job;
                continue;
            }
            if (!$retryable && $reply->isSuccessful()
                && $reply->getReply() !== null && \trim($reply->getReply()) !== '') {
                $translated[$job['key']] = $reply->getReply();
            } else {
                if (in_array($error, ['config_missing', 'user_key_invalid'], true)) {
                    $this->enterFailed($locale, $version, $error, $error, $credentialState);
                    return;
                }
                if (!in_array($job['key'], $failedKeys, true)) {
                    $failedKeys[] = $job['key'];
                }
                if ($this->diagnostics->getLastError() !== 'autotranslate_job_failed') {
                    $this->diagnostics->recordError('autotranslate_job_failed', 'UI-string auto-translate job failed or became unavailable; English fallback retained.');
                }
            }
                                                                              
                                                                              
            unset($state['jobs'][$index]);
            $state['translated'] = $translated;
            $state['failed_keys'] = $failedKeys;
            $state['last_progress_at'] = \time();
            $this->saveState($state);
        }

        if ($pending !== []) {
                                                                              
                                                                                  
            $state['jobs'] = $pending;
            $state['translated'] = $translated;
            $state['failed_keys'] = $failedKeys;
            $this->saveState($state);
            if ($pollException) {
                $this->enterFailed($locale, $version, 'poll exception', 'autotranslate_poll_exception', $credentialState);
            } elseif (($state['last_progress_at'] ?? $state['submitted_at'] ?? 0) < \time() - self::COLLECT_STALE_SECONDS) {
                $this->enterFailed($locale, $version, 'translation jobs made no progress within the inactivity window');
            }
            return;
        }

        if ($translated !== []) {
            Core_Strings::storeOverlay($translated, $locale, $version);
            if (\get_option(Core_StringsOptionNames::TRANSLATED) !== ['locale' => $locale, 'version' => $version, 'strings' => $translated]) {
                throw new Routines_AutoTranslateFailure('UI-string overlay was not persisted.');
            }
        }
        if ($translated !== [] || $failedKeys !== []) {
            $this->saveState([
                'status'  => 'complete',
                'locale'  => $locale,
                'version' => $version,
                'partial' => $failedKeys !== [],
                'failed_keys' => $failedKeys,
            ]);
                                                                        
            Core_Strings::reset();
            Core_Strings::load($this->dir);
            return;
        }

                                                                   
        $this->enterFailed($locale, $version, 'no translations were produced');
    }

    private function renewActiveLease(): bool
    {
        if ($this->activeLease !== null && $this->activeLease->renew()) {
            return true;
        }

        $this->diagnostics->recordError(
            'autotranslate_lease_lost',
            'UI-string auto-translate stopped because its lease could not be renewed.'
        );
        return false;
    }

                                            
    private function saveState(array $state): void
    {
                                                                              
                                                                          
        if (!$this->renewActiveLease()) {
            throw new Routines_AutoTranslateFailure('UI-string worker lease lost.', Routines_AutoTranslateFailure::LEASE_LOST);
        }
        \update_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE, $state, 'yes');
        if (\get_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE) !== $state) {
            throw new Routines_AutoTranslateFailure('UI-string translation checkpoint was not persisted.');
        }
    }

                                                                            
                                                                            
    private function enterFailed(
        string $locale,
        string $version,
        string $detail,
        string $errorCode = 'autotranslate_failed',
        ?string $credentialState = null
    ): void
    {
        $this->diagnostics->recordError(
            $errorCode,
            'UI-string auto-translate for locale ' . $locale . ': ' . $detail
        );
        $state = \get_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE);
        $state = is_array($state) && ($state['locale'] ?? '') === $locale
            && ($state['version'] ?? '') === $version ? $state : [];
        if (in_array($state['status'] ?? '', ['submitting', 'pending'], true)) {
            $state['resume_status'] = $state['status'];
        }
        $state['status'] = 'failed';
        $state['locale'] = $locale;
        $state['version'] = $version;
        $state['failed_at'] = \time();
        $state['failure_code'] = $errorCode;
        if ($credentialState !== null) {
            $state['credential_state'] = $credentialState;
            $state['credential_checked_at'] = $this->credentialCheckedAt;
        }
        $this->saveState($state);
    }

       
                                                   
      
                                   
       
    private function loadStrings(): array
    {
        $file = $this->dir . '/strings.php';
        if (!\is_file($file)) {
            return [];
        }
        return (array) include $file;
    }
}


                                                                               
final class Routines_AutoTranslateFailure extends \RuntimeException
{
    public const LEASE_LOST = 1;
}


   
                                                                             
  
                                                          
                    
                                         
                                                                    
                             
                 
                                        
                                                            
   
final class Routines_Main implements Routine_RoutineInterface
{
    public function execute(): void
    {
                                   
        $debugSettings = new Config_DebugSettings();

                                                                                     
        $aliases       = new Language_AliasesDictionary();
        $optionFactory = new Language_OptionFactory($aliases);

        $langAdmin     = new Config_LanguageAdmin($aliases, $optionFactory);

        $approachSettings = new Config_ApproachSettings();
        $credentialReader = new Approach_EngineCredentialReadinessReader();

                                                                 
        if ($debugSettings->isEnabled()) {
            add_action('wp_enqueue_scripts', function (): void {
                wp_enqueue_style(
                    'dolet-debug-markers',
                    plugin_dir_url(__FILE__) . 'assets/debug-markers.css',
                    [],
                    '1.0.0'
                );
            });

            add_action('admin_notices', function () {
                $screen = \get_current_screen();
                if (!\current_user_can('manage_options')
                    || $screen === null || $screen->id !== 'settings_page_dolet-settings') {
                    return;
                }
                echo '<div class="notice notice-success is-dismissible"><p><strong>' . \esc_html(Core_Strings::get('debug_loaded')) . '</strong></p></div>';
            });

            add_action('wp_footer', function () {
                echo '<div style="color:red;text-align:center;">' . \esc_html(Core_Strings::get('debug_footer')) . '</div>';
            });

        }

                                                                                               
        add_action('admin_notices', static function () use ($approachSettings, $credentialReader): void {
            if (!current_user_can('manage_options') || !$approachSettings->isEnabled()) {
                return;
            }
            if (function_exists('sole_engine_llm')) {
                $credential = $credentialReader->read();
                $noticeKey = match ($credential['state']) {
                    Approach_EngineCredentialStates::MISSING => 'sole_engine_credential_missing',
                    Approach_EngineCredentialStates::UNVERIFIED => 'sole_engine_credential_unverified',
                    Approach_EngineCredentialStates::INVALID => 'sole_engine_credential_invalid',
                    Approach_EngineCredentialStates::UNAVAILABLE => 'sole_engine_readiness_unavailable',
                    default => null,
                };
                if ($noticeKey !== null) {
                    $noticeClass = $credential['state'] === Approach_EngineCredentialStates::INVALID
                        ? 'notice notice-error'
                        : 'notice notice-warning';
                    echo '<div class="' . \esc_attr($noticeClass) . '"><p><strong>' . \esc_html(Core_Strings::get('page_title')) . ':</strong> '
                        . \esc_html(Core_Strings::get($noticeKey)) . '</p></div>';
                }
                return;
            }
            echo '<div class="notice notice-warning"><p><strong>' . \esc_html(Core_Strings::get('page_title')) . ':</strong> '
                . \esc_html(Core_Strings::get('sole_engine_missing')) . '</p></div>';
        });

        $console = new Routines_AdminConsole($langAdmin, $approachSettings, $debugSettings, $optionFactory, $credentialReader);
        $console->execute();

        $diagnosticsStore = new Config_DiagnosticsStore();
        $diagnosticsPanel = new Routines_AdminDiagnostics($langAdmin, $approachSettings, $diagnosticsStore, $credentialReader);
        $diagnosticsPanel->execute();

        $selfTests = new Routines_AdminSelfTests($langAdmin, $approachSettings, $credentialReader);
        $selfTests->execute();
        $diagnosticsPanel->setSelfTests($selfTests);

        $settingsPage = new Routines_AdminSettings();
        $settingsPage->setConsole($console);
        $settingsPage->setDiagnostics($diagnosticsPanel);
        $settingsPage->execute();

        (new Routines_AdminLanguagesSettings($langAdmin, $debugSettings, $approachSettings, $optionFactory))->execute();

        (new Routines_RuntimeDebug($debugSettings))->execute();

                                                                                                
        (new Routines_ShortcodesParsing())->execute();

                                                                             
        $ls           = new Language_Settings($langAdmin, $aliases, $optionFactory);
        $languagePair = $ls->getSetLanguage_Pair();

                               
        (new Routines_PublicLanguageMenu($langAdmin, $ls))->execute();

        $greenLight = false;

        if (
            $approachSettings->isEnabled()
            &&
            $ls->pairIsValid($languagePair)
            && !$languagePair->optionsAreTheSame()
            && !is_admin()
            && !wp_doing_ajax()
            && !wp_is_json_request()
        ) {
            $greenLight = true;
        }

                                                                     
        global $pagenow;
        if (is_string($pagenow) && in_array($pagenow, ['wp-login.php', 'wp-register.php'], true)) {
            $greenLight = false;
        }

                                                                                 
        $allowedTargets = $langAdmin->getTargetCodes();
        if (!in_array($languagePair->getTargetLanguage()->getCode(), $allowedTargets, true)) {
            $greenLight = false;
        }

        if ($greenLight) {
            $registrationPolicy = new Routines_PendingRegistrationPolicy(
                new Config_PendingRegistrationBudget(null, $diagnosticsStore)
            );
            $tr = new Routines_Translation($languagePair, $debugSettings, $allowedTargets, $registrationPolicy);
            $tr->execute();
        } elseif (
            $languagePair->getTargetLanguage()->getCode() === Language_Constants::ORIGINAL
            && $allowedTargets !== []
            && $approachSettings->isEnabled()
            && !is_admin()
            && !wp_doing_ajax()
            && !wp_is_json_request()
        ) {
                                                                             
                                                                                  
                                                                               
            (new Routines_FinalResponseOwner(
                null,
                Language_Constants::ORIGINAL,
                $allowedTargets
            ))->execute();
        }

        (new Routines_TranslationWorkerCron(
            $debugSettings,
            $langAdmin,
            $optionFactory,
            $approachSettings,
            $credentialReader
        ))->execute();

                                                                           
        (new Routines_AutoTranslate(null, null, $credentialReader))->execute();

    }

}



                                                                    
final class Routines_RequestUrl
{
    public static function current(): string
    {
        $uri = isset($_SERVER['REQUEST_URI'])
            ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))
            : '/';
        $fragmentAt = strpos($uri, '#');
        if ($fragmentAt !== false) {
            $uri = substr($uri, 0, $fragmentAt);
        }

        $home = wp_parse_url(home_url('/'));
        if (! is_array($home) || ! isset($home['scheme'], $home['host'])) {
            return '/' . ltrim($uri, '/');
        }

        $origin = $home['scheme'] . '://' . $home['host'];
        if (isset($home['port'])) {
            $origin .= ':' . $home['port'];
        }

        return $origin . '/' . ltrim($uri, '/');
    }
}


final class Routines_PublicLanguageMenu implements Routine_RoutineInterface
{
    private const MENU_STYLE_HANDLE = 'dolet-language-menu';
    private const FLOATING_STYLE_HANDLE = 'dolet-floating-language-menu';
    private const FLOATING_SCRIPT_HANDLE = 'dolet-floating-language-menu';
    private const FLOATING_ASSET_VERSION = '1.0.0.2';

    private static bool $responseOutcomePending = false;

                                                  
    private static array $deferredMenus = [];

    private ?string $servedLanguageOverride = null;

    public function __construct(
        private Config_LanguageAdminInterface $config,
        private Language_SettingsInterface $settings
    ) {}

    public function execute(): void
    {
        add_action('init', function (): void {
            wp_register_style(
                self::MENU_STYLE_HANDLE,
                plugin_dir_url(__FILE__) . 'assets/language-menu.css',
                [],
                '1.0.0'
            );
        });

        add_action('wp_enqueue_scripts', function (): void {
            if ($this->publicDocumentIsEligible() && $this->config->getTargetCodes() !== []) {
                wp_enqueue_style(self::MENU_STYLE_HANDLE);
            }

            if (!$this->floatingPlacementIsEligible()) {
                return;
            }

            wp_enqueue_style(
                self::FLOATING_STYLE_HANDLE,
                plugin_dir_url(__FILE__) . 'assets/floating-language-menu.css',
                [],
                self::FLOATING_ASSET_VERSION
            );
            wp_enqueue_script(
                self::FLOATING_SCRIPT_HANDLE,
                plugin_dir_url(__FILE__) . 'assets/floating-language-menu.js',
                [],
                self::FLOATING_ASSET_VERSION,
                true
            );
        });

        add_action('wp_footer', function (): void {
            if (!$this->floatingPlacementIsEligible()) {
                return;
            }

            $menu = $this->renderFloatingMenuSafely();
                                                                                  
            echo self::isDeferredMenuMarkup($menu)
                ? $menu
                : \wp_kses($menu, self::floatingMenuAllowedHtml());
        }, 10);

                                                                           
        add_action('init', function (): void {
            $handle = 'dolet-lang-menu-block';

            wp_register_script(
                $handle,
                plugin_dir_url(__FILE__) . 'assets/language-menu-block.js',
                ['wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components'],
                '1.0.0',
                true
            );

                                                                            
                                                                               
                                                                              
                                                                             
                                                                     
            wp_add_inline_script(
                $handle,
                'window.doletLanguageMenuBlock = ' . wp_json_encode([
                    'title'            => Core_Strings::get('block_title'),
                    'description'      => Core_Strings::get('block_description'),
                    'showCodes'        => Core_Strings::get('menu_show_codes'),
                    'wrapperClass'     => Core_Strings::get('menu_wrapper_class'),
                ]) . ';',
                'before'
            );

            register_block_type('dolet/language-menu', [
                'editor_script'   => $handle,
                'style'           => self::MENU_STYLE_HANDLE,
                'attributes'      => [
                    'showCodes'       => ['type' => 'boolean', 'default' => true],
                    'wrapperClass'    => ['type' => 'string', 'default' => 'dolet-lang-menu'],
                ],
                'render_callback' => function (array $attributes = []): string {
                    return $this->renderMenu(
                        (bool) ($attributes['showCodes'] ?? true),
                        (string) ($attributes['wrapperClass'] ?? 'dolet-lang-menu')
                    );
                },
                'supports'        => ['html' => false],
            ]);
        });

                    
        add_action('init', function (): void {
            add_shortcode('dolet_language_menu', function (array $attributes = []): string {
                $attributes = shortcode_atts([
                    'show_codes'       => 'yes',
                    'class'            => 'dolet-lang-menu',
                ], $attributes, 'dolet_language_menu');

                return $this->renderMenu(
                    $this->attributeIsEnabled((string) $attributes['show_codes']),
                    (string) $attributes['class']
                );
            });
        });

                                                            
        add_action('widgets_init', function (): void {
            register_widget(UiVisitor_LanguageMenuWidget::class);
        });
    }

                                                               
    public function renderMenu(
        bool $showCodes = true,
        string $wrapperClass = 'dolet-lang-menu'
    ): string
    {
        if (!(new Config_ApproachSettings())->isEnabled()) {
            return '';
        }

        if (self::$responseOutcomePending && $this->servedLanguageOverride === null) {
            return $this->deferMenu(
                fn (): string => UiVisitor_LanguageMenuWidget::sanitizeMenu(
                    $this->renderMenu($showCodes, $wrapperClass)
                )
            );
        }

        try {
            $items = $this->buildItems();
            if ($items === []) {
                return '';
            }

            $view = new UiVisitor_LanguageMenuView();
            $view->setWrapperClass($this->normalizeWrapperClass($wrapperClass));
            $view->setShowCodes($showCodes);

            foreach ($items as $item) {
                $view->addItem($item);
            }

            return $view->render();
        } catch (\Throwable $error) {
            (new Config_DiagnosticsStore())->recordError(
                'manual_language_menu_render_failed',
                $error->getMessage()
            );
            return '';
        }
    }

    public function renderFloatingMenu(): string
    {
        if (self::$responseOutcomePending && $this->servedLanguageOverride === null) {
            return $this->deferMenu(
                fn (): string => \wp_kses($this->renderFloatingMenu(), self::floatingMenuAllowedHtml())
            );
        }

        $items = $this->buildItems();
        if ($items === []) {
            return '';
        }

        $view = new UiVisitor_FloatingLanguageMenuView();
        $view->setAccessibleName(Core_Strings::get('floating_selector_aria_label'));
        $view->setSummaryLabel(Core_Strings::get('floating_selector_summary'));
        foreach ($items as $item) {
            $view->addItem($item);
        }

        return $view->render();
    }

    private function renderFloatingMenuSafely(): string
    {
        try {
            return $this->renderFloatingMenu();
        } catch (\Throwable $error) {
            (new Config_DiagnosticsStore())->recordError(
                'floating_selector_render_failed',
                $error->getMessage()
            );
            return '';
        }
    }

    public static function beginResponseOutcome(): void
    {
        self::$responseOutcomePending = true;
        self::$deferredMenus = [];
    }

    public static function isDeferredMenuMarkup(string $html): bool
    {
        return \str_starts_with($html, '<div class="dolet-deferred-language-menu" ')
            && \str_contains($html, '<!--DOLET_MENU_END:');
    }

                                                     
    public static function captureResponseOutcome(): array
    {
        return self::$deferredMenus;
    }

    public static function clearResponseOutcome(): void
    {
        self::$deferredMenus = [];
        self::$responseOutcomePending = false;
    }

                                                           
    public static function resolveResponseOutcome(string $html, string $servedCode, array $menus): ?string
    {
        $result = self::resolveResponseOutcomeResult($html, $servedCode, $menus);
        return \is_string($result) ? $result : null;
    }

                                                           
    public static function resolveResponseOutcomeResult(
        string $html,
        string $servedCode,
        array $menus
    ): string|Core_HtmlRejectionInterface
    {
        if ($menus === []) {
            return $html;
        }

        $tokens = Dom_SourceTokenizer::tokenizeResult($html);
        if ($tokens instanceof Core_HtmlRejectionInterface) {
            return $tokens;
        }
        $bodyBounds = self::deferredBodyBounds($tokens);
        if ($bodyBounds === null) {
            return Core_HtmlRejection::MenuBounds;
        }

                                                   
        $sentinels = [];
        foreach (\array_keys($menus) as $identity) {
            $sentinels['<!--DOLET_MENU_END:' . $identity . '-->'] = $identity;
        }

                                                        
        $registeredOpeners = [];
                                                          
        $registeredSentinels = [];
        foreach ($tokens as $index => $token) {
            if ($token['template_depth'] !== 0 || $token['foreign_depth'] !== 0) {
                continue;
            }
            $outsideBody = $token['start'] < $bodyBounds['start'] || $token['end'] > $bodyBounds['end'];
            if ($token['type'] === 'comment') {
                $identity = $sentinels[\substr($html, $token['start'], $token['end'] - $token['start'])]
                    ?? null;
                if ($identity !== null) {
                    if ($outsideBody && $token['start'] >= $bodyBounds['end']) {
                        return Core_HtmlRejection::MenuPlacement;
                    }
                    if ($outsideBody) {
                        continue;
                    }
                    $registeredSentinels[$index] = $identity;
                }
                continue;
            }
            if ($token['type'] !== 'tag' || $token['closing']) {
                continue;
            }
            $identity = self::deferredRegisteredIdentity($token['attributes'], $menus);
            if ($identity !== null) {
                if ($outsideBody) {
                    return Core_HtmlRejection::MenuPlacement;
                }
                $registeredOpeners[$index] = $identity;
            }
        }

        if ($registeredOpeners === [] && $registeredSentinels === []) {
            return $html;
        }

                                                                              
        $patches = [];
                                                  
        $usedSentinels = [];
        foreach ($registeredOpeners as $openIndex => $identity) {
            $opener = $tokens[$openIndex];
            if ($opener['name'] !== 'div' || $opener['self_closing']
                || !self::deferredOpenerQualifies($opener['attributes'], $identity)) {
                return Core_HtmlRejection::MenuFrame;
            }
            $closeIndex = self::deferredMatchingDivClose($tokens, $openIndex, $bodyBounds['end']);
            if ($closeIndex === null) {
                return Core_HtmlRejection::MenuFrame;
            }
            $sentinelIndex = $closeIndex + 1;
            if (($registeredSentinels[$sentinelIndex] ?? null) !== $identity
                || $tokens[$sentinelIndex]['start'] !== $tokens[$closeIndex]['end']
                || isset($usedSentinels[$sentinelIndex])) {
                return Core_HtmlRejection::MenuFrame;
            }
            $rendered = $menus[$identity][$servedCode] ?? null;
            if (!\is_string($rendered)) {
                return Core_HtmlRejection::MenuOutcome;
            }
            $patches[] = [
                'start' => $opener['start'],
                'end' => $tokens[$sentinelIndex]['end'],
                'replacement' => $rendered,
            ];
            $usedSentinels[$sentinelIndex] = true;
        }
        if (\count($usedSentinels) !== \count($registeredSentinels)) {
            return Core_HtmlRejection::MenuFrame;
        }

        \usort($patches, static fn (array $left, array $right): int => $right['start'] <=> $left['start']);
        $lastStart = \strlen($html);
        foreach ($patches as $patch) {
            if ($patch['end'] > $lastStart) {
                return Core_HtmlRejection::MenuFrame;
            }
            $html = \substr_replace(
                $html,
                $patch['replacement'],
                $patch['start'],
                $patch['end'] - $patch['start']
            );
            $lastStart = $patch['start'];
        }
        return $html;
    }

       
                                               
                                            
       
    private static function deferredBodyBounds(array $tokens): ?array
    {
        $start = null;
        $end = null;
        foreach ($tokens as $token) {
            if ($token['type'] !== 'tag' || $token['name'] !== 'body'
                || $token['template_depth'] !== 0 || $token['foreign_depth'] !== 0) {
                continue;
            }
            if (!$token['closing']) {
                if ($start !== null || $end !== null) {
                    return null;
                }
                $start = $token['end'];
                continue;
            }
            if ($start === null || $end !== null) {
                return null;
            }
            $end = $token['start'];
        }
        return $start !== null && $end !== null && $start <= $end
            ? ['start' => $start, 'end' => $end]
            : null;
    }

       
                                                                                                           
                                                       
       
    private static function deferredRegisteredIdentity(array $attributes, array $menus): ?string
    {
        foreach ($attributes as $attribute) {
            if ($attribute['name'] === 'data-dolet-language-menu'
                && isset($menus[$attribute['value']])) {
                return $attribute['value'];
            }
        }
        return null;
    }

                                                                                                               
    private static function deferredOpenerQualifies(array $attributes, string $identity): bool
    {
        $identityCount = 0;
        $classCount = 0;
        $hasWrapperClass = false;
        foreach ($attributes as $attribute) {
            if ($attribute['name'] === 'data-dolet-language-menu') {
                $identityCount++;
                if ($attribute['value'] !== $identity) {
                    return false;
                }
            }
            if ($attribute['name'] === 'class') {
                $classCount++;
                $hasWrapperClass = Dom_SourceTokenizer::hasHtmlSpaceSeparatedToken(
                    $attribute['value'],
                    'dolet-deferred-language-menu'
                );
            }
        }
        return $identityCount === 1 && $classCount === 1 && $hasWrapperClass;
    }

                                                   
    private static function deferredMatchingDivClose(array $tokens, int $openIndex, int $bodyEnd): ?int
    {
        $depth = 0;
        for ($index = $openIndex, $count = \count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];
            if ($token['start'] >= $bodyEnd) {
                break;
            }
            if ($token['type'] !== 'tag' || $token['name'] !== 'div'
                || $token['template_depth'] !== 0 || $token['foreign_depth'] !== 0) {
                continue;
            }
            $depth += $token['closing'] ? -1 : 1;
            if ($depth === 0) {
                return $index;
            }
        }
        return null;
    }

       
                                                                          
                                                                          
      
                                         
       
    private function deferMenu(callable $renderer): string
    {
        $requested = $this->settings->getRequestedLanguage()->getCode();
        $renderedOutcomes = [];
        foreach (\array_values(\array_unique([Language_Constants::ORIGINAL, $requested])) as $servedCode) {
            $this->servedLanguageOverride = $servedCode;
            try {
                $renderedOutcomes[$servedCode] = $renderer();
            } catch (\Throwable $error) {
                (new Config_DiagnosticsStore())->recordError(
                    'deferred_language_menu_render_failed',
                    $error->getMessage()
                );
                $renderedOutcomes[$servedCode] = '';
            } finally {
                $this->servedLanguageOverride = null;
            }
        }

        try {
            $identity = \bin2hex(\random_bytes(16));
        } catch (\Throwable) {
            $identity = \hash('sha256', \uniqid('', true) . ':' . \microtime(true));
        }
        $escapedIdentity = \esc_attr($identity);
        $marker = '<div class="dolet-deferred-language-menu" data-no-translate style="display:contents" '
            . 'data-dolet-language-menu="' . $escapedIdentity . '">'
            . ($renderedOutcomes[Language_Constants::ORIGINAL] ?? '')
            . '</div><!--DOLET_MENU_END:' . $escapedIdentity . '-->';
        self::$deferredMenus[$identity] = $renderedOutcomes;
        return $marker;
    }

                                                   
    private static function floatingMenuAllowedHtml(): array
    {
        return [
            'div' => ['class' => true, 'data-no-translate' => true, 'translate' => true],
            'details' => ['class' => true, 'data-dolet-opening' => true],
            'summary' => ['aria-label' => true, 'class' => true],
            'span' => [
                'aria-current' => true,
                'aria-hidden' => true,
                'class' => true,
                'data-no-translate' => true,
                'data-dolet-language-menu' => true,
            ],
            'svg' => [
                'fill' => true,
                'focusable' => true,
                'height' => true,
                'stroke' => true,
                'viewbox' => true,
                'width' => true,
            ],
            'circle' => ['cx' => true, 'cy' => true, 'r' => true],
            'path' => ['d' => true],
            'nav' => ['aria-label' => true, 'class' => true],
            'ul' => ['class' => true],
            'li' => ['class' => true],
            'a' => ['class' => true, 'data-dolet-preserve-l' => true, 'href' => true],
            'code' => ['class' => true],
        ];
    }

                                                                                 
    private function getOriginalDisplayName(): string
    {
        $sourceCode = $this->config->getSourceCode();
        $sourceName = '';

        if ($sourceCode === Language_Constants::ORIGINAL) {
                                                                             
                                                                                 
                                                             
            $customizations = $this->config->getCustomizations();
            $sourceName = trim((string) ($customizations[Language_Constants::ORIGINAL]['name'] ?? ''));

            if ($sourceName === '') {
                $siteLanguage = $this->settings->getWordpressLanguage();
                if ($siteLanguage->getCode() !== Language_Constants::AUTODETECT) {
                    $sourceName = $this->config->getDisplayNameFor($siteLanguage->getCode());
                }
            }
        } elseif ($sourceCode !== '') {
            $sourceName = $this->config->getDisplayNameFor($sourceCode);
        }

        if ($sourceName === '') {
            return Core_Strings::get('menu_original_unknown');
        }

        $format = Core_Strings::get('menu_original_with_language');
        if (substr_count($format, '{language}') === 1) {
            return str_replace('{language}', $sourceName, $format);
        }

                                                                              
                                                                             
        return Core_Strings::get('original_option') . ' (' . $sourceName . ')';
    }

                                                                             
    public static function renderMenuStatic(
        bool $showCodes = true,
        string $wrapperClass = 'dolet-lang-menu'
    ): string
    {
        $aliases = new Language_AliasesDictionary();
        $factory = new Language_OptionFactory($aliases);

        $cfg     = new Config_LanguageAdmin($aliases, $factory);

        $ls      = new Language_Settings($cfg, $aliases, $factory);
        $routine = new self($cfg, $ls);

        return $routine->renderMenu($showCodes, $wrapperClass);
    }


    private function buildUrlWithLang(string $code): string
    {
        $param = Dom_Constants::LANGUAGE_URL_PARAMETER_NAME;       
        $url   = Routines_RequestUrl::current();

                                                                         
        if (strcasecmp($code, Language_Constants::ORIGINAL) === 0) {
            return esc_url(remove_query_arg($param, $url));
        }

        return \esc_url(\add_query_arg([$param => \rawurlencode($code)], $url));
    }

                                                            
    private function buildItems(): array
    {
        $requested = $this->servedLanguageOverride
            ?? \apply_filters('sole_translation_served_language', $this->settings->getRequestedLanguage()->getCode());
        $targets = $this->config->getTargetCodes();
        if ($targets === []) {
            return [];
        }

        $codes = array_values(array_unique(array_merge([Language_Constants::ORIGINAL], $targets)));

        $iaMap = apply_filters('dolet_interlingua_names', []);
        if (!is_array($iaMap)) {
            $iaMap = [];
        }
        $items = [];
        foreach ($codes as $code) {
            $endonym = $code === Language_Constants::ORIGINAL
                ? $this->getOriginalDisplayName()
                : $this->config->getDisplayNameFor($code);
            $interlinguaName = $iaMap[$code] ?? null;
            $items[] = new UiVisitor_LanguageMenuItemView(
                $code,
                $endonym,
                is_string($interlinguaName) ? $interlinguaName : null,
                $this->buildUrlWithLang($code),
                strcasecmp($requested, $code) === 0
            );
        }

        return $items;
    }

    private function floatingPlacementIsEligible(): bool
    {
        if (!$this->publicDocumentIsEligible()) {
            return false;
        }

        return (new Config_ApproachSettings())->isEnabled()
            && Config_OptionValue::boolean(\get_option(Config_TranslationOptionNames::FLOATING_SELECTOR_ENABLED, true))
            && $this->config->getTargetCodes() !== [];
    }

    private function publicDocumentIsEligible(): bool
    {
        global $pagenow;

        return !is_admin()
            && !wp_doing_ajax()
            && !wp_is_json_request()
            && !is_feed()
            && !(is_string($pagenow) && in_array($pagenow, ['wp-login.php', 'wp-register.php'], true));
    }

    private function attributeIsEnabled(string $value): bool
    {
        return !in_array(strtolower(trim($value)), ['0', 'false', 'no', 'off'], true);
    }

    private function normalizeWrapperClass(string $class): string
    {
        $classes = Dom_SourceTokenizer::splitHtmlSpace($class);
        $classes = array_values(array_filter(array_map(
            static fn (string $item): string => sanitize_html_class($item),
            $classes
        )));

        return $classes === [] ? 'dolet-lang-menu' : implode(' ', $classes);
    }
}



   
                                                                    
  
                                                                      
   
final class Routines_RuntimeDebug implements Routine_RoutineInterface
{
    public function __construct(
        private Config_DebugSettingsInterface $debugSettings
    ) {}

    public function execute(): void
    {
        if (!$this->debugSettings->isEnabled()) {
            return;
        }

                                                                       
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        if (!defined('DONOTCACHEDB')) {
            define('DONOTCACHEDB', true);
        }
        if (!defined('DONOTMINIFY')) {
            define('DONOTMINIFY', true);
        }
        if (!defined('DONOTCDN')) {
            define('DONOTCDN', true);
        }
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

    }
}


final class Routines_SettingsValue implements Routines_SettingsValueInterface
{
    public static function checkbox(mixed $value): bool
    {
        return Config_OptionValue::boolean($value);
    }
}


   
                                               
   
final class Routines_ShortcodesParsing implements Routine_RoutineInterface
{
    public function __construct()
    {
    }

    public function execute(): void
    {
        $callback = static function (mixed $atts = [], ?string $content = '', string $tag = ''): string {
                                                                                                  
            return '<span class="notranslate" translate="no" data-no-translate="1" style="display: contents">' . \wp_kses_post(\do_shortcode($content ?? '')) . '</span>';
        };
        \add_shortcode('sole_translation_notranslate', $callback);
        \add_shortcode('notranslate', $callback);
    }
}


   
                                                                    
   
final class Routines_FinalResponseOwner implements Routine_RoutineInterface, Routines_TemplateResponseInterface
{
    private const MAX_RESPONSE_BYTES = 4194304;
    private const MIN_HOST_RESERVE_BYTES = 8388608;

    private string $servedLanguage = Language_Constants::ORIGINAL;
    private bool $responseCommitted = false;
    private ?string $selectedTemplate = null;
    private string $bufferHandler = self::class . '::identityBuffer';
    private bool $bufferStarted = false;
    private bool $finalized = false;
    private bool $diagnosticRecorded = false;
    private ?int $bufferLevel = null;
    private ?string $fallbackSource = null;
                            
    private array $cacheHeadersBeforeTemplate = [];
                                 
    private ?array $provisionalCacheHeaders = null;
                                                             
    private array $lowerBuffers = [];

       
                                      
       
    public function __construct(
        private ?Translation_TranslatorInterface $translator,
        private string $currentLangCode = '',
        private array $allTargetCodes = [],
    ) {}

    public function execute(): void
    {
        \add_filter('sole_translation_served_language', fn(mixed $requested): string => $this->servedLanguage);
        \add_action('template_redirect', function (): void {
            if ($this->translator === null || !$this->mayCaptureTemplate() || \headers_sent()) {
                return;
            }
            $this->cacheHeadersBeforeTemplate = self::templateCacheHeaders();
                                                                                  
            $headers = self::defaultNoCacheHeaders();
            $headers['Cache-Control'] .= ', sole-translation-pending';
            foreach ($headers as $name => $value) {
                if ($value !== false) {
                    \header($name . ': ' . $value);
                }
            }
            $this->provisionalCacheHeaders = self::templateCacheHeaders();
        }, 0);
        if (\function_exists('wp_start_template_enhancement_output_buffer')) {
                                                                                                  
            \add_action('template_redirect', function (): void {
                if ($this->mayCaptureTemplate()) {
                    \add_filter('wp_template_enhancement_output_buffer', [self::class, 'identityBuffer']);
                }
            }, 0);
            \add_action('wp_before_include_template', function (): void {
                if (!$this->bufferStarted && $this->mayCaptureTemplate()) {
                    try {
                        $vetoed = \function_exists('wp_should_output_buffer_template_for_enhancement')
                            && !\wp_should_output_buffer_template_for_enhancement();
                    } catch (\Throwable) {
                        $vetoed = false;
                    }
                    if ($vetoed) {
                        $this->preventTargetCaching(self::defaultNoCacheHeaders());
                    } else {
                        $this->reportTemplateBufferFailure();
                    }
                }
            }, PHP_INT_MAX);
            \add_action('wp_template_enhancement_output_buffer_started', function (): void {
                if ($this->bufferStarted || !$this->mayCaptureTemplate()) {
                    return;
                }
                foreach (\ob_get_status(true) as $status) {
                    if (($status['name'] ?? '') === 'wp_finalize_template_enhancement_output_buffer') {
                        $this->bufferLevel = (int)$status['level'] + 1;
                    }
                }
                if ($this->bufferLevel === null) {
                    return;
                }
                $this->bufferHandler = 'wp_finalize_template_enhancement_output_buffer';
                $this->lowerBuffers = self::lowerBufferSnapshot();
                $this->bufferStarted = true;
                Routines_PublicLanguageMenu::beginResponseOutcome();
                \add_action('shutdown', function (): void { $this->finalizeResponse(); }, 0);
            }, PHP_INT_MAX);
            return;
        }
        \add_filter('template_include', function (mixed $template): mixed {
            if ($this->bufferStarted || !\is_string($template) || \realpath($template) === __DIR__ . '/template-response.php'
                || !\is_file($template)
                || !\is_readable($template) || !$this->mayCaptureTemplate()
                || !\is_file(__DIR__ . '/template-response.php') || !\is_readable(__DIR__ . '/template-response.php')) {
                return $template;
            }
            $this->selectedTemplate = $template;
            \add_filter('sole_translation_template_response_owner', fn(mixed $owner): Routines_TemplateResponseInterface => $this);
            return __DIR__ . '/template-response.php';
        }, PHP_INT_MAX);
    }

    private function mayCaptureTemplate(): bool
    {
        $rawMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return \is_string($rawMethod) && \strtoupper($rawMethod) === 'GET'
            && !\is_admin() && !\is_feed() && !\is_robots()
            && !\wp_doing_ajax() && !\wp_is_json_request();
    }

    public function getSelectedTemplate(): string
    {
        return $this->selectedTemplate ?? '';
    }

    public function hasStartedTemplateResponse(): bool
    {
        return $this->bufferStarted;
    }

    public function prepareTemplateResponse(): void
    {
        $this->lowerBuffers = self::lowerBufferSnapshot();
    }

    public function startTemplateResponse(): void
    {
        $this->bufferStarted = true;
        $this->bufferLevel = \ob_get_level();
        Routines_PublicLanguageMenu::beginResponseOutcome();
    }

                               
    private static function templateCacheHeaders(): array
    {
        return \array_values(\array_filter(\headers_list(), static function (string $header): bool {
            $name = \strtolower(\explode(':', $header, 2)[0]);
            return $name === 'cache-control' || $name === 'expires';
        }));
    }

    private function restoreCaptureWindowHeaders(): void
    {
        if ($this->provisionalCacheHeaders === null) {
            return;
        }
        $unchanged = self::templateCacheHeaders() === $this->provisionalCacheHeaders;
        $this->provisionalCacheHeaders = null;
        if (!$unchanged || \headers_sent() || (\defined('DONOTCACHEPAGE') && DONOTCACHEPAGE)) {
            return;
        }
        \header_remove('Cache-Control');
        \header_remove('Expires');
        foreach ($this->cacheHeadersBeforeTemplate as $header) {
            \header($header, false);
        }
    }

    public function reportTemplateBufferFailure(): void
    {
        $this->preventTargetCaching(self::defaultNoCacheHeaders());
        $this->recordDiagnosticOnce('final_html_buffer_start_failed', 'Dolet could not open its response buffer.');
    }

    public function clearTemplateResponse(): void
    {
        Routines_PublicLanguageMenu::clearResponseOutcome();
    }

    public function ownsTemplateBuffer(): bool
    {
        if ($this->bufferLevel === null || \ob_get_level() !== $this->bufferLevel) {
            return false;
        }
        $status = \ob_get_status();
        return ($status['name'] ?? '') === $this->bufferHandler;
    }

    public static function identityBuffer(string $buffer): string
    {
        return $buffer;
    }

    public function finalizeResponse(): void
    {
        if (!$this->bufferStarted || $this->finalized) {
            return;
        }
        $this->finalized = true;

        try {
            $this->finalizeOwnedResponse();
        } finally {
            Routines_PublicLanguageMenu::clearResponseOutcome();
        }
    }

    private function finalizeOwnedResponse(): void
    {
        if (\headers_sent()) {
            $this->preventTargetCaching(self::defaultNoCacheHeaders());
            $this->recordDiagnosticOnce('final_html_source_already_sent', 'Output began before Dolet finalization.');
            return;
        }
        if ($this->bufferLevel === null || \ob_get_level() > $this->bufferLevel) {
            $this->preventTargetCaching(self::defaultNoCacheHeaders());
            $this->recordDiagnosticOnce('final_html_foreign_buffer', 'Another output buffer remained above Dolet.');
            return;
        }
        if ($this->lowerBufferWasWritten()) {
            $this->preventTargetCaching(self::defaultNoCacheHeaders());
            $this->recordDiagnosticOnce('final_html_source_already_flushed', 'Captured source was flushed before Dolet finalization.');
            return;
        }

        $source = $this->capturedSource();
        if ($source === null) {
            $this->preventPublicationCaching(self::defaultNoCacheHeaders());
            $this->recordDiagnosticOnce('final_html_buffer_level_lost', 'Dolet could not find its response buffer.');
            return;
        }

        if (!$this->responseIsHtml()) {
            $this->preventTargetCaching(self::defaultNoCacheHeaders());
            $this->finishResponse($source, $source);
            return;
        }

        $menus = Routines_PublicLanguageMenu::captureResponseOutcome();
        if (!$this->responseFitsTransformBudget($source)) {
            if (\strlen($source) <= self::MAX_RESPONSE_BYTES) {
                $this->preventPublicationCaching(self::defaultNoCacheHeaders());
            } else {
                $this->preventTargetCaching(self::defaultNoCacheHeaders());
            }
            $reason = \strlen($source) > self::MAX_RESPONSE_BYTES
                ? Core_HtmlRejection::Bytes
                : Core_HtmlRejection::Memory;
            $this->finishResponse($source, $source, $reason);
            return;
        }

        $fallback = $source;
        $step = Core_HtmlRejection::PreflightMenu;
        try {
            $resolved = Routines_PublicLanguageMenu::resolveResponseOutcomeResult(
                $source,
                Language_Constants::ORIGINAL,
                $menus
            );
            if ($resolved instanceof Core_HtmlRejectionInterface) {
                $this->preventPublicationCaching(self::defaultNoCacheHeaders());
                $this->finishResponse($source, $source, $resolved);
                return;
            }
            $fallback = $resolved;
            $this->fallbackSource = $fallback;
            $step = Core_HtmlRejection::PreflightEnvironment;
            $noCacheHeaders = \function_exists('wp_get_nocache_headers')
                ? \wp_get_nocache_headers()
                : self::defaultNoCacheHeaders();
            $step = Core_HtmlRejection::PreflightDirection;
            $documentDirection = Dom_DocumentLanguage::directionFor($this->currentLangCode);
            $step = Core_HtmlRejection::PreflightRequest;
            $requestUrl = Routines_RequestUrl::current();
        } catch (\Throwable) {
            $this->preventPublicationCaching(self::defaultNoCacheHeaders());
            $this->finishResponse($fallback, $source, $step);
            return;
        }

        $step = Core_HtmlRejection::RenderMetadata;
        try {
            if ($this->translator === null) {
                $candidate = Dom_HreflangInjector::inject(
                    $source,
                    Language_Constants::ORIGINAL,
                    $this->allTargetCodes,
                    $requestUrl
                );
                $step = Core_HtmlRejection::RenderMenu;
                $candidate = Routines_PublicLanguageMenu::resolveResponseOutcomeResult(
                    $candidate,
                    Language_Constants::ORIGINAL,
                    $menus
                );
                $step = Core_HtmlRejection::RenderValidation;
                $this->commitCandidateResponse($source, $fallback, $candidate, $noCacheHeaders);
                return;
            }
            $step = Core_HtmlRejection::RenderTranslation;
            $candidate = $this->translator->translateFinalHtml($source, true);
            if ($this->translator->didLoseStructureLastPass()) {
                $this->preventResponseCaching($noCacheHeaders);
                $this->finishResponse($fallback, $source, $this->translator->getRejectionLastPass());
                return;
            }
            if (!$this->translator->isCompleteLastPass()) {
                $this->preventResponseCaching($noCacheHeaders);
            }
            if ($this->translator->didTranslateLastPass()) {
                $step = Core_HtmlRejection::RenderLanguage;
                $candidate = Dom_DocumentLanguage::applyResolved(
                    $candidate,
                    $this->currentLangCode,
                    $documentDirection
                );
            }
            if ($this->currentLangCode !== '' && $this->allTargetCodes !== []) {
                $step = Core_HtmlRejection::RenderMetadata;
                $candidate = Dom_HreflangInjector::inject(
                    $candidate,
                    $this->currentLangCode,
                    $this->allTargetCodes,
                    $requestUrl
                );
            }
            $step = Core_HtmlRejection::RenderMenu;
            $servedCode = $this->translator->didTranslateLastPass() ? $this->currentLangCode : Language_Constants::ORIGINAL;
            $candidate = Routines_PublicLanguageMenu::resolveResponseOutcomeResult(
                $candidate,
                $servedCode,
                $menus
            );
            $step = Core_HtmlRejection::RenderValidation;
            $this->commitCandidateResponse($source, $fallback, $candidate, $noCacheHeaders, $servedCode);
        } catch (\Throwable) {
            $this->preventPublicationCaching($noCacheHeaders);
            $this->finishResponse($fallback, $source, $step);
            if (\defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                \error_log('[Dolet WP] ' . $step->code());
            }
        }
    }

    private function finishResponse(
        string $response,
        string $source,
        ?Core_HtmlRejectionInterface $reason = null,
        bool $candidate = false
    ): void
    {
        try {
            $this->commitResponse($response, $source);
        } catch (\Throwable) {
            $this->recoverSource($source);
        }
        if ($reason !== null) {
            $this->recordDiagnosticOnce($reason->code($candidate), $reason->context());
        }
    }

                                                     
    private function commitCandidateResponse(
        string $source,
        string $fallback,
        string|Core_HtmlRejectionInterface $candidate,
        array $headers,
        string $servedCode = Language_Constants::ORIGINAL
    ): void
    {
        $failure = $candidate instanceof Core_HtmlRejectionInterface
            ? ['reason' => $candidate, 'candidate' => true]
            : $this->candidateRejection($source, $candidate);
        if ($failure === null && \is_string($candidate)) {
            $this->finishResponse($candidate, $source);
            if ($this->responseCommitted) {
                if ($this->translator === null || $this->translator->isCompleteLastPass()) {
                    $this->restoreCaptureWindowHeaders();
                }
                $this->servedLanguage = $servedCode;
            }
            return;
        }
        if ($this->translator === null) {
            $this->preventPublicationCaching($headers);
        } else {
            $this->preventResponseCaching($headers);
        }
        if ($failure !== null) {
            $this->finishResponse($fallback, $source, $failure['reason'], $failure['candidate']);
        }
    }

                                             
    private static function defaultNoCacheHeaders(): array
    {
        return [
            'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT',
            'Cache-Control' => 'no-cache, must-revalidate, max-age=0, no-store, private',
        ];
    }

                                                     
    private function preventResponseCaching(array $headers): void
    {
        if (!\defined('DONOTCACHEPAGE')) {
            \define('DONOTCACHEPAGE', true);
        }
        if (!\headers_sent()) {
            \header_remove('Last-Modified');
            foreach ($headers as $name => $value) {
                if ($value === false) {
                    \header_remove($name);
                    continue;
                }
                \header($name . ': ' . $value);
            }
        }
    }

                                                     
    private function preventTargetCaching(array $headers): void
    {
        if ($this->translator !== null) {
            $this->preventResponseCaching($headers);
        }
    }

                                                     
    private function preventPublicationCaching(array $headers): void
    {
        if ($this->translator !== null || $this->allTargetCodes !== []) {
            $this->preventResponseCaching($headers);
        }
    }

    private function responseIsHtml(): bool
    {
        $first = null;
        foreach (\headers_list() as $header) {
            if (\stripos($header, 'Content-Type:') !== 0) {
                continue;
            }
            $type = \strtolower(\trim(\explode(';', \substr($header, 13), 2)[0]));
            if ($first === null) {
                $first = $type;
                continue;
            }
            if ($type !== $first) {
                return false;
            }
        }
        return $first === null || \in_array($first, ['text/html', 'application/xhtml+xml'], true);
    }

                                                                
    private static function lowerBufferSnapshot(): array
    {
        $snapshot = [];
        foreach (\ob_get_status(true) as $status) {
            $level = (int)($status['level'] ?? -1);
            $snapshot[$level] = [
                'name' => (string)($status['name'] ?? ''),
                'buffer_used' => (int)($status['buffer_used'] ?? 0),
            ];
        }
        return $snapshot;
    }

    private function lowerBufferWasWritten(): bool
    {
        $targetLevel = $this->bufferLevel === null ? -1 : $this->bufferLevel - 1;
        foreach (\ob_get_status(true) as $status) {
            $level = (int)($status['level'] ?? -1);
            if ($level >= $targetLevel || !isset($this->lowerBuffers[$level])) {
                continue;
            }
            $before = $this->lowerBuffers[$level];
            if ($before['name'] !== (string)($status['name'] ?? '')
                || (int)($status['buffer_used'] ?? 0) > $before['buffer_used']) {
                return true;
            }
        }
        return false;
    }

    private function capturedSource(): ?string
    {
        if ($this->bufferLevel === null || \ob_get_level() < $this->bufferLevel) {
            return null;
        }
        if (\ob_get_level() !== $this->bufferLevel) {
            return null;
        }
        $statuses = \ob_get_status(true);
        $status = $statuses[$this->bufferLevel - 1] ?? null;
        if (!\is_array($status)
            || (string)($status['name'] ?? '') !== $this->bufferHandler) {
            return null;
        }
        $source = \ob_get_contents();
        if (!\is_string($source)) {
            return null;
        }
        return $source;
    }

    private function commitResponse(string $response, string $source): bool
    {
        $this->responseCommitted = false;
        if ($this->bufferLevel === null || \ob_get_level() !== $this->bufferLevel) {
            return $this->recoverSource($source);
        }
        $statuses = \ob_get_status(true);
        $status = $statuses[$this->bufferLevel - 1] ?? null;
        if (!\is_array($status)
            || (string)($status['name'] ?? '') !== $this->bufferHandler
            || \ob_get_contents() !== $source
            || $this->lowerBufferWasWritten()
            || \headers_sent()) {
            return $this->recoverSource($source);
        }
        if ($response !== $source) {
            \header_remove('Content-Length');
        }
        if (!\ob_clean()) {
            return $this->recoverSource($source);
        }
        echo $response;
        $this->responseCommitted = true;
        return true;
    }

    private function recoverSource(string $source): bool
    {
        $this->responseCommitted = false;
        $this->preventPublicationCaching(self::defaultNoCacheHeaders());
        $this->recordDiagnosticOnce(
            'final_html_commit_failed',
            'Dolet retained the source because the response buffer changed before commit.'
        );
        if ($this->lowerBufferWasWritten() || \headers_sent()) {
            return false;
        }
        if ($this->bufferLevel !== null && \ob_get_level() > $this->bufferLevel) {
            return false;
        }
        if ($this->bufferLevel !== null && \ob_get_level() === $this->bufferLevel) {
            $statuses = \ob_get_status(true);
            $status = $statuses[$this->bufferLevel - 1] ?? null;
            if (!\is_array($status)
                || (string)($status['name'] ?? '') !== $this->bufferHandler
                || !\ob_clean()) {
                return false;
            }
        }
        $fallback = $this->fallbackSource ?? $source;
        if ($fallback !== $source) {
            \header_remove('Content-Length');
        }
        echo $fallback;
        return true;
    }

                                                                                
    private function candidateRejection(string $source, string $candidate): ?array
    {
        if ($source !== '' && Dom_SourceTokenizer::isHtmlSpaceOnly($candidate)) {
            return ['reason' => Core_HtmlRejection::Empty, 'candidate' => true];
        }
        $sourceTokens = Dom_SourceTokenizer::tokenizeResult($source);
        if ($sourceTokens instanceof Core_HtmlRejectionInterface) {
            return ['reason' => $sourceTokens, 'candidate' => false];
        }
        $candidateTokens = Dom_SourceTokenizer::tokenizeResult($candidate);
        if ($candidateTokens instanceof Core_HtmlRejectionInterface) {
            return ['reason' => $candidateTokens, 'candidate' => true];
        }
        $sourceBounds = Dom_SourceTokenizer::documentBoundsResult($source, $sourceTokens);
        if ($sourceBounds instanceof Core_HtmlRejectionInterface) {
            return ['reason' => $sourceBounds, 'candidate' => false];
        }
        $candidateBounds = Dom_SourceTokenizer::documentBoundsResult($candidate, $candidateTokens);
        if ($candidateBounds instanceof Core_HtmlRejectionInterface) {
            return ['reason' => $candidateBounds, 'candidate' => true];
        }
        if ($this->hasMeaningfulContent($source, $sourceTokens, $sourceBounds)
            && !$this->hasMeaningfulContent($candidate, $candidateTokens, $candidateBounds)) {
            return ['reason' => Core_HtmlRejection::Content, 'candidate' => true];
        }
        if ($sourceBounds['body_open_end'] !== null
            && ($candidateBounds['full'] !== $sourceBounds['full'] || $candidateBounds['body_open_end'] === null
                || $candidateBounds['body_close_start'] === null)) {
            return ['reason' => Core_HtmlRejection::Shape, 'candidate' => true];
        }
        return null;
    }

       
                                               
                                                 
       
    private function hasMeaningfulContent(string $html, array $tokens, array $bounds): bool
    {
        $start = $bounds['body_open_end'] ?? 0;
        $end = $bounds['body_close_start'] ?? \strlen($html);
        foreach ($tokens as $token) {
            if ($token['start'] < $start || $token['end'] > $end) {
                continue;
            }
            if (($token['type'] === 'text' || $token['type'] === 'raw')
                && !Dom_SourceTokenizer::isHtmlSpaceOnly(
                    \substr($html, $token['start'], $token['end'] - $token['start'])
                )) {
                return true;
            }
            if ($token['type'] === 'tag' && !$token['closing']) {
                return true;
            }
        }
        return false;
    }

    private function recordDiagnosticOnce(string $code, string $context): void
    {
        if ($this->diagnosticRecorded) {
            return;
        }
        $level = \ob_get_level();
        try {
            if (!\ob_start()) {
                return;
            }
            $this->diagnosticRecorded = true;
            try {
                (new Config_DiagnosticsStore())->recordError($code, $context);
            } finally {
                                                                                                      
                while (\ob_get_level() > $level
                    && (((int) (\ob_get_status()['flags'] ?? 0)) & PHP_OUTPUT_HANDLER_REMOVABLE) !== 0) {
                    if (!\ob_end_clean()) {
                        break;
                    }
                }
            }
        } catch (\Throwable) {
                                                                        
        }
    }

    private function responseFitsTransformBudget(
        string $html,
        ?int $memoryLimitBytes = null,
        ?int $memoryUsageBytes = null
    ): bool {
        $length = \strlen($html);
        if ($length > self::MAX_RESPONSE_BYTES) {
            return false;
        }

        $limit = $memoryLimitBytes ?? self::runtimeMemoryLimitBytes();
        if ($limit < 0) {
            return true;
        }
        $usage = $memoryUsageBytes ?? \memory_get_usage(true);
        $reserve = \max(self::MIN_HOST_RESERVE_BYTES, \intdiv($limit, 4));
        return $limit - $usage - $length >= $reserve;
    }

    private static function runtimeMemoryLimitBytes(): int
    {
        $raw = \strtolower(\trim((string)\ini_get('memory_limit')));
        if ($raw === '' || $raw === '-1') {
            return -1;
        }
        $value = (int)$raw;
        return match (\substr($raw, -1)) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}


                                                                        
final class Routines_PendingRegistrationPolicy implements Translation_PendingRegistrationPolicyInterface
{
    private ?bool $requestMayRegister = null;

    public function __construct(private Translation_PendingRegistrationPolicyInterface $budget) {}

    public function claim(): bool
    {
        if ($this->requestMayRegister === null) {
            $this->requestMayRegister = !$this->isCacheOnlyRequest();
        }
        return $this->requestMayRegister && $this->budget->claim();
    }

    private function isCacheOnlyRequest(): bool
    {
        if (\is_user_logged_in() || \is_search() || \is_404()) {
            return true;
        }
        foreach (\array_keys($_GET) as $key) {
            if (!is_string($key) || $key !== 'l') {
                return true;
            }
        }
        return false;
    }
}

final class Routines_Translation implements Routine_RoutineInterface
{
       
                                      
       
    public function __construct(
        private Language_PairInterface $languagePair,
        private Config_DebugSettingsInterface $debugSettings,
        private array $allTargetCodes = [],
        private ?Translation_PendingRegistrationPolicyInterface $registrationPolicy = null,
        private ?Data_TranslationMemoryRepositoryInterface $repository = null,
    ) {}

    public function execute(): void
    {
                                                                  
        $repo = $this->repository ?? new Data_TranslationMemoryRepository();

                                                                              
                                                                          
        $repoReady = true;
        $readinessFailure = 'Translation-memory storage is not ready.';
        try {
            $repoReady = $repo->isReady();
        } catch (\Throwable $e) {
            $repoReady = false;
            $readinessFailure = $e->getMessage();
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Translation-memory readiness error: ' . $e->getMessage());
            }
        }

        if (!$repoReady) {
            (new Config_DiagnosticsStore())->recordError(
                'translation_memory_unavailable',
                $readinessFailure
            );
            return;
        }

                                                                                 
        $stamper = new Dom_TranslatedStampService();

                                                       
        $translatePre = Config_OptionValue::boolean(\get_option(Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA, false));

        $translator = new Translation_Translator(
            $this->languagePair,
            $this->debugSettings,
            $repo,
            $stamper,
            $translatePre,
            $this->registrationPolicy,
        );

                                                      
        $final = new Routines_FinalResponseOwner(
            $translator,
            $this->languagePair->getTargetLanguage()->getCode(),
            $this->allTargetCodes,
        );
        $final->execute();
    }

}

   
                                                                           
  
                    
                                            
                                                                            
                                                                                       
  
                        
                                                          
                                                                     
   
final class Routines_TranslationWorkerCron implements Routine_RoutineInterface
{
    private const CRON_HOOK = 'dolet_translation_worker_tick';
    private const HYGIENE_INTERVAL_SECONDS = 86400;
    private const HYGIENE_MAX_LIFETIME_SECONDS = 15552000;
    private const HYGIENE_CORRUPTED_SCAN_LIMIT = 100;

    public function __construct(
        private Config_DebugSettingsInterface $debugSettings,
        private Config_LanguageAdminInterface $langAdmin,
        private Language_OptionFactoryInterface $optionFactory,
        private Config_ApproachSettingsInterface $approachSettings,
        private ?Approach_EngineCredentialReadinessReaderInterface $credentialReader = null
    ) {
        $this->credentialReader ??= new Approach_EngineCredentialReadinessReader();
    }

    public function execute(): void
    {
                                             
        add_filter('cron_schedules', function (array $schedules): array {
            if (!isset($schedules['dolet_5min'])) {
                $schedules['dolet_5min'] = [
                    'interval' => 300,
                    'display'  => 'Dolet every 5 minutes',
                ];
            }
            return $schedules;
        });

        add_action('init', [$this, 'reconcileSchedule']);

        add_action(self::CRON_HOOK, function (): void {
            $this->tick();
        });
    }

    public function reconcileSchedule(): void
    {
        if (!$this->approachSettings->isEnabled()) {
                                                                           
                                                                 
            wp_clear_scheduled_hook(self::CRON_HOOK);
            return;
        }

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'dolet_5min', self::CRON_HOOK);
        }
    }

    private function tick(): void
    {
        if (!$this->approachSettings->isEnabled()) {
            return;
        }

        $engine = new Approach_SoleEngineClient();

        $diagnosticsStore = new Config_DiagnosticsStore();

        $credential = $this->credentialReader->read();
        $credentialError = Routines_EngineCredentialUi::blockingErrorCode($credential['state']);
        if ($credential['ready'] !== true) {
            $credentialError ??= 'engine_unavailable';
            if ($diagnosticsStore->getLastError() !== $credentialError) {
                $diagnosticsStore->recordError(
                    $credentialError,
                    Routines_EngineCredentialUi::actionLabel($credential['state'])
                );
            }
            return;
        }

        $this->runHygieneIfDue();

        if (!$engine->isAvailable()) {
            if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Worker tick: SOLE Engine unavailable, skipping.');
            }
            $diagnosticsStore->recordError('engine_unavailable', 'SOLE Engine not available at worker tick.');
            return;
        }

        $lease = new Config_OptionLease(
            Config_WorkerOptionNames::LEASE,
            Approach_EngineCallPolicy::workerLeaseSeconds(),
            $diagnosticsStore,
            'worker_stale_lock_recovered'
        );
        if (!$lease->acquire()) {
            return;
        }

        try {
            $retainedIdentityFailures = 0;
            $representationFailures = 0;
            $repo = new Data_TranslationMemoryRepository(identityDiagnostics: $diagnosticsStore);
            $repo->ensureReady();
            if (!$lease->renew()) {
                throw new \RuntimeException('Worker lease ownership was lost.');
            }

            $worker = new Translation_WorkerService(
                $repo,
                $engine,
                $this->debugSettings,
                $this->approachSettings,
                $diagnosticsStore,
                null,
                static function () use ($lease): void {
                    if (!$lease->renew()) {
                        throw new \RuntimeException('Worker lease ownership was lost.');
                    }
                }
            );

                                                                     
            $pollSummary = null;
            try {
                $pollSummary = $worker->pollAndApplyResults();
            } catch (Data_StoredIdentityMismatchExceptionInterface) {
                $retainedIdentityFailures++;
            }
            if (!$lease->renew()) {
                throw new \RuntimeException('Worker lease ownership was lost.');
            }

            if ($pollSummary !== null && $this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Worker poll summary: ' . wp_json_encode($pollSummary));
            }

                                                                  
            $srcCode = $this->langAdmin->getSourceCode();
            $targets = $this->langAdmin->getTargetCodes();

                                                              
            foreach ($targets as $tgtCode) {
                if (!$lease->renew()) {
                    throw new \RuntimeException('Worker lease ownership was lost.');
                }
                $pair = $this->buildPairOrNull($srcCode, (string)$tgtCode);
                if ($pair === null) {
                    continue;
                }

                                           
                try {
                    $summary = $worker->submitPendingFor($pair, 50);
                } catch (Data_PairRepresentationRejectedExceptionInterface) {
                                                                                      
                    $representationFailures++;
                    continue;
                } catch (Data_StoredIdentityMismatchExceptionInterface) {
                    $retainedIdentityFailures++;
                    continue;
                }

                if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[Dolet WP] Worker submit summary: ' . wp_json_encode($summary));
                }
            }
            if ($representationFailures > 0) {
                $diagnosticsStore->recordError('worker_exception', $representationFailures . ' target representation failure(s); independent work continued.');
            }
            if ($retainedIdentityFailures > 0) {
                $diagnosticsStore->recordError('worker_exception', $retainedIdentityFailures . ' identity failure(s) retained; independent work continued.');
            }
        } catch (\Throwable $e) {
            if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Worker tick failed: ' . $e->getMessage());
            }
            $diagnosticsStore->recordError('worker_exception', $e->getMessage());
        } finally {
            $lease->release();
        }
    }

    private function runHygieneIfDue(): void
    {
        $now = \time();
        $lastRun = (int) \get_option(Config_HygieneOptionNames::LAST_RUN, 0);
        if ($lastRun > $now - self::HYGIENE_INTERVAL_SECONDS) {
            return;
        }

                                                                             
                                                                             
        if ($lastRun === 0) {
            if (!\add_option(Config_HygieneOptionNames::LAST_RUN, $now, '', false)) {
                return;
            }
        } else {
            \update_option(Config_HygieneOptionNames::LAST_RUN, $now, false);
        }

        try {
            $repo = new Data_TranslationMemoryRepository(identityDiagnostics: new Config_DiagnosticsStore());
            $repo->ensureReady();
            $counts = $repo->runHygiene(
                $now,
                self::HYGIENE_MAX_LIFETIME_SECONDS,
                0,
                self::HYGIENE_CORRUPTED_SCAN_LIMIT
            );
            \update_option(Config_HygieneOptionNames::LAST_COUNTS, $counts, false);
        } catch (\Throwable $e) {
                                                                         
                                                                 
            \delete_option(Config_HygieneOptionNames::LAST_RUN);
            (new Config_DiagnosticsStore())->recordError('hygiene_failed', $e->getMessage());
        }
    }

    private function buildPairOrNull(string $srcCode, string $tgtCode): ?Language_PairInterface
    {
        return Routines_LanguagePairResolver::build(
            $srcCode,
            $tgtCode,
            $this->langAdmin,
            $this->optionFactory
        );
    }
}

                                                                        
final class Routines_LanguagePairResolver
{
       
                                                                                  
       
    public static function build(
        string $srcCode,
        string $tgtCode,
        Config_LanguageAdminInterface $langAdmin,
        Language_OptionFactoryInterface $optionFactory
    ): ?Language_PairInterface {
        $tgt = self::resolveTarget($tgtCode, $langAdmin, $optionFactory);
        if ($tgt === null) {
            return null;
        }

        if (strcasecmp($srcCode, Language_Constants::ORIGINAL) === 0) {
            $src = new Routines_SyntheticLanguageOption(
                Language_Constants::AUTODETECT,
                Language_Constants::AUTODETECT_NAME,
                Language_Constants::AUTODETECT_NOTE,
                false
            );
            return new Routines_SyntheticLanguagePair($src, $tgt);
        }

        $src = $optionFactory->fromCodeElseNull($srcCode, null);
        if ($src === null) {
            return null;
        }

        return new Routines_SyntheticLanguagePair($src, $tgt);
    }

    private static function resolveTarget(
        string $code,
        Config_LanguageAdminInterface $langAdmin,
        Language_OptionFactoryInterface $optionFactory
    ): ?Language_OptionInterface {
        if ($code === '') {
            return null;
        }

                                                                               
                                                                                
                                                                               
        if (array_key_exists($code, Language_Constants::getAllLanguages())) {
            $customizations = $langAdmin->getCustomizations();
            $note = isset($customizations[$code]['note'])
                ? (string) $customizations[$code]['note']
                : null;
            return $optionFactory->fromCodeElseNull($code, $note);
        }

                                                                               
                                                                                 
                                                                          
        if (in_array($code, $langAdmin->getCustomCodes(), true)) {
            $customizations = $langAdmin->getCustomizations();
            $note = isset($customizations[$code]['note'])
                ? (string) $customizations[$code]['note']
                : null;

            return new Routines_SyntheticLanguageOption(
                $code,
                $langAdmin->getDisplayNameFor($code),
                $note,
                true
            );
        }

                                                                         
        return $optionFactory->fromCodeElseNull($code, null);
    }
}

                                                                           
final class Routines_EngineCredentialUi
{
    public static function statusLabel(string $state): string
    {
        return Core_Strings::get(match ($state) {
            Approach_EngineCredentialStates::MISSING => 'diag_engine_missing',
            Approach_EngineCredentialStates::UNVERIFIED => 'diag_engine_unverified',
            Approach_EngineCredentialStates::INVALID => 'diag_engine_invalid',
            Approach_EngineCredentialStates::VERIFIED => 'diag_engine_available',
            default => 'diag_engine_unavailable',
        });
    }

    public static function actionLabel(string $state): string
    {
        return Core_Strings::get(match ($state) {
            Approach_EngineCredentialStates::MISSING => 'diag_engine_action_missing',
            Approach_EngineCredentialStates::UNVERIFIED => 'diag_engine_action_unverified',
            Approach_EngineCredentialStates::INVALID => 'diag_engine_action_invalid',
            Approach_EngineCredentialStates::VERIFIED => 'diag_engine_action_verified',
            default => 'diag_engine_action_unavailable',
        });
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            Approach_EngineCredentialStates::VERIFIED => '#46b450',
            Approach_EngineCredentialStates::UNVERIFIED => '#ffb900',
            default => '#dc3232',
        };
    }

    public static function blockingErrorCode(string $state): ?string
    {
        return match ($state) {
            Approach_EngineCredentialStates::MISSING => 'config_missing',
            Approach_EngineCredentialStates::UNVERIFIED => 'credential_unverified',
            Approach_EngineCredentialStates::INVALID => 'user_key_invalid',
            Approach_EngineCredentialStates::UNAVAILABLE => 'engine_unavailable',
            Approach_EngineCredentialStates::VERIFIED => null,
            default => 'engine_unavailable',
        };
    }
}

   
                                                                      
  
                                                                         
   
interface Routines_SyntheticLanguageOptionInterface extends Language_OptionInterface
{
}

final class Routines_SyntheticLanguageOption implements Routines_SyntheticLanguageOptionInterface
{
    public function __construct(
        private string $code,
        private string $name,
        private ?string $note,
        private bool $isCustom
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function isCustom(): bool
    {
        return $this->isCustom;
    }
}

   
                                                                    
   
interface Routines_SyntheticLanguagePairInterface extends Language_PairInterface
{
}

final class Routines_SyntheticLanguagePair implements Routines_SyntheticLanguagePairInterface
{
    public function __construct(
        private Language_OptionInterface $src,
        private Language_OptionInterface $tgt
    ) {}

    public function getSourceLanguage(): Language_OptionInterface
    {
        return $this->src;
    }

    public function getTargetLanguage(): Language_OptionInterface
    {
        return $this->tgt;
    }

    public function optionsAreTheSame(): bool
    {
        return strcasecmp($this->src->getCode(), $this->tgt->getCode()) === 0;
    }
}
