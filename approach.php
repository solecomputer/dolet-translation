<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                                
  
                                                                         
                                                                      
                                      
  
                    
                                                                                       
                                                                       
                                                                         
  
                        
                                                    
                                                                           
                                       
  
                                                  
                                                                          
                                                                       
                                                                       
                                                                                   
  
                                                                    
                                                                         
                                                                     
                                                                              
  
                                                                           
                                                                             
                 
   

require_once __DIR__ . '/contracts.php';


                                                                             
                              
                                                                             

   
                                                  
  
                                           
                                                               
                                                                      
  
                                                                  
                                                                          
                                                                                
  
                                                             
                                                                             
                                                                             
  
                                                            
                                                                      
                                    
  
                                                                           
                                                                                 
   
interface Approach_ArrayNormalizerInterface
{
       
                                                                             
                          
                                                        
                                           
      
                                    
       
    public static function toIntKeysArrayOrderedStrict(mixed $input): ?array;

       
                                                                                               
      
                 
                                                                                
                                                                        
                                        
       
    public static function isStrIntKeysObjectJsonLike(mixed $input): bool;

       
                                                                                        
      
                 
                                                                       
                                                                             
                                                                    
      
                                       
       
    public static function toStrIntKeysObjectJsonFromIntMap(array $intMap): ?string;
}

   
                                                       
  
                 
                                                                                 
                                                           
  
                                                                           
                                                                        
                                                                          
                                   
   
interface Approach_SparseMapCodecInterface
{
       
                                                                                      
      
                                           
       
    public function encodeSparseMapJson(array $map): ?string;

       
                                                                                    
                                             
      
                                                     
      
                                                                    
      
                 
                                                                           
                                                                          
                                                            
       
    public function parseSparseMapJsonOrNull(string $json, ?array $allowedIntKeysSet): ?array;

       
                                                                            
      
                                           
      
                                     
      
                 
                                                                          
                                                                             
                                                                      
       
    public function toIntMapOrNull(array $map): ?array;

       
                                                        
      
                                      
      
                              
      
                 
                                                                            
                                                         
       
    public function toIntKeysSet(array $intMap): array;

       
                                                                            
      
                                                                           
                                                                          
                                                                          
                                                           
      
                                           
       
    public function toSparseMapStdClass(array $map): ?\stdClass;
}


                                                                             
         
                                                                             
         


                                                                             
        
                                                                             
         


                                                                             
                         
                                                                             

   
                                                            
  
                                                                           
                                    
                                                
                                                                               
  
             
                                                                             
                                                                               
                                                                           
                                                                                 
   
final class Approach_ArrayNormalizer implements Approach_ArrayNormalizerInterface
{
    public static function toIntKeysArrayOrderedStrict(mixed $input): ?array
    {
        $source = self::coerceToArray($input);
        if ($source === null) {
            return null;
        }

        $normalized = [];

        foreach ($source as $key => $value) {
            $intKey = self::keyToIntOrNullStrict($key);
            if ($intKey === null) {
                return null;
            }

            if (array_key_exists($intKey, $normalized)) {
                                                                                             
                                                                                        
                return null;
            }

            $normalized[$intKey] = $value;
        }

        ksort($normalized, SORT_NUMERIC);
        return $normalized;
    }

    public static function isStrIntKeysObjectJsonLike(mixed $input): bool
    {
        if (!is_string($input)) {
            return false;
        }

                                                                                    
                                                                             
                                                                                  
        $decoded = json_decode($input, false);
        if (json_last_error() !== JSON_ERROR_NONE || !($decoded instanceof \stdClass)) {
            return false;
        }

                                                                               
                                                                         
                                                                         
                                                                            
                                              
        foreach ($decoded as $propName => $_) {
            if (!is_string($propName) || !self::isCanonicalIntString($propName)) {
                return false;
            }
        }

        return true;
    }

       
                                       
       
    public static function toStrIntKeysObjectJsonFromIntMap(array $intMap): ?string
    {
        $obj = new \stdClass();

        foreach ($intMap as $k => $v) {
            $ks = (string)$k;

            if (!self::isCanonicalIntString($ks)) {
                return null;
            }

            if (property_exists($obj, $ks)) {
                                                                                
                                                                    
                return null;
            }

            $obj->{$ks} = $v;
        }

                                                                   
        if (function_exists('wp_json_encode')) {
            $json = wp_json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return is_string($json) ? $json : null;
        }

        $json = json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : null;
    }

                                                                             
                       
                                                                             

       
                                                                         
      
                 
                                                                        
                                                                          
                                                 
      
                                          
       
    private static function coerceToArray(mixed $input): ?array
    {
        if (is_array($input)) {
            return $input;
        }

        if ($input instanceof \Traversable) {
            try {
                                                   
                $arr = iterator_to_array($input, true);
            } catch (\Throwable) {
                return null;
            }

            return $arr;
        }

        if (is_object($input)) {
                                                  
            $props = get_object_vars($input);
            return $props;
        }

        return null;
    }

       
                                                                           
       
    private static function keyToIntOrNullStrict(string|int $key): ?int
    {
        if (is_int($key)) {
            return $key;
        }

        if (!self::isCanonicalIntString($key)) {
            return null;
        }

        return (int)$key;
    }

       
                                      
      
                      
                
                                                             
      
                                                                       
                      
       
    private static function isCanonicalIntString(string $s): bool
    {
        if ($s === '0') {
            return true;
        }

        return (preg_match('/^-?[1-9]\d*$/', $s) === 1);
    }
}

   
                                       
   
final class Approach_PollResult implements Approach_PollResultInterface
{
       
                                                 
       
    public function __construct(
        private bool $pending,
        private bool $successful,
        private ?string $error,
        private array $translatedMap
    ) {}

    public function isPending(): bool
    {
        return $this->pending;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getErrorOrNull(): ?string
    {
        return $this->error;
    }

    public function getTranslatedMap(): array
    {
        return $this->translatedMap;
    }
}

                                                                 
final class Approach_TranslationWireProjection implements Approach_TranslationWireProjectionInterface
{
                                              
    public function __construct(
        private string $payloadJson,
        private array $options,
        private string $optionsJson
    ) {}

    public function getPayloadJson(): string
    {
        return $this->payloadJson;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOptionsJson(): string
    {
        return $this->optionsJson;
    }

    public function getPayloadBytes(): int
    {
        return strlen($this->payloadJson);
    }

    public function getOptionsBytes(): int
    {
        return strlen($this->optionsJson);
    }
}

   
                                                                            
                                                                              
   
final class Approach_TranslationWireProjector implements Approach_TranslationWireProjectorInterface
{
    public function __construct(
        private ?Approach_SparseMapCodecInterface $codec = null
    ) {
        $this->codec ??= new Approach_SparseMapCodec();
    }

    public function project(
        Approach_TranslationBatchInterface $batch
    ): ?Approach_TranslationWireProjectionInterface {
        $dataToTranslate = $batch->getDataToTranslate();
        $payloadJson = $dataToTranslate === []
            ? '{}'
            : $this->codec->encodeSparseMapJson($dataToTranslate);
        if ($payloadJson === null) {
            return null;
        }

        $pair = $batch->getLanguagePair();
        $src = $pair->getSourceLanguage();
        $tgt = $pair->getTargetLanguage();
        $options = ['targetLanguage' => $tgt->getCode()];

        if ($tgt->getName() !== '') {
            $options['targetLanguageName'] = $tgt->getName();
        }
        if (is_string($tgt->getNote()) && $tgt->getNote() !== '') {
            $options['targetLanguageNote'] = $tgt->getNote();
        }

        $srcCode = $src->getCode();
        if ($srcCode !== '' && $srcCode !== 'autodetect' && $srcCode !== 'original') {
            $options['sourceLanguage'] = $srcCode;
            if ($src->getName() !== '') {
                $options['sourceLanguageName'] = $src->getName();
            }
            if (is_string($src->getNote()) && $src->getNote() !== '') {
                $options['sourceLanguageNote'] = $src->getNote();
            }
        }

        $context = $batch->getContextOrNull();
        if (is_array($context) && $context !== []) {
            $contextObject = $this->codec->toSparseMapStdClass($context);
            if ($contextObject !== null) {
                $options['context'] = $contextObject;
            }
        }

        $samples = $batch->getSamplesOrNull();
        if (is_array($samples) && $samples !== []) {
            $options['samples'] = $samples;
        }

        $siteDescription = $batch->getSiteDescriptionOrNull();
        if (is_string($siteDescription) && $siteDescription !== '') {
            $options['siteDescription'] = $siteDescription;
        }

                                                                         
        $optionsJson = wp_json_encode($options);
        if (!is_string($optionsJson)) {
            return null;
        }

        return new Approach_TranslationWireProjection($payloadJson, $options, $optionsJson);
    }
}

   
                                              
  
                                                                                       
                            
                                                                          
                                                                          
  
                                                                        
                                                                         
                                                      
   
final class Approach_SoleEngineClient implements Approach_SoleEngineClientInterface
{
    private Approach_SparseMapCodecInterface $codec;
    private Approach_TranslationWireProjectorInterface $wireProjector;

    public function __construct(
        ?Approach_SparseMapCodecInterface $codec = null,
        ?Approach_TranslationWireProjectorInterface $wireProjector = null
    ) {
        $this->codec = $codec ?? new Approach_SparseMapCodec();
        $this->wireProjector = $wireProjector ?? new Approach_TranslationWireProjector($this->codec);
    }

    public function isAvailable(): bool
    {
        return function_exists('sole_engine_llm');
    }

    public function submitTranslateBatch(
        Approach_TranslationBatchInterface $batch
    ): Approach_SubmitResultInterface {
        if (!$this->isAvailable()) {
            return new Approach_SubmitResult(false, null, 'SOLE Engine WP plugin is not active.');
        }

        if ($batch->getDataToTranslate() === []) {
            return new Approach_SubmitResult(false, null, 'data_to_translate is empty.');
        }

        $wire = $this->wireProjector->project($batch);
        if ($wire === null) {
            return new Approach_SubmitResult(false, null, 'Could not encode payload (invalid key shape).');
        }

        try {
                                                             
            $ticket = sole_engine_llm()->submit(
                'dolet-wp',
                'translate_batch',
                $wire->getPayloadJson(),
                $wire->getOptions()
            );                             
        } catch (\Throwable $e) {
            return new Approach_SubmitResult(false, null, 'SOLE Engine submit error: ' . $e->getMessage());
        }

        if (!$ticket->wasAccepted()) {
            return new Approach_SubmitResult(false, null, $ticket->getError() ?? 'Job was not accepted.');
        }

        return new Approach_SubmitResult(true, $ticket->getJobId(), null);
    }

    public function pollResult(string $jobId): Approach_PollResultInterface
    {
        if (!$this->isAvailable()) {
            return new Approach_PollResult(false, false, 'SOLE Engine WP plugin is not active.', []);
        }

        try {
                                                              
            $reply = sole_engine_llm()->getResult($jobId);
        } catch (\Throwable $e) {
            return new Approach_PollResult(false, false, 'SOLE Engine poll error: ' . $e->getMessage(), []);
        }

        if ($reply->isPending()) {
            return new Approach_PollResult(true, false, null, []);
        }

        if (!$reply->isSuccessful()) {
            return new Approach_PollResult(false, false, $reply->getError() ?? 'Job failed.', []);
        }

                                                
        $replyString = $reply->getReply();
        if (!is_string($replyString) || $replyString === '') {
            return new Approach_PollResult(false, false, 'Empty reply from SOLE Engine.', []);
        }

        $parsed = $this->codec->parseSparseMapJsonOrNull($replyString, null);
        if ($parsed === null) {
            return new Approach_PollResult(false, false, 'Invalid translated_list wire-shape from SOLE Engine.', []);
        }

        return new Approach_PollResult(false, true, null, $parsed);
    }
}

   
                                                                              
  
                                                                                
                                                                             
                                          
   
final class Approach_EngineCredentialReadinessReader implements Approach_EngineCredentialReadinessReaderInterface
{
    public function read(): array
    {
        if (!function_exists('sole_engine_credential_readiness')) {
            return $this->unavailable();
        }

        try {
            $snapshot = \sole_engine_credential_readiness();
            $state = $snapshot->getState();
            $ready = $snapshot->isReady();
            $checkedAt = $snapshot->getCheckedAt();
        } catch (\Throwable) {
            return $this->unavailable();
        }

        if (!in_array($state, [
            Approach_EngineCredentialStates::MISSING,
            Approach_EngineCredentialStates::UNVERIFIED,
            Approach_EngineCredentialStates::INVALID,
            Approach_EngineCredentialStates::VERIFIED,
        ], true)) {
            return $this->unavailable();
        }

                                                                             
                                                                    
        $expectedReady = $state === Approach_EngineCredentialStates::VERIFIED;
        $mustHaveEvidence = in_array($state, [
            Approach_EngineCredentialStates::INVALID,
            Approach_EngineCredentialStates::VERIFIED,
        ], true);
        if (
            $ready !== $expectedReady
            || ($mustHaveEvidence && $checkedAt === null)
            || (!$mustHaveEvidence && $checkedAt !== null)
        ) {
            return $this->unavailable();
        }

        return [
            'state'      => $state,
            'ready'      => $ready,
            'checked_at' => $checkedAt,
        ];
    }

                                                                 
    private function unavailable(): array
    {
        return [
            'state'      => Approach_EngineCredentialStates::UNAVAILABLE,
            'ready'      => false,
            'checked_at' => null,
        ];
    }
}

   
                                    
  
                    
                                                                        
                                                                     
                                                                        
  
                                                                         
                                                                              
                                                                             
                                 
   
final class Approach_SparseMapCodec implements Approach_SparseMapCodecInterface
{
    public function encodeSparseMapJson(array $map): ?string
    {
        $intMap = $this->toIntMapOrNull($map);
        if ($intMap === null) {
            return null;
        }

                                                                                       
                                                                                   
                                                                                  
                                                 
        foreach ($intMap as $v) {
            if ($v === '') {
                return null;
            }
        }

                                               
        return Approach_ArrayNormalizer::toStrIntKeysObjectJsonFromIntMap($intMap);
    }

    public function parseSparseMapJsonOrNull(string $json, ?array $allowedIntKeysSet): ?array
    {
        if ($json === '') {
                                                                                    
            return [];
        }

                                                                                            
        if (!Approach_ArrayNormalizer::isStrIntKeysObjectJsonLike($json)) {
            return null;
        }

        $decoded = json_decode($json, false);
        if (!($decoded instanceof \stdClass)) {
            return null;
        }

                                              
        $props = get_object_vars($decoded);

        $intMap = Approach_ArrayNormalizer::toIntKeysArrayOrderedStrict($props);
        if ($intMap === null) {
            return null;
        }

        $out = [];

        foreach ($intMap as $k => $v) {
            if ($allowedIntKeysSet !== null && !isset($allowedIntKeysSet[$k])) {
                                                                   
                continue;
            }

            if (!is_string($v) || $v === '') {
                                                                                               
                continue;
            }

                                                                
            $out[(string)$k] = $v;
        }

        return $out;
    }

    public function toIntMapOrNull(array $map): ?array
    {
        $intMap = Approach_ArrayNormalizer::toIntKeysArrayOrderedStrict($map);
        if ($intMap === null) {
            return null;
        }

        $out = [];
        foreach ($intMap as $k => $v) {
            if (!is_string($v)) {
                return null;
            }

                                                                                               
            $out[$k] = $v;
        }

        ksort($out, SORT_NUMERIC);
        return $out;
    }

    public function toIntKeysSet(array $intMap): array
    {
        $set = [];

        foreach ($intMap as $k => $_) {
            $set[$k] = true;
        }

        return $set;
    }

    public function toSparseMapStdClass(array $map): ?\stdClass
    {
        $intMap = $this->toIntMapOrNull($map);
        if ($intMap === null) {
            return null;
        }

        foreach ($intMap as $v) {
            if ($v === '') {
                return null;
            }
        }

        $obj = new \stdClass();
        foreach ($intMap as $k => $v) {
            $obj->{(string)$k} = $v;
        }

        return $obj;
    }
}

   
                                               
   
final class Approach_SubmitResult implements Approach_SubmitResultInterface
{
    public function __construct(
        private bool $accepted,
        private ?string $jobId,
        private ?string $error
    ) {}

    public function wasAccepted(): bool
    {
        return $this->accepted;
    }

    public function getJobIdOrNull(): ?string
    {
        return $this->jobId;
    }

    public function getErrorOrNull(): ?string
    {
        return $this->error;
    }
}

   
                                                                          
   
final class Approach_TranslationBatch implements Approach_TranslationBatchInterface
{
       
                                                   
                                                
                                                          
       
    public function __construct(
        private Language_PairInterface $pair,
        private array $dataToTranslate,
        private ?array $context = null,
        private ?array $samples = null,
        private ?string $siteDescription = null
    ) {}

    public function getLanguagePair(): Language_PairInterface
    {
        return $this->pair;
    }

    public function getDataToTranslate(): array
    {
        return $this->dataToTranslate;
    }

    public function getContextOrNull(): ?array
    {
        return $this->context;
    }

    public function getSamplesOrNull(): ?array
    {
        return $this->samples;
    }

    public function getSiteDescriptionOrNull(): ?string
    {
        return $this->siteDescription;
    }
}
