<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                                       
  
                                                                         
                                                                        
  
           
                

           
             
           
          
               
              
              
                  
                
                  
   


                                                                             
                  
                                                                             

   
                                                                  
  
                                                              
                                                         
   
interface Approach_SoleEngineClientInterface
{
       
                                                 
       
    public function submitTranslateBatch(
        Approach_TranslationBatchInterface $batch
    ): Approach_SubmitResultInterface;

       
                                                   
       
    public function pollResult(string $jobId): Approach_PollResultInterface;

                                                                  
    public function isAvailable(): bool;
}

   
                                                             
  
                                                                          
                                                          
   
interface Approach_EngineCredentialReadinessReaderInterface
{
       
                                                                
       
    public function read(): array;
}

                                                                       
final class Approach_EngineCredentialStates
{
    public const UNAVAILABLE = 'unavailable';
    public const MISSING     = 'missing';
    public const UNVERIFIED  = 'unverified';
    public const INVALID     = 'invalid';
    public const VERIFIED    = 'verified';
}

   
                                                           
  
                                                                          
   
interface Approach_TranslationBatchInterface
{
                                        
    public function getLanguagePair(): Language_PairInterface;

       
                                                                     
      
                                   
       
    public function getDataToTranslate(): array;

       
                                                                                      
      
                                        
       
    public function getContextOrNull(): ?array;

       
                                                                         
      
                                                  
       
    public function getSamplesOrNull(): ?array;

       
                                             
       
    public function getSiteDescriptionOrNull(): ?string;
}

                                                                              
interface Approach_TranslationWireProjectionInterface
{
    public function getPayloadJson(): string;

                                      
    public function getOptions(): array;

    public function getOptionsJson(): string;
    public function getPayloadBytes(): int;
    public function getOptionsBytes(): int;
}

interface Approach_TranslationWireProjectorInterface
{
    public function project(
        Approach_TranslationBatchInterface $batch
    ): ?Approach_TranslationWireProjectionInterface;
}

   
                                               
   
interface Approach_SubmitResultInterface
{
                                                       
    public function wasAccepted(): bool;
                                                  
    public function getJobIdOrNull(): ?string;
                                                 
    public function getErrorOrNull(): ?string;
}

   
                                       
   
interface Approach_PollResultInterface
{
                                               
    public function isPending(): bool;
                                                  
    public function isSuccessful(): bool;
                                                              
    public function getErrorOrNull(): ?string;

       
                                                           
                                                   
      
                                   
       
    public function getTranslatedMap(): array;
}


                                                                             
                
                                                                             

   
                                               
   
interface Config_DebugSettingsInterface
{
                                     
    public function isEnabled(): bool;

                                                 
    public function setEnabled(bool $enabled): void;
}

   
                                      
   
interface Config_LanguageAdminInterface
{
                                                              
    public function getSourceCode(): string;
    public function setSourceCode(string $code): void;

                                                                    
                               
    public function getTargetCodes(): array;
                                     
    public function setTargetCodes(array $codes): void;

                                                              
                               
    public function getCustomCodes(): array;
                                     
    public function setCustomCodes(array $codes): void;

                                                         
                                                                 
    public function getCustomizations(): array;
                                                                     
    public function setCustomizations(array $map): void;

                                                                                         
                                       
    public function getSelectableCatalog(): array;

                                                                                        
    public function getDisplayNameFor(string $code): string;
}

   
                                                    
  
                                                                  
                                                            
   
interface Config_ApproachSettingsInterface
{
                                                         
    public function isEnabled(): bool;

                                                                 
    public function getMaxBatchChars(): int;

                                                            
    public function getMaxBatchItems(): int;
}

   
                                      
   
final class Config_ApproachOptionNames
{
    public const APPROACH_ENABLED   = 'dolet_approach_enabled';
    public const MAX_BATCH_CHARS    = 'dolet_approach_max_batch_chars';
    public const MAX_BATCH_ITEMS    = 'dolet_approach_max_batch_items';
}


   
                                                       
  
                           
   
final class Config_DebugOptionNames
{
                                              
    public const DEBUG_OPTION_NAME = 'dolet_debug_enabled';
}

   
                                                  
   
final class Config_TranslationOptionNames
{
                                                                  
    public const FLOATING_SELECTOR_ENABLED = 'dolet_floating_language_selector_enabled';

                                                                         
    public const TRANSLATE_PRE_TEXTAREA = 'dolet_translate_pre_textarea';
}

                                                     
final class Config_InstallationOptionNames
{
    public const ACTIVATED_AT = 'dolet_activated_at';
}

   
                                                     
  
                                                                         
                                                              
   
final class Config_DiagnosticsOptionNames
{
    public const ERROR_LOG     = 'dolet_error_log';
    public const LAST_ERROR    = 'dolet_last_error';
    public const LAST_ERROR_AT = 'dolet_last_error_at';
    public const LAST_SUCCESS  = 'dolet_last_success';
}



                                                                             
              
                                                                             

   
                                                        
  
                                                                           
                                                                             
                                                                           
   
final class Core_StringsOptionNames
{
                                                                     
    public const TRANSLATED = 'dolet_strings_translated';
                                                                             
    public const AUTOTRANSLATE_STATE = 'dolet_strings_translate';
}



                                                                             
                     
                                                                             

                                                                                                
interface Diagnostics_StoreInterface
{
                                                          
    public function recordError(string $code, string $context): void;
                                                   
    public function recordSuccess(): void;
       
                                                                     
      
                                                                       
       
    public function getErrorLog(): array;
                                                   
    public function getLastError(): ?string;
                                                               
    public function getLastErrorAt(): ?string;
                                                                 
    public function getLastSuccess(): ?string;
                                                     
    public function clearErrorLog(): void;
}



                                                                             
                                                            
                                                                             

  
            
                                                                            
                                         
                                                                         
                               
   

   
                                                   
  
                                                                        
                                                                        
   
interface Data_TmRowInterface
{
    public function getId(): int;

    public function getSourceLanguageCode(): string;
    public function getTargetLanguageCode(): string;

    public function getSourcePrevOrNull(): ?string;
    public function getSourceCore(): string;
    public function getSourceNextOrNull(): ?string;

    public function isTranslated(): bool;
    public function isHumanLocked(): bool;

    public function getTargetCoreOrNull(): ?string;

    public function getMetaJsonOrNull(): ?string;

    public function getLastUsageEpochOrNull(): ?int;
    public function getExpirationEpochOrNull(): ?int;
}

   
                                                   
  
                                                                          
                                                                               
   
interface Data_TranslationMemoryRepositoryInterface
{
       
                                                                   
      
                                                                                
                                             
                                                                                   
                          
      
                                                                           
       
    public function findOrRegisterPending(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): ?string;

                                                                         
                                                                               
                                                                         

                                                             
    public function ensureReady(): void;

       
                                 
       
    public function findById(int $id): ?Data_TmRowInterface;

       
                                                                         
       
    public function findExactRecord(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): ?Data_TmRowInterface;

       
                                                                               
      
                                                                                                                                      
      
                                        
       
    public function findNeighborCandidates(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next,
        int $limit = 0,
        bool $allowHygiene = true
    ): array;

       
                                                                             
       
    public function touchUsageIfNeeded(int $id): bool;

       
                                                                      
                                                
       
    public function ensurePending(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): int;

                                                                     
                                                     
    public function updateTranslation(
        int $id,
        string $translatedText,
        bool $humanTouched,
        ?array $extraMeta
    ): void;

    public function markHumanTouched(int $id, bool $value): void;

    public function deleteById(int $id): void;

    public function deleteByWindow(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): void;

       
                                                      
      
                                        
       
    public function fetchPending(
        Language_PairInterface $pair,
        int $limit,
        bool $allowHygiene = true
    ): array;

       
                            
      
                                
       
    public function runHygiene(
        int $nowEpoch,
        int $maxLifetimeSeconds,
        int $pendingMaxAgeSeconds = 0,
        int $corruptedScanLimit = 500
    ): array;

       
                                                         
      
                                                                                                                                   
       
    public function getStatsByPair(): array;
}

   
                                                                       
  
                                       
   
final class Data_UniversalTableL1_Options
{
       
                             
      
                                                                     
                                                                            
      
                                                                  
       
    public const OPTION_DELETE_ON_UNINSTALL = 'dolet_universal_memory_delete_on_uninstall';
}



                                                                             
             
                                                                             

   
                                                                           
   
interface Dom_EngineInterface
{
                                                                                  
    public function loadFragment(string $html): void;

                                                                                
                                                                         
    public function forEachDocumentTitleText(callable $callback): void;

                                                                                     
                                                                                   
    public function forEachTextNode(bool $translatePreTextarea, callable $callback): void;

                                                                              
                                                                             
                                                  
    public function forEachBodyTranslationUnit(bool $translatePreTextarea, callable $callback): void;

                                                                      
    public function isNodeTextValid(mixed $nodeRef, string $text): bool;

                                                                             
    public function setNodeText(mixed $nodeRef, string $text): void;

                                                                              
                                                                                
    public function restoreSearchQueryEcho(
        mixed $nodeRef,
        string $source,
        string $translated,
        string $query
    ): string;

       
                                                                                     
                                                                                      
                                                                         
       
    public function forEachAnchor(bool $translatePreTextarea, callable $callback): void;

                                                                                     
    public function setAnchorHref(mixed $nodeRef, string $href): void;

                                                          
    public function serializeFragment(): string;
}

   
                                                                           
                                                                                  
   
interface Dom_TranslatableInterface
{
                                                                                                           
    public function getDocumentTitleTranslatableStrings(): ?Structs_FixedOrderedKeysArrayOfStringsInterface;

                                                                                                                                                               
                                                                                            
    public function getOrderedTranslatableStrings(): ?Structs_FixedOrderedKeysArrayOfStringsInterface;

                                                                            
    public function substituteDocumentTitleTranslatableStrings(
        Structs_FixedOrderedKeysArrayOfStringsInterface $substitutions
    ): bool;

                                                             
                                                   
    public function substituteOrderedTranslatableStrings(
        Structs_FixedOrderedKeysArrayOfStringsInterface $substitutions
    ): bool;

                                             
                                                                                                                          
    public function applyLanguageParameterToLinks(Language_OptionInterface $languageTarget): bool;

                       
    public function getHtml(): string;
}

   
                                                           
   
interface Dom_TranslatedStampInterface
{
                                                          
    public function isStampedAs(string $html, Language_OptionInterface $language): bool;

                              
    public function stamp(string $html, Language_OptionInterface $language): string;
}



   
                                                          
   
final class Dom_Constants
{
    public const LANGUAGE_URL_PARAMETER_NAME = 'l';

    public const PAGE_STAMP_PREFIX = 'DOLET_PAGE:';                                          
}



                                                                             
                  
                                                                             

   
                                                                                    
   
interface Language_AliasesDictionaryInterface
{
    public function isAliasOf(string $code): ?string;
}
   
                                             
   
interface Language_OptionInterface
{
                                            
    public function getCode(): string;

                                                 
    public function getName(): string;

                     
    public function getNote(): ?string;

                                        
    public function isCustom(): bool;
}
   
                                        
  
                                                                                        
                                         
   
interface Language_OptionFactoryInterface
{
    public function fromCodeElseNull(string $code, ?string $note = null): ?Language_OptionInterface;
}


   
                                                    
   
interface Language_PairInterface
{
    public function getSourceLanguage(): Language_OptionInterface;
    public function getTargetLanguage(): Language_OptionInterface;

                                                   
    public function optionsAreTheSame(): bool;
}

   
                                                                        
   
interface Language_SettingsInterface
{
                                     
    public function getWordpressLanguage(): Language_OptionInterface;

                                                    
    public function getRequestedLanguage(): Language_OptionInterface;

                                                            
    public function getSetLanguage_Pair(): Language_PairInterface;

                                                                               
    public function pairIsValid(Language_PairInterface $pair): bool;
}

   
                                                         
   
final class Language_AdminOptionNames
{
    public const SOURCE_CODE              = 'dolet_source_code';
    public const TARGET_LANGUAGES         = 'dolet_target_languages';
    public const LANGUAGE_CUSTOMIZATIONS  = 'dolet_language_customizations';
                                                                                           
}

   
                                                             
   
final class Language_Constants
{
    public const ORIGINAL      = 'original';
    public const ORIGINAL_NAME = 'Original';
    public const ORIGINAL_NOTE = 'show the original page';

    public const AUTODETECT      = 'autodetect';
    public const AUTODETECT_NAME = 'autodetected language';
    public const AUTODETECT_NOTE = 'autodetect the language';

    public const CUSTOM_LANGUAGES_LIST_OPTION_NAME = 'dolet_custom_languages_list';

       
                                           
      
                                                                 
      
                                                                                                                              
      
                                   
       
    public static function getAllLanguages(): array
    {
                                                    
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $loaded = include __DIR__ . '/all_languages.php';
        $cache  = is_array($loaded) ? $loaded : [];

        return $cache;
    }

}



                                                                             
                 
                                                                             

   
                                                            
  
                             
   
interface Routine_RoutineInterface
{
                                                                              
    public function execute(): void;
}



                                                                             
                 
                                                                             

   
                                                           
   
interface Structs_FixedOrderedKeysArrayOfStringsInterface
{
                                    
    public function asArray(): array;

                                                                                          
                                              
    public function setFromArray(array $newValues): void;

                                                                                    
    public function setFromSibling(
        Structs_FixedOrderedKeysArrayOfStringsInterface $sibling
    ): bool;

                                       
    public function getKeysHash(): string;

                             
    public function getValue(int|string $key): ?string;

                                     
    public function makeCopy(): Structs_FixedOrderedKeysArrayOfStringsInterface;
}

   
                                                                                  
   
interface Structs_TrimmableStringsArrayInterface
{
                             
    public function getTrimmedStrings(): Structs_FixedOrderedKeysArrayOfStringsInterface;

    public function setTrimmedStrings(
        Structs_FixedOrderedKeysArrayOfStringsInterface $modifiedTrimmedParts
    ): bool;

                                  
    public function getRebuiltArray(): Structs_FixedOrderedKeysArrayOfStringsInterface;
}



                                                                             
                     
                                                                             

   
                                                                   
   
interface Translation_TranslatorInterface
{
                                                                                    
    public function naiveTranslate(string $rawString): string;

                                                                                                                                                    
    public function translateContextual(
        Structs_FixedOrderedKeysArrayOfStringsInterface $arrayOfStrings
    ): Structs_FixedOrderedKeysArrayOfStringsInterface;

                     
    public function translateHtml(string $html): string;

                                                                                                  
    public function translateFinalHtml(string $html): string;
}

   
                                                                  
  
                                                                          
                                                                                 
   
interface Translation_WorkerServiceInterface
{
       
                                                                    
      
                                            
       
    public function submitPendingFor(Language_PairInterface $pair, int $limit): array;

       
                                                                       
      
                                       
       
    public function pollAndApplyResults(): array;
}

                                                                              
interface Translation_BatchProjectionInterface
{
    public function getBatch(): Approach_TranslationBatchInterface;

                                                                        
    public function getIndexToRowId(): array;

       
                                                                           
                                                                              
                                                           
      
                                                                                                    
       
    public function getSelectedRowCommitments(): array;

    public function getPendingFound(): int;
    public function getItemCount(): int;
    public function getCoreCharacterCount(): int;
}

interface Translation_BatchProjectorInterface
{
       
                                                                             
                                                 
       
    public function project(
        Language_PairInterface $pair,
        int $fetchLimit,
        int $maxItems,
        int $maxCoreCharacters
    ): Translation_BatchProjectionInterface;
}



                                                                             
                        
                                                                             

                                                         
interface UiVisitor_FloatingLanguageMenuViewInterface
{
    public function setAccessibleName(string $accessibleName): void;
    public function setSummaryLabel(string $summaryLabel): void;
    public function addItem(UiVisitor_LanguageMenuItemViewInterface $item): void;
    public function render(): string;
}

   
                                                              
  
                                        
   
interface UiVisitor_LanguageMenuWidgetInterface
{
                                                                              
                                                                             
                                                                            
                                                      
       
                                       
                                           
       
    public function widget($args, $instance): void;
                                               
    public function form($instance): void;
       
                                               
                                               
                                  
       
    public function update($new_instance, $old_instance): array;
}

   
                                                                          
  
                                                                                   
   
interface UiVisitor_LanguageMenuViewInterface
{
    public function setWrapperClass(string $class): void;
    public function setShowCodes(bool $showCodes): void;
    public function setShowInterlingua(bool $showInterlingua): void;

    public function addItem(UiVisitor_LanguageMenuItemViewInterface $item): void;

    public function render(): string;
}

   
                                                              
  
                                                                         
   
interface UiVisitor_LanguageMenuItemViewInterface
{
    public function getCode(): string;
    public function getEndonym(): string;
    public function getInterlinguaNameOrNull(): ?string;
    public function getUrl(): string;
    public function isActive(): bool;
}



                                                                             
                          
                                                                             

   
                                                                    
  
                                                                            
   
interface UiWebmaster_DebugToggleViewInterface
{
    public function setOptionName(string $optionName): void;
    public function setChecked(bool $checked): void;
    public function setLabel(string $labelHtml): void;

    public function render(): string;
}

   
                                                    
  
                                                                                      
   
interface UiWebmaster_LanguagesBuilderViewInterface
{
                     
    public function setLabelSource(string $labelHtml): void;
    public function setHintSource(string $hintHtml): void;
    public function setLabelTargets(string $labelHtml): void;
    public function setHintTargets(string $hintHtml): void;
    public function setLabelPalette(string $labelHtml): void;
    public function setLabelAddCustom(string $labelHtml): void;
    public function setPlaceholderSearch(string $placeholder): void;
    public function setTitleChipAdd(string $title): void;
    public function setTitleChipDisabled(string $title): void;

                      
    public function setSourceHiddenName(string $fieldName): void;
    public function setSourceHiddenValue(string $value): void;
    public function setSourceModeIsOriginal(bool $isOriginal): void;
    public function setSourceModeIsSite(bool $isSite): void;
    public function setSourceModeIsSpecific(bool $isSpecific): void;
    public function setSiteCode(string $siteCode): void;

       
                                                         
       
    public function setCatalogNoOriginal(array $catalog): void;

                       
    public function setSourceCustomizationFieldBase(string $fieldBase): void;
    public function setSourceDisplayCode(string $code): void;
    public function setSourceDefaultName(string $name): void;
    public function setSourceCurrentName(string $name): void;
    public function setSourceCurrentNote(string $note): void;

              
    public function setTargetsHolderDomId(string $domId): void;

       
                                      
       
    public function setTargetsSorted(array $codes): void;

    public function setLanguageCustomizationsOptionName(string $optionName): void;

       
                                           
       
    public function setCatalogForDefaults(array $catalog): void;

       
                                      
       
    public function setCustomCodes(array $codes): void;

    public function setCustomListOptionName(string $optionName): void;

    public function addPaletteItem(UiWebmaster_PaletteItemViewInterface $item): void;

    public function setPaletteAddTitle(string $title): void;
    public function setPaletteAlreadyTitle(string $title): void;

    public function render(): string;
}

   
                                                                      
  
                                                                            
   
interface UiWebmaster_PaletteItemViewInterface
{
    public function getCode(): string;
    public function getLabel(): string;
    public function isDisabled(): bool;
}
