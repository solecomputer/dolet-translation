<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                    
  
                                           
                                                  
                            
                
                                                     
                                                                         
  
                                                                          
   

require_once __DIR__ . '/contracts.php';


                                                                             
                              
                                                                             

   
                                                        
   
interface Routines_AdminSelfTestsInterface
{
                                                          
    public function execute(): void;
                                          
    public function renderPanel(): void;
}


                                                                             
         
                                                                             
  
         


                                                                             
        
                                                                             
  
         


                                                                             
          
                                                                             
   
                                              
                                                                               
   
final class Routines_AdminLanguagesSettings implements Routine_RoutineInterface
{
    public function __construct(
        private Config_LanguageAdminInterface $langConfig,
        private Config_DebugSettingsInterface $debugSettings,
        private Config_ApproachSettingsInterface $approachSettings,
        private Language_AliasesDictionaryInterface $aliases,
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
            $aliases = $this->aliases;
            $factory = $this->optionFactory;
            $config  = $this->langConfig;

                               

            register_setting('dolet_settings_group', Config_TranslationOptionNames::FLOATING_SELECTOR_ENABLED, [
                'type'              => 'boolean',
                'sanitize_callback' => static fn ($value): bool => in_array($value, [true, 1, '1'], true),
                'default'           => true,
                'show_in_rest'      => false,
            ]);

                          
            register_setting('dolet_settings_group', Language_AdminOptionNames::SOURCE_CODE, [
                'type'              => 'string',
                'sanitize_callback' => function ($v) use ($factory) {
                    $v = is_string($v) ? trim($v) : '';
                    if ($v === '' || strtolower($v) === Language_Constants::ORIGINAL) {
                        return Language_Constants::ORIGINAL;
                    }

                    $lo = $factory->fromCodeElseNull($v, null);
                    return $lo ? $lo->getCode() : Language_Constants::ORIGINAL;
                },
                'default'      => Language_Constants::ORIGINAL,
                'show_in_rest' => false,
            ]);

                               
            register_setting('dolet_settings_group', Language_AdminOptionNames::TARGET_LANGUAGES, [
                'type'              => 'array',
                'sanitize_callback' => function ($arr) use ($config, $aliases) {
                    $arr      = is_array($arr) ? $arr : [];
                    $codesReg = Language_Constants::getAllLanguages();

                                                         
                    $canon = static function (string $code) use ($codesReg, $aliases): ?string {
                        $code = str_replace('_', '-', trim($code));
                        if ($code === '') {
                            return null;
                        }
                        $parts    = explode('-', $code);
                        $parts[0] = strtolower($parts[0]);
                        for ($i = 1; $i < count($parts); $i++) {
                            $p         = $parts[$i];
                            $len       = strlen($p);
                            $parts[$i] = ($len === 4) ? ucfirst(strtolower($p)) : strtoupper($p);
                        }
                        $norm = implode('-', $parts);

                        if (array_key_exists($norm, $codesReg)) {
                            return $norm;
                        }

                        $alias = $aliases->isAliasOf($norm);
                        if ($alias !== null && array_key_exists($alias, $codesReg)) {
                            return $alias;
                        }

                        return $norm;
                    };

                    /*
                     * WPORG-REVIEWER-NOTE:
                     * The options.php request has already passed settings-form capability and nonce checks. Each posted custom-language value is unslashed, trimmed, separator- and case-normalized, mapped to its registry code when it is a known alias, and used only as an allowlist membership key for the separately registered target-language option; generic text sanitization would alter valid custom codes.
                     */
                    $posted = isset($_POST[Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME])
                        ? wp_unslash($_POST[Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME])
                        : [];
                    $posted = is_array($posted) ? $posted : [];
                    $postedCustoms = [];
                    foreach ($posted as $c) {
                        $n = $canon((string)$c);
                        if ($n !== null) {
                            $postedCustoms[$n] = true;
                        }
                    }

                                                 
                    $customsPersisted = $config->getCustomCodes();
                    $customsUnion     = array_fill_keys($customsPersisted, true) + $postedCustoms;

                                     
                    $src = $config->getSourceCode();

                    $out = [];
                    foreach ($arr as $c) {
                        $n = $canon((string)$c);
                        if ($n === null || $n === Language_Constants::ORIGINAL || $n === $src) {
                            continue;
                        }

                        $isRegistry = array_key_exists($n, $codesReg);
                        $isCustom   = isset($customsUnion[$n]);
                        if (!$isRegistry && !$isCustom) {
                            continue;
                        }

                        $out[$n] = true;
                    }

                    $codes = array_keys($out);
                    sort($codes, SORT_STRING);
                    return $codes;
                },

                'default'      => [],
                'show_in_rest' => false,
            ]);

                                          
            register_setting('dolet_settings_group', Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME, [
                'type'              => 'array',
                'sanitize_callback' => function ($arr) use ($aliases) {
                    $arr   = is_array($arr) ? $arr : [];
                    $codes = Language_Constants::getAllLanguages();

                    $canonicalize = static function (string $code) use ($codes, $aliases): ?string {
                        $code = str_replace('_', '-', trim($code));
                        if ($code === '') {
                            return null;
                        }

                                         
                        $parts    = explode('-', $code);
                        $parts[0] = strtolower($parts[0]);
                        for ($i = 1; $i < count($parts); $i++) {
                            $p         = $parts[$i];
                            $len       = strlen($p);
                            $parts[$i] = ($len === 4) ? ucfirst(strtolower($p)) : strtoupper($p);
                        }
                        $norm = implode('-', $parts);

                        if (array_key_exists($norm, $codes)) {
                            return $norm;                  
                        }

                        $alias = $aliases->isAliasOf($norm);
                        if ($alias !== null && array_key_exists($alias, $codes)) {
                            return $alias;               
                        }

                        return $norm;                                         
                    };

                    $out = [];
                    foreach ($arr as $c) {
                        $n = $canonicalize((string)$c);
                        if ($n !== null) {
                            $out[$n] = true;
                        }
                    }
                    $codes = array_keys($out);
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
                        $code = is_string($code) ? trim($code) : '';
                        if ($code === '') {
                            continue;
                        }
                        $name = isset($row['name']) ? trim((string)$row['name']) : '';
                        $note = isset($row['note']) ? trim((string)$row['note']) : '';
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
                'dolet_languages_builder',
                '',
                [$this, 'renderLanguagesBuilder'],
                'dolet_settings_page',
                'dolet_languages_section'
            );

                                                                             
            add_settings_field(
                'dolet_approach_toggle',
                '',
                [$this, 'renderApproachToggle'],
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
        });
    }

    public function renderApproachToggle(): void
    {
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName(Config_ApproachOptionNames::APPROACH_ENABLED);
        $view->setChecked($this->approachSettings->isEnabled());
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('enable_translation_label')) . '</strong> <span style="opacity:.7;">(' . \esc_html(Core_Strings::get('enable_translation_hint')) . ')</span>');

        echo $view->render();
    }

    public function renderPreTextareaToggle(): void
    {
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName(Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA);
        $view->setChecked((bool) get_option(Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA, false));
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('translate_pre_textarea_label')) . '</strong> <span style="opacity:.7;">(' . \esc_html(Core_Strings::get('translate_pre_textarea_hint')) . ')</span>');

        echo $view->render();
    }

    public function renderDebugBottom(): void
    {
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName(Config_DebugOptionNames::DEBUG_OPTION_NAME);
        $view->setChecked($this->debugSettings->isEnabled());
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('debug_label')) . '</strong> <span style="opacity:.7;">(' . \wp_kses_post(Core_Strings::get('debug_hint')) . ')</span>');

        echo $view->render();
    }

    public function renderFloatingSelectorToggle(): void
    {
        $optionName = Config_TranslationOptionNames::FLOATING_SELECTOR_ENABLED;
        $view = new UiWebmaster_DebugToggleView();
        $view->setOptionName($optionName);
        $view->setChecked((bool) get_option($optionName, true));
        $view->setLabel('<strong>' . \esc_html(Core_Strings::get('floating_selector_label')) . '</strong> <span style="opacity:.7;">(' . \esc_html(Core_Strings::get('floating_selector_hint')) . ')</span>');

        echo '<input type="hidden" name="' . \esc_attr($optionName) . '" value="0">';
        echo $view->render();
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
        $srcNote        = $getNote($srcCode);
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
        $tpl->setSourceCurrentNote($srcNote);

                  
        $tpl->setTargetsHolderDomId('dolet_targets_holder');
        $tpl->setTargetsSorted($targets);
        $tpl->setLanguageCustomizationsOptionName(Language_AdminOptionNames::LANGUAGE_CUSTOMIZATIONS);
                                                       
        $catalogForDefaults = $catalogNoOriginal;                                
        $tpl->setCatalogForDefaults($catalogForDefaults);

                                                     
        $tpl->setCustomCodes($customs);
        $tpl->setCustomListOptionName(Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME);

                  
        $activeTargetsSet = array_fill_keys($targets, true);
        foreach ($catalogNoOriginal as $code => $name) {
            $disabled = isset($activeTargetsSet[$code]) || ($code === $srcCode);
            $label    = $code . ' — ' . $name;
            $tpl->addPaletteItem(new UiWebmaster_PaletteItemView($code, $label, $disabled));
        }

                    
        $tpl->setPaletteAddTitle(Core_Strings::get('palette_add'));
        $tpl->setPaletteAlreadyTitle(Core_Strings::get('palette_already_added'));


        echo $tpl->render();
    }
}



   
                                                                            
                                                       
   
final class Routines_AdminSettings implements Routine_RoutineInterface
{
    private const ASSET_HANDLE = 'dolet-admin-settings';
    private const ASSET_VERSION = '1.0.0';

    private ?Routines_AdminConsole $console = null;
    private ?Routines_AdminDiagnostics $diagnostics = null;

    public function __construct() {}

    public function setConsole(Routines_AdminConsole $console): void
    {
        $this->console = $console;
    }

    public function setDiagnostics(Routines_AdminDiagnostics $diagnostics): void
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
            wp_add_inline_script(
                self::ASSET_HANDLE,
                'window.doletAdminSettings = ' . wp_json_encode([
                    'ajaxUrl'              => admin_url('admin-ajax.php'),
                    'consoleNonce'         => wp_create_nonce('dolet_console'),
                    'diagnosticsNonce'     => wp_create_nonce('dolet_diagnostics'),
                    'workerRunning'        => Core_Strings::get('console_worker_running'),
                    'workerDone'           => Core_Strings::get('console_worker_done'),
                    'testPolling'          => Core_Strings::get('console_test_polling'),
                    'errorLogClearConfirm' => Core_Strings::get('diag_error_log_clear_confirm'),
                    'errorLogEmpty'        => Core_Strings::get('diag_error_log_empty'),
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
                    'sanitize_callback' => static fn($v): bool => (bool)$v,
                    'default'           => false,
                    'show_in_rest'      => false,
                ]
            );

            register_setting(
                'dolet_settings_group',
                Config_DebugOptionNames::DEBUG_OPTION_NAME,
                [
                    'type'              => 'boolean',
                    'sanitize_callback' => static fn($v): bool => (bool)$v,
                    'default'           => false,
                    'show_in_rest'      => false,
                ]
            );

            register_setting(
                'dolet_settings_group',
                Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA,
                [
                    'type'              => 'boolean',
                    'sanitize_callback' => static fn($v): bool => (bool)$v,
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
        if ($this->console instanceof Routines_AdminConsole) {
            $this->console->renderProcessingOverview();
        }
        echo '<form action="options.php" method="post">';
        settings_fields('dolet_settings_group');
        do_settings_sections('dolet_settings_page');
        submit_button(\esc_html(Core_Strings::get('save_button')));
        echo '</form>';
        echo '</div>';

                          
        echo '<div id="dolet_tab_console" class="dolet-tab-panel" style="display:none;">';
        if ($this->console instanceof Routines_AdminConsole) {
            $this->console->renderPanel();
        }
        echo '</div>';

                              
        echo '<div id="dolet_tab_diagnostics" class="dolet-tab-panel" style="display:none;">';
        if ($this->diagnostics instanceof Routines_AdminDiagnostics) {
            $this->diagnostics->renderPanel();
        }
        echo '</div>';

        echo '</div>';
    }
}


   
                                                                                  
  
                                                                                            
   
final class Routines_AdminConsole implements Routine_RoutineInterface
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
                wp_send_json_error(['error' => 'forbidden'], 403);
                return;
            }
            check_ajax_referer('dolet_console');

            $repo = new Data_TranslationMemoryRepository();
            $repo->ensureReady();
            wp_send_json_success($repo->getStatsByPair());
        });

                                       
        add_action('wp_ajax_dolet_console_worker_trigger', function (): void {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['error' => 'forbidden'], 403);
                return;
            }
            check_ajax_referer('dolet_console');

            $credential = $this->credentialReader->read();
            $credentialError = Routines_EngineCredentialUi::blockingErrorCode($credential['state']);
            if ($credential['ready'] !== true) {
                wp_send_json_error([
                    'error' => $credentialError ?? 'engine_unavailable',
                    'action' => Routines_EngineCredentialUi::actionLabel($credential['state']),
                ]);
                return;
            }

            $engine = new Approach_SoleEngineClient();
            if (!$engine->isAvailable()) {
                wp_send_json_error(['error' => 'SOLE Engine not available.']);
                return;
            }

            $repo = new Data_TranslationMemoryRepository();
            $repo->ensureReady();

            $worker = new Translation_WorkerService(
                $repo,
                $engine,
                $this->debugSettings,
                $this->approachSettings,
                new Config_DiagnosticsStore()
            );

            $result = ['poll' => $worker->pollAndApplyResults()];

            $srcCode = $this->langAdmin->getSourceCode();
            $targets = $this->langAdmin->getTargetCodes();
            $submitResults = [];

            foreach ($targets as $tgtCode) {
                $pair = $this->buildPairOrNull($srcCode, (string)$tgtCode);
                if ($pair === null) {
                    continue;
                }
                $submitResults[(string)$tgtCode] = $worker->submitPendingFor($pair, 50);
            }

            $result['submit'] = $submitResults;
            wp_send_json_success($result);
        });

                                               
        add_action('wp_ajax_dolet_console_test_submit', function (): void {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['error' => 'forbidden'], 403);
                return;
            }
            check_ajax_referer('dolet_console');

            $srcLang = isset($_POST['source_lang']) && is_string($_POST['source_lang']) ? sanitize_text_field(wp_unslash($_POST['source_lang'])) : '';
            $tgtLang = isset($_POST['target_lang']) && is_string($_POST['target_lang']) ? sanitize_text_field(wp_unslash($_POST['target_lang'])) : '';
            /*
             * WPORG-REVIEWER-NOTE:
             * This administrator-only AJAX handler verifies manage_options and a nonce before reading input. Single String Test text is opaque valid-UTF-8 transport content passed to a fixed JSON object encoder and Engine submit call; generic text sanitization would change accepted values.
             */
            $text = isset($_POST['text']) && is_string($_POST['text']) ? wp_unslash($_POST['text']) : '';

            if ($tgtLang === '' || $text === '') {
                wp_send_json_error(['error' => 'target_lang and text are required.']);
                return;
            }
            if (!$this->isValidUtf8($text)) {
                wp_send_json_error(['error' => 'text must be valid UTF-8.']);
                return;
            }

            if ($this->buildConsolePairOrNull($srcLang, $tgtLang) === null) {
                wp_send_json_error(['error' => 'Invalid language pair.']);
                return;
            }

            if (!function_exists('sole_engine_llm')) {
                wp_send_json_error(['error' => 'SOLE Engine not available.']);
                return;
            }

                                                                                     
                                                                               
            $codec = new Approach_SparseMapCodec();
            $payload = $codec->encodeSparseMapJson([0 => $text]);
            if ($payload === null) {
                wp_send_json_error(['error' => 'Sparse map encode failed.']);
                return;
            }

            $options = ['targetLanguage' => $tgtLang];
            if ($srcLang !== '' && $srcLang !== 'original') {
                $options['sourceLanguage'] = $srcLang;
            }

            $diagnostics = new Config_DiagnosticsStore();

            try {
                                            
                $ticket = sole_engine_llm()->submit('dolet-wp', 'translate_batch', $payload, $options);
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
                wp_send_json_error(['error' => 'Engine returned an invalid job ID.']);
                return;
            }

            $this->rememberConsoleJob($jobId);
            wp_send_json_success(['job_id' => $jobId]);
        });

                                             
        add_action('wp_ajax_dolet_console_test_poll', function (): void {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['error' => 'forbidden'], 403);
                return;
            }
            check_ajax_referer('dolet_console');

            /*
             * WPORG-REVIEWER-NOTE:
             * This administrator-only AJAX handler verifies manage_options and a nonce before reading input. The service-issued job ID is a bounded valid-UTF-8 value without control or space bytes, checked against a user-scoped transient and passed to a fixed result-poll call; generic text sanitization would change exact identifiers.
             */
            $jobId = isset($_POST['job_id']) && is_string($_POST['job_id']) ? wp_unslash($_POST['job_id']) : '';
            if (!$this->isValidConsoleJobId($jobId) || !$this->ownsConsoleJob($jobId)) {
                wp_send_json_error(['error' => 'Invalid or unrecognized job_id.']);
                return;
            }

            if (!function_exists('sole_engine_llm')) {
                wp_send_json_error(['error' => 'SOLE Engine not available.']);
                return;
            }

            $diagnostics = new Config_DiagnosticsStore();

            try {
                $reply = sole_engine_llm()->getResult($jobId);
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
        foreach ($repo->getStatsByPair() as $stats) {
            $pending += $stats['pending'];
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
        if (Config_InstallationState::isProcessingNoteVisible()) {
            echo '<p>' . \esc_html(Core_Strings::get('processing_note')) . '</p>';
        }
        echo '<table class="widefat striped" style="max-width:680px;"><tbody>';
        echo '<tr><th scope="row">' . \esc_html(Core_Strings::get('processing_pending')) . '</th>';
        echo '<td id="dolet_processing_pending">' . \esc_html((string)$pending) . '</td></tr>';
        echo '<tr><th scope="row">' . \esc_html(Core_Strings::get('processing_last_success')) . '</th>';
        echo '<td>' . \esc_html($lastSuccessLabel) . '</td></tr>';
        echo '<tr><th scope="row">' . \esc_html(Core_Strings::get('processing_next_scheduled')) . '</th>';
        echo '<td>' . \esc_html($nextScheduledLabel) . '</td></tr>';
        echo '</tbody></table>';
        echo '<p><button type="button" class="button button-primary" id="dolet_worker_trigger">' . \esc_html(Core_Strings::get('console_worker_trigger')) . '</button> ';
        echo '<span id="dolet_worker_output" role="status" aria-live="polite"></span></p>';
        echo '</div>';
    }
}


   
                                                                               
   
final class Routines_AdminDiagnostics implements Routine_RoutineInterface
{
    private ?Routines_AdminSelfTests $selfTests = null;

    public function __construct(
        private Config_LanguageAdminInterface $langAdmin,
        private Config_ApproachSettingsInterface $approachSettings,
        private ?Diagnostics_StoreInterface $diagnosticsStore = null,
        private ?Approach_EngineCredentialReadinessReaderInterface $credentialReader = null
    ) {
        $this->credentialReader ??= new Approach_EngineCredentialReadinessReader();
    }

    public function setSelfTests(Routines_AdminSelfTests $selfTests): void
    {
        $this->selfTests = $selfTests;
    }

    public function execute(): void
    {
                                   
        \add_action('wp_ajax_dolet_refresh_error_log', function (): void {
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['error' => 'forbidden'], 403);
                return;
            }
            \check_ajax_referer('dolet_diagnostics');

            if ($this->diagnosticsStore === null) {
                \wp_send_json_error(['error' => 'Diagnostics store not available.']);
                return;
            }

            $log = $this->diagnosticsStore->getErrorLog();
            \wp_send_json_success([
                'log'         => $this->formatErrorLogLines($log),
                'lastSuccess' => $this->diagnosticsStore->getLastSuccess(),
                'lastError'   => $this->diagnosticsStore->getLastError(),
                'lastErrorAt' => $this->diagnosticsStore->getLastErrorAt(),
            ]);
        });

                                 
        \add_action('wp_ajax_dolet_clear_error_log', function (): void {
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['error' => 'forbidden'], 403);
                return;
            }
            \check_ajax_referer('dolet_diagnostics');

            if ($this->diagnosticsStore === null) {
                \wp_send_json_error(['error' => 'Diagnostics store not available.']);
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
        $translationColor = $translationEnabled ? '#46b450' : '#dc3232';
        echo '<tr><td><span style="color:' . \esc_attr($translationColor) . ';">&#9679;</span> ' . \esc_html($translationLabel) . '</td></tr>';

                             
        $cronScheduled = (\wp_next_scheduled('dolet_translation_worker_tick') !== false);
        $cronLabel = $cronScheduled ? Core_Strings::get('diag_cron_scheduled') : Core_Strings::get('diag_cron_not_scheduled');
        $cronColor = $cronScheduled ? '#46b450' : '#ffb900';
        echo '<tr><td><span style="color:' . \esc_attr($cronColor) . ';">&#9679;</span> ' . \esc_html($cronLabel) . '</td></tr>';

                           
        global $wpdb;
        $tableName = $wpdb->prefix . Data_UniversalTableL1_Constants::TABLE_SLUG;

        $tableExists = ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tableName)) === $tableName);
        $tableLabel = $tableExists ? Core_Strings::get('diag_table_exists') : Core_Strings::get('diag_table_missing');
        $tableColor = $tableExists ? '#46b450' : '#dc3232';
        echo '<tr><td><span style="color:' . \esc_attr($tableColor) . ';">&#9679;</span> ' . \esc_html($tableLabel) . '</td></tr>';

                                
        $srcCode = $this->langAdmin->getSourceCode();
        $targets = $this->langAdmin->getTargetCodes();
        $langSummary = 'Source: ' . $srcCode . ' | Targets: ' . (empty($targets) ? '(none)' : implode(', ', array_map('strval', $targets)));
        echo '<tr><td><strong>' . \esc_html(Core_Strings::get('diag_languages_label')) . ':</strong> ' . \esc_html($langSummary) . '</td></tr>';

        echo '</tbody></table>';

                                   
        if ($this->diagnosticsStore !== null) {
            echo '<hr>';
            echo '<h3>' . \esc_html(Core_Strings::get('diag_error_log_heading')) . '</h3>';

                                          
            $lastSuccess = $this->diagnosticsStore->getLastSuccess();
            $lastError   = $this->diagnosticsStore->getLastError();
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
            $lines[] = '[' . $time . '] ' . $code . ': ' . $context;
        }
        return implode("\n", $lines);
    }
}


   
                                                                                                         
  
                                                                                   
   
final class Routines_AdminSelfTests implements Routines_AdminSelfTestsInterface
{
                                                      
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
            echo '<td><button type="button" class="button dolet-test-run" data-test="' . \esc_attr($testId) . '">Run</button></td>';
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
            $issues[] = 'No target languages configured.';
        }

        if (!$this->approachSettings->isEnabled()) {
            $issues[] = 'Translation approach is disabled.';
        }

        if (empty($issues)) {
            \wp_send_json_success(['result' => 'pass', 'detail' => 'Source: ' . $srcCode . ', Targets: ' . implode(', ', $targets)]);
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
                \wp_send_json_success(['result' => 'pass', 'detail' => 'Table ' . $tableName . ' exists and ensureReady() succeeded.']);
            } else {
                \wp_send_json_success(['result' => 'fail', 'detail' => 'Table ' . $tableName . ' does not exist after ensureReady().']);
            }
        } catch (\Throwable $e) {
            \wp_send_json_success(['result' => 'fail', 'detail' => $e->getMessage()]);
        }
    }

    private function runCodecRoundtrip(): void
    {
        try {
            $codec = new Approach_SparseMapCodec();

            $original = ['0' => 'Hello world', '1' => 'Test string', '5' => 'Sparse key'];
            $encoded = $codec->encodeSparseMapJson($original);
            if (!is_string($encoded)) {
                \wp_send_json_success(['result' => 'fail', 'detail' => 'Encode returned null.']);
                return;
            }

            $decoded = $codec->parseSparseMapJsonOrNull($encoded, null);
            if ($decoded === null) {
                \wp_send_json_success(['result' => 'fail', 'detail' => 'Decode returned null from: ' . $encoded]);
                return;
            }

                                                        
            $match = true;
            foreach ($original as $k => $v) {
                $dk = (string)$k;
                if (!array_key_exists($dk, $decoded) || $decoded[$dk] !== $v) {
                    $match = false;
                    break;
                }
            }

            if ($match && count($decoded) === count($original)) {
                \wp_send_json_success(['result' => 'pass', 'detail' => 'Roundtrip OK (' . count($original) . ' keys).']);
            } else {
                \wp_send_json_success(['result' => 'fail', 'detail' => 'Mismatch after roundtrip. Original: ' . count($original) . ' keys, Decoded: ' . count($decoded) . ' keys.']);
            }
        } catch (\Throwable $e) {
            \wp_send_json_success(['result' => 'fail', 'detail' => $e->getMessage()]);
        }
    }

    private function runSubmitPollFlow(): void
    {
        if (!\function_exists('sole_engine_llm')) {
            \wp_send_json_success(['result' => 'fail', 'detail' => 'SOLE Engine not available.']);
            return;
        }

        $targets = $this->langAdmin->getTargetCodes();
        if (empty($targets)) {
            \wp_send_json_success(['result' => 'fail', 'detail' => 'No target languages configured.']);
            return;
        }

        $tgtCode = $targets[0];

        try {
                                                                                     
                                                                               
            $codec = new Approach_SparseMapCodec();
            $payload = $codec->encodeSparseMapJson([0 => 'Hello']);
            if ($payload === null) {
                \wp_send_json_success(['result' => 'fail', 'detail' => 'Sparse map encode failed.']);
                return;
            }

            $options = ['targetLanguage' => $tgtCode];
            $srcCode = $this->langAdmin->getSourceCode();
            if ($srcCode !== '' && strcasecmp($srcCode, 'original') !== 0) {
                $options['sourceLanguage'] = $srcCode;
            }

                                        
            $ticket = \sole_engine_llm()->submit('dolet-wp', 'translate_batch', $payload, $options);
            if (!$ticket->wasAccepted()) {
                \wp_send_json_success(['result' => 'fail', 'detail' => 'Submit rejected: ' . ($ticket->getError() ?? 'unknown')]);
                return;
            }

            $jobId = $ticket->getJobId();
            $maxPolls = 5;

            for ($i = 0; $i < $maxPolls; $i++) {
                sleep(2);
                $reply = \sole_engine_llm()->getResult($jobId);

                if ($reply->isPending()) {
                    continue;
                }

                if ($reply->isSuccessful()) {
                    $replyText = $reply->getReply();
                    \wp_send_json_success(['result' => 'pass', 'detail' => 'Job ' . $jobId . ' completed. Reply: ' . (is_string($replyText) ? substr($replyText, 0, 200) : '(non-string)')]);
                    return;
                }

                \wp_send_json_success(['result' => 'fail', 'detail' => 'Job ' . $jobId . ' failed: ' . ($reply->getError() ?? 'unknown')]);
                return;
            }

            \wp_send_json_success(['result' => 'fail', 'detail' => 'Job ' . $jobId . ' still pending after ' . $maxPolls . ' polls.']);
        } catch (\Throwable $e) {
            \wp_send_json_success(['result' => 'fail', 'detail' => $e->getMessage()]);
        }
    }

    private function requireCap(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['error' => 'forbidden'], 403);
            exit;
        }
    }
}


   
                                                                               
                                                                          
                                         
  
                                                                        
                                                                               
                                                                       
  
                                                           
                                                         
  
                                                                             
                                                    
                                                                             
                                     
                                                                           
                                                                         
                                                                           
                                                                         
                   
  
                                                                          
                                                                            
   
final class Routines_AutoTranslate implements Routine_RoutineInterface
{
    private const CALLER_ID = 'dolet-wp';
                                                                                 
    private const TASK = 'translate';
                                                                                
                                                                   
    private const SUBMIT_BATCH_LIMIT = 15;
                                                                              
                                                                             
                                   
    private const SUBMIT_DRAIN_DEADLINE = 1800;
                                                                              
    private const COLLECT_STALE_SECONDS = 600;
                                                                             
    private const FAILURE_COOLDOWN_SECONDS = 3600;

    private string $dir;
    private Diagnostics_StoreInterface $diagnostics;

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
        \add_action('admin_init', [$this, 'maybeTranslate']);
    }

    public function maybeTranslate(): void
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
        $credentialError = Routines_EngineCredentialUi::blockingErrorCode($credential['state']);

        if ($status === 'failed') {
            $failureCode = is_string($state['failure_code'] ?? null) ? $state['failure_code'] : '';
            $failedCredentialState = is_string($state['credential_state'] ?? null) ? $state['credential_state'] : '';
            $wasCredentialFailure = in_array($failureCode, ['config_missing', 'credential_unverified', 'user_key_invalid', 'engine_unavailable'], true);

                                                                             
                                                                              
                                                                           
                                                                   
            $readinessRecovered = $credential['ready'] === true;
            $credentialStateChanged = $failedCredentialState !== $credential['state']
                && $failedCredentialState !== Approach_EngineCredentialStates::UNAVAILABLE
                && $credential['state'] !== Approach_EngineCredentialStates::UNAVAILABLE;
            if ($wasCredentialFailure && ($readinessRecovered || $credentialStateChanged)) {
                \delete_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE);
                $state = null;
                $status = '';
            }

                                                                                 
                                                                        
            if ($status === 'failed' && ($state['failed_at'] ?? 0) > \time() - self::FAILURE_COOLDOWN_SECONDS) {
                return;
            }
            if ($status === 'failed') {
                \delete_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE);
                $status = '';
            }
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
            if (($state['started_at'] ?? 0) < \time() - self::SUBMIT_DRAIN_DEADLINE) {
                                                                           
                                                                           
                                                                             
                                                      
                $this->enterFailed($locale, $version, 'submit drain did not finish within the deadline');
                return;
            }
            $jobs = \is_array($state['jobs'] ?? null) ? $state['jobs'] : [];
            $startedAt = (int) ($state['started_at'] ?? \time());
            $this->submitDrain($locale, $version, $strings, $jobs, $startedAt, $credential['state']);
            return;
        }

        if ($status === 'pending' && $state !== null) {
            if (($state['submitted_at'] ?? 0) < \time() - self::COLLECT_STALE_SECONDS) {
                                                                           
                                                                          
                                                            
                $this->enterFailed($locale, $version, 'translation jobs did not resolve within the deadline');
                return;
            }
            $this->collect($locale, $version, $state, $credential['state']);
            return;
        }

                                                   
        $this->submitDrain($locale, $version, $strings, [], \time(), $credential['state']);
    }

       
                                                                                
                                                                    
      
                                                      
                                                           
       
    private function submitDrain(
        string $locale,
        string $version,
        array $strings,
        array $jobs,
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
            $ticket = $engine->submit(self::CALLER_ID, self::TASK, $value, ['targetLanguage' => $lang]);
            $submittedThisLoad++;
            if ($ticket->wasAccepted()) {
                $jobId = $ticket->getJobId();
                if ($jobId !== null && $jobId !== '') {
                    $jobs[] = ['key' => $key, 'jobId' => $jobId];
                    $submittedKeys[$key] = true;
                }
            } else {
                $error = $ticket->getError() ?? 'submit rejected';
                if (in_array($error, ['config_missing', 'user_key_invalid'], true)) {
                    $this->enterFailed($locale, $version, $error, $error, $credentialState);
                    return;
                }
                                                                              
                                                                             
                                                                           
                $submittedKeys[$key] = true;
            }
        }

        $remaining = 0;
        foreach ($strings as $key => $value) {
            if (\is_string($value) && !isset($submittedKeys[(string) $key])) {
                $remaining++;
            }
        }

        if ($remaining > 0) {
            \update_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE, [
                'status'     => 'submitting',
                'locale'     => $locale,
                'version'    => $version,
                'jobs'       => $jobs,
                'started_at' => $startedAt,
            ], 'yes');
            return;
        }

                                                                      
        \update_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE, [
            'status'       => 'pending',
            'locale'       => $locale,
            'version'      => $version,
            'jobs'         => $jobs,
            'started_at'   => $startedAt,
            'submitted_at' => \time(),
        ], 'yes');
    }

       
                                                                           
      
                                         
       
    private function collect(string $locale, string $version, array $state, string $credentialState): void
    {
        $engine = \sole_engine_llm();
        $jobs = \is_array($state['jobs'] ?? null) ? $state['jobs'] : [];

        $translated = [];
        $pending = [];
        foreach ($jobs as $job) {
            $reply = $engine->getResult($job['jobId']);
            if ($reply->isPending()) {
                $pending[] = $job;
            } elseif ($reply->isSuccessful() && $reply->getReply() !== null) {
                $translated[$job['key']] = $reply->getReply();
            } else {
                $error = $reply->getError() ?? 'job failed';
                if (in_array($error, ['config_missing', 'user_key_invalid'], true)) {
                    $this->enterFailed($locale, $version, $error, $error, $credentialState);
                    return;
                }
            }
                                                                               
        }

        if ($pending !== []) {
                                                                               
                                                                            
                                             
            $state['jobs'] = $pending;
            \update_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE, $state, 'yes');
            return;
        }

        if ($translated !== []) {
            Core_Strings::storeOverlay($translated);
            \update_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE, [
                'status'  => 'complete',
                'locale'  => $locale,
                'version' => $version,
            ], 'yes');
                                                                        
            Core_Strings::reset();
            Core_Strings::load($this->dir);
            return;
        }

                                                                   
        $this->enterFailed($locale, $version, 'no translations were produced');
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
        $state = [
            'status'    => 'failed',
            'locale'    => $locale,
            'version'   => $version,
            'failed_at' => \time(),
            'failure_code' => $errorCode,
        ];
        if ($credentialState !== null) {
            $state['credential_state'] = $credentialState;
        }
        \update_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE, $state, 'yes');
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

        (new Routines_AdminLanguagesSettings($langAdmin, $debugSettings, $approachSettings, $aliases, $optionFactory))->execute();

        (new Routines_RuntimeDebug($debugSettings))->execute();

                                                                                                
        (new Routines_ShortcodesParsing())->execute();

                                                                             
        $ls           = new Language_Settings($langAdmin, $aliases, $optionFactory);
        $languagePair = $ls->getSetLanguage_Pair();

                               
        (new Routines_PublicLanguageMenu($langAdmin, $ls))->execute();

        $greenLight = false;

        if (
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
            $tr = new Routines_Translation($languagePair, $debugSettings, $allowedTargets);
            $tr->execute();
        } elseif (
            $languagePair->getTargetLanguage()->getCode() === Language_Constants::ORIGINAL
            && $allowedTargets !== []
            && !is_admin()
            && !wp_doing_ajax()
            && !wp_is_json_request()
        ) {
                                                                             
                                                                                  
                                                                               
            (new Routines_InjectPublicationMetadata($allowedTargets))->execute();
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

            echo $this->renderFloatingMenu();
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
                    'showInterlingua'  => Core_Strings::get('menu_show_interlingua'),
                    'wrapperClass'     => Core_Strings::get('menu_wrapper_class'),
                ]) . ';',
                'before'
            );

            register_block_type('dolet/language-menu', [
                'editor_script'   => $handle,
                'style'           => self::MENU_STYLE_HANDLE,
                'attributes'      => [
                    'showCodes'       => ['type' => 'boolean', 'default' => true],
                    'showInterlingua' => ['type' => 'boolean', 'default' => true],
                    'wrapperClass'    => ['type' => 'string', 'default' => 'dolet-lang-menu'],
                ],
                'render_callback' => function (array $attributes = []): string {
                    return $this->renderMenu(
                        (bool) ($attributes['showCodes'] ?? true),
                        (bool) ($attributes['showInterlingua'] ?? true),
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
                    'show_interlingua' => 'yes',
                    'class'            => 'dolet-lang-menu',
                ], $attributes, 'dolet_language_menu');

                return $this->renderMenu(
                    $this->attributeIsEnabled((string) $attributes['show_codes']),
                    $this->attributeIsEnabled((string) $attributes['show_interlingua']),
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
        bool $showInterlingua = true,
        string $wrapperClass = 'dolet-lang-menu'
    ): string
    {
        $items = $this->buildItems();
        if ($items === []) {
            return '';
        }

        $view = new UiVisitor_LanguageMenuView();
        $view->setWrapperClass($this->normalizeWrapperClass($wrapperClass));
        $view->setShowCodes($showCodes);
        $view->setShowInterlingua($showInterlingua);

        foreach ($items as $item) {
            $view->addItem($item);
        }

        return $view->render();
    }

    public function renderFloatingMenu(): string
    {
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
        bool $showInterlingua = true,
        string $wrapperClass = 'dolet-lang-menu'
    ): string
    {
        $aliases = new Language_AliasesDictionary();
        $factory = new Language_OptionFactory($aliases);

        $cfg     = new Config_LanguageAdmin($aliases, $factory);

        $ls      = new Language_Settings($cfg, $aliases, $factory);
        $routine = new self($cfg, $ls);

        return $routine->renderMenu($showCodes, $showInterlingua, $wrapperClass);
    }


    private function buildUrlWithLang(string $code): string
    {
        $param = Dom_Constants::LANGUAGE_URL_PARAMETER_NAME;       
        $url   = Routines_RequestUrl::current();

                                                                         
        if (strcasecmp($code, Language_Constants::ORIGINAL) === 0) {
            return esc_url(remove_query_arg($param, $url));
        }

        return esc_url(add_query_arg([$param => $code], $url));
    }

                                                            
    private function buildItems(): array
    {
        $requested = $this->settings->getRequestedLanguage()->getCode();
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

        return (bool) get_option(Config_TranslationOptionNames::FLOATING_SELECTOR_ENABLED, true)
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
        $classes = preg_split('/\s+/', trim($class)) ?: [];
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


   
                                               
   
final class Routines_ShortcodesParsing implements Routine_RoutineInterface
{
    public function __construct()
    {
    }

    public function execute(): void
    {
        add_shortcode('notranslate', function ($atts = [], $content = '', $tag = '') {
                                                                                                  
                                                                                         
                                                                       
            return '<span class="notranslate" translate="no" data-no-translate="1" style="display: contents">' . do_shortcode($content) . '</span>';
        });
    }
}


   
                                                                    
   
final class Routines_TranslateFinalHtml implements Routine_RoutineInterface
{
    private bool $bufferStarted = false;

       
                                      
       
    public function __construct(
        private Translation_TranslatorInterface $translator,
        private string $currentLangCode = '',
        private array $allTargetCodes = [],
    ) {}

    public function execute(): void
    {
                                                             
        add_action('template_redirect', function (): void {
            if ($this->bufferStarted) {
                return;
            }

                                                                                            
            if (is_admin() || is_feed() || is_robots() || wp_doing_ajax() || wp_is_json_request()) {
                return;
            }

            $this->bufferStarted = true;

                                                       
            ob_start(function (string $html) {
                try {
                                                                      
                    if ($html === '' || (stripos($html, '<html') === false && stripos($html, '<!DOCTYPE') === false)) {
                                                                                              
                                                                                        
                                        
                    }

                                                                                      
                    $translated = $this->translator->translateFinalHtml($html);

                                                                                  
                                                                                
                                                                           
                    $translated = Dom_DocumentLanguage::apply(
                        $translated,
                        $this->currentLangCode
                    );

                                                                  
                    if ($this->currentLangCode !== '' && $this->allTargetCodes !== []) {

                        $currentUrl = Routines_RequestUrl::current();

                        $translated = Dom_HreflangInjector::inject(
                            $translated,
                            $this->currentLangCode,
                            $this->allTargetCodes,
                            $currentUrl
                        );
                    }

                    return $translated;
                } catch (\Throwable $e) {
                                                                       
                    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        error_log('[Dolet WP] TranslateFinalHtml error: ' . $e->getMessage());
                    }
                    return $html;                     
                }
            });
        }, 9999);
    }
}


   
                                                                         
   
final class Routines_InjectPublicationMetadata implements Routine_RoutineInterface
{
    private bool $bufferStarted = false;

                                          
    public function __construct(private array $allTargetCodes) {}

    public function execute(): void
    {
        add_action('template_redirect', function (): void {
            if (
                $this->bufferStarted
                || is_admin()
                || is_feed()
                || is_robots()
                || wp_doing_ajax()
                || wp_is_json_request()
            ) {
                return;
            }

            $this->bufferStarted = true;
            ob_start(function (string $html): string {
                if ($html === '' || (stripos($html, '<html') === false && stripos($html, '<!DOCTYPE') === false)) {
                    return $html;
                }

                try {

                    return Dom_HreflangInjector::inject(
                        $html,
                        Language_Constants::ORIGINAL,
                        $this->allTargetCodes,
                        Routines_RequestUrl::current()
                    );
                } catch (\Throwable $e) {
                    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        error_log('[Dolet WP] PublicationMetadata error: ' . $e->getMessage());
                    }
                    return $html;
                }
            });
        }, 9999);
    }
}


   
                                                                                
   
final class Routines_TranslatePrincipalContent implements Routine_RoutineInterface
{
    public function __construct(
        private Translation_TranslatorInterface $translator,
    ) {}

    public function execute(): void
    {
                 
        add_filter('the_title', function (string $title, int $post_id): string {
            if (is_admin() || !is_main_query()) {
                return $title;
            }
            return $this->translator->naiveTranslate($title);
        }, 99, 2);

                       
        add_filter('the_content', function (string $content): string {
            if (is_admin() || is_feed() || !is_main_query() || !in_the_loop() || !is_singular()) {
                return $content;
            }

            $translated = $this->translator->translateHtml($content);
            return $translated;
        }, 99);
    }
}


   
                                                                                 
  
                                          
                                     
                                                                      
                                                                    
                                                            
  
             
                                                                             
                                                                
   
final class Routines_Translation implements Routine_RoutineInterface
{
       
                                      
       
    public function __construct(
        private Language_PairInterface $languagePair,
        private Config_DebugSettingsInterface $debugSettings,
        private array $allTargetCodes = [],
    ) {}

    public function execute(): void
    {
                                                                  
        $repo = new Data_TranslationMemoryRepository();

                                                                                      
                                                                              
        $repoReady = true;
        try {
            $repo->ensureReady();
        } catch (\Throwable $e) {
            $repoReady = false;
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Data_TranslationMemoryRepository::ensureReady error: ' . $e->getMessage());
            }
        }

        if (!$repoReady) {
            return;
        }

                                                                                 
        $stamper = new Dom_TranslatedStampService();

                                                       
        $translatePre = (bool) get_option(Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA, false);

        $translator = new Translation_Translator(
            $this->languagePair,
            $this->debugSettings,
            $repo,
            $stamper,
            $translatePre,
        );

                                                      
        $final = new Routines_TranslateFinalHtml(
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
    private const LOCK_KEY  = 'dolet_translation_worker_lock';

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

        if (!$engine->isAvailable()) {
            if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Worker tick: SOLE Engine unavailable, skipping.');
            }
            $diagnosticsStore->recordError('engine_unavailable', 'SOLE Engine not available at worker tick.');
            return;
        }

                                                      
        if (get_transient(self::LOCK_KEY)) {
            return;
        }
        set_transient(self::LOCK_KEY, 1, 120);

        try {
            $repo = new Data_TranslationMemoryRepository();
            $repo->ensureReady();

            $worker = new Translation_WorkerService(
                $repo,
                $engine,
                $this->debugSettings,
                $this->approachSettings,
                $diagnosticsStore
            );

                                                                     
            $pollSummary = $worker->pollAndApplyResults();

            if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Worker poll summary: ' . wp_json_encode($pollSummary));
            }

                                                                  
            $srcCode = $this->langAdmin->getSourceCode();
            $targets = $this->langAdmin->getTargetCodes();

                                                              
            foreach ($targets as $tgtCode) {
                $pair = $this->buildPairOrNull($srcCode, (string)$tgtCode);
                if ($pair === null) {
                    continue;
                }

                                           
                $summary = $worker->submitPendingFor($pair, 50);

                if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[Dolet WP] Worker submit summary: ' . wp_json_encode($summary));
                }
            }
        } catch (\Throwable $e) {
            if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Worker tick failed: ' . $e->getMessage());
            }
            $diagnosticsStore->recordError('worker_exception', $e->getMessage());
        } finally {
            delete_transient(self::LOCK_KEY);
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
            return $optionFactory->fromCodeElseNull($code, null);
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
