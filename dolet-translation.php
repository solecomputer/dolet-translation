<?php
/**
 * Plugin Name:       Dolet AI Translation
 * Plugin URI:        https://sole.computer/wordpress/dolet/
 * Description:       Context-aware AI translation for WordPress via SOLE Engine.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.3
 * Author:            Sole Computer
 * Author URI:        https://sole.computer/
 * License:           AGPL-3.0-only
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.html
 * Requires Plugins:  sole-engine
 */

declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/core.php';

                                                                            
                  
                                                                            

register_activation_hook(__FILE__, static function (): void {
    Config_InstallationState::recordActivation();

                                                                             
                                                                            
                                                            
    (new Data_TranslationMemoryRepository())->ensureReady();
});

register_deactivation_hook(__FILE__, static function (): void {
                                                                               
    $hook = 'dolet_translation_worker_tick';
    $ts   = wp_next_scheduled($hook);
    if ($ts !== false) {
        wp_unschedule_event($ts, $hook);
    }

                                                                            
    $registryRaw = get_transient('dolet_pending_jobs');
    if (is_string($registryRaw) && $registryRaw !== '') {
        $registry = json_decode($registryRaw, true);
        if (is_array($registry)) {
            foreach ($registry as $jobKey) {
                delete_transient((string) $jobKey);
            }
        }
    }
    delete_transient('dolet_pending_jobs');
    delete_transient('dolet_translation_worker_lock');
});

                                                                                
                                                                          

                                                                            
            
                                                                            

   
                                                          
  
                                                          
   
add_action(
    'plugins_loaded',
    static function (): void {
        Core_Plugin::bootstrap();
    }
);
