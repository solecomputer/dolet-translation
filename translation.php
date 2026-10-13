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
require_once __DIR__ . '/structs.php';

                                                                             
                              
                                                                             

   
                                                              
   
interface Translation_TmLookupServiceInterface
{
    public function findBestTranslationOrNull(
        Data_TranslationMemoryRepositoryInterface $repo,
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next,
        int $neighborLimit = 0
    ): ?string;
}

   
                                                                    
   
interface Translation_TriadV1NeighborChooserInterface
{
       
                 
                                                                            
                                                                       
      
                                                   
       
    public function chooseBest(array $candidates): ?Data_TmRowInterface;
}


                                                                             
         
                                                                             
         


                                                                             
        
                                                                             
         


                                                                             
                         
                                                                             

final class Translation_TmLookupService implements Translation_TmLookupServiceInterface
{
    private const DEFAULT_NEIGHBOR_LIMIT = 30;

    private Translation_TriadV1NeighborChooserInterface $chooser;

    public function __construct(?Translation_TriadV1NeighborChooserInterface $chooser = null)
    {
        $this->chooser = $chooser ?? new Translation_TriadV1NeighborChooser();
    }

    public function findBestTranslationOrNull(
        Data_TranslationMemoryRepositoryInterface $repo,
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next,
        int $neighborLimit = 0
    ): ?string {
        $exact = $repo->findExactRecord($pair, $prev, $core, $next);

        $exactTranslation = $this->extractTranslationOrNull($exact);
        if ($exactTranslation !== null) {
            $id = $this->extractRowIdOrNull($exact);

            if (is_int($id) && $id > 0) {
                $repo->touchUsageIfNeeded($id);
            }

            return $exactTranslation;
        }

        if ($neighborLimit <= 0) {
            $neighborLimit = self::DEFAULT_NEIGHBOR_LIMIT;
        }

        $candidates = $repo->findNeighborCandidates($pair, $prev, $core, $next, $neighborLimit);
        $best = $this->chooser->chooseBest($candidates);

        $bestTranslation = $this->extractTranslationOrNull($best);
        if ($bestTranslation !== null) {
            $id = $this->extractRowIdOrNull($best);

            if (is_int($id) && $id > 0) {
                $repo->touchUsageIfNeeded($id);
            }

            return $bestTranslation;
        }

        $repo->ensurePending($pair, $prev, $core, $next);
        return null;
    }

    private function extractTranslationOrNull(?Data_TmRowInterface $row): ?string
    {
        if (!$row instanceof Data_TmRowInterface) {
            return null;
        }

        if (!$row->isTranslated()) {
            return null;
        }

        $t = $row->getTargetCoreOrNull();
        if (!is_string($t) || $t === '') {
            return null;
        }

        return $t;
    }

    private function extractRowIdOrNull(?Data_TmRowInterface $row): ?int
    {
        if (!$row instanceof Data_TmRowInterface) {
            return null;
        }

        $id = $row->getId();
        return ($id > 0) ? $id : null;
    }
}

   
                                                                         
                                         
  
        
                                                                                   
                                                                       
                                                          
   
final class Translation_Translator implements Translation_TranslatorInterface
{
    public function __construct(
        private Language_PairInterface $languagePair,
        private Config_DebugSettingsInterface $debugSettings,
        private Data_TranslationMemoryRepositoryInterface $memory,
        private Dom_TranslatedStampInterface $stamper,
        private bool $translatePreTextarea = false,
    ) {}

       
                                                                               
                                                                          
                                     
       
    private static bool $ranThisRequest = false;

       
                                                                                               
       
    private ?Translation_TmLookupServiceInterface $tmLookup = null;

                                                                             
                                            
                                                                             

       
                                                            
                                                
       
    public function naiveTranslate(string $rawString): string
    {
        if (!$this->debugSettings->isEnabled()) {
            return $rawString;
        }

        $target = $this->languagePair->getTargetLanguage()->getCode();

                                                 
        $headComment = "<!-- DOLET_LANG:$target -->";
        $tailComment = "<!-- DOLET_END -->";

                                             
        $start = "<div class='dolet-debug dolet-start'>⟦ $target ⟧ (DEBUG) Naive TRANSLATION START</div>";
        $end   = "<div class='dolet-debug dolet-end'> Naive TRANSLATION END (DEBUG) ⟦ $target ⟧</div>";

        return $headComment . "\n" . $start . "\n" . $rawString . "\n" . $end . "\n" . $tailComment;
    }

                                                                             
                                                            
                                                                             

       
                                                                       
                                                             
      
                 
                                                                               
       
    public function translateContextual(
        Structs_FixedOrderedKeysArrayOfStringsInterface $arrayOfStrings
    ): Structs_FixedOrderedKeysArrayOfStringsInterface {
                                                                           
        $copy  = $arrayOfStrings->makeCopy();
        $debug = $this->debugSettings->isEnabled();
        $pair  = $this->languagePair;

        $coresByKey = $arrayOfStrings->asArray();
        $keys       = array_keys($coresByKey);

        $n = count($keys);
        if ($n === 0) {
            return $copy;
        }

        $out = $copy->asArray();

        $tm = $this->getTmLookupService();

        for ($pos = 0; $pos < $n; $pos++) {
            $k    = $keys[$pos];
            $core = (string)($coresByKey[$k] ?? '');

                                                                                      
            if ($core === '') {
                continue;
            }

                                                                                
            if (self::isPureNumberCore($core)) {
                continue;
            }

            $prevKey = $pos > 0      ? $keys[$pos - 1] : null;
            $nextKey = $pos < $n - 1 ? $keys[$pos + 1] : null;

            $prev = ($prevKey !== null) ? (string)($coresByKey[$prevKey] ?? '') : '';
            $next = ($nextKey !== null) ? (string)($coresByKey[$nextKey] ?? '') : '';

            $translated = null;

            try {
                $translated = $tm->findBestTranslationOrNull($this->memory, $pair, $prev, $core, $next, 30);
            } catch (\Throwable $e) {
                                                                  
                if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[Dolet WP] TM lookup error: ' . $e->getMessage());
                }
            }

            $hit  = (is_string($translated) && $translated !== '');
            $text = $hit ? (string)$translated : $core;

            if ($debug) {
                $mark    = $hit ? '✓ ' : '✗ ';
                $out[$k] = $mark . '➡️' . $text . '⬅️';
            } else {
                $out[$k] = $text;
            }
        }

        $copy->setFromArray($out);
        return $copy;
    }

       
                                                                 
      
               
              
                  
                  
             
                                  
       
    private static function isPureNumberCore(string $s): bool
    {
        return Structs_PureNumber::is($s);
    }

                                                                             
                                                             
                                                                             

       
                                  
       
    public function translateHtml(string $html): string
    {
        try {
            $searchQueryEcho = '';
            if (\function_exists('is_search') && \is_search() && \function_exists('get_search_query')) {
                $candidate = \get_search_query(false);
                if (\is_string($candidate)) {
                    $searchQueryEcho = $candidate;
                }
            }
            $dom = new Dom_Translatable(
                $html,
                $this->translatePreTextarea,
                $this->debugSettings->isEnabled(),
                $searchQueryEcho
            );

                                                                           
                                                                            
            $documentTitleCores = $dom->getDocumentTitleTranslatableStrings();
            $cores = $dom->getOrderedTranslatableStrings();

            $ok = $dom->applyLanguageParameterToLinks($this->languagePair->getTargetLanguage());
            if (!$ok) {
                return $html;
            }

            if ($cores === null) {
                return $dom->getHtml();
            }

            if (empty($cores->asArray())) {
                if ($documentTitleCores === null || empty($documentTitleCores->asArray())) {
                    return $dom->getHtml();
                }
            } else {
                $translated = $this->translateContextual($cores);

                $ok = $dom->substituteOrderedTranslatableStrings($translated);
                if (!$ok) {
                    return $dom->getHtml();
                }
            }

            if ($documentTitleCores !== null && !empty($documentTitleCores->asArray())) {
                $translatedTitle = $this->translateContextual($documentTitleCores);
                $ok = $dom->substituteDocumentTitleTranslatableStrings($translatedTitle);
                if (!$ok) {
                    return $dom->getHtml();
                }
            }

            return $dom->getHtml();
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] translateHtml error: ' . $e->getMessage());
            }
            return $html;
        }
    }

                                                                             
                                       
                                                                             

       
                                     
       
    public function translateFinalHtml(string $html): string
    {
        $targetLanguage = $this->languagePair->getTargetLanguage();
        $debug          = $this->debugSettings->isEnabled();

        if ($debug) {
            if (self::$ranThisRequest) {
                return $html;
            }

            self::$ranThisRequest = true;

            $translated = $this->translateHtml($html);
            return $this->stamper->stamp($translated, $targetLanguage);
        }

        $stamped = $this->stamper->isStampedAs($html, $targetLanguage);
        if ($stamped) {
            return $html;
        }

        if (self::$ranThisRequest) {
            return $html;
        }

        self::$ranThisRequest = true;

        $translated = $this->translateHtml($html);
        return $this->stamper->stamp($translated, $targetLanguage);
    }

    private function getTmLookupService(): Translation_TmLookupServiceInterface
    {
        if ($this->tmLookup !== null) {
            return $this->tmLookup;
        }

        $this->tmLookup = new Translation_TmLookupService();
        return $this->tmLookup;
    }
}

   
                                               
  
                                                                   
   
final class Translation_TriadV1NeighborChooser implements Translation_TriadV1NeighborChooserInterface
{
       
                                                                                         
       
    public function chooseBest(array $candidates): ?Data_TmRowInterface
    {
        $best = null;

        foreach ($candidates as $c) {
            $id = $c->getId();
            if ($id <= 0) {
                continue;
            }

                                                                                                  
            if (!$this->rowHasUsableTranslation($c)) {
                continue;
            }

            if ($best === null) {
                $best = $c;
                continue;
            }

                                                    
            if ($c->isHumanLocked() !== $best->isHumanLocked()) {
                if ($c->isHumanLocked()) {
                    $best = $c;
                }
                continue;
            }

                                                           
            $cUsage = $this->usageEpochOrZero($c);
            $bUsage = $this->usageEpochOrZero($best);
            if ($cUsage !== $bUsage) {
                if ($cUsage > $bUsage) {
                    $best = $c;
                }
                continue;
            }

                                                          
            if ($id < $best->getId()) {
                $best = $c;
            }
        }

        return $best;
    }

    private function rowHasUsableTranslation(Data_TmRowInterface $row): bool
    {
        if (!$row->isTranslated()) {
            return false;
        }

        $t = $row->getTargetCoreOrNull();
        return (is_string($t) && $t !== '');
    }

    private function usageEpochOrZero(Data_TmRowInterface $row): int
    {
        $v = $row->getLastUsageEpochOrNull();
        return is_int($v) ? $v : 0;
    }
}

                                                           
final class Translation_BatchProjection implements Translation_BatchProjectionInterface
{
       
                                             
                                                                                                                
       
    public function __construct(
        private Approach_TranslationBatchInterface $batch,
        private array $indexToRowId,
        private array $commitments,
        private int $pendingFound,
        private int $itemCount,
        private int $coreCharacterCount
    ) {}

    public function getBatch(): Approach_TranslationBatchInterface
    {
        return $this->batch;
    }

    public function getIndexToRowId(): array
    {
        return $this->indexToRowId;
    }

    public function getSelectedRowCommitments(): array
    {
        return $this->commitments;
    }

    public function getPendingFound(): int
    {
        return $this->pendingFound;
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    public function getCoreCharacterCount(): int
    {
        return $this->coreCharacterCount;
    }
}

   
                                                                              
                                                                               
   
final class Translation_BatchProjector implements Translation_BatchProjectorInterface
{
    public function __construct(
        private Data_TranslationMemoryRepositoryInterface $repo
    ) {}

    public function project(
        Language_PairInterface $pair,
        int $fetchLimit,
        int $maxItems,
        int $maxCoreCharacters
    ): Translation_BatchProjectionInterface {
        if ($fetchLimit < 1 || $maxItems < 1 || $maxCoreCharacters < 1) {
            throw new Data_InvalidArgumentException('Projection limits must be positive.');
        }

        $pending = $this->repo->fetchPending($pair, $fetchLimit, false);

                                                 
        $payload = [];
                                                 
        $context = [];
                                                   
        $indexToRowId = [];
                                                                                                                      
        $commitments = [];
                                                            
        $placedNeighborSources = [];

        $usedCharacters = 0;
        $usedItems = 0;
        $key = 0;

        foreach ($pending as $row) {
            $rowId = $row->getId();
            $core = $row->getSourceCore();
            if ($rowId <= 0 || $row->isHumanLocked() || $core === '' || Structs_PureNumber::is($core)) {
                continue;
            }

            $coreCharacters = $this->stringLength($core);
            if ($usedItems + 1 > $maxItems || $usedCharacters + $coreCharacters > $maxCoreCharacters) {
                break;
            }

            $previous = (string) ($row->getSourcePrevOrNull() ?? '');
            $next = (string) ($row->getSourceNextOrNull() ?? '');

            if ($previous !== '' && !isset($placedNeighborSources[$previous])) {
                $translation = $this->findTranslatedNeighbor($pair, $previous, $core, true);
                if ($translation !== null) {
                    $payload[(string) $key] = $previous;
                    $context[(string) $key] = $translation;
                    $placedNeighborSources[$previous] = $key;
                    $key++;
                }
            }

            $coreKey = (string) $key;
            $payload[$coreKey] = $core;
            $indexToRowId[$coreKey] = $rowId;
            $sourceHash = hash('sha256', $core);
            $commitmentJson = wp_json_encode([
                'schema' => 'dolet.selected_row.v1',
                'pair' => [
                    'source' => $pair->getSourceLanguage()->getCode(),
                    'target' => $pair->getTargetLanguage()->getCode(),
                ],
                'row_id' => $rowId,
                'core_key' => $coreKey,
                'source_sha256' => $sourceHash,
            ], JSON_UNESCAPED_SLASHES);
            $commitments[] = [
                'row_id' => $rowId,
                'core_key' => $coreKey,
                'source_sha256' => $sourceHash,
                'commitment_sha256' => hash('sha256', is_string($commitmentJson) ? $commitmentJson : ''),
            ];
            $key++;

            if ($next !== '' && !isset($placedNeighborSources[$next])) {
                $translation = $this->findTranslatedNeighbor($pair, $next, $core, false);
                if ($translation !== null) {
                    $payload[(string) $key] = $next;
                    $context[(string) $key] = $translation;
                    $placedNeighborSources[$next] = $key;
                    $key++;
                }
            }

            $usedItems++;
            $usedCharacters += $coreCharacters;
        }

        return new Translation_BatchProjection(
            new Approach_TranslationBatch($pair, $payload, $context !== [] ? $context : null, null, null),
            $indexToRowId,
            $commitments,
            count($pending),
            $usedItems,
            $usedCharacters
        );
    }

    private function findTranslatedNeighbor(
        Language_PairInterface $pair,
        string $neighborSource,
        string $pendingCore,
        bool $neighborIsPrevious
    ): ?string {
        $candidates = $this->repo->findNeighborCandidates(
            $pair,
            $neighborIsPrevious ? '' : $pendingCore,
            $neighborSource,
            $neighborIsPrevious ? $pendingCore : '',
            10,
            false
        );
        $best = (new Translation_TriadV1NeighborChooser())->chooseBest($candidates);
        if ($best === null) {
            return null;
        }
        $translation = $best->getTargetCoreOrNull();
        return is_string($translation) && $translation !== '' ? $translation : null;
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen')
            ? (int) mb_strlen($value, 'UTF-8')
            : (int) strlen($value);
    }
}

   
                                                                  
  
                    
                                                                               
                                                                                        
  
                           
                                                        
                                                                                                 
                                                               
                                                                                                     
                         
  
                                                                                  
                                                                                    
                                                                                    
                                                                                 
                            
  
                        
                                                                    
   
final class Translation_WorkerService implements Translation_WorkerServiceInterface
{
                                                                                  
    private const DEFAULT_MAX_BATCH_CHARS = 120000;
    private const DEFAULT_MAX_BATCH_ITEMS = 200;

    private const JOB_REGISTRY_KEY = 'dolet_pending_jobs';
    private const JOB_PREFIX       = 'dolet_job_';
    private const JOB_TTL          = 7200;           

    public function __construct(
        private Data_TranslationMemoryRepositoryInterface $repo,
        private Approach_SoleEngineClientInterface $engine,
        private Config_DebugSettingsInterface $debugSettings,
        private ?Config_ApproachSettingsInterface $approachSettings = null,
        private ?Diagnostics_StoreInterface $diagnostics = null,
        private ?Translation_BatchProjectorInterface $batchProjector = null
    ) {
        $this->batchProjector ??= new Translation_BatchProjector($this->repo);
    }

                                                                             
                                      
                                                                             

       
                                       
       
    public function pollAndApplyResults(): array
    {
        $registry = $this->getJobRegistry();
        if (empty($registry)) {
            return [
                'jobs_checked' => 0,
                'applied'      => 0,
                'pending'      => 0,
                'failed'       => 0,
                'lost'         => 0,
            ];
        }

        $applied = 0;
        $pending = 0;
        $failed  = 0;
        $lost    = 0;
        $remaining = [];

        foreach ($registry as $jobKey) {
            $jobData = $this->loadJob((string)$jobKey);
            if ($jobData === null) {
                $lost++;
                $this->diagnostics?->recordError('job_data_lost', 'Transient missing for key ' . $jobKey);
                continue;
            }

            $jobId = $jobData['job_id'] ?? null;
            if (!is_string($jobId) || $jobId === '') {
                $lost++;
                $this->diagnostics?->recordError('job_data_lost', 'No job_id in transient ' . $jobKey);
                $this->deleteJob((string)$jobKey);
                continue;
            }

            $result = $this->engine->pollResult($jobId);

            if ($result->isPending()) {
                $pending++;
                $remaining[] = $jobKey;
                continue;
            }

            if (!$result->isSuccessful()) {
                $failed++;
                $error = $result->getErrorOrNull() ?? 'Unknown error';
                if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[Dolet WP] Job ' . $jobId . ' failed: ' . $error);
                }
                $errorCode = in_array($error, ['config_missing', 'user_key_invalid'], true) ? $error : 'poll_failed';
                $this->diagnostics?->recordError($errorCode, 'Job ' . $jobId . ': ' . $error);
                $this->deleteJob((string)$jobKey);
                                                                      
                continue;
            }

                                  
            $translatedMap  = $result->getTranslatedMap();
            if (empty($translatedMap)) {
                $failed++;
                $this->diagnostics?->recordError('poll_empty', 'Job ' . $jobId . ': successful poll returned empty translated map');
                $this->deleteJob((string)$jobKey);
                continue;
            }
            $indexToRowId   = $jobData['index_to_row_id'] ?? [];

            $jobApplied = 0;

            foreach ($translatedMap as $k => $translated) {
                if ($translated === '') {
                    continue;
                }
                                                                                           
                                                    
                $k = (string) $k;

                $rowId = $indexToRowId[$k] ?? null;
                if (!is_int($rowId) && !(is_string($rowId) && ctype_digit($rowId))) {
                    continue;
                }
                $rowId = (int)$rowId;
                if ($rowId <= 0) {
                    continue;
                }

                                               
                $row = $this->repo->findById($rowId);
                if (!$row instanceof Data_TmRowInterface || $row->isHumanLocked()) {
                    continue;
                }

                $this->repo->updateTranslation($rowId, $translated, false, null);
                $jobApplied++;
            }

            $applied += $jobApplied;
            if ($jobApplied > 0) {
                $this->diagnostics?->recordSuccess();
            }
            $this->deleteJob((string)$jobKey);
        }

        $this->setJobRegistry($remaining);

        return [
            'jobs_checked' => count($registry),
            'applied'      => $applied,
            'pending'      => $pending,
            'failed'       => $failed,
            'lost'         => $lost,
        ];
    }

                                                                             
                                                     
                                                                             

       
                                            
       
    public function submitPendingFor(Language_PairInterface $pair, int $limit): array
    {
        $limit = max(1, $limit);

                                                                   
        $pairHash = md5($pair->getSourceLanguage()->getCode() . ':' . $pair->getTargetLanguage()->getCode());
        $pairKeyPrefix = self::JOB_PREFIX . $pairHash;
        $registry = $this->getJobRegistry();
        foreach ($registry as $jobKey) {
            if (str_starts_with((string)$jobKey, $pairKeyPrefix)) {
                return [
                    'pair'          => $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode(),
                    'pending_found' => 0,
                    'batched'       => 0,
                    'submitted'     => false,
                    'error'         => '',
                    'skipped'       => 'job_in_flight',
                ];
            }
        }

        $projection = $this->batchProjector->project(
            $pair,
            $limit,
            $this->getMaxBatchItems(),
            $this->getMaxBatchChars()
        );

        if ($projection->getItemCount() === 0) {
            return [
                'pair'          => $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode(),
                'pending_found' => $projection->getPendingFound(),
                'batched'       => 0,
                'submitted'     => false,
                'error'         => '',
            ];
        }

        $result = $this->engine->submitTranslateBatch($projection->getBatch());

        if (!$result->wasAccepted()) {
            $msg = $result->getErrorOrNull() ?? 'Submit failed.';

            if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Worker submit failure: ' . $msg);
            }
            $errorCode = in_array($msg, ['config_missing', 'user_key_invalid'], true) ? $msg : 'submit_failed';
            $this->diagnostics?->recordError($errorCode, $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode() . ': ' . $msg);

            return [
                'pair'          => $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode(),
                'pending_found' => $projection->getPendingFound(),
                'batched'       => $projection->getItemCount(),
                'submitted'     => false,
                'error'         => $msg,
            ];
        }

                                                       
        $jobId  = $result->getJobIdOrNull() ?? '';
        $pairHash = md5($pair->getSourceLanguage()->getCode() . ':' . $pair->getTargetLanguage()->getCode());
        $jobKey = self::JOB_PREFIX . $pairHash . '_' . substr(md5($jobId), 0, 8);

        $this->storeJob($jobKey, [
            'job_id'          => $jobId,
            'pair_src'        => $pair->getSourceLanguage()->getCode(),
            'pair_tgt'        => $pair->getTargetLanguage()->getCode(),
            'index_to_row_id' => $projection->getIndexToRowId(),
            'submitted_at'    => time(),
            'keys_submitted'  => count($projection->getBatch()->getDataToTranslate()),
        ]);

                           
        $registry   = $this->getJobRegistry();
        $registry[] = $jobKey;
        $this->setJobRegistry($registry);

        return [
            'pair'          => $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode(),
            'pending_found' => $projection->getPendingFound(),
            'batched'       => $projection->getItemCount(),
            'submitted'     => true,
            'job_id'        => $jobId,
            'error'         => '',
        ];
    }

                                                                             
                                  
                                                                             

       
                                       
       
    private function storeJob(string $jobKey, array $data): void
    {
        $json = wp_json_encode($data);
        if (is_string($json)) {
            set_transient($jobKey, $json, self::JOB_TTL);
        }
    }

       
                                       
       
    private function loadJob(string $jobKey): ?array
    {
        $raw = get_transient($jobKey);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function deleteJob(string $jobKey): void
    {
        delete_transient($jobKey);
    }

       
                           
       
    private function getJobRegistry(): array
    {
        $raw = get_transient(self::JOB_REGISTRY_KEY);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? array_values($data) : [];
    }

       
                                
       
    private function setJobRegistry(array $keys): void
    {
        $keys = array_values(array_unique($keys));
        $json = wp_json_encode($keys);
        if (is_string($json)) {
            set_transient(self::JOB_REGISTRY_KEY, $json, self::JOB_TTL);
        }
    }

                                                                             
                                                   
                                                                             

    private function getMaxBatchChars(): int
    {
        if ($this->approachSettings !== null) {
            $v = $this->approachSettings->getMaxBatchChars();
            if ($v > 0) {
                return $v;
            }
        }
        return self::DEFAULT_MAX_BATCH_CHARS;
    }

    private function getMaxBatchItems(): int
    {
        if ($this->approachSettings !== null) {
            $v = $this->approachSettings->getMaxBatchItems();
            if ($v > 0) {
                return $v;
            }
        }
        return self::DEFAULT_MAX_BATCH_ITEMS;
    }

}

   
                                                                              
  
                                                                           
                                                                            
                                                          
  
                              
   
function dolet_project_translation_batch(
    string $sourceCode,
    string $targetCode,
    int $fetchLimit,
    int $maxItems,
    int $maxCoreCharacters
): array {
    $schema = 'dolet.batch_projection.v1';
    if ($fetchLimit < 1 || $maxItems < 1 || $maxCoreCharacters < 1) {
        return ['schema' => $schema, 'error' => 'invalid_limits'];
    }

    $aliases = new Language_AliasesDictionary();
    $factory = new Language_OptionFactory($aliases);
    $languageAdmin = new Config_LanguageAdmin($aliases, $factory);
    $configuredSource = $languageAdmin->getSourceCode();
    $configuredTargets = $languageAdmin->getTargetCodes();
    if ($sourceCode !== $configuredSource || !in_array($targetCode, $configuredTargets, true)) {
        return ['schema' => $schema, 'error' => 'pair_not_configured'];
    }

    $pair = Routines_LanguagePairResolver::build(
        $sourceCode,
        $targetCode,
        $languageAdmin,
        $factory
    );
    if ($pair === null) {
        return ['schema' => $schema, 'error' => 'invalid_pair'];
    }

    $projection = (new Translation_BatchProjector(
        new Data_TranslationMemoryRepository()
    ))->project($pair, $fetchLimit, $maxItems, $maxCoreCharacters);
    $wire = (new Approach_TranslationWireProjector())->project($projection->getBatch());
    if ($wire === null) {
        return ['schema' => $schema, 'error' => 'wire_projection_failed'];
    }

    $requestWithoutCustody = [
        'task' => 'translate_batch',
        'payload' => $wire->getPayloadJson(),
        'options' => $wire->getOptions(),
        'requester' => 'dolet-wp',
    ];
                                                                           
    $requestWithoutCustodyJson = wp_json_encode($requestWithoutCustody);
    if (!is_string($requestWithoutCustodyJson)) {
        return ['schema' => $schema, 'error' => 'request_projection_failed'];
    }

    $commitmentMaterial = [
        'schema' => $schema,
        'pair' => [
            'source' => $pair->getSourceLanguage()->getCode(),
            'target' => $pair->getTargetLanguage()->getCode(),
        ],
        'limits' => [
            'fetch' => $fetchLimit,
            'items' => $maxItems,
            'core_characters' => $maxCoreCharacters,
        ],
        'selected_rows' => $projection->getSelectedRowCommitments(),
        'index_to_row_id' => $projection->getIndexToRowId(),
        'pending_found' => $projection->getPendingFound(),
        'item_count' => $projection->getItemCount(),
        'core_character_count' => $projection->getCoreCharacterCount(),
        'payload_json' => $wire->getPayloadJson(),
        'options_json' => $wire->getOptionsJson(),
        'request_without_custody_json' => $requestWithoutCustodyJson,
    ];
    $commitmentJson = wp_json_encode(
        $commitmentMaterial,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if (!is_string($commitmentJson)) {
        return ['schema' => $schema, 'error' => 'commitment_failed'];
    }

    return [
        'schema' => $schema,
        'error' => '',
        'pair' => $commitmentMaterial['pair'],
        'limits' => $commitmentMaterial['limits'],
        'pending_found' => $projection->getPendingFound(),
        'item_count' => $projection->getItemCount(),
        'core_character_count' => $projection->getCoreCharacterCount(),
        'selected_rows' => $projection->getSelectedRowCommitments(),
        'index_to_row_id' => $projection->getIndexToRowId(),
        'payload' => $projection->getBatch()->getDataToTranslate(),
        'payload_json' => $wire->getPayloadJson(),
        'payload_bytes' => $wire->getPayloadBytes(),
        'options' => $wire->getOptions(),
        'options_json' => $wire->getOptionsJson(),
        'options_bytes' => $wire->getOptionsBytes(),
        'request_without_custody_json' => $requestWithoutCustodyJson,
        'request_without_custody_bytes' => strlen($requestWithoutCustodyJson),
        'custody_bytes_unmeasured' => true,
        'outer_envelope_ceiling_evaluated' => false,
        'owner_commitment_sha256' => hash('sha256', $commitmentJson),
    ];
}
