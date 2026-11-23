<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
               
  
                                                            
                                                                         
   

  
                

                                                                                                           


                                                                                                                                                

                                                                                                                   

                                                                                                 

                                                                      

                                                                                           

                                                                                                                
                                                                                                                                               

                                                                                                                             


                                                                                 

                                                                                   

                                                                                           
  


                                                                               
                                        
                                                                               

                                          
require_once __DIR__ . '/contracts.php';

                                                                              
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/dom.php';
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/approach.php';
require_once __DIR__ . '/routines.php';
require_once __DIR__ . '/structs.php';
require_once __DIR__ . '/translation.php';
require_once __DIR__ . '/ui_visitor.php';
require_once __DIR__ . '/ui_webmaster.php';


                                                                               
             
                                                                               

   
                                            
  
                                                                                 
                                                                                
   
interface Core_PluginInterface
{
       
                                                                 
      
                                                                                   
       
    public function boot(): void;
}


                                                                               
         
                                                                               
         


                                                                               
        
                                                                               
         


                                                                               
            
                                                                               
                                                                                  


                                                                               
          
                                                                               

                                                                                     
final class Core_HtmlCandidateRejection implements Core_HtmlRejectionInterface
{
    public function __construct(private readonly Core_HtmlRejectionInterface $reason)
    {
    }

                                                                        
    public function code(bool $candidate = false): string
    {
        return $this->reason->code(true);
    }

    public function context(): string
    {
        return $this->reason->context();
    }

    public function label(): string
    {
        return $this->reason->label();
    }
}

   
                                                                              
  
                                                                          
                                                                                 
                                                                           
                                              
   
final class Core_Strings
{
                                     
    private static array $strings = [];
    private static bool $loaded = false;

    public static function load(string $dir): void
    {
        if (self::$loaded) {
            return;
        }

        $base = [];
        $source = $dir . '/strings.php';
        if (\is_file($source)) {
            $base = (array) include $source;
        }

        $version = \md5(\serialize($base));
        $overlay = \get_option(Core_StringsOptionNames::TRANSLATED);
        if (\is_array($overlay)
            && ($overlay['locale'] ?? null) === \get_locale()
            && ($overlay['version'] ?? null) === $version
            && \is_array($overlay['strings'] ?? null)) {
            $base = \array_merge($base, $overlay['strings']);
        }

        self::$strings = $base;
        self::$loaded = true;
    }

    public static function get(string $key): string
    {
        return self::$strings[$key] ?? $key;
    }

       
                                                                              
                           
      
                                               
       
    public static function storeOverlay(array $translated, string $locale, string $version): void
    {
        \update_option(Core_StringsOptionNames::TRANSLATED, [
            'locale' => $locale,
            'version' => $version,
            'strings' => $translated,
        ], 'yes');
    }

       
                                                                            
      
                
       
    public static function deleteOnUninstall(): void
    {
        \delete_option(Core_StringsOptionNames::TRANSLATED);
        \delete_option(Core_StringsOptionNames::AUTOTRANSLATE_STATE);
        \delete_option(Core_StringsOptionNames::AUTOTRANSLATE_LOCK);
    }

                                      
    public static function reset(): void
    {
        self::$strings = [];
        self::$loaded = false;
    }
}


   
                                
  
                    
                                                              
                                                                        
                                                                           
  
                                               
   
final class Core_Plugin implements Core_PluginInterface
{
       
                                                               
      
                                                                                        
                                                                          
       
    private Routine_RoutineInterface $mainRoutine;

    public function __construct(?Routine_RoutineInterface $mainRoutine = null)
    {
                                                                                    
                                                                
                                      
                                           
                                      
                                 
                                     
                                                     
        $this->mainRoutine = $mainRoutine ?? new Routines_Main();
    }

       
                       
                                                             
                                                                                 
       
    public function boot(): void
    {
        Core_Strings::load(__DIR__);
        $this->mainRoutine->execute();
    }

       
                                                                
      
                                                     
      
                                              
                                    
       
    public static function bootstrap(): void
    {
        (new self())->boot();
    }
}
