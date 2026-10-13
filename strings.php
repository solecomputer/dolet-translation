<?php

return [
                                                 
    'block_title'                   => 'Dolet: Language Menu',
    'block_description'             => 'Display the Dolet language switcher menu.',

                    
    'page_title'                    => 'Dolet AI Translation',
    'save_button'                   => 'Save Languages',
    'section_title'                 => 'Languages',
    'section_description'           => 'Pick one <strong>Source</strong> (including <em>Original</em>) and any number of <strong>Targets</strong>.',
    'processing_heading'            => 'Translation processing',
    'processing_note'               => 'Translating your website may take a few hours, depending on its size and visitor activity. Processing happens as people use your site.',
    'processing_pending'            => 'Pending text segments',
    'processing_last_success'       => 'Last successful translation update',
    'processing_next_scheduled'     => 'Next automatic processing time',
    'processing_never'              => 'None yet',
    'processing_not_scheduled'      => 'Not scheduled',

                                
    'enable_translation_label'      => 'Enable translation',
    'enable_translation_hint'       => 'activate the background translation worker',

                                    
    'translate_pre_textarea_label'  => 'Translate pre/textarea',
    'translate_pre_textarea_hint'   => 'include &lt;pre&gt; and &lt;textarea&gt; content in translation',

                   
    'debug_label'                   => 'Debug mode',
    'debug_hint'                    => 'for diagnostics only; shows ✓ (translated) and ✗ (pending) markers around text segments and adds a colored footer badge. The translation worker runs asynchronously via WP-Cron &mdash; text is not translated instantly on first visit.',

                    
    'sole_engine_missing'           => 'The SOLE Engine plugin is not active. New translations will not be generated until it is installed and activated.',
    'sole_engine_credential_missing' => 'Translation is enabled, but the SOLE Engine credential is missing. Enter a user key in SOLE Engine settings, then verify Account Status.',
    'sole_engine_credential_unverified' => 'Translation is enabled, but the saved SOLE Engine credential has not been verified. Open SOLE Engine Diagnostics and refresh Account Status.',
    'sole_engine_credential_invalid' => 'Translation is enabled, but SOLE Engine rejected the saved credential. Replace the saved user key from your SOLE account, then verify Account Status.',
    'sole_engine_readiness_unavailable' => 'Translation is enabled, but SOLE Engine credential readiness is unavailable. Update or activate SOLE Engine, then review its Diagnostics.',
    'debug_loaded'                  => 'Dolet WP loaded (debug on).',
    'debug_footer'                  => 'Translated by Dolet AI (debug)',

                                         
    'source_label'                  => 'Source language',
    'source_hint'                   => 'Pick how the source language is determined. "Original" lets the system autodetect the content\'s original language. You can override the display label and note for the chosen source.',
    'targets_label'                 => 'Target languages',
    'targets_hint'                  => 'Add languages visitors can read the site in. You can rename labels and add AI notes per language.',
    'available_label'               => 'Available languages',
    'add_custom_button'             => 'Add custom language',
    'search_placeholder'            => 'Search by code or name',

                                                
    'palette_add'                   => 'Add',
    'palette_already_added'         => 'Already added or selected as Source',

                                                 
    'original_option'               => 'Original',
    'original_option_hint'          => 'autodetect original language',
    'site_language_option'          => 'Use WordPress site language',
    'site_language_unavailable'     => 'Not available',
    'specify_source_option'         => 'Specify a source language',

                                             
    'source_display_name_label'     => 'Source display name (optional)',
    'source_note_label'             => 'Source note (for AI, optional)',
    'source_note_placeholder'       => 'e.g. International neutral Spanish',

                                             
    'select_default'                => '— Select —',
    'col_code'                      => 'Code',
    'col_display_name'              => 'Display name',
    'col_note'                      => 'Note (for AI)',
    'col_remove'                    => 'Remove',
    'note_placeholder'              => 'Optional note',

                           
    'widget_title'                  => 'Dolet: Language Menu',
    'widget_description'            => 'Display the Dolet language switcher menu.',
    'widget_no_settings'            => 'No settings. Use the Languages page to configure sources/targets and labels.',
    'menu_show_codes'               => 'Show language codes',
    'menu_show_interlingua'         => 'Show Interlingua names',
    'menu_wrapper_class'            => 'Wrapper CSS class',

                                           
    'floating_selector_label'       => 'Show floating language selector',
    'floating_selector_hint'        => 'show a language dropdown at the bottom of public pages',
    'floating_selector_summary'     => 'Language',
    'floating_selector_aria_label'  => 'Floating language selector',

                 
    'nav_aria_label'                => 'Languages',
    'menu_original_with_language'   => 'Original ({language})',
    'menu_original_unknown'         => 'Original (language unknown)',

           
    'tab_settings'                  => 'Settings',
    'tab_console'                   => 'Console',
    'tab_diagnostics'               => 'Diagnostics',

                           
    'stats_heading'                 => 'Translation Memory Stats',
    'stats_total'                   => 'Total',
    'stats_translated'              => 'Translated',
    'stats_pending'                 => 'Pending',
    'stats_human_locked'            => 'Human-locked',
    'stats_word_count'              => 'Words (source)',
    'stats_refresh'                 => 'Refresh',
    'stats_no_data'                 => 'No translation memory entries yet.',
    'stats_source'                  => 'Source',
    'stats_target'                  => 'Target',

                                                                
    'console_worker_trigger'        => 'Process now',
    'console_worker_running'        => 'Processing…',
    'console_worker_done'           => 'Processing request complete.',

                                     
    'console_test_heading'          => 'Single String Test',
    'console_test_submit'           => 'Translate',
    'console_test_polling'          => 'Polling for result…',
    'console_test_source_label'     => 'Source language',
    'console_test_target_label'     => 'Target language',
    'console_test_text_label'       => 'Text to translate',
    'console_test_no_targets'       => 'No target languages configured',
    'console_test_no_targets_hint'  => 'Add target languages on the Settings tab to run a translation test.',

                  
    'diag_heading'                  => 'Diagnostics',
    'diag_engine_available'         => 'SOLE Engine credential: verified',
    'diag_engine_unavailable'       => 'SOLE Engine: not available',
    'diag_engine_missing'           => 'SOLE Engine credential: missing',
    'diag_engine_unverified'        => 'SOLE Engine credential: present but not verified',
    'diag_engine_invalid'           => 'SOLE Engine credential: invalid',
    'diag_engine_action_label'      => 'Credential action',
    'diag_engine_action_unavailable' => 'Update or activate SOLE Engine, then review its Diagnostics.',
    'diag_engine_action_missing'    => 'Enter a user key in SOLE Engine settings, then verify Account Status.',
    'diag_engine_action_unverified' => 'Open SOLE Engine Diagnostics and refresh Account Status.',
    'diag_engine_action_invalid'    => 'Replace the saved user key from your SOLE account, then verify Account Status.',
    'diag_engine_action_verified'   => 'No credential action needed.',
    'diag_cron_scheduled'           => 'Worker cron: scheduled',
    'diag_cron_not_scheduled'       => 'Worker cron: not scheduled',
    'diag_table_exists'             => 'TM table: exists',
    'diag_table_missing'            => 'TM table: missing',
    'diag_translation_enabled'      => 'Translation: enabled',
    'diag_translation_disabled'     => 'Translation: disabled',
    'diag_languages_label'          => 'Configured languages',

                                
    'diag_error_log_heading'        => 'Error log',
    'diag_error_log_empty'          => 'No errors recorded.',
    'diag_error_log_refresh'        => 'Refresh',
    'diag_error_log_clear'          => 'Clear error log',
    'diag_error_log_clear_confirm'  => 'Clear the entire error log?',
    'diag_last_success'             => 'Last success',
    'diag_last_error'               => 'Last error',
    'diag_last_error_at'            => 'Last error at',

                                 
    'diag_tests_heading'            => 'Self-tests',
    'diag_test_run_all'             => 'Run all',
    'diag_test_col_test'            => 'Test',
    'diag_test_col_description'     => 'Description',
    'diag_test_col_result'          => 'Result',
    'diag_test_col_detail'          => 'Detail',
    'diag_test_engine_available'    => 'SOLE Engine credential ready',
    'diag_test_config_valid'        => 'Configuration valid',
    'diag_test_tm_table_ready'      => 'Translation memory table ready',
    'diag_test_codec_roundtrip'     => 'Codec encode/decode roundtrip',
    'diag_test_submit_poll_flow'    => 'Submit and poll translation flow',
];
