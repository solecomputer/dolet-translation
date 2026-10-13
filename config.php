<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                              
  
                    
                               
                                                                                   
  
                                           
                                   
                                   
  
                        
                             
                               
                        
                                                                                                          
                                                                                             
   

                                                                             
          
                                                                             

   
                                                               
   
final class Config_DebugSettings implements Config_DebugSettingsInterface
{
    public function __construct()
    {
    }

    public function isEnabled(): bool
    {
        $raw = get_option(Config_DebugOptionNames::DEBUG_OPTION_NAME, false);
        return (bool) $raw;
    }

    public function setEnabled(bool $enabled): void
    {
        update_option(Config_DebugOptionNames::DEBUG_OPTION_NAME, $enabled);
    }
}

                                                                             
final class Config_InstallationState
{
    private const PROCESSING_NOTE_LIFETIME_SECONDS = 86400;

    public static function recordActivation(): void
    {
                                                                               
                                                                             
        \add_option(Config_InstallationOptionNames::ACTIVATED_AT, \time(), '', false);
    }

    public static function isProcessingNoteVisible(): bool
    {
        $activatedAt = \get_option(Config_InstallationOptionNames::ACTIVATED_AT, null);
        if (!is_int($activatedAt) && !(is_string($activatedAt) && ctype_digit($activatedAt))) {
            return false;
        }

        $activatedAt = (int)$activatedAt;
        $now = \time();
        return $activatedAt > 0
            && $now >= $activatedAt
            && ($now - $activatedAt) < self::PROCESSING_NOTE_LIFETIME_SECONDS;
    }

    public static function deleteOnUninstall(): void
    {
        \delete_option(Config_InstallationOptionNames::ACTIVATED_AT);
    }
}

   
                                                                                       
   
final class Config_DiagnosticsStore implements Diagnostics_StoreInterface
{
    private const OPTION_ERROR_LOG     = Config_DiagnosticsOptionNames::ERROR_LOG;
    private const OPTION_LAST_ERROR    = Config_DiagnosticsOptionNames::LAST_ERROR;
    private const OPTION_LAST_ERROR_AT = Config_DiagnosticsOptionNames::LAST_ERROR_AT;
    private const OPTION_LAST_SUCCESS  = Config_DiagnosticsOptionNames::LAST_SUCCESS;
    private const MAX_LOG_ENTRIES      = 50;

    public function __construct()
    {
    }

    public function recordError(string $code, string $context): void
    {
        $log = $this->getErrorLog();
        $log[] = [
            'time'    => \gmdate('c'),
            'code'    => $code,
            'context' => $context,
        ];
        if (count($log) > self::MAX_LOG_ENTRIES) {
            $log = array_slice($log, -self::MAX_LOG_ENTRIES);
        }
        $this->updateOption(self::OPTION_ERROR_LOG, $log);
        $this->updateOption(self::OPTION_LAST_ERROR, $code);
        $this->updateOption(self::OPTION_LAST_ERROR_AT, \gmdate('c'));
    }

    public function recordSuccess(): void
    {
        $this->updateOption(self::OPTION_LAST_SUCCESS, \gmdate('c'));
    }

       
                                                                       
       
    public function getErrorLog(): array
    {
        $raw = \get_option(self::OPTION_ERROR_LOG, []);
        return is_array($raw) ? $raw : [];
    }

    public function getLastError(): ?string
    {
        $raw = \get_option(self::OPTION_LAST_ERROR, null);
        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    public function getLastErrorAt(): ?string
    {
        $raw = \get_option(self::OPTION_LAST_ERROR_AT, null);
        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    public function getLastSuccess(): ?string
    {
        $raw = \get_option(self::OPTION_LAST_SUCCESS, null);
        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    public function clearErrorLog(): void
    {
        \delete_option(self::OPTION_ERROR_LOG);
        \delete_option(self::OPTION_LAST_ERROR);
        \delete_option(self::OPTION_LAST_ERROR_AT);
        \delete_option(self::OPTION_LAST_SUCCESS);
    }

       
                                                        
       
    private function updateOption(string $name, mixed $value): void
    {
                                                                                 
                                                                             
        if (\get_option($name) === false) {
            \add_option($name, $value, '', false);
        } else {
            \update_option($name, $value, false);
        }
    }
}

   
                                                                
   
final class Config_LanguageAdmin implements Config_LanguageAdminInterface
{
    public function __construct(
        private Language_AliasesDictionaryInterface $aliases,
        private Language_OptionFactoryInterface $optionFactory
    ) {}

    public function getSourceCode(): string
    {
        $v = get_option(Language_AdminOptionNames::SOURCE_CODE, 'original');
        $v = is_string($v) ? trim($v) : 'original';
        return $v === '' ? 'original' : $this->normalizeCodeOrOriginal($v);
    }

    public function setSourceCode(string $code): void
    {
        update_option(Language_AdminOptionNames::SOURCE_CODE, $this->normalizeCodeOrOriginal($code));
    }

                               
    public function getTargetCodes(): array
    {
        $arr = get_option(Language_AdminOptionNames::TARGET_LANGUAGES, []);
        if (!is_array($arr)) {
            $arr = [];
        }

        $src     = $this->getSourceCode();
        $customs = $this->getCustomCodes();
        $catalog = $this->getSelectableCatalog();

        $out = [];
        foreach ($arr as $c) {
            $n = $this->normalizeCodeLoose((string) $c);
            if ($n === null) {
                continue;
            }
            if ($n === 'original') {
                continue;
            }
            if ($n === $src) {
                continue;
            }

                                                     
            if (!array_key_exists($n, $catalog) && !in_array($n, $customs, true)) {
                continue;
            }
            $out[$n] = true;
        }
        $codes = array_keys($out);
        sort($codes, SORT_STRING);
        return $codes;
    }

                                     
    public function setTargetCodes(array $codes): void
    {
        $src     = $this->getSourceCode();
        $catalog = $this->getSelectableCatalog();
        $customs = $this->getCustomCodes();

        $out = [];
        foreach ($codes as $c) {
            $n = $this->normalizeCodeLoose((string) $c);
            if ($n === null || $n === 'original' || $n === $src) {
                continue;
            }
            if (!array_key_exists($n, $catalog) && !in_array($n, $customs, true)) {
                continue;
            }
            $out[$n] = true;
        }
        $codes = array_keys($out);
        sort($codes, SORT_STRING);
        update_option(Language_AdminOptionNames::TARGET_LANGUAGES, $codes);
    }

                               
    public function getCustomCodes(): array
    {
        $arr = get_option(Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME, []);
        if (!is_array($arr)) {
            $arr = [];
        }
        $out = [];
        foreach ($arr as $c) {
            $n = $this->normalizeCodeLoose((string) $c);
            if ($n !== null) {
                $out[$n] = true;
            }
        }
        $codes = array_keys($out);
        sort($codes, SORT_STRING);
        return $codes;
    }

                                     
    public function setCustomCodes(array $codes): void
    {
        $out = [];
        foreach ($codes as $c) {
            $n = $this->normalizeCodeLoose((string) $c);
            if ($n !== null) {
                $out[$n] = true;
            }
        }
        $codes = array_keys($out);
        sort($codes, SORT_STRING);
        update_option(Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME, $codes);
    }

                                                                 
    public function getCustomizations(): array
    {
                                                                                                       
        $raw = get_option(Language_AdminOptionNames::LANGUAGE_CUSTOMIZATIONS, []);
        if (!is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $code => $row) {
            $norm = $this->normalizeCodeOrOriginalLoose((string) $code);                                                            
            if ($norm === null) {
                continue;
            }

            $name = '';
            $note = '';
            if (is_array($row)) {
                $name = isset($row['name']) ? trim((string) $row['name']) : '';
                $note = isset($row['note']) ? trim((string) $row['note']) : '';
            }

            $r = [];
            if ($name !== '') {
                $r['name'] = $name;
            }
            if ($note !== '') {
                $r['note'] = $note;
            }

            if (!empty($r)) {
                                                                            
                $clean[$norm] = $r;
            }
        }

        ksort($clean, SORT_STRING);
        return $clean;
    }

                                                                     
    public function setCustomizations(array $map): void
    {
                                                                                       
        $clean = [];
        foreach ($map as $code => $data) {
            $norm = $this->normalizeCodeOrOriginalLoose((string) $code);                                             
            if ($norm === null) {
                continue;
            }

            $name = isset($data['name']) ? trim((string) $data['name']) : '';
            $note = isset($data['note']) ? trim((string) $data['note']) : '';

            $row = [];
            if ($name !== '') {
                $row['name'] = $name;
            }
            if ($note !== '') {
                $row['note'] = $note;
            }

            if (!empty($row)) {
                $clean[$norm] = $row;
            }
        }
        ksort($clean, SORT_STRING);
        update_option(Language_AdminOptionNames::LANGUAGE_CUSTOMIZATIONS, $clean);
    }

                                       
    public function getSelectableCatalog(): array
    {
        $reg     = Language_Constants::getAllLanguages();            
        $customs = $this->getCustomCodes();

                                                     
        foreach ($customs as $c) {
            if (!array_key_exists($c, $reg)) {
                $reg[$c] = $c;
            }
        }

                                                         
        $reg = array_merge(['original' => 'Original'], $reg);
        ksort($reg, SORT_STRING);
        return $reg;
    }

    public function getDisplayNameFor(string $code): string
    {
                                                            
        $norm = $this->normalizeCodeOrOriginalLoose($code) ?? 'original';

        $custom = $this->getCustomizations();
        if (isset($custom[$norm]['name']) && $custom[$norm]['name'] !== '') {
            return (string) $custom[$norm]['name'];
        }

        $catalog = $this->getSelectableCatalog();                                            
        return $catalog[$norm] ?? $norm;
    }

                                                                             
              
                                                                             

       
                                                                                 
       
    private function normalizeCodeOrOriginalLoose(string $code): ?string
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        if (strcasecmp($code, 'original') === 0) {
            return 'original';
        }
                                                                                               
        return $this->normalizeCodeLoose($code);                                                                     
    }

       
                                                              
       
    private function normalizeCodeOrOriginal(string $code): string
    {
        $code = trim($code);
        if ($code === '' || strtolower($code) === 'original') {
            return 'original';
        }
        $n = $this->normalizeCode($code);
        return $n ?? '';
    }

       
                                
                                          
                                               
                                                                      
       
    private function normalizeCodeLoose(string $code): ?string
    {
        $code = str_replace('_', '-', trim($code));
        if ($code === '') {
            return null;
        }

        $codes = Language_Constants::getAllLanguages();
        $norm  = $this->canonicalizeCase($code);

                              
        if (array_key_exists($norm, $codes)) {
            return $norm;
        }

                                    
        $alias = $this->aliases->isAliasOf($norm);
        if ($alias !== null && array_key_exists($alias, $codes)) {
            return $alias;
        }

                                                
        return $norm;
    }

       
                                                        
                                     
                                                    
                           
       
    private function normalizeCode(string $code): ?string
    {
        $lo = $this->optionFactory->fromCodeElseNull($code, null);
        return $lo ? $lo->getCode() : null;
    }

       
                                                      
       
    private function canonicalizeCase(string $code): string
    {
        $code  = str_replace('_', '-', $code);
        $parts = explode('-', $code);
        $parts[0] = strtolower($parts[0]);
        for ($i = 1; $i < count($parts); $i++) {
            $p   = $parts[$i];
            $len = strlen($p);
            $parts[$i] = ($len === 4)
                ? ucfirst(strtolower($p))            
                : strtoupper($p);                              
        }
        return implode('-', $parts);
    }
}


   
                                                                               
  
                                                                  
                                                            
   
final class Config_ApproachSettings implements Config_ApproachSettingsInterface
{
    public function isEnabled(): bool
    {
                                                                             
                                                                            
        return (bool) get_option(Config_ApproachOptionNames::APPROACH_ENABLED, false);
    }

    public function getMaxBatchChars(): int
    {
                                                                      
        $v = get_option(Config_ApproachOptionNames::MAX_BATCH_CHARS, 120000);
        return max(1000, (int)$v);
    }

    public function getMaxBatchItems(): int
    {
                                                                      
        $v = get_option(Config_ApproachOptionNames::MAX_BATCH_ITEMS, 200);
        return max(1, (int)$v);
    }
}
