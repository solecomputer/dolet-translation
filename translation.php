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
        int $neighborLimit = 0,
        ?Translation_PendingRegistrationPolicyInterface $registrationPolicy = null
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
        int $neighborLimit = 0,
        ?Translation_PendingRegistrationPolicyInterface $registrationPolicy = null
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

        if ($exact === null && $registrationPolicy !== null && !$registrationPolicy->claim()) {
            return null;
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
        private ?Translation_PendingRegistrationPolicyInterface $registrationPolicy = null,
    ) {
    }

       
                                                                               
                                                                          
                                     
       
    private static bool $ranThisRequest = false;

       
                                                                                               
       
    private ?Translation_TmLookupServiceInterface $tmLookup = null;
    private bool $completeLastPass = true;
    private bool $structureLostLastPass = false;
    private bool $translatedLastPass = false;
    private bool $deferTypedRejection = false;
    private ?Core_HtmlRejectionInterface $rejectionLastPass = null;

                                                                             
                                                            
                                                                             

       
                                                                       
                                                             
      
                 
                                                                               
       
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
        $lookupFailureCount = 0;
        $firstLookupFailure = '';

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
                $translated = $tm->findBestTranslationOrNull(
                    $this->memory,
                    $pair,
                    $prev,
                    $core,
                    $next,
                    30,
                    $this->registrationPolicy
                );
            } catch (\Throwable $e) {
                                                                  
                $lookupFailureCount++;
                if ($firstLookupFailure === '') {
                    $firstLookupFailure = $e->getMessage();
                }
            }

            $hit  = (is_string($translated) && $translated !== '');
            if (!$hit) {
                $this->completeLastPass = false;
            }
            $text = $hit ? (string)$translated : $core;

            if ($debug) {
                $mark    = $hit ? '✓ ' : '✗ ';
                $out[$k] = $mark . '➡️' . $text . '⬅️';
            } else {
                $out[$k] = $text;
            }
        }

        if ($lookupFailureCount > 0) {
            $failureSummary = 'Translation-memory lookup failed: ' . $firstLookupFailure;
            (new Config_DiagnosticsStore())->recordError('tm_lookup_failed', $failureSummary);
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] TM lookup error: ' . $failureSummary);
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
        $this->resetPassState();
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

                                                                             
                                                                               
            $loss = $dom->findStructureLossResult($html);
            if ($loss !== null) {
                return $this->guardedHtml($html, $dom, $loss);
            }

                                                                           
                                                                            
            $documentTitleCores = $dom->getDocumentTitleTranslatableStrings();
            $cores = $dom->getOrderedTranslatableStrings();

            $ok = $dom->applyLanguageParameterToLinks($this->languagePair->getTargetLanguage());
            if (!$ok) {
                $this->completeLastPass = false;
                return $html;
            }
            $loss = $dom->findStructureLossResult($dom->getHtml());
            if ($loss !== null) {
                return $this->guardedHtml($html, $dom, $loss);
            }

            if ($cores === null) {
                return $this->guardedHtml($html, $dom);
            }

            if (empty($cores->asArray())) {
                if ($documentTitleCores === null || empty($documentTitleCores->asArray())) {
                    return $this->guardedHtml($html, $dom);
                }
            } else {
                $translated = $this->translateContextual($cores);

                $beforeSubstitution = $dom->getHtml();
                $ok = $dom->substituteOrderedTranslatableStrings($translated);
                if ($dom->getHtml() !== $beforeSubstitution) {
                    $this->translatedLastPass = true;
                }
                if (!$ok) {
                    $hashes = $dom->getRejectedInlinePhraseHashes();
                    if ($hashes === []) {
                        $this->completeLastPass = false;
                    } else {
                        (new Config_DiagnosticsStore())->recordError(
                            'inline_phrase_translation_rejected',
                            \count($hashes) . ' translated inline phrase(s) failed the source-shape check; first source hash: ' . $hashes[0]
                        );
                    }
                }
            }

            if ($documentTitleCores !== null && !empty($documentTitleCores->asArray())) {
                $translatedTitle = $this->translateContextual($documentTitleCores);
                $beforeTitleSubstitution = $dom->getHtml();
                $ok = $dom->substituteDocumentTitleTranslatableStrings($translatedTitle);
                if ($dom->getHtml() !== $beforeTitleSubstitution) {
                    $this->translatedLastPass = true;
                }
                if (!$ok) {
                    $this->completeLastPass = false;
                    return $this->guardedHtml($html, $dom);
                }
            }

            return $this->guardedHtml($html, $dom);
        } catch (\Throwable $e) {
            $this->completeLastPass = false;
            (new Config_DiagnosticsStore())->recordError('translate_html_failed', $e->getMessage());
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] translateHtml error: ' . $e->getMessage());
            }
            return $html;
        }
    }

                                                                             
                                       
                                                                             

       
                                     
       
    public function translateFinalHtml(string $html, bool $deferTypedRejection = false): string
    {
        $this->deferTypedRejection = $deferTypedRejection;
        try {
            return $this->translateFinalHtmlPass($html);
        } finally {
            $this->deferTypedRejection = false;
        }
    }

    private function translateFinalHtmlPass(string $html): string
    {
        $this->resetPassState();
        $targetLanguage = $this->languagePair->getTargetLanguage();
        $debug          = $this->debugSettings->isEnabled();

        if ($debug) {
            if (self::$ranThisRequest) {
                return $html;
            }

            self::$ranThisRequest = true;

            $translated = $this->translateHtml($html);
            if ($this->structureLostLastPass || !$this->translatedLastPass) {
                return $translated;
            }
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
        if ($this->structureLostLastPass || !$this->translatedLastPass) {
            return $translated;
        }
        return $this->stamper->stamp($translated, $targetLanguage);
    }

    public function didTranslateLastPass(): bool
    {
        return $this->translatedLastPass;
    }

    public function getRejectionLastPass(): ?Core_HtmlRejectionInterface
    {
        return $this->rejectionLastPass;
    }

    public function isCompleteLastPass(): bool
    {
        return $this->completeLastPass;
    }

    public function didLoseStructureLastPass(): bool
    {
        return $this->structureLostLastPass;
    }

    private function resetPassState(): void
    {
        $this->rejectionLastPass = null;
        $this->completeLastPass = true;
        $this->structureLostLastPass = false;
        $this->translatedLastPass = false;
    }

    private function guardedHtml(
        string $sourceHtml,
        Dom_TranslatableInterface $dom,
        string|Core_HtmlRejectionInterface|null $loss = null
    ): string {
        $serialized = $dom->getHtml();
        $loss ??= $dom->findStructureLossResult($serialized);
        if ($loss === null) {
            return $serialized;
        }

        $this->structureLostLastPass = true;
        $this->completeLastPass = false;
        $this->translatedLastPass = false;
        $reason = $loss instanceof Core_HtmlRejectionInterface ? $loss : null;
        $this->rejectionLastPass = $reason;
                                                                                                     
        if (!$this->deferTypedRejection || $reason === null) {
            (new Config_DiagnosticsStore())->recordError($reason?->code() ?? 'html_structure_loss', $reason?->context() ?? $loss);
        }
        return $sourceHtml;
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
        private ?Translation_BatchProjectorInterface $batchProjector = null,
        private ?\Closure $leaseHeartbeat = null
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
        $retainedIdentityFailures = 0;

        foreach ($registry as $jobKey) {
            $jobApplied = 0;
            $failedBeforeJob = $failed;
            try {
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

                $commitmentsByKey = $this->indexRowCommitments($jobData['row_commitments'] ?? []);
                $pairSource = (string) ($jobData['pair_src'] ?? '');
                $pairTarget = (string) ($jobData['pair_tgt'] ?? '');

                $this->heartbeatLease();
                $result = $this->engine->pollResult($jobId);
                $this->heartbeatLease();

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
                    $commitmentMismatch = $this->deferRows(
                        $jobData['index_to_row_id'] ?? [],
                        $error,
                        $pairSource,
                        $pairTarget,
                        $commitmentsByKey
                    );
                    if ($commitmentMismatch) {
                        $this->recordJobCommitmentMismatch($jobId);
                    }
                    $this->deleteJob((string)$jobKey);
                    continue;
                }

                                      
                $translatedMap  = $result->getTranslatedMap();
                if (empty($translatedMap)) {
                    $failed++;
                    $this->diagnostics?->recordError('poll_empty', 'Job ' . $jobId . ': successful poll returned empty translated map');
                    $commitmentMismatch = $this->deferRows(
                        $jobData['index_to_row_id'] ?? [],
                        'poll_empty',
                        $pairSource,
                        $pairTarget,
                        $commitmentsByKey
                    );
                    if ($commitmentMismatch) {
                        $this->recordJobCommitmentMismatch($jobId);
                    }
                    $this->deleteJob((string)$jobKey);
                    continue;
                }
                $indexToRowId   = $jobData['index_to_row_id'] ?? [];
                $omittedIndexToRowId = [];
                foreach ($indexToRowId as $index => $rowId) {
                    $translated = $translatedMap[$index] ?? null;
                    if (!is_string($translated) || $translated === '') {
                        $omittedIndexToRowId[$index] = $rowId;
                    }
                }
                $jobCommitmentMismatch = false;
                if ($omittedIndexToRowId !== []) {
                    $this->diagnostics?->recordError(
                        'poll_partial',
                        'Job ' . $jobId . ': the Engine omitted ' . count($omittedIndexToRowId) . ' submitted translation(s); retry was deferred.'
                    );
                    $jobCommitmentMismatch = $this->deferRows(
                        $omittedIndexToRowId,
                        'poll_partial',
                        $pairSource,
                        $pairTarget,
                        $commitmentsByKey
                    );
                }

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
                    if (!$row instanceof Data_TmRowInterface || !$this->rowMatchesJobCommitment(
                        $row,
                        $k,
                        $pairSource,
                        $pairTarget,
                        $commitmentsByKey[$k] ?? null
                    )) {
                        $jobCommitmentMismatch = true;
                        continue;
                    }
                    if ($row->isHumanLocked()) {
                        continue;
                    }

                    try {
                        $this->repo->updateTranslation($rowId, $translated, false, null);
                    } catch (Data_HumanLockedException) {
                        continue;
                    }
                    $this->repo->clearRetryState($rowId);
                    $jobApplied++;
                }

                $applied += $jobApplied;
                if ($jobCommitmentMismatch) {
                    $failed++;
                    $this->recordJobCommitmentMismatch($jobId);
                }
                if ($jobApplied > 0) {
                    $this->diagnostics?->recordSuccess();
                }
                $this->deleteJob((string)$jobKey);
            } catch (Data_StoredIdentityMismatchExceptionInterface) {
                                                                                             
                $remaining[] = $jobKey;
                $applied += $jobApplied;
                if ($failed === $failedBeforeJob) {
                    $failed++;
                }
                $retainedIdentityFailures++;
            }
        }

        if ($retainedIdentityFailures > 0) {
            $this->diagnostics?->recordError('worker_exception', $retainedIdentityFailures . ' accepted job(s) retained after identity failure.');
        }
        if (!$this->setJobRegistry($remaining)) {
            $this->diagnostics?->recordError('job_registry_write_failed', 'Could not persist the polled job registry.');
        }

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

        $pairSource = $pair->getSourceLanguage()->getCode();
        $pairTarget = $pair->getTargetLanguage()->getCode();
        $rowCommitments = $this->indexRowCommitments($projection->getSelectedRowCommitments());

        $this->heartbeatLease();
        $result = $this->engine->submitTranslateBatch($projection->getBatch());

        if (!$result->wasAccepted()) {
            $this->heartbeatLease();
            $msg = $result->getErrorOrNull() ?? 'Submit failed.';

            if ($this->debugSettings->isEnabled() && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Dolet WP] Worker submit failure: ' . $msg);
            }
            $errorCode = in_array($msg, ['config_missing', 'user_key_invalid'], true) ? $msg : 'submit_failed';
            $this->diagnostics?->recordError($errorCode, $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode() . ': ' . $msg);
            $this->deferRows($projection->getIndexToRowId(), $msg, $pairSource, $pairTarget, $rowCommitments);

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

        if (!$this->storeJob($jobKey, [
            'job_id'          => $jobId,
            'pair_src'        => $pair->getSourceLanguage()->getCode(),
            'pair_tgt'        => $pair->getTargetLanguage()->getCode(),
            'index_to_row_id' => $projection->getIndexToRowId(),
            'row_commitments' => $projection->getSelectedRowCommitments(),
            'submitted_at'    => time(),
            'keys_submitted'  => count($projection->getBatch()->getDataToTranslate()),
        ])) {
            $this->deferRows(
                $projection->getIndexToRowId(),
                'job_storage_failed',
                $pairSource,
                $pairTarget,
                $rowCommitments
            );
            $this->diagnostics?->recordError('job_storage_failed', 'Could not persist accepted job ' . $jobId);
            return [
                'pair' => $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode(),
                'pending_found' => $projection->getPendingFound(),
                'batched' => $projection->getItemCount(),
                'submitted' => false,
                'error' => 'job_storage_failed',
            ];
        }

                           
        $registry   = $this->getJobRegistry();
        $registry[] = $jobKey;
        if (!$this->setJobRegistry($registry)) {
            $this->deleteJob($jobKey);
            $this->deferRows(
                $projection->getIndexToRowId(),
                'job_registry_write_failed',
                $pairSource,
                $pairTarget,
                $rowCommitments
            );
            $this->diagnostics?->recordError('job_registry_write_failed', 'Could not register accepted job ' . $jobId);
            return [
                'pair' => $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode(),
                'pending_found' => $projection->getPendingFound(),
                'batched' => $projection->getItemCount(),
                'submitted' => false,
                'error' => 'job_registry_write_failed',
            ];
        }

                                                                              
                                                                          
        $this->heartbeatLease();

        return [
            'pair'          => $pair->getSourceLanguage()->getCode() . '→' . $pair->getTargetLanguage()->getCode(),
            'pending_found' => $projection->getPendingFound(),
            'batched'       => $projection->getItemCount(),
            'submitted'     => true,
            'job_id'        => $jobId,
            'error'         => '',
        ];
    }

                                                                             
                                  
                                                                             

                                                      
    private function rowMatchesJobCommitment(
        Data_TmRowInterface $row,
        string $coreKey,
        string $sourceCode,
        string $targetCode,
        ?array $commitment
    ): bool {
        if ($commitment === null
            || !isset($commitment['row_id'], $commitment['core_key'], $commitment['source_sha256'], $commitment['commitment_sha256'])
            || (int) $commitment['row_id'] !== $row->getId()
            || (string) $commitment['core_key'] !== $coreKey
            || $sourceCode === ''
            || $targetCode === ''
            || $row->getSourceLanguageCode() !== $sourceCode
            || $row->getTargetLanguageCode() !== $targetCode) {
            return false;
        }

        $sourceHash = hash('sha256', $row->getSourceCore());
        if (!hash_equals((string) $commitment['source_sha256'], $sourceHash)) {
            return false;
        }

        $commitmentJson = wp_json_encode([
            'schema' => 'dolet.selected_row.v1',
            'pair' => ['source' => $sourceCode, 'target' => $targetCode],
            'row_id' => $row->getId(),
            'core_key' => $coreKey,
            'source_sha256' => $sourceHash,
        ], JSON_UNESCAPED_SLASHES);

        return is_string($commitmentJson)
            && hash_equals((string) $commitment['commitment_sha256'], hash('sha256', $commitmentJson));
    }

       
                                
                                                    
       
    private function indexRowCommitments(mixed $commitments): array
    {
        if (!is_array($commitments)) {
            return [];
        }

        $byKey = [];
        foreach ($commitments as $commitment) {
            if (!is_array($commitment) || !isset($commitment['core_key']) || !is_scalar($commitment['core_key'])) {
                continue;
            }
            $byKey[(string) $commitment['core_key']] = $commitment;
        }
        return $byKey;
    }

    private function recordJobCommitmentMismatch(string $jobId): void
    {
        $this->diagnostics?->recordError(
            'job_row_commitment_mismatch',
            'Job ' . $jobId . ': a submitted row identity no longer matches; no mismatched result was applied.'
        );
    }

    private function heartbeatLease(): void
    {
        if ($this->leaseHeartbeat !== null) {
            ($this->leaseHeartbeat)();
        }
    }

       
                                       
       
    private function storeJob(string $jobKey, array $data): bool
    {
        $json = wp_json_encode($data);
        if (is_string($json)) {
            return set_transient($jobKey, $json, self::JOB_TTL);
        }
        return false;
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

       
                                
       
    private function setJobRegistry(array $keys): bool
    {
        $keys = array_values(array_unique($keys));
        $json = wp_json_encode($keys);
        if (is_string($json)) {
            if (set_transient(self::JOB_REGISTRY_KEY, $json, self::JOB_TTL)) {
                return true;
            }
            return get_transient(self::JOB_REGISTRY_KEY) === $json;
        }
        return false;
    }

       
                                                   
                                                                     
                                                                             
       
    private function deferRows(
        array $indexToRowId,
        string $error,
        string $sourceCode,
        string $targetCode,
        array $commitmentsByKey
    ): bool
    {
        $seen = [];
        $deferredCount = 0;
        $highestFailureCount = 0;
        $commitmentMismatch = false;
        foreach ($indexToRowId as $coreKey => $rowId) {
            $rowId = (int)$rowId;
            if ($rowId <= 0 || isset($seen[$rowId])) {
                continue;
            }
            $seen[$rowId] = true;
            $coreKey = (string) $coreKey;
            $row = $this->repo->findById($rowId);
            if (!$row instanceof Data_TmRowInterface || !$this->rowMatchesJobCommitment(
                $row,
                $coreKey,
                $sourceCode,
                $targetCode,
                $commitmentsByKey[$coreKey] ?? null
            )) {
                $commitmentMismatch = true;
                continue;
            }
            $failures = $this->repo->recordRetryFailure($rowId, $error);
            if ($failures >= 3) {
                $deferredCount++;
                $highestFailureCount = max($highestFailureCount, $failures);
            }
        }
        if ($deferredCount > 0) {
            $this->diagnostics?->recordError(
                'translation_retry_deferred',
                $deferredCount . ' row(s) reached repeated failures (up to ' . $highestFailureCount . '); automatic retry is delayed.'
            );
        }
        return $commitmentMismatch;
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

    try {
        $projection = (new Translation_BatchProjector(
            new Data_TranslationMemoryRepository()
        ))->project($pair, $fetchLimit, $maxItems, $maxCoreCharacters);
    } catch (\Throwable) {
        return ['schema' => $schema, 'error' => 'projection_failed'];
    }
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
