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
        return Config_OptionValue::boolean($raw) && \current_user_can('manage_options');
    }

    public function setEnabled(bool $enabled): void
    {
        update_option(Config_DebugOptionNames::DEBUG_OPTION_NAME, $enabled);
    }
}

   
                                                                                       
   
final class Config_DiagnosticsStore implements Diagnostics_StoreInterface
{
    private const OPTION_ERROR_LOG     = Config_DiagnosticsOptionNames::ERROR_LOG;
    private const OPTION_LAST_ERROR    = Config_DiagnosticsOptionNames::LAST_ERROR;
    private const OPTION_LAST_ERROR_AT = Config_DiagnosticsOptionNames::LAST_ERROR_AT;
    private const OPTION_LAST_SUCCESS  = Config_DiagnosticsOptionNames::LAST_SUCCESS;
    private const OPTION_ROOT_INCIDENT = Config_DiagnosticsOptionNames::ROOT_INCIDENT;
    private const OPTION_MUTATION_LEASE = Config_DiagnosticsOptionNames::MUTATION_LEASE;
    private const MAX_LOG_ENTRIES      = 50;
    private const DUPLICATE_COOLDOWN_SECONDS = 300;
    private const SUMMARY_REFRESH_SECONDS = 60;
    private ?string $lastErrorOverride = null;
    private ?string $lastErrorAtOverride = null;

    public function __construct()
    {
    }

    public function recordError(string $code, string $context): void
    {
        global $wpdb;
        $lease = null;
        if ($wpdb instanceof \wpdb) {
            $lease = new Config_OptionLease(self::OPTION_MUTATION_LEASE, 5);
            if (!$lease->acquire()) {
                $this->recordSummaryOnly($code);
                return;
            }
        }

        try {
            $this->recordErrorWhileOwned($code, $context);
        } finally {
            $lease?->release();
        }
    }

    private function recordErrorWhileOwned(string $code, string $context): void
    {
        $log = $this->getErrorLog();
        $now = \time();
        $timestamp = \gmdate('c', $now);
        $root = $this->readEntry(self::OPTION_ROOT_INCIDENT);
        $previousAt = \get_option(self::OPTION_LAST_ERROR_AT, null);
        $previousEpoch = \strtotime(is_string($previousAt) ? $previousAt : '');
        if ($root === null
            || (is_int($previousEpoch) && ($now - $previousEpoch) >= self::DUPLICATE_COOLDOWN_SECONDS)) {
            $this->updateOption(self::OPTION_ROOT_INCIDENT, [
                'time' => $timestamp,
                'code' => $code,
                'context' => $context,
            ]);
        }
        foreach (array_reverse($log) as $entry) {
            if ($entry['code'] !== $code || $entry['context'] !== $context) {
                continue;
            }
            $recordedAt = \strtotime($entry['time']);
            if (is_int($recordedAt) && ($now - $recordedAt) < self::DUPLICATE_COOLDOWN_SECONDS) {
                $this->lastErrorOverride = $code;
                $this->lastErrorAtOverride = $timestamp;
                $persistedCode = \get_option(self::OPTION_LAST_ERROR, null);
                $persistedAt = \get_option(self::OPTION_LAST_ERROR_AT, null);
                $summaryAt = \strtotime(is_string($persistedAt) ? $persistedAt : '');
                if (!is_int($summaryAt) || ($now - $summaryAt) >= self::SUMMARY_REFRESH_SECONDS) {
                    if ($persistedCode !== $code) {
                        $this->updateOption(self::OPTION_LAST_ERROR, $code);
                    }
                    $this->updateOption(self::OPTION_LAST_ERROR_AT, $timestamp);
                }
                return;
            }
            break;
        }

        $log[] = [
            'time'    => $timestamp,
            'code'    => $code,
            'context' => $context,
        ];
        if (count($log) > self::MAX_LOG_ENTRIES) {
            $log = array_slice($log, -self::MAX_LOG_ENTRIES);
        }
        $this->updateOption(self::OPTION_ERROR_LOG, $log);
        $this->updateOption(self::OPTION_LAST_ERROR, $code);
        $this->updateOption(self::OPTION_LAST_ERROR_AT, $timestamp);
        $this->lastErrorOverride = null;
        $this->lastErrorAtOverride = null;
    }

    private function recordSummaryOnly(string $code): void
    {
        $timestamp = \gmdate('c');
        $this->lastErrorOverride = $code;
        $this->lastErrorAtOverride = $timestamp;
    }

    public function recordSuccess(): void
    {
        global $wpdb;
        $lease = null;
        if ($wpdb instanceof \wpdb) {
            $lease = new Config_OptionLease(self::OPTION_MUTATION_LEASE, 5);
            if (!$lease->acquire()) {
                return;
            }
        }
        try {
            $this->updateOption(self::OPTION_LAST_SUCCESS, \gmdate('c'));
        } finally {
            $lease?->release();
        }
    }

       
                                                                       
       
    public function getErrorLog(): array
    {
        $raw = \get_option(self::OPTION_ERROR_LOG, []);
        if (!is_array($raw)) {
            return [];
        }

        $log = [];
        foreach ($raw as $entry) {
            if (
                !is_array($entry)
                || !isset($entry['time'], $entry['code'], $entry['context'])
                || !is_string($entry['time'])
                || !is_string($entry['code'])
                || !is_string($entry['context'])
            ) {
                continue;
            }
            $log[] = [
                'time'    => $entry['time'],
                'code'    => $entry['code'],
                'context' => $entry['context'],
            ];
        }
        $root = $this->readEntry(self::OPTION_ROOT_INCIDENT);
        if ($root !== null) {
            $present = false;
            foreach ($log as $entry) {
                if ($entry === $root) {
                    $present = true;
                    break;
                }
            }
            if (!$present) {
                $log = [$root, ...$log];
            }
            if (count($log) > self::MAX_LOG_ENTRIES) {
                $log = [$root, ...array_slice($log, -(self::MAX_LOG_ENTRIES - 1))];
            }
        }
        return $log;
    }

    public function getLastError(): ?string
    {
        if ($this->lastErrorOverride !== null) {
            return $this->lastErrorOverride;
        }
        $raw = \get_option(self::OPTION_LAST_ERROR, null);
        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    public function getLastErrorAt(): ?string
    {
        if ($this->lastErrorAtOverride !== null) {
            return $this->lastErrorAtOverride;
        }
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
        global $wpdb;
        $lease = null;
        if ($wpdb instanceof \wpdb) {
            $lease = new Config_OptionLease(self::OPTION_MUTATION_LEASE, 5);
            if (!$lease->acquire()) {
                return;
            }
        }
        try {
            \delete_option(self::OPTION_ERROR_LOG);
            \delete_option(self::OPTION_LAST_ERROR);
            \delete_option(self::OPTION_LAST_ERROR_AT);
            \delete_option(self::OPTION_LAST_SUCCESS);
            \delete_option(self::OPTION_ROOT_INCIDENT);
            $this->lastErrorOverride = null;
            $this->lastErrorAtOverride = null;
        } finally {
            $lease?->release();
        }
    }

                                                                     
    private function readEntry(string $name): ?array
    {
        $entry = \get_option($name, null);
        if (!is_array($entry)
            || !isset($entry['time'], $entry['code'], $entry['context'])
            || !is_string($entry['time'])
            || !is_string($entry['code'])
            || !is_string($entry['context'])) {
            return null;
        }
        return [
            'time' => $entry['time'],
            'code' => $entry['code'],
            'context' => $entry['context'],
        ];
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

                                                                              
final class Config_OptionLease implements Config_OptionLeaseInterface
{
    private const MAX_FUTURE_SKEW_SECONDS = 30;

    private ?string $ownedValue = null;

    public function __construct(
        private string $optionName,
        private int $ttlSeconds,
        private ?Diagnostics_StoreInterface $diagnostics = null,
        private string $staleCode = 'stale_lease_recovered',
    ) {}

    public function acquire(): bool
    {
        $now = \time();
        $value = \wp_json_encode([
            'owner' => \bin2hex(\random_bytes(16)),
            'acquired_at' => $now,
        ]);
        if (!\is_string($value)) {
            return false;
        }

        global $wpdb;
        if (!($wpdb instanceof \wpdb)) {
            return false;
        }

        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO `{$wpdb->options}` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s)",
            $this->optionName,
            $value,
            'no'
        ));
        \wp_cache_delete($this->optionName, 'options');
        if ($inserted === 1) {
            $this->ownedValue = $value;
            return true;
        }

                                                                              
                                                                               
                                                                    
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT `option_value` FROM `{$wpdb->options}` WHERE `option_name` = %s LIMIT 1",
            $this->optionName
        ));
        $acquiredAt = self::acquiredAt($current);
        if ($acquiredAt !== null) {
            $nearFuture = $acquiredAt > $now
                && ($acquiredAt - $now) <= self::MAX_FUTURE_SKEW_SECONDS;
            $unexpired = $acquiredAt <= $now && ($now - $acquiredAt) < $this->ttlSeconds;
            if ($nearFuture || $unexpired) {
                return false;
            }
        }

        $changed = $wpdb->update(
            $wpdb->options,
            ['option_value' => $value],
            ['option_name' => $this->optionName, 'option_value' => $current],
            ['%s'],
            ['%s', '%s']
        );
        \wp_cache_delete($this->optionName, 'options');
        if ($changed !== 1) {
            return false;
        }

        $this->ownedValue = $value;
        $context = $acquiredAt === null || $acquiredAt > $now
            ? 'Recovered an invalid lease record.'
            : 'Recovered an expired lease after ' . ($now - $acquiredAt) . ' seconds.';
        $this->diagnostics?->recordError($this->staleCode, $context);
        return true;
    }

    public function release(): void
    {
        if ($this->ownedValue === null) {
            return;
        }

        global $wpdb;
        if ($wpdb instanceof \wpdb) {
            $wpdb->delete(
                $wpdb->options,
                ['option_name' => $this->optionName, 'option_value' => $this->ownedValue],
                ['%s', '%s']
            );
            \wp_cache_delete($this->optionName, 'options');
        } elseif (\get_option($this->optionName, null) === $this->ownedValue) {
            \delete_option($this->optionName);
        }
        $this->ownedValue = null;
    }

                          
    public function renew(): bool
    {
        if ($this->ownedValue === null) {
            return false;
        }

        $owned = \json_decode($this->ownedValue, true);
        $owner = \is_array($owned) ? ($owned['owner'] ?? null) : null;
        if (!\is_string($owner) || $owner === '') {
            return false;
        }
        $renewedValue = \wp_json_encode([
            'owner' => $owner,
            'acquired_at' => \time(),
                                                                            
                                                                              
            'renewal' => \bin2hex(\random_bytes(8)),
        ]);
        if (!\is_string($renewedValue)) {
            return false;
        }

        global $wpdb;
        if (!($wpdb instanceof \wpdb)) {
            return false;
        }
        $changed = $wpdb->update(
            $wpdb->options,
            ['option_value' => $renewedValue],
            ['option_name' => $this->optionName, 'option_value' => $this->ownedValue],
            ['%s'],
            ['%s', '%s']
        );
        \wp_cache_delete($this->optionName, 'options');
        if ($changed !== 1) {
            $this->ownedValue = null;
            return false;
        }

        $this->ownedValue = $renewedValue;
        return true;
    }

    private static function acquiredAt(mixed $raw): ?int
    {
                                                                             
                                                                       
        if (\is_int($raw) || (\is_string($raw) && \ctype_digit($raw))) {
            $timestamp = (int)$raw;
            return $timestamp > 0 ? $timestamp : null;
        }
        if (!\is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = \json_decode($raw, true);
        if (!\is_array($decoded)) {
            return null;
        }
        $timestamp = $decoded['acquired_at'] ?? null;
        return \is_int($timestamp) && $timestamp > 0 ? $timestamp : null;
    }
}

interface Config_DailyAdmissionCounterInterface
{
    public function claim(string $utcDay, int $limit): bool;
}

final class Config_OptionValue implements Config_OptionValueInterface
{
    public static function boolean(mixed $value): bool
    {
        return \in_array($value, [true, 1, '1'], true);
    }
}

                                                                              
final class Config_WpDailyAdmissionCounter implements Config_DailyAdmissionCounterInterface
{
    public function claim(string $utcDay, int $limit): bool
    {
        global $wpdb;
        $name = Config_PendingRegistrationOptionNames::DAILY_BUDGET;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $raw = \get_option($name, null);
            if (!is_string($raw)) {
                if (!\add_option($name, $utcDay . ':0', '', false)) {
                    \wp_cache_delete($name, 'options');
                    continue;
                }
                $raw = $utcDay . ':0';
            }

            $count = 0;
            if (\preg_match('/^([0-9]{8}):([0-9]+)$/D', $raw, $matches) === 1
                && $matches[1] === $utcDay) {
                $count = (int)$matches[2];
            }
            if ($count >= $limit) {
                return false;
            }

            $next = $utcDay . ':' . ($count + 1);
            $changed = $wpdb->update(
                $wpdb->options,
                ['option_value' => $next],
                ['option_name' => $name, 'option_value' => $raw],
                ['%s'],
                ['%s', '%s']
            );
            \wp_cache_delete($name, 'options');
            if ($changed === 1) {
                return true;
            }
        }
        return false;
    }
}

                                                                                
final class Config_PendingRegistrationBudget implements Translation_PendingRegistrationPolicyInterface
{
    public const PER_REQUEST_LIMIT = 100;
    public const PER_DAY_LIMIT = 1000;

    private int $claimedThisRequest = 0;
    private bool $reported = false;

    public function __construct(
        private ?Config_DailyAdmissionCounterInterface $dailyCounter = null,
        private ?Diagnostics_StoreInterface $diagnostics = null,
    ) {
        $this->dailyCounter ??= new Config_WpDailyAdmissionCounter();
        $this->diagnostics ??= new Config_DiagnosticsStore();
    }

    public function claim(): bool
    {
        if ($this->claimedThisRequest >= self::PER_REQUEST_LIMIT) {
            $this->reportOnce('pending_registration_request_limit', 'New text registration stopped at 100 rows for this request.');
            return false;
        }
        if (!$this->dailyCounter->claim(\gmdate('Ymd'), self::PER_DAY_LIMIT)) {
            $this->reportOnce('pending_registration_daily_limit', 'New text registration stopped at the 1000-row UTC daily ceiling or a safe counter refusal.');
            return false;
        }
        $this->claimedThisRequest++;
        return true;
    }

    private function reportOnce(string $code, string $context): void
    {
        if ($this->reported) {
            return;
        }
        $this->reported = true;
        if ($this->diagnostics->getLastError() !== $code) {
            $this->diagnostics->recordError($code, $context);
        }
    }
}

   
                                                                
   
final class Config_LanguageAdmin implements Config_LanguageAdminInterface
{
    public function __construct(
        private Language_AliasesDictionaryInterface $aliases,
        private Language_OptionFactoryInterface $optionFactory
    ) {}

    public function sanitizeCodeInput(mixed $value): ?string
    {
        return $this->sanitizeCodeInputs([$value])[0] ?? null;
    }

    public function sanitizeCodeInputs(array $values): array
    {
                                                                               
        $legacy = \array_fill_keys($this->getCustomCodes(), true);
        $legacy[$this->getSourceCode()] = true;
        $out = [];
        foreach ($values as $value) {
            $code = $this->normalizeCodeLoose($value);
            if ($code === null) {
                continue;
            }
            if (isset($legacy[$code]) || (\sanitize_text_field($code) === $code
                && \preg_match('/\A[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*\z/D', $code) === 1)) {
                $out[] = $code;
            }
        }
        return $out;
    }

    public function getSourceCode(): string
    {
        $v = get_option(Language_AdminOptionNames::SOURCE_CODE, 'original');
        $v = (\is_string($v) || \is_int($v)) ? \trim((string)$v) : 'original';
        return $v === '' ? 'original' : $this->normalizeCodeOrOriginal($v);
    }

    public function setSourceCode(string $code): void
    {
        update_option(Language_AdminOptionNames::SOURCE_CODE, $this->normalizeCodeOrOriginal($this->sanitizeCodeInput($code) ?? 'original'));
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
            $n = $this->normalizeCodeLoose($c);
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
        $codes = \array_map('strval', \array_keys($out));
        sort($codes, SORT_STRING);
        return $codes;
    }

                                     
    public function setTargetCodes(array $codes): void
    {
        $src     = $this->getSourceCode();
        $catalog = $this->getSelectableCatalog();
        $customs = $this->getCustomCodes();

        $out = [];
        foreach ($this->sanitizeCodeInputs($codes) as $n) {
            if ($n === 'original' || $n === $src) {
                continue;
            }
            if (!array_key_exists($n, $catalog) && !in_array($n, $customs, true)) {
                continue;
            }
            $out[$n] = true;
        }
        $codes = \array_map('strval', \array_keys($out));
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
            $n = $this->normalizeCodeLoose($c);
            if ($n !== null) {
                $out[$n] = true;
            }
        }
        $codes = \array_map('strval', \array_keys($out));
        sort($codes, SORT_STRING);
        return $codes;
    }

                                     
    public function setCustomCodes(array $codes): void
    {
        $out = [];
        foreach ($this->sanitizeCodeInputs($codes) as $n) {
            $out[$n] = true;
        }
        $codes = \array_map('strval', \array_keys($out));
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
                $name = isset($row['name']) && \is_string($row['name']) ? \trim($row['name']) : '';
                $note = isset($row['note']) && \is_string($row['note']) ? \trim($row['note']) : '';
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
            if (!\is_array($data)) {
                continue;
            }
            $norm = $this->normalizeCodeOrOriginalLoose((string) $code);                                             
            if ($norm === null) {
                continue;
            }

            $name = isset($data['name']) && \is_string($data['name']) ? \trim($data['name']) : '';
            $note = isset($data['note']) && \is_string($data['note']) ? \trim($data['note']) : '';

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

                                                         
        $reg = ['original' => 'Original'] + $reg;
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

       
                                
                                          
                                               
                                                                      
       
    private function normalizeCodeLoose(mixed $code): ?string
    {
                                                                                
        if (!\is_string($code) && !\is_int($code)) {
            return null;
        }
        $code = (string) $code;
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
                                                                             
                                                                            
        return Config_OptionValue::boolean(\get_option(Config_ApproachOptionNames::APPROACH_ENABLED, false));
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
