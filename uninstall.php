<?php
   
                                              
  
                                                                            
                                                                            
                                                                               
                 
  
                                                                          
                                                                              
                                        
                                                                         
                                          
  
                                                                        
                                                                             
                                                                          
                                               
   

declare(strict_types=1);

                                                             
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

                                                                                 
                                               
require_once __DIR__ . '/core.php';

                                                                             
\DoletWP\Data_UniversalTableL1_Tools::maybeDropOnUninstall();
\DoletWP\Core_Strings::deleteOnUninstall();
\DoletWP\Config_InstallationState::deleteOnUninstall();
