<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                                
  
                    
                                                                              
                                                                                 
                                                 
                                                             
  
                                                          
                                         
                              
                            
                                
  
   

                                                                             
                              
                                                                             
         

                                                                             
                          
                                                                             
         

                                                                             
                         
                                                                             
         

                                                                             
          
                                                                             
   
                                                   
  
                                                  
   
final class Language_AliasesDictionary implements Language_AliasesDictionaryInterface
{
    public function isAliasOf(string $code): ?string
    {
        $aliases = self::getAliasesList();
        return $aliases[$code] ?? null;
    }

       
                                           
      
                       
                 
                           
              
           
      
                                   
       
    private static function getAliasesList(): array
    {
                                                    
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

                                                 
        $aliases = include __DIR__ . '/wp_codes.php';
        $cache   = $aliases;

        return $cache;
    }
}

   
                                                         
  
                                       
   
final class Language_Option implements Language_OptionInterface
{
    public function __construct(
        private string $languageCode,
        private ?string $languageName = null,
        private ?string $note = null,
    ) {}

       
                                            
       
    public function getCode(): string
    {
        return $this->languageCode;
    }

       
                                                 
       
    public function getName(): string
    {
                               
        if (!$this->isCustom()) {
            $all = Language_Constants::getAllLanguages();
            return $all[$this->languageCode] ?? $this->languageCode;
        }

                                                                 
        return $this->languageName ?? $this->languageCode;
    }

       
                                                               
       
    public function getNote(): ?string
    {
        return $this->note;
    }

       
                                        
       
    public function isCustom(): bool
    {
        $all = Language_Constants::getAllLanguages();
        return !array_key_exists($this->languageCode, $all);
    }
}

   
                                        
  
                                              
   
final class Language_OptionFactory implements Language_OptionFactoryInterface
{
    public function __construct(
        private Language_AliasesDictionaryInterface $aliases
    ) {}

       
                                                                              
      
             
                                                          
                                                          
                                                                             
                                                 
                                                     
       
    public function fromCodeElseNull(string $code, ?string $note = null): ?Language_OptionInterface
    {
        $normalized = self::normalizeCode($code);
        if ($normalized === '') {
            return null;
        }

        $all = Language_Constants::getAllLanguages();

                                 
        if (array_key_exists($normalized, $all)) {
            return new Language_Option($normalized, $all[$normalized], $note);
        }

                                                     
        $aliasTarget = $this->aliases->isAliasOf($normalized);
        if ($aliasTarget !== null && array_key_exists($aliasTarget, $all)) {
            return new Language_Option($aliasTarget, $all[$aliasTarget], $note);
        }

                                                             
        $base = strtolower(explode('-', $normalized)[0]);
        if ($base !== '' && array_key_exists($base, $all)) {
            return new Language_Option($base, $all[$base], $note);
        }

                     
        return null;
    }

       
                                                             
                         
                                       
                                       
       
    private static function normalizeCode(string $code): string
    {
        $code = str_replace('_', '-', trim($code));
        if ($code === '') {
            return '';
        }

        $parts = explode('-', $code);
        $parts[0] = strtolower($parts[0]);            

        $count = count($parts);
        for ($i = 1; $i < $count; $i++) {
            $p   = $parts[$i];
            $len = strlen($p);
                                                                       
            $parts[$i] = ($len === 4)
                ? ucfirst(strtolower($p))
                : strtoupper($p);
        }

        return implode('-', $parts);
    }
}


   
                                              
  
                                     
   
final class Language_Pair implements Language_PairInterface
{
    public function __construct(
        private Language_OptionInterface $sourceLanguage,
        private Language_OptionInterface $targetLanguage
    ) {}

    public function getSourceLanguage(): Language_OptionInterface
    {
        return $this->sourceLanguage;
    }

    public function getTargetLanguage(): Language_OptionInterface
    {
        return $this->targetLanguage;
    }

       
                                                               
       
    public function optionsAreTheSame(): bool
    {
        return $this->sourceLanguage->getCode() === $this->targetLanguage->getCode();
    }
}
   
                                                         
  
                                         
  
                    
                                                                    
                                                                     
                                                                      
                                                                  
   
final class Language_Settings implements Language_SettingsInterface
{
    public function __construct(
        private Config_LanguageAdminInterface $config,
        private Language_AliasesDictionaryInterface $aliases,
        private Language_OptionFactoryInterface $optionFactory
    ) {}

       
                                 
      
                          
                                                                             
                               
                                                                                    
       
    public function getRequestedLanguage(): Language_OptionInterface
    {

        $rawParam = isset($_GET[Dom_Constants::LANGUAGE_URL_PARAMETER_NAME])
            ? wp_unslash($_GET[Dom_Constants::LANGUAGE_URL_PARAMETER_NAME])
            : null;
        $param = is_string($rawParam)
            ? sanitize_text_field($rawParam)
            : null;

        if ($param === null || trim($param) === '') {
            return new Language_Option(
                Language_Constants::ORIGINAL,
                Language_Constants::ORIGINAL_NAME,
                Language_Constants::ORIGINAL_NOTE
            );
        }

        $raw = str_replace('_', '-', trim((string) $param));
        if ($raw === '') {
            return new Language_Option(
                Language_Constants::ORIGINAL,
                Language_Constants::ORIGINAL_NAME,
                Language_Constants::ORIGINAL_NOTE
            );
        }

        $codes = Language_Constants::getAllLanguages();
        $norm  = $this->canonicalizeCase($raw);

                                   
        if (strcasecmp($norm, Language_Constants::ORIGINAL) === 0) {
            return new Language_Option(
                Language_Constants::ORIGINAL,
                Language_Constants::ORIGINAL_NAME,
                Language_Constants::ORIGINAL_NOTE
            );
        }

                        
        if (array_key_exists($norm, $codes)) {
            return new Language_Option($norm, $codes[$norm], null);
        }

                                            
        $alias = $this->aliases->isAliasOf($norm);
        if ($alias !== null && array_key_exists($alias, $codes)) {
            return new Language_Option($alias, $codes[$alias], null);
        }

                                  
        $customList = $this->getCustomLanguagesList();
        if (in_array($norm, $customList, true)) {
            return new Language_Option($norm, $norm, null);
        }

                                                                                      
        return new Language_Option(
            Language_Constants::ORIGINAL,
            Language_Constants::ORIGINAL_NAME,
            Language_Constants::ORIGINAL_NOTE
        );
    }

       
                                                                  
      
                                        
                                                                                      
                                                        
       
    public function getWordpressLanguage(): Language_OptionInterface
    {
        $locale = (string) get_locale();                 
        $lo     = $this->optionFactory->fromCodeElseNull($locale, $locale);

        if ($lo !== null) {
            return $lo;
        }

        return new Language_Option(
            Language_Constants::AUTODETECT,
            Language_Constants::AUTODETECT_NAME,
            Language_Constants::AUTODETECT_NOTE
        );
    }

       
                                                            
       
    public function getSetLanguage_Pair(): Language_PairInterface
    {
        return new Language_Pair(
            $this->getConfiguredSourceLanguage(),
            $this->getRequestedLanguage()
        );
    }

       
                                                                               
      
                   
                                        
                                   
                                                               
                                                                               
                                                   
       
    public function pairIsValid(Language_PairInterface $pair): bool
    {
        $src = $pair->getSourceLanguage()->getCode();
        $tgt = $pair->getTargetLanguage()->getCode();

                                                    
        if ($tgt === Language_Constants::ORIGINAL) {
            return false;
        }

                                                                        
        if ($src === $tgt) {
            return false;
        }

                                                           
        if ($tgt === Language_Constants::AUTODETECT) {
            return false;
        }

        $all = Language_Constants::getAllLanguages();

                                              
        $customList  = $this->getCustomLanguagesList();
        $tgtIsKnown  = array_key_exists($tgt, $all);
        $tgtIsCustom = !$tgtIsKnown && in_array($tgt, $customList, true);
        if (!$tgtIsKnown && !$tgtIsCustom) {
            return false;
        }

                                                
        $srcIsKnown = array_key_exists($src, $all) || $src === Language_Constants::AUTODETECT;
        if (!$srcIsKnown) {
            return false;
        }

        return true;
    }

                                                                             
                       
                                                                             

       
                                                         
                                                    
                                                                                                      
                                     
       
    private function getConfiguredSourceLanguage(): Language_OptionInterface
    {
        $code = $this->config->getSourceCode();                                 

        if ($code === Language_Constants::ORIGINAL) {
                                                        
            return new Language_Option(
                Language_Constants::AUTODETECT,
                Language_Constants::AUTODETECT_NAME,
                Language_Constants::AUTODETECT_NOTE
            );
        }

        $lo = $this->optionFactory->fromCodeElseNull($code, null);
        if ($lo !== null) {
            return $lo;
        }

                                             
        return $this->getWordpressLanguage();
    }

       
                                                                                          
       
    private function canonicalizeCase(string $code): string
    {
        $code = str_replace('_', '-', trim($code));
        if ($code === '') {
            return '';
        }

        $parts    = explode('-', $code);
        $parts[0] = strtolower($parts[0]);
        $count    = count($parts);

        for ($i = 1; $i < $count; $i++) {
            $p   = $parts[$i];
            $len = strlen($p);
            $parts[$i] = ($len === 4)
                ? ucfirst(strtolower($p))
                : strtoupper($p);
        }

        return implode('-', $parts);
    }

       
                                                                                       
      
                                
       
    private function getCustomLanguagesList(): array
    {
        return $this->config->getCustomCodes();
    }
}
