<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                            
  
                    
                                                                                   
                                                                               
                                                           
                                                                                       
  
                                                                         
                                                                                
   

require_once __DIR__ . '/contracts.php';


                            

             
             

   
                                                                          
  
                                                                      
                                                                               
  
                                        
   
interface Data_UniversalTableL1Interface
{
                                                    
    public function getTableName(): string;

                                                                                   
    public function ensureSchema(): void;

       
                                       
      
                                                                         
                                                                 
      
                                                                      
                                                              
      
                                       
       
    public function insert(array $data): int;

       
                                                    
      
                                                                         
                                                                         
                                                     
      
                                       
       
    public function updateById(int $id, array $data): bool;

       
                                          
      
                                                                           
       
    public function deleteById(int $id): bool;

       
                                                                 
      
                                                                     
                                                                          
      
                                             
                                       
       
    public function selectOneByConditions(array $conditions): ?array;

       
                                                                  
      
                                                                  
      
                                             
                                        
       
    public function selectManyByConditions(array $conditions, int $limit = 0): array;

       
                                                                                     
      
                                                                                
      
                                          
      
                                                   
       
    public function deleteByConditionsAndLessThan(
        array $equalsConditions,
        string $lessThanColumn,
        int $cutoffEpoch
    ): int;

       
                                        
      
                                                                                
                                                                            
                                                                  
                                                            
                                                                     
      
                                                
       
    public function runHygiene(int $nowEpoch, int $maxLifetimeSeconds): int;
}


         
         
         


        
         
         


          
          

   
                               
   
class Data_Exception extends \RuntimeException
{
}

final class Data_DbException extends Data_Exception
{
}

final class Data_HumanLockedException extends Data_Exception
{
}

final class Data_InvalidArgumentException extends Data_Exception
{
}

final class Data_RowCorruptException extends Data_Exception
{
}

   
                                                                            
  
                                                
                                                                         
                                                                    
                                                                          
  
                                                                          
                                                                   
   
final class Data_UniversalTableL1 implements Data_UniversalTableL1Interface
{
    private string $tableName;

                                    
    private array $columnFormats;

                                  
    private array $allowedColumns;

                                  
    private array $boolColumns;

                                  
    private array $intColumns;

                                  
    private array $floatColumns;

                                  
    private array $binaryColumns;

                                  
    private array $timeColumns;

    private \wpdb $wpdb;


    public function __construct(?\wpdb $wpdb = null, ?string $tableName = null)
    {
        if ($wpdb instanceof \wpdb) {
            $this->wpdb = $wpdb;
        } elseif (isset($GLOBALS['wpdb']) && $GLOBALS['wpdb'] instanceof \wpdb) {
            $this->wpdb = $GLOBALS['wpdb'];
        } else {
            throw new Data_DbException('wpdb is not available.');
        }

                                                                 
        $this->tableName = $tableName ?? ($this->wpdb->prefix . Data_UniversalTableL1_Constants::TABLE_SLUG);

        $this->bootstrapColumns();
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function ensureSchema(): void
    {
                                                                        
        $table = $this->tableName;

        $sql = "
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scheme_id` VARCHAR(64) NOT NULL,

  `unique_hash_1` VARBINARY(64),
  `unique_hash_2` VARBINARY(64),
  `unique_hash_3` VARBINARY(64),
  `unique_hash_4` VARBINARY(64),
  `unique_hash_5` VARBINARY(64),
  `unique_hash_6` VARBINARY(64),
  `unique_hash_7` VARBINARY(64),

  `hash_1` VARBINARY(64),
  `hash_2` VARBINARY(64),
  `hash_3` VARBINARY(64),
  `hash_4` VARBINARY(64),
  `hash_5` VARBINARY(64),
  `hash_6` VARBINARY(64),
  `hash_7` VARBINARY(64),

  `bool_1` TINYINT(1) UNSIGNED,
  `bool_2` TINYINT(1) UNSIGNED,
  `bool_3` TINYINT(1) UNSIGNED,
  `bool_4` TINYINT(1) UNSIGNED,
  `bool_5` TINYINT(1) UNSIGNED,
  `bool_6` TINYINT(1) UNSIGNED,
  `bool_7` TINYINT(1) UNSIGNED,

  `int_1` BIGINT,
  `int_2` BIGINT,
  `int_3` BIGINT,
  `int_4` BIGINT,
  `int_5` BIGINT,
  `int_6` BIGINT,
  `int_7` BIGINT,

  `float_1` DOUBLE,
  `float_2` DOUBLE,
  `float_3` DOUBLE,
  `float_4` DOUBLE,
  `float_5` DOUBLE,
  `float_6` DOUBLE,
  `float_7` DOUBLE,

  `short_string_1` VARCHAR(191),
  `short_string_2` VARCHAR(191),
  `short_string_3` VARCHAR(191),
  `short_string_4` VARCHAR(191),
  `short_string_5` VARCHAR(191),
  `short_string_6` VARCHAR(191),
  `short_string_7` VARCHAR(191),

  `long_string_1` LONGTEXT,
  `long_string_2` LONGTEXT,
  `long_string_3` LONGTEXT,
  `long_string_4` LONGTEXT,
  `long_string_5` LONGTEXT,
  `long_string_6` LONGTEXT,
  `long_string_7` LONGTEXT,

  `timestamp_1` BIGINT UNSIGNED,
  `timestamp_2` BIGINT UNSIGNED,
  `timestamp_3` BIGINT UNSIGNED,
  `timestamp_4` BIGINT UNSIGNED,
  `timestamp_5` BIGINT UNSIGNED,
  `timestamp_6` BIGINT UNSIGNED,
  `timestamp_7` BIGINT UNSIGNED,

  `creation_date` BIGINT UNSIGNED,
  `last_modification_date` BIGINT UNSIGNED,
  `last_review_date` BIGINT UNSIGNED,
  `expiration_date` BIGINT UNSIGNED,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uidx_scheme_unique_hash_1` (`scheme_id`,`unique_hash_1`),
  KEY `idx_scheme_id` (`scheme_id`),
  KEY `idx_expiration_date` (`expiration_date`),
  KEY `idx_neighbor_xy` (`scheme_id`,`short_string_1`,`short_string_2`,`hash_1`,`hash_2`),
  KEY `idx_neighbor_yz` (`scheme_id`,`short_string_1`,`short_string_2`,`hash_2`,`hash_3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
";


        $ok = $this->wpdb->query($sql);
        if ($ok === false) {

            throw new Data_DbException('ensureSchema failed: ' . (string) $this->wpdb->last_error);
        }
    }

    public function insert(array $data): int
    {
        $now = \time();

        if (!array_key_exists(Data_UniversalTableL1_Constants::COL_CREATION_DATE, $data)) {
            $data[Data_UniversalTableL1_Constants::COL_CREATION_DATE] = $now;
        }
        if (!array_key_exists(Data_UniversalTableL1_Constants::COL_LAST_MODIFICATION_DATE, $data)) {
            $data[Data_UniversalTableL1_Constants::COL_LAST_MODIFICATION_DATE] = $now;
        }

        $filtered = $this->filterAndNormalizeData($data);
        if ($filtered === []) {
            throw new Data_InvalidArgumentException('insert() received no supported columns.');
        }

        $columns      = array_keys($filtered);
        $placeholders = [];
        $values       = [];

        foreach ($columns as $column) {
            $value = $filtered[$column];
            if ($value === null) {
                $placeholders[] = 'NULL';
                continue;
            }

            $format = $this->columnFormats[$column] ?? '%s';
            $placeholders[] = $format;
            $values[] = $value;
        }

        $sql = 'INSERT INTO `' . $this->tableName . '` (`' .
            implode('`,`', $columns) .
            '`) VALUES (' . implode(',', $placeholders) . ')';

        $prepared = ($values !== [])
            ? $this->wpdb->prepare($sql, $values)
            : $sql;


        $ok = $this->wpdb->query($prepared);
        if ($ok === false) {
            $err = (string) $this->wpdb->last_error;

                                                                                                                                          
            if ($err !== '' && \stripos($err, 'Duplicate entry') !== false) {
                return 0;
            }


            throw new Data_DbException('insert failed: ' . $err);
        }

        return (int) $this->wpdb->insert_id;
    }

    public function updateById(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new Data_InvalidArgumentException('updateById() invalid id.');
        }

        $data[Data_UniversalTableL1_Constants::COL_LAST_MODIFICATION_DATE] = \time();

        $filtered = $this->filterAndNormalizeData($data);
        unset($filtered[Data_UniversalTableL1_Constants::COL_ID]);

        if ($filtered === []) {
            throw new Data_InvalidArgumentException('updateById() received no supported columns.');
        }

        $sets   = [];
        $values = [];

        foreach ($filtered as $column => $value) {
            if ($value === null) {
                $sets[] = '`' . $column . '` = NULL';
                continue;
            }
            $format   = $this->columnFormats[$column] ?? '%s';
            $sets[]   = '`' . $column . '` = ' . $format;
            $values[] = $value;
        }

        $values[] = $id;

        $sql = 'UPDATE `' . $this->tableName . '` SET ' . implode(', ', $sets) .
               ' WHERE `' . Data_UniversalTableL1_Constants::COL_ID . '` = %d';


        $prepared = $this->wpdb->prepare($sql, $values);

        $rows = $this->wpdb->query($prepared);

        if ($rows === false) {

            throw new Data_DbException('updateById failed: ' . (string) $this->wpdb->last_error);
        }

                                                                                                    
        return true;
    }

    public function deleteById(int $id): bool
    {
        if ($id <= 0) {
            throw new Data_InvalidArgumentException('deleteById() invalid id.');
        }

        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE `' .
            Data_UniversalTableL1_Constants::COL_ID . '` = %d';


        $prepared = $this->wpdb->prepare($sql, [$id]);

        $rows = $this->wpdb->query($prepared);

        if ($rows === false) {

            throw new Data_DbException('deleteById failed: ' . (string) $this->wpdb->last_error);
        }

                                                                                
        return true;
    }

    public function selectOneByConditions(array $conditions): ?array
    {
        $whereClauses = [];
        $values       = [];

        foreach ($conditions as $column => $value) {
            if (!isset($this->allowedColumns[$column])) {
                continue;
            }

            if ($value === null) {
                $whereClauses[] = '`' . $column . '` IS NULL';
                continue;
            }

            $format = $this->columnFormats[$column] ?? '%s';
            $whereClauses[] = '`' . $column . '` = ' . $format;
            $values[] = $this->normalizeSingleValue($column, $value);
        }

        if ($whereClauses === []) {
            throw new Data_InvalidArgumentException('selectOneByConditions() received no valid conditions.');
        }

        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE ' .
               implode(' AND ', $whereClauses) . ' LIMIT 1';

        $prepared = ($values !== [])
            ? $this->wpdb->prepare($sql, $values)
            : $sql;


        $row = $this->wpdb->get_row($prepared, ARRAY_A);

        if ($row === null) {
            if ($this->wpdb->last_error !== '') {

                throw new Data_DbException('selectOneByConditions failed: ' . (string) $this->wpdb->last_error);
            }
            return null;
        }

        if (!is_array($row)) {
            return null;
        }

                                            
        return $row;
    }

    public function selectManyByConditions(array $conditions, int $limit = 0): array
    {
        $whereClauses = [];
        $values       = [];

        foreach ($conditions as $column => $value) {
            if (!isset($this->allowedColumns[$column])) {
                continue;
            }

            if ($value === null) {
                $whereClauses[] = '`' . $column . '` IS NULL';
                continue;
            }

            $format = $this->columnFormats[$column] ?? '%s';
            $whereClauses[] = '`' . $column . '` = ' . $format;
            $values[] = $this->normalizeSingleValue($column, $value);
        }

        if ($whereClauses === []) {
            throw new Data_InvalidArgumentException('selectManyByConditions() received no valid conditions.');
        }

        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE ' .
               implode(' AND ', $whereClauses) .
               ' ORDER BY `' . Data_UniversalTableL1_Constants::COL_ID . '` ASC';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $prepared = ($values !== [])
            ? $this->wpdb->prepare($sql, $values)
            : $sql;


        $rows = $this->wpdb->get_results($prepared, ARRAY_A);

        if ($rows === null && $this->wpdb->last_error !== '') {

            throw new Data_DbException('selectManyByConditions failed: ' . (string) $this->wpdb->last_error);
        }

        if (!is_array($rows)) {
            return [];
        }

                                                   
        return $rows;
    }

    public function deleteByConditionsAndLessThan(
        array $equalsConditions,
        string $lessThanColumn,
        int $cutoffEpoch
    ): int {
        if (!isset($this->allowedColumns[$lessThanColumn])) {
            throw new Data_InvalidArgumentException('deleteByConditionsAndLessThan(): invalid column.');
        }

        $whereClauses = [];
        $values       = [];

        foreach ($equalsConditions as $column => $value) {
            if (!isset($this->allowedColumns[$column])) {
                continue;
            }

            if ($value === null) {
                $whereClauses[] = '`' . $column . '` IS NULL';
                continue;
            }

            $format = $this->columnFormats[$column] ?? '%s';
            $whereClauses[] = '`' . $column . '` = ' . $format;
            $values[] = $this->normalizeSingleValue($column, $value);
        }

        if ($whereClauses === []) {
            throw new Data_InvalidArgumentException('deleteByConditionsAndLessThan(): received no valid equality conditions.');
        }

                                                                                        
        $whereClauses[] = '`' . $lessThanColumn . '` IS NOT NULL';
        $whereClauses[] = '`' . $lessThanColumn . '` < %d';
        $values[] = (int) $cutoffEpoch;

        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE ' . implode(' AND ', $whereClauses);


        $prepared = $this->wpdb->prepare($sql, $values);

        $rows = $this->wpdb->query($prepared);

        if ($rows === false) {

            throw new Data_DbException('deleteByConditionsAndLessThan failed: ' . (string) $this->wpdb->last_error);
        }

        return (int) $rows;
    }

    public function runHygiene(int $nowEpoch, int $maxLifetimeSeconds): int
    {
        $deleted = 0;
        $table   = $this->tableName;

        $horizonFuture = $nowEpoch + $maxLifetimeSeconds;
        $horizonPast   = $nowEpoch - $maxLifetimeSeconds;

                                                                  
        $sqlDeleteExpired = $this->wpdb->prepare(
            "DELETE FROM `{$table}` WHERE `expiration_date` IS NOT NULL AND `expiration_date` < %d",
            $nowEpoch
        );

        $rows = $this->wpdb->query($sqlDeleteExpired);
        if ($rows === false) {

            throw new Data_DbException('runHygiene delete expired failed: ' . (string) $this->wpdb->last_error);
        }
        $deleted += (int) $rows;

                                                                                       
        $sqlClamp = $this->wpdb->prepare(
            "UPDATE `{$table}`
             SET `expiration_date` = %d
             WHERE `expiration_date` IS NOT NULL AND `expiration_date` > %d",
            $horizonFuture,
            $horizonFuture
        );

        $ok = $this->wpdb->query($sqlClamp);
        if ($ok === false) {

            throw new Data_DbException('runHygiene clamp failed: ' . (string) $this->wpdb->last_error);
        }

                                                                                                  
        $sqlDeleteStale = $this->wpdb->prepare(
            "DELETE FROM `{$table}`
             WHERE `expiration_date` IS NULL
               AND `last_modification_date` IS NOT NULL
               AND `last_modification_date` < %d",
            $horizonPast
        );

        $rows = $this->wpdb->query($sqlDeleteStale);
        if ($rows === false) {

            throw new Data_DbException('runHygiene delete stale failed: ' . (string) $this->wpdb->last_error);
        }
        $deleted += (int) $rows;

        return $deleted;
    }

       
                                                         
       
    private function bootstrapColumns(): void
    {
        $bool = [
            Data_UniversalTableL1_Constants::COL_BOOL_1,
            Data_UniversalTableL1_Constants::COL_BOOL_2,
            Data_UniversalTableL1_Constants::COL_BOOL_3,
            Data_UniversalTableL1_Constants::COL_BOOL_4,
            Data_UniversalTableL1_Constants::COL_BOOL_5,
            Data_UniversalTableL1_Constants::COL_BOOL_6,
            Data_UniversalTableL1_Constants::COL_BOOL_7,
        ];

        $ints = [
            Data_UniversalTableL1_Constants::COL_ID,
            Data_UniversalTableL1_Constants::COL_INT_1,
            Data_UniversalTableL1_Constants::COL_INT_2,
            Data_UniversalTableL1_Constants::COL_INT_3,
            Data_UniversalTableL1_Constants::COL_INT_4,
            Data_UniversalTableL1_Constants::COL_INT_5,
            Data_UniversalTableL1_Constants::COL_INT_6,
            Data_UniversalTableL1_Constants::COL_INT_7,
        ];

        $floats = [
            Data_UniversalTableL1_Constants::COL_FLOAT_1,
            Data_UniversalTableL1_Constants::COL_FLOAT_2,
            Data_UniversalTableL1_Constants::COL_FLOAT_3,
            Data_UniversalTableL1_Constants::COL_FLOAT_4,
            Data_UniversalTableL1_Constants::COL_FLOAT_5,
            Data_UniversalTableL1_Constants::COL_FLOAT_6,
            Data_UniversalTableL1_Constants::COL_FLOAT_7,
        ];

                                               
        $binaries = [
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_1,
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_2,
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_3,
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_4,
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_5,
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_6,
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_7,

            Data_UniversalTableL1_Constants::COL_HASH_1,
            Data_UniversalTableL1_Constants::COL_HASH_2,
            Data_UniversalTableL1_Constants::COL_HASH_3,
            Data_UniversalTableL1_Constants::COL_HASH_4,
            Data_UniversalTableL1_Constants::COL_HASH_5,
            Data_UniversalTableL1_Constants::COL_HASH_6,
            Data_UniversalTableL1_Constants::COL_HASH_7,
        ];

                                      
        $strings = [
            Data_UniversalTableL1_Constants::COL_SCHEME_ID,

            Data_UniversalTableL1_Constants::COL_SHORT_STRING_1,
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_2,
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_3,
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_4,
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_5,
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_6,
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_7,

            Data_UniversalTableL1_Constants::COL_LONG_STRING_1,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_2,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_3,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_4,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_5,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_6,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_7,
        ];

        $time = [
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_1,
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_2,
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_3,
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_4,
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_5,
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_6,
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_7,
            Data_UniversalTableL1_Constants::COL_CREATION_DATE,
            Data_UniversalTableL1_Constants::COL_LAST_MODIFICATION_DATE,
            Data_UniversalTableL1_Constants::COL_LAST_REVIEW_DATE,
            Data_UniversalTableL1_Constants::COL_EXPIRATION_DATE,
        ];

        $all = array_merge($bool, $ints, $floats, $binaries, $strings, $time);

        $this->boolColumns    = array_fill_keys($bool, true);
        $this->intColumns     = array_fill_keys($ints, true);
        $this->floatColumns   = array_fill_keys($floats, true);
        $this->binaryColumns  = array_fill_keys($binaries, true);
        $this->timeColumns    = array_fill_keys($time, true);
        $this->allowedColumns = array_fill_keys($all, true);

        $formats = [];
        foreach ($all as $column) {
            if (isset($this->boolColumns[$column])) {
                $formats[$column] = '%d';
                continue;
            }
            if (isset($this->intColumns[$column]) || isset($this->timeColumns[$column])) {
                $formats[$column] = '%d';
                continue;
            }
            if (isset($this->floatColumns[$column])) {
                $formats[$column] = '%f';
                continue;
            }
            if (isset($this->binaryColumns[$column])) {
                                                                          
                $formats[$column] = 'UNHEX(%s)';
                continue;
            }
                                          
            $formats[$column] = '%s';
        }

        $this->columnFormats = $formats;
    }


       
                                                                 
      
                                       
                                  
       
    private function filterAndNormalizeData(array $data): array
    {
        $out = [];

        foreach ($data as $column => $value) {
            if (!isset($this->allowedColumns[$column])) {
                continue;
            }

            if ($value === null) {
                $out[$column] = null;
                continue;
            }

            $out[$column] = $this->normalizeSingleValue($column, $value);
        }

        return $out;
    }

       
                                                                                
       
    private function normalizeSingleValue(string $column, mixed $value): int|float|string
    {
        if (isset($this->boolColumns[$column])) {
            return (int) ((bool) $value);
        }

        if (isset($this->intColumns[$column]) || isset($this->timeColumns[$column])) {
            return (int) $value;
        }

        if (isset($this->floatColumns[$column])) {
            return (float) $value;
        }

        if (isset($this->binaryColumns[$column])) {
                                                                                         
                                                                              
            $raw = (string) $value;
            return \bin2hex($raw);
        }

                                      
        return (string) $value;
    }

}


   
                                                               
  
                                         
   
final class Data_UniversalTableL1_Constants
{
                                                
    public const TABLE_SLUG = 'dolet_universal_memory';

                                                                       
    public const COL_ID                      = 'id';
    public const COL_SCHEME_ID               = 'scheme_id';

    public const COL_UNIQUE_HASH_1           = 'unique_hash_1';
    public const COL_UNIQUE_HASH_2           = 'unique_hash_2';
    public const COL_UNIQUE_HASH_3           = 'unique_hash_3';
    public const COL_UNIQUE_HASH_4           = 'unique_hash_4';
    public const COL_UNIQUE_HASH_5           = 'unique_hash_5';
    public const COL_UNIQUE_HASH_6           = 'unique_hash_6';
    public const COL_UNIQUE_HASH_7           = 'unique_hash_7';

    public const COL_HASH_1                  = 'hash_1';
    public const COL_HASH_2                  = 'hash_2';
    public const COL_HASH_3                  = 'hash_3';
    public const COL_HASH_4                  = 'hash_4';
    public const COL_HASH_5                  = 'hash_5';
    public const COL_HASH_6                  = 'hash_6';
    public const COL_HASH_7                  = 'hash_7';

    public const COL_BOOL_1                  = 'bool_1';
    public const COL_BOOL_2                  = 'bool_2';
    public const COL_BOOL_3                  = 'bool_3';
    public const COL_BOOL_4                  = 'bool_4';
    public const COL_BOOL_5                  = 'bool_5';
    public const COL_BOOL_6                  = 'bool_6';
    public const COL_BOOL_7                  = 'bool_7';

    public const COL_INT_1                   = 'int_1';
    public const COL_INT_2                   = 'int_2';
    public const COL_INT_3                   = 'int_3';
    public const COL_INT_4                   = 'int_4';
    public const COL_INT_5                   = 'int_5';
    public const COL_INT_6                   = 'int_6';
    public const COL_INT_7                   = 'int_7';

    public const COL_FLOAT_1                 = 'float_1';
    public const COL_FLOAT_2                 = 'float_2';
    public const COL_FLOAT_3                 = 'float_3';
    public const COL_FLOAT_4                 = 'float_4';
    public const COL_FLOAT_5                 = 'float_5';
    public const COL_FLOAT_6                 = 'float_6';
    public const COL_FLOAT_7                 = 'float_7';

    public const COL_SHORT_STRING_1          = 'short_string_1';
    public const COL_SHORT_STRING_2          = 'short_string_2';
    public const COL_SHORT_STRING_3          = 'short_string_3';
    public const COL_SHORT_STRING_4          = 'short_string_4';
    public const COL_SHORT_STRING_5          = 'short_string_5';
    public const COL_SHORT_STRING_6          = 'short_string_6';
    public const COL_SHORT_STRING_7          = 'short_string_7';

    public const COL_LONG_STRING_1           = 'long_string_1';
    public const COL_LONG_STRING_2           = 'long_string_2';
    public const COL_LONG_STRING_3           = 'long_string_3';
    public const COL_LONG_STRING_4           = 'long_string_4';
    public const COL_LONG_STRING_5           = 'long_string_5';
    public const COL_LONG_STRING_6           = 'long_string_6';
    public const COL_LONG_STRING_7           = 'long_string_7';

    public const COL_TIMESTAMP_1             = 'timestamp_1';
    public const COL_TIMESTAMP_2             = 'timestamp_2';
    public const COL_TIMESTAMP_3             = 'timestamp_3';
    public const COL_TIMESTAMP_4             = 'timestamp_4';
    public const COL_TIMESTAMP_5             = 'timestamp_5';
    public const COL_TIMESTAMP_6             = 'timestamp_6';
    public const COL_TIMESTAMP_7             = 'timestamp_7';

    public const COL_CREATION_DATE           = 'creation_date';
    public const COL_LAST_MODIFICATION_DATE  = 'last_modification_date';
    public const COL_LAST_REVIEW_DATE        = 'last_review_date';
    public const COL_EXPIRATION_DATE         = 'expiration_date';

                                                                 
    public const DEFAULT_MAX_LIFETIME_SECONDS = 15552000;
}


   
                                                                            
  
                                                                         
                                                                        
                                                                     
  
                                                                                
   
final class Data_UniversalTableL1_Tools
{
       
                                                                             
      
                                               
      
                                 
                      
                                                                    
           
       
    public static function maybeDropOnUninstall(): void
    {
        $shouldDelete = (bool) get_option(
            Data_UniversalTableL1_Options::OPTION_DELETE_ON_UNINSTALL,
            false
        );

        if (!$shouldDelete) {
            return;
        }

        self::dropTableSilently();
        self::deleteOptionsSilently();
    }

       
                                                                       
      
                                                                    
       
    private static function deleteOptionsSilently(): void
    {
        $options = [
            Config_ApproachOptionNames::APPROACH_ENABLED,
            Config_ApproachOptionNames::MAX_BATCH_CHARS,
            Config_ApproachOptionNames::MAX_BATCH_ITEMS,
            Config_DebugOptionNames::DEBUG_OPTION_NAME,
            Config_TranslationOptionNames::FLOATING_SELECTOR_ENABLED,
            Config_TranslationOptionNames::TRANSLATE_PRE_TEXTAREA,
            Language_AdminOptionNames::SOURCE_CODE,
            Language_AdminOptionNames::TARGET_LANGUAGES,
            Language_AdminOptionNames::LANGUAGE_CUSTOMIZATIONS,
            Language_Constants::CUSTOM_LANGUAGES_LIST_OPTION_NAME,
            Config_DiagnosticsOptionNames::ERROR_LOG,
            Config_DiagnosticsOptionNames::LAST_ERROR,
            Config_DiagnosticsOptionNames::LAST_ERROR_AT,
            Config_DiagnosticsOptionNames::LAST_SUCCESS,
        ];

        foreach ($options as $option) {
            delete_option($option);
        }
    }

       
                                                       
      
                                                                           
                                                   
       
    public static function dropTableSilently(): void
    {
        global $wpdb;

        try {
            $table = $wpdb->prefix . Data_UniversalTableL1_Constants::TABLE_SLUG;

            $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log(
                    '[Dolet WP] Data_UniversalTableL1_Tools::dropTableSilently error: ' .
                    $e->getMessage()
                );
            }
        }
    }
}

                          



































                            

             
             

   
                                                            
  
                         
   
interface Data_ContextWindowInterface
{
    public function getPrev(): string;
    public function getCore(): string;
    public function getNext(): string;

                                                                         
    public function getPrevForHash(): ?string;
    public function getCoreForHash(): string;
    public function getNextForHash(): ?string;

                                                                                                   
    public function isEmptyCore(): bool;

}

   
                                                          
  
                                                                             
                                                                               
                          
  
                                                                                                         
   
interface Data_L2ContextWindowStoreInterface
{
                                                           
    public function ensureSchema(): void;

                                                         
    public function makeWindow(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): Data_ContextWindowInterface;

       
                                                                                       
      
                                                                                    
                                                                                  
                         
       
    public function findExactOrNull(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair,
        int $nowEpoch
    ): ?Data_RowRecordInterface;

           
                                                                                   
                                
       

    public function ensurePendingRegistered(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair,
        int $nowEpoch
    ): void;

       
                                                                           
      
                                                                         
       
    public function ensurePendingAndReturnId(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair,
        int $nowEpoch
    ): int;

       
                                                                       
                                   
                                   
      
                                                                               
                                                                                
      
                                            
       
    public function findNeighborCandidates(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair,
        int $limit = 0,
        bool $allowHygiene = true
    ): array;

       
                                                            
       
    public function findByIdOrNull(int $id): ?Data_RowRecordInterface;

       
                                                                               
                             
       
    public function touchUsageIfNeeded(Data_RowRecordInterface $record, int $nowEpoch): bool;

       
                                                 
       
    public function touchUsageByIdIfNeeded(int $id, int $nowEpoch): bool;

       
                                                        
                                             
      
                                                                                         
       
    public function updateTranslationById(
        int $id,
        string $tgtY,
        bool $humanTouched,
        ?string $metaJsonOrNull,
        int $nowEpoch
    ): void;

    public function setHumanLockedById(int $id, bool $value, int $nowEpoch): void;

    public function deleteById(int $id): void;

    public function deleteByWindow(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair
    ): void;

       
                                                                          
                                                                                
      
                                            
       
    public function fetchPending(
        Language_PairInterface $pair,
        int $limit,
        bool $allowHygiene = true
    ): array;

       
                                        
      
                                                                                                                                 
      
                                
       
    public function runHygieneAll(
        int $nowEpoch,
        int $maxLifetimeSeconds,
        int $pendingMaxAgeSeconds,
        int $corruptedScanLimit
    ): array;
}


   
                                                               
  
                                                                               
                                                                            
                                     
   
interface Data_L2TriadV1StoreInterface extends Data_L2ContextWindowStoreInterface
{
}


   
                                                                   
  
                         
   
interface Data_RowDraftInterface
{
    public function key(): Data_RowKeyInterface;

                                                  
    public function srcXOrNull(): ?string;
    public function srcY(): string;
    public function srcZOrNull(): ?string;

                                                                     
    public function payloadJson(): string;

    public function isTranslated(): bool;                            
    public function isHumanLocked(): bool;                           
    public function targetTextOrNull(): ?string;                    

       
                                                                        
      
                                  
       
    public function toL1InsertData(int $nowEpoch, int $expirationEpoch): array;
}

   
                                                                             
  
                         
   
interface Data_RowKeyInterface
{
    public function schemeId(): string;
    public function srcLang(): string;                                                
    public function tgtLang(): string;                                                
    public function hashX(): ?string;
    public function hashY(): string;
    public function hashZ(): ?string;
    public function triadHash(): string;                 
}

   
                         
  
                         
   
interface Data_RowRecordInterface
{
    public function id(): int;

    public function key(): Data_RowKeyInterface;

                                                  
    public function srcXOrNull(): ?string;
    public function srcY(): string;
    public function srcZOrNull(): ?string;

                                                                     
    public function payloadJson(): string;

    public function isTranslated(): bool;
    public function isHumanLocked(): bool;
    public function targetTextOrNull(): ?string;

    public function lastUsageEpochOrNull(): ?int;
    public function expirationEpochOrNull(): ?int;
}

   
                                                                                                
  
                         
   
interface Data_SchemeInterface
{
    public function schemeId(): string;

                                                                                    
    public function makeWindow(
        string $prev,
        string $core,
        string $next,
        Language_PairInterface $pair
    ): Data_ContextWindowInterface;

                                                            
    public function buildRowKey(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair
    ): Data_RowKeyInterface;

                                                      
    public function makeRowDraft(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair
    ): Data_RowDraftInterface;

                                                                                                         
    public function isAcceptableNeighborMatch(
        Data_RowRecordInterface $record,
        Data_ContextWindowInterface $w
    ): bool;

                                                           
    public function entryExpirationDurationSeconds(): int;
    public function lastUsageUpdateThresholdSeconds(): int;

    public function computeExpirationEpoch(int $nowEpoch): int;

    public function shouldUpdateLastUsage(?int $currentLastUsageEpoch, int $nowEpoch): bool;

       
                                                     
                                                                                          
      
                                      
       
    public function hydrateRecordFromL1Row(array $row): ?Data_RowRecordInterface;
}

   
                                                          
  
                         
   
interface Data_SchemeNavigatorInterface
{
       
                                                                   
       
    public function iterateWindows(
        Structs_FixedOrderedKeysArrayOfStringsInterface $cores,
        Data_SchemeInterface $scheme,
        Language_PairInterface $pair
    ): iterable;
}


         
         
         


        
         
         


          
          

   
                                                              
   
final class Data_ContextWindow implements Data_ContextWindowInterface
{
    public function __construct(
        private string $prev,
        private string $core,
        private string $next,
        private ?string $prevHash,
        private string $coreHash,
        private ?string $nextHash,
        private bool $emptyCore

    ) {
    }

    public function getPrev(): string
    {
        return $this->prev;
    }

    public function getCore(): string
    {
        return $this->core;
    }

    public function getNext(): string
    {
        return $this->next;
    }

    public function getPrevForHash(): ?string
    {
        return $this->prevHash;
    }

    public function getCoreForHash(): string
    {
        return $this->coreHash;
    }

    public function getNextForHash(): ?string
    {
        return $this->nextHash;
    }

    public function isEmptyCore(): bool
    {
        return $this->emptyCore;
    }

}

   
                                                                       
  
              
                                    
                                                                
                                
                                             
                                                                               
                                           
                                                                          
   
final class Data_L2TriadV1Store implements Data_L2TriadV1StoreInterface
{
    private Data_SchemeInterface $scheme;
    private Data_UniversalTableL1Interface $l1;

    public function __construct(
        ?Data_UniversalTableL1Interface $l1 = null,
        ?Data_SchemeInterface $scheme = null
    ) {
        $this->l1 = $l1 ?? new Data_UniversalTableL1();
        $this->scheme = $scheme ?? new Data_SchemeV1();
    }

    public function ensureSchema(): void
    {
        $this->l1->ensureSchema();
    }

    public function makeWindow(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): Data_ContextWindowInterface {
        return $this->scheme->makeWindow($prev, $core, $next, $pair);
    }

    public function findExactOrNull(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair,
        int $nowEpoch
    ): ?Data_RowRecordInterface {
        $key = $this->scheme->buildRowKey($w, $pair);

        $conditions = [
            Data_UniversalTableL1_Constants::COL_SCHEME_ID      => $key->schemeId(),
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_1  => $key->triadHash(),

            Data_UniversalTableL1_Constants::COL_SHORT_STRING_1 => $key->srcLang(),
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_2 => $key->tgtLang(),
        ];

        $row = $this->l1->selectOneByConditions($conditions);
        if ($row === null) {
            return null;
        }

        $record = $this->scheme->hydrateRecordFromL1Row($row);
        if ($record !== null) {
            return $record;
        }

                                                                           
        $id = isset($row[Data_UniversalTableL1_Constants::COL_ID])
            ? (int) $row[Data_UniversalTableL1_Constants::COL_ID]
            : 0;

        if ($id > 0) {
            $this->l1->deleteById($id);
        }

        $this->ensurePendingRegistered($w, $pair, $nowEpoch);
        return null;
    }

    public function ensurePendingRegistered(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair,
        int $nowEpoch
    ): void {
                                                                               
        $this->ensurePendingAndReturnId($w, $pair, $nowEpoch);
    }

    public function ensurePendingAndReturnId(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair,
        int $nowEpoch
    ): int {
                                                          
        if ($w->isEmptyCore()) {
            throw new Data_InvalidArgumentException('ensurePendingAndReturnId() called for ineligible core.');
        }


        $key = $this->scheme->buildRowKey($w, $pair);

        $conditions = [
            Data_UniversalTableL1_Constants::COL_SCHEME_ID      => $key->schemeId(),
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_1  => $key->triadHash(),
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_1 => $key->srcLang(),
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_2 => $key->tgtLang(),
        ];

        $existing = $this->l1->selectOneByConditions($conditions);
        if (is_array($existing) && isset($existing[Data_UniversalTableL1_Constants::COL_ID])) {
            $id = (int) $existing[Data_UniversalTableL1_Constants::COL_ID];
            if ($id > 0) {
                return $id;
            }
        }

        $draft      = $this->scheme->makeRowDraft($w, $pair);
        $expiration = $this->scheme->computeExpirationEpoch($nowEpoch);

        $id = $this->l1->insert($draft->toL1InsertData($nowEpoch, $expiration));
        if ($id > 0) {
            return $id;
        }

                                                                               
        $existing = $this->l1->selectOneByConditions($conditions);
        if (is_array($existing) && isset($existing[Data_UniversalTableL1_Constants::COL_ID])) {
            $id = (int) $existing[Data_UniversalTableL1_Constants::COL_ID];
            if ($id > 0) {
                return $id;
            }
        }

        throw new Data_DbException('ensurePendingAndReturnId(): insert returned 0 and re-select did not find an existing row.');
    }

    public function findNeighborCandidates(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair,
        int $limit = 0,
        bool $allowHygiene = true
    ): array {
        $schemeId = $this->scheme->schemeId();
        $srcLang  = $pair->getSourceLanguage()->getCode();
        $tgtLang  = $pair->getTargetLanguage()->getCode();

        $rowsById = [];

        $hx = $w->getPrevForHash();
        $hy = $w->getCoreForHash();
        $hz = $w->getNextForHash();

                         
        if ($hx !== null) {
            $conds = [
                Data_UniversalTableL1_Constants::COL_SCHEME_ID      => $schemeId,
                Data_UniversalTableL1_Constants::COL_SHORT_STRING_1 => $srcLang,
                Data_UniversalTableL1_Constants::COL_SHORT_STRING_2 => $tgtLang,
                Data_UniversalTableL1_Constants::COL_HASH_1         => $hx,
                Data_UniversalTableL1_Constants::COL_HASH_2         => $hy,
            ];

            $rows = $this->l1->selectManyByConditions($conds, $limit);
            foreach ($rows as $r) {
                $id = isset($r[Data_UniversalTableL1_Constants::COL_ID])
                    ? (int) $r[Data_UniversalTableL1_Constants::COL_ID]
                    : 0;
                if ($id > 0) {
                    $rowsById[$id] = $r;
                }
            }
        }

                         
        if ($hz !== null) {
            $conds = [
                Data_UniversalTableL1_Constants::COL_SCHEME_ID      => $schemeId,
                Data_UniversalTableL1_Constants::COL_SHORT_STRING_1 => $srcLang,
                Data_UniversalTableL1_Constants::COL_SHORT_STRING_2 => $tgtLang,
                Data_UniversalTableL1_Constants::COL_HASH_2         => $hy,
                Data_UniversalTableL1_Constants::COL_HASH_3         => $hz,
            ];

            $rows = $this->l1->selectManyByConditions($conds, $limit);
            foreach ($rows as $r) {
                $id = isset($r[Data_UniversalTableL1_Constants::COL_ID])
                    ? (int) $r[Data_UniversalTableL1_Constants::COL_ID]
                    : 0;
                if ($id > 0) {
                    $rowsById[$id] = $r;
                }
            }
        }

        if ($rowsById === []) {
            return [];
        }

        \ksort($rowsById, SORT_NUMERIC);

        $out = [];
        foreach ($rowsById as $row) {
            $record = $this->scheme->hydrateRecordFromL1Row($row);
            if ($record === null) {
                                                                               
                $id = isset($row[Data_UniversalTableL1_Constants::COL_ID])
                    ? (int) $row[Data_UniversalTableL1_Constants::COL_ID]
                    : 0;
                if ($allowHygiene && $id > 0) {
                    $this->l1->deleteById($id);
                }
                continue;
            }

                                                                          
            if (!$this->scheme->isAcceptableNeighborMatch($record, $w)) {
                continue;
            }

            $out[] = $record;
        }

        return $out;
    }

    public function findByIdOrNull(int $id): ?Data_RowRecordInterface
    {
        if ($id <= 0) {
            throw new Data_InvalidArgumentException('Invalid id.');
        }

        $row = $this->l1->selectOneByConditions([
            Data_UniversalTableL1_Constants::COL_ID => $id,
        ]);

        if ($row === null) {
            return null;
        }

        $record = $this->scheme->hydrateRecordFromL1Row($row);
        if ($record !== null) {
            return $record;
        }

                                               
        $this->l1->deleteById($id);

        throw new Data_RowCorruptException('Row corrupted; deleted id=' . $id);
    }

    public function touchUsageIfNeeded(Data_RowRecordInterface $record, int $nowEpoch): bool
    {
        if (!$this->scheme->shouldUpdateLastUsage($record->lastUsageEpochOrNull(), $nowEpoch)) {
            return false;
        }

        $expiration = $this->scheme->computeExpirationEpoch($nowEpoch);

        $data = [
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_1     => $nowEpoch,
            Data_UniversalTableL1_Constants::COL_EXPIRATION_DATE => $expiration,
        ];

        return $this->l1->updateById($record->id(), $data);
    }

    public function touchUsageByIdIfNeeded(int $id, int $nowEpoch): bool
    {
        $r = $this->findByIdOrNull($id);
        if ($r === null) {
            return false;
        }
        return $this->touchUsageIfNeeded($r, $nowEpoch);
    }

    public function updateTranslationById(
        int $id,
        string $tgtY,
        bool $humanTouched,
        ?string $metaJsonOrNull,
        int $nowEpoch
    ): void {
        $record = $this->findByIdOrNull($id);
        if ($record === null) {

            throw new Data_InvalidArgumentException('Row not found id=' . $id);
        }

        if ($record->isHumanLocked() && !$humanTouched) {
            throw new Data_HumanLockedException('Row is human-locked; refusing auto overwrite.');
        }

        $expiration = $this->scheme->computeExpirationEpoch($nowEpoch);

        $data = [
            Data_UniversalTableL1_Constants::COL_LONG_STRING_4   => $tgtY,
            Data_UniversalTableL1_Constants::COL_BOOL_1          => 1,
            Data_UniversalTableL1_Constants::COL_BOOL_2          => ($record->isHumanLocked() || $humanTouched) ? 1 : 0,
            Data_UniversalTableL1_Constants::COL_TIMESTAMP_1     => $nowEpoch,
            Data_UniversalTableL1_Constants::COL_EXPIRATION_DATE => $expiration,
        ];

        if ($metaJsonOrNull !== null) {
            $data[Data_UniversalTableL1_Constants::COL_LONG_STRING_5] = $metaJsonOrNull;
        }

        $ok = $this->l1->updateById($id, $data);
        if (!$ok) {
            throw new Data_DbException('updateTranslationById(): update returned false.');
        }
    }

    public function setHumanLockedById(int $id, bool $value, int $nowEpoch): void
    {
        $record = $this->findByIdOrNull($id);
        if ($record === null) {

            throw new Data_InvalidArgumentException('Row not found id=' . $id);
        }

        $expiration = $this->scheme->computeExpirationEpoch($nowEpoch);

        $data = [
            Data_UniversalTableL1_Constants::COL_BOOL_2          => $value ? 1 : 0,
            Data_UniversalTableL1_Constants::COL_EXPIRATION_DATE => $expiration,
        ];

        $ok = $this->l1->updateById($id, $data);
        if (!$ok) {
            throw new Data_DbException('setHumanLockedById(): update returned false.');
        }
    }

    public function deleteById(int $id): void
    {
        $ok = $this->l1->deleteById($id);
        if (!$ok) {
            throw new Data_DbException('deleteById(): delete returned false.');
        }
    }

    public function deleteByWindow(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair
    ): void {
        $key = $this->scheme->buildRowKey($w, $pair);

        $conditions = [
            Data_UniversalTableL1_Constants::COL_SCHEME_ID      => $key->schemeId(),
            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_1  => $key->triadHash(),
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_1 => $key->srcLang(),
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_2 => $key->tgtLang(),
        ];

        $row = $this->l1->selectOneByConditions($conditions);
        if ($row === null) {
            return;
        }

        $id = isset($row[Data_UniversalTableL1_Constants::COL_ID])
            ? (int) $row[Data_UniversalTableL1_Constants::COL_ID]
            : 0;

        if ($id > 0) {
            $this->deleteById($id);
        }
    }

    public function fetchPending(
        Language_PairInterface $pair,
        int $limit,
        bool $allowHygiene = true
    ): array {
        $schemeId = $this->scheme->schemeId();
        $srcLang  = $pair->getSourceLanguage()->getCode();
        $tgtLang  = $pair->getTargetLanguage()->getCode();

        $conditions = [
            Data_UniversalTableL1_Constants::COL_SCHEME_ID      => $schemeId,
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_1 => $srcLang,
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_2 => $tgtLang,
            Data_UniversalTableL1_Constants::COL_BOOL_1         => 0,
        ];

        $rows = $this->l1->selectManyByConditions($conditions, $limit);

        $out = [];
        foreach ($rows as $row) {
            $record = $this->scheme->hydrateRecordFromL1Row($row);
            if ($record === null) {
                $id = isset($row[Data_UniversalTableL1_Constants::COL_ID])
                    ? (int) $row[Data_UniversalTableL1_Constants::COL_ID]
                    : 0;
                if ($allowHygiene && $id > 0) {
                    $this->l1->deleteById($id);
                }
                continue;
            }
            $out[] = $record;
        }

        return $out;
    }

    public function runHygieneAll(
        int $nowEpoch,
        int $maxLifetimeSeconds,
        int $pendingMaxAgeSeconds,
        int $corruptedScanLimit
    ): array {
        $deletedGeneric = $this->l1->runHygiene($nowEpoch, $maxLifetimeSeconds);

        $deletedPending = 0;
        if ($pendingMaxAgeSeconds > 0) {
            $cutoff = $nowEpoch - $pendingMaxAgeSeconds;

            $deletedPending = $this->l1->deleteByConditionsAndLessThan(
                [
                    Data_UniversalTableL1_Constants::COL_SCHEME_ID => $this->scheme->schemeId(),
                    Data_UniversalTableL1_Constants::COL_BOOL_1    => 0,
                ],
                Data_UniversalTableL1_Constants::COL_LAST_MODIFICATION_DATE,
                $cutoff
            );
        }

        $deletedCorrupted = 0;
        $reregistered     = 0;

        if ($corruptedScanLimit > 0) {
            $rows = $this->l1->selectManyByConditions(
                [
                    Data_UniversalTableL1_Constants::COL_SCHEME_ID => $this->scheme->schemeId(),
                ],
                (int) $corruptedScanLimit
            );

            foreach ($rows as $row) {
                $id = isset($row[Data_UniversalTableL1_Constants::COL_ID])
                    ? (int) $row[Data_UniversalTableL1_Constants::COL_ID]
                    : 0;
                if ($id <= 0) {
                    continue;
                }

                $hydrated = $this->scheme->hydrateRecordFromL1Row($row);
                if ($hydrated !== null) {
                    continue;
                }

                $ok = $this->l1->deleteById($id);
                if (!$ok) {

                    throw new Data_DbException('runHygieneAll(): failed to delete corrupted row id=' . $id);
                }
                $deletedCorrupted++;

                                                                             
                $triad = $row[Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_1] ?? null;
                $hy    = $row[Data_UniversalTableL1_Constants::COL_HASH_2] ?? null;
                $src   = $row[Data_UniversalTableL1_Constants::COL_SHORT_STRING_1] ?? null;
                $tgt   = $row[Data_UniversalTableL1_Constants::COL_SHORT_STRING_2] ?? null;
                $srcY  = $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_2] ?? null;

                if (!is_string($triad) || $triad === '') {
                    continue;
                }
                if (!is_string($hy) || $hy === '') {
                    continue;
                }
                if (!is_string($src) || $src === '' || !is_string($tgt) || $tgt === '') {
                    continue;
                }
                if (!is_string($srcY)) {
                    continue;
                }

                $expiration = $this->scheme->computeExpirationEpoch($nowEpoch);

                $insert = [
                    Data_UniversalTableL1_Constants::COL_SCHEME_ID       => $this->scheme->schemeId(),
                    Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_1   => $triad,
                    Data_UniversalTableL1_Constants::COL_HASH_1          => (is_string($row[Data_UniversalTableL1_Constants::COL_HASH_1] ?? null) ? (string) $row[Data_UniversalTableL1_Constants::COL_HASH_1] : null),
                    Data_UniversalTableL1_Constants::COL_HASH_2          => $hy,
                    Data_UniversalTableL1_Constants::COL_HASH_3          => (is_string($row[Data_UniversalTableL1_Constants::COL_HASH_3] ?? null) ? (string) $row[Data_UniversalTableL1_Constants::COL_HASH_3] : null),
                    Data_UniversalTableL1_Constants::COL_SHORT_STRING_1  => $src,
                    Data_UniversalTableL1_Constants::COL_SHORT_STRING_2  => $tgt,
                    Data_UniversalTableL1_Constants::COL_LONG_STRING_1   => (is_string($row[Data_UniversalTableL1_Constants::COL_LONG_STRING_1] ?? null) ? (string) $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_1] : null),
                    Data_UniversalTableL1_Constants::COL_LONG_STRING_2   => $srcY,
                    Data_UniversalTableL1_Constants::COL_LONG_STRING_3   => (is_string($row[Data_UniversalTableL1_Constants::COL_LONG_STRING_3] ?? null) ? (string) $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_3] : null),
                    Data_UniversalTableL1_Constants::COL_LONG_STRING_4   => null,
                    Data_UniversalTableL1_Constants::COL_LONG_STRING_5   => (is_string($row[Data_UniversalTableL1_Constants::COL_LONG_STRING_5] ?? null) ? (string) $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_5] : null),
                    Data_UniversalTableL1_Constants::COL_BOOL_1          => 0,
                    Data_UniversalTableL1_Constants::COL_BOOL_2          => 0,
                    Data_UniversalTableL1_Constants::COL_TIMESTAMP_1     => $nowEpoch,
                    Data_UniversalTableL1_Constants::COL_EXPIRATION_DATE => $expiration,
                ];

                $newId = $this->l1->insert($insert);
                if ($newId > 0) {
                    $reregistered++;
                }
            }
        }

        return [
            'deleted_generic'    => (int) $deletedGeneric,
            'deleted_pending'    => (int) $deletedPending,
            'deleted_corrupted'  => (int) $deletedCorrupted,
            'reregistered'       => (int) $reregistered,
            'total_deleted'      => (int) ($deletedGeneric + $deletedPending + $deletedCorrupted),
        ];
    }
}



   
                                                                    
   
final class Data_RowDraft implements Data_RowDraftInterface
{
    public function __construct(
        private Data_RowKeyInterface $key,
        private ?string $srcX,
        private string $srcY,
        private ?string $srcZ,
        private ?string $metaJsonOrNull
    ) {
    }

    public function key(): Data_RowKeyInterface
    {
        return $this->key;
    }

    public function srcXOrNull(): ?string
    {
        return $this->srcX;
    }

    public function srcY(): string
    {
        return $this->srcY;
    }

    public function srcZOrNull(): ?string
    {
        return $this->srcZ;
    }

    public function payloadJson(): string
    {
        return $this->metaJsonOrNull ?? '';
    }

    public function isTranslated(): bool
    {
        return false;
    }

    public function isHumanLocked(): bool
    {
        return false;
    }

    public function targetTextOrNull(): ?string
    {
        return null;
    }

    public function toL1InsertData(int $nowEpoch, int $expirationEpoch): array
    {
        return [
            Data_UniversalTableL1_Constants::COL_SCHEME_ID       => $this->key->schemeId(),

            Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_1   => $this->key->triadHash(),

            Data_UniversalTableL1_Constants::COL_HASH_1          => $this->key->hashX(),
            Data_UniversalTableL1_Constants::COL_HASH_2          => $this->key->hashY(),
            Data_UniversalTableL1_Constants::COL_HASH_3          => $this->key->hashZ(),

            Data_UniversalTableL1_Constants::COL_SHORT_STRING_1  => $this->key->srcLang(),
            Data_UniversalTableL1_Constants::COL_SHORT_STRING_2  => $this->key->tgtLang(),

            Data_UniversalTableL1_Constants::COL_LONG_STRING_1   => $this->srcX,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_2   => $this->srcY,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_3   => $this->srcZ,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_4   => null,
            Data_UniversalTableL1_Constants::COL_LONG_STRING_5   => $this->metaJsonOrNull,

            Data_UniversalTableL1_Constants::COL_BOOL_1          => 0,
            Data_UniversalTableL1_Constants::COL_BOOL_2          => 0,

            Data_UniversalTableL1_Constants::COL_TIMESTAMP_1     => $nowEpoch,
            Data_UniversalTableL1_Constants::COL_EXPIRATION_DATE => $expirationEpoch,
        ];
    }
}


   
                               
   
final class Data_RowKey implements Data_RowKeyInterface
{
    public function __construct(
        private string $schemeId,
        private string $srcLang,
        private string $tgtLang,
        private ?string $hashX,
        private string $hashY,
        private ?string $hashZ,
        private string $triadHash
    ) {
    }

    public function schemeId(): string
    {
        return $this->schemeId;
    }

    public function srcLang(): string
    {
        return $this->srcLang;
    }

    public function tgtLang(): string
    {
        return $this->tgtLang;
    }

    public function hashX(): ?string
    {
        return $this->hashX;
    }

    public function hashY(): string
    {
        return $this->hashY;
    }

    public function hashZ(): ?string
    {
        return $this->hashZ;
    }

    public function triadHash(): string
    {
        return $this->triadHash;
    }
}


   
                                  
   
final class Data_RowRecord implements Data_RowRecordInterface
{
    public function __construct(
        private int $id,
        private Data_RowKeyInterface $key,
        private ?string $srcX,
        private string $srcY,
        private ?string $srcZ,
        private bool $isTranslated,
        private bool $isHumanLocked,
        private ?string $targetText,
        private ?string $metaJsonOrNull,
        private ?int $lastUsageEpoch,
        private ?int $expirationEpoch
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function key(): Data_RowKeyInterface
    {
        return $this->key;
    }

    public function srcXOrNull(): ?string
    {
        return $this->srcX;
    }

    public function srcY(): string
    {
        return $this->srcY;
    }

    public function srcZOrNull(): ?string
    {
        return $this->srcZ;
    }

    public function payloadJson(): string
    {
        return $this->metaJsonOrNull ?? '';
    }

    public function isTranslated(): bool
    {
        return $this->isTranslated;
    }

    public function isHumanLocked(): bool
    {
        return $this->isHumanLocked;
    }

    public function targetTextOrNull(): ?string
    {
        return $this->targetText;
    }

    public function lastUsageEpochOrNull(): ?int
    {
        return $this->lastUsageEpoch;
    }

    public function expirationEpochOrNull(): ?int
    {
        return $this->expirationEpoch;
    }
}


   
                                                        
   
final class Data_SchemeNavigatorSequential implements Data_SchemeNavigatorInterface
{
       
                                                                   
       
    public function iterateWindows(
        Structs_FixedOrderedKeysArrayOfStringsInterface $cores,
        Data_SchemeInterface $scheme,
        Language_PairInterface $pair
    ): iterable {
                                                                      
        $arr = \array_values($cores->asArray());
        $n   = \count($arr);

        for ($i = 0; $i < $n; $i++) {
            $prev = $i > 0      ? (string) $arr[$i - 1] : '';
            $core = (string) $arr[$i];
            $next = $i < $n - 1 ? (string) $arr[$i + 1] : '';

            yield [$i, $scheme->makeWindow($prev, $core, $next, $pair)];
        }
    }
}


   
                                                                      
   
final class Data_SchemeV1 implements Data_SchemeInterface
{
               
    private const LAST_USAGE_UPDATE_THRESHOLD_SECONDS = 1209600;

                
    private const ENTRY_EXPIRATION_DURATION_SECONDS = 15552000;

    public function schemeId(): string
    {
        return Data_Schemes::TRIAD_V1;
    }

    public function entryExpirationDurationSeconds(): int
    {
        return self::ENTRY_EXPIRATION_DURATION_SECONDS;
    }

    public function lastUsageUpdateThresholdSeconds(): int
    {
        return self::LAST_USAGE_UPDATE_THRESHOLD_SECONDS;
    }

    public function computeExpirationEpoch(int $nowEpoch): int
    {
        return $nowEpoch + self::ENTRY_EXPIRATION_DURATION_SECONDS;
    }

    public function shouldUpdateLastUsage(?int $currentLastUsageEpoch, int $nowEpoch): bool
    {
        if ($currentLastUsageEpoch === null || $currentLastUsageEpoch <= 0) {
            return true;
        }

        return ($nowEpoch - $currentLastUsageEpoch) >= self::LAST_USAGE_UPDATE_THRESHOLD_SECONDS;
    }

    public function makeWindow(
        string $prev,
        string $core,
        string $next,
        Language_PairInterface $pair
    ): Data_ContextWindowInterface {
        $pCanon = self::canonForHash($prev);
        $cCanon = self::canonForHash($core);
        $nCanon = self::canonForHash($next);

        $prevHash = ($pCanon === '') ? null : self::hashStr($pCanon);
        $coreHash = self::hashStr($cCanon);
        $nextHash = ($nCanon === '') ? null : self::hashStr($nCanon);

        $isEmptyCore = ($cCanon === '');

        return new Data_ContextWindow(
            $prev,
            $core,
            $next,
            $prevHash,
            $coreHash,
            $nextHash,
            $isEmptyCore
        );

    }

    public function buildRowKey(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair
    ): Data_RowKeyInterface {
        $scheme = $this->schemeId();
        $src    = $pair->getSourceLanguage()->getCode();
        $tgt    = $pair->getTargetLanguage()->getCode();

        $hx = $w->getPrevForHash();
        $hy = $w->getCoreForHash();
        $hz = $w->getNextForHash();

        $triad = self::hashStr(
            $scheme . '|' . $src . '|' . $tgt . '|' . ($hx ?? '') . '|' . $hy . '|' . ($hz ?? '')
        );

        return new Data_RowKey($scheme, $src, $tgt, $hx, $hy, $hz, $triad);
    }

    public function makeRowDraft(
        Data_ContextWindowInterface $w,
        Language_PairInterface $pair
    ): Data_RowDraftInterface {
        $key = $this->buildRowKey($w, $pair);

        $srcX = ($w->getPrev() === '') ? null : $w->getPrev();
        $srcY = $w->getCore();
        $srcZ = ($w->getNext() === '') ? null : $w->getNext();

        return new Data_RowDraft($key, $srcX, $srcY, $srcZ, null);
    }

    public function isAcceptableNeighborMatch(
        Data_RowRecordInterface $record,
        Data_ContextWindowInterface $w
    ): bool {
        $k = $record->key();

        $yOk = $k->hashY() === $w->getCoreForHash();

        $xOk = ($k->hashX() !== null) && ($w->getPrevForHash() !== null) && ($k->hashX() === $w->getPrevForHash());
        $zOk = ($k->hashZ() !== null) && ($w->getNextForHash() !== null) && ($k->hashZ() === $w->getNextForHash());

        return $yOk && ($xOk || $zOk);
    }

    public function hydrateRecordFromL1Row(array $row): ?Data_RowRecordInterface
    {
        $id = isset($row[Data_UniversalTableL1_Constants::COL_ID])
            ? (int) $row[Data_UniversalTableL1_Constants::COL_ID]
            : 0;

        if ($id <= 0) {
            return null;
        }

        $scheme = isset($row[Data_UniversalTableL1_Constants::COL_SCHEME_ID])
            ? (string) $row[Data_UniversalTableL1_Constants::COL_SCHEME_ID]
            : '';

        if ($scheme !== $this->schemeId()) {
            return null;
        }

        $triad = $row[Data_UniversalTableL1_Constants::COL_UNIQUE_HASH_1] ?? null;
        $hy    = $row[Data_UniversalTableL1_Constants::COL_HASH_2] ?? null;

        if (!is_string($triad) || $triad === '') {
            return null;
        }
        if (!is_string($hy) || $hy === '') {
            return null;
        }

        $src = isset($row[Data_UniversalTableL1_Constants::COL_SHORT_STRING_1])
            ? (string) $row[Data_UniversalTableL1_Constants::COL_SHORT_STRING_1]
            : '';

        $tgt = isset($row[Data_UniversalTableL1_Constants::COL_SHORT_STRING_2])
            ? (string) $row[Data_UniversalTableL1_Constants::COL_SHORT_STRING_2]
            : '';

        $hx = $row[Data_UniversalTableL1_Constants::COL_HASH_1] ?? null;
        $hz = $row[Data_UniversalTableL1_Constants::COL_HASH_3] ?? null;

        $hx = (is_string($hx) && $hx !== '') ? $hx : null;
        $hz = (is_string($hz) && $hz !== '') ? $hz : null;

        $srcY = $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_2] ?? null;
        if (!is_string($srcY)) {
            return null;
        }

        $srcX = $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_1] ?? null;
        $srcZ = $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_3] ?? null;

        $srcX = is_string($srcX) ? $srcX : null;
        $srcZ = is_string($srcZ) ? $srcZ : null;

        $tgtY = $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_4] ?? null;
        $tgtY = is_string($tgtY) ? $tgtY : null;

        $meta = $row[Data_UniversalTableL1_Constants::COL_LONG_STRING_5] ?? null;
        $meta = is_string($meta) ? $meta : null;

        $isTranslated = (bool) (int) ($row[Data_UniversalTableL1_Constants::COL_BOOL_1] ?? 0);
        $isLocked     = (bool) (int) ($row[Data_UniversalTableL1_Constants::COL_BOOL_2] ?? 0);

                                                          
        if ($isTranslated && $tgtY === null) {
            return null;
        }
        if (!$isTranslated && $tgtY !== null) {
            return null;
        }

        $lastUsage = isset($row[Data_UniversalTableL1_Constants::COL_TIMESTAMP_1])
            ? (int) $row[Data_UniversalTableL1_Constants::COL_TIMESTAMP_1]
            : null;
        if ($lastUsage !== null && $lastUsage <= 0) {
            $lastUsage = null;
        }

        $expiration = isset($row[Data_UniversalTableL1_Constants::COL_EXPIRATION_DATE])
            ? (int) $row[Data_UniversalTableL1_Constants::COL_EXPIRATION_DATE]
            : null;
        if ($expiration !== null && $expiration <= 0) {
            $expiration = null;
        }

        $key = new Data_RowKey($scheme, $src, $tgt, $hx, $hy, $hz, $triad);

        return new Data_RowRecord(
            $id,
            $key,
            $srcX,
            $srcY,
            $srcZ,
            $isTranslated,
            $isLocked,
            $tgtY,
            $meta,
            $lastUsage,
            $expiration
        );
    }

                                  

    private static function canonForHash(string $s): string
    {
        if ($s === '') {
            return '';
        }

        $decoded = \html_entity_decode($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                                             
        $decoded = \preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $decoded) ?? $decoded;

                                            
        $collapsed = \preg_replace('/\s+/u', ' ', $decoded) ?? $decoded;

        return \trim($collapsed);
    }


    private static function hashStr(string $s): string
    {
                                                                       
        return \hash('sha256', $s, true);
    }
}


   
                                 
  
                                         
   
final class Data_Schemes
{
    public const TRIAD_V1 = 'triad_v1';
}

                          



































                            

             
             
                                                            


         
         
         


        
         
         


          
          

   
                                              
  
                                                                             
   
final class Data_TmRow implements Data_TmRowInterface
{
    public function __construct(
        private Data_RowRecordInterface $record
    ) {}

    public function getId(): int
    {
        return $this->record->id();
    }

    public function getSourceLanguageCode(): string
    {
        return $this->record->key()->srcLang();
    }

    public function getTargetLanguageCode(): string
    {
        return $this->record->key()->tgtLang();
    }

    public function getSourcePrevOrNull(): ?string
    {
        return $this->record->srcXOrNull();
    }

    public function getSourceCore(): string
    {
        return $this->record->srcY();
    }

    public function getSourceNextOrNull(): ?string
    {
        return $this->record->srcZOrNull();
    }

    public function isTranslated(): bool
    {
        return $this->record->isTranslated();
    }

    public function isHumanLocked(): bool
    {
        return $this->record->isHumanLocked();
    }

    public function getTargetCoreOrNull(): ?string
    {
        return $this->record->targetTextOrNull();
    }

    public function getMetaJsonOrNull(): ?string
    {
        $p = $this->record->payloadJson();
        return ($p === '') ? null : $p;
    }

    public function getLastUsageEpochOrNull(): ?int
    {
        return $this->record->lastUsageEpochOrNull();
    }

    public function getExpirationEpochOrNull(): ?int
    {
        return $this->record->expirationEpochOrNull();
    }
}

   
                                                
  
                                                    
   
final class Data_TranslationMemoryDbRepository implements Data_TranslationMemoryRepositoryInterface
{
    private Data_L2ContextWindowStoreInterface $store;

    public function __construct(?Data_L2ContextWindowStoreInterface $store = null)
    {
        $this->store = $store ?? new Data_L2TriadV1Store();
    }

    public function ensureReady(): void
    {
        $this->store->ensureSchema();
    }

    public function findById(int $id): ?Data_TmRowInterface
    {
        $record = $this->store->findByIdOrNull($id);
        if ($record === null) {
            return null;
        }

        return self::recordToRow($record);
    }

    public function findExactRecord(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): ?Data_TmRowInterface {
        $now    = \time();
        $window = $this->store->makeWindow($pair, $prev, $core, $next);
        $r      = $this->store->findExactOrNull($window, $pair, $now);

        if ($r === null) {
            return null;
        }

        return self::recordToRow($r);
    }

    public function findNeighborCandidates(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next,
        int $limit = 0,
        bool $allowHygiene = true
    ): array {
                                                                                                                                       
        $window = $this->store->makeWindow($pair, $prev, $core, $next);
        $rows   = $this->store->findNeighborCandidates($window, $pair, $limit, $allowHygiene);

        $out = [];
        foreach ($rows as $r) {
            $out[] = self::recordToRow($r);
        }

        return $out;
    }

    public function touchUsageIfNeeded(int $id): bool
    {
        return $this->store->touchUsageByIdIfNeeded($id, \time());
    }

    public function ensurePending(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): int {
        $now    = \time();
        $window = $this->store->makeWindow($pair, $prev, $core, $next);

        if ($window->isEmptyCore()) {
            throw new Data_InvalidArgumentException('ensurePending() called for ineligible core.');
        }


        return $this->store->ensurePendingAndReturnId($window, $pair, $now);
    }

    public function updateTranslation(
        int $id,
        string $translatedText,
        bool $humanTouched,
        ?array $extraMeta
    ): void {
        $metaJsonOrNull = null;

        if ($extraMeta !== null) {
            $json = \json_encode($extraMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($json)) {
                throw new Data_InvalidArgumentException('extraMeta could not be JSON-encoded.');
            }
            $metaJsonOrNull = $json;
        }

        $this->store->updateTranslationById($id, $translatedText, $humanTouched, $metaJsonOrNull, \time());
    }

    public function markHumanTouched(int $id, bool $value): void
    {
        $this->store->setHumanLockedById($id, $value, \time());
    }

    public function deleteById(int $id): void
    {
        $this->store->deleteById($id);
    }

    public function deleteByWindow(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): void {
        $window = $this->store->makeWindow($pair, $prev, $core, $next);
        $this->store->deleteByWindow($window, $pair);
    }

    public function fetchPending(
        Language_PairInterface $pair,
        int $limit,
        bool $allowHygiene = true
    ): array
    {
        $rows = $this->store->fetchPending($pair, $limit, $allowHygiene);

        $out = [];
        foreach ($rows as $r) {
            $out[] = self::recordToRow($r);
        }

        return $out;
    }

    public function runHygiene(
        int $nowEpoch,
        int $maxLifetimeSeconds,
        int $pendingMaxAgeSeconds = 0,
        int $corruptedScanLimit = 500
    ): array {
        return $this->store->runHygieneAll($nowEpoch, $maxLifetimeSeconds, $pendingMaxAgeSeconds, $corruptedScanLimit);
    }

    public function getStatsByPair(): array
    {
        global $wpdb;
        if (!($wpdb instanceof \wpdb)) {
            return [];
        }

        $table = $wpdb->prefix . Data_UniversalTableL1_Constants::TABLE_SLUG;

                                                                             
                                                                          
                                                                     
        $rows = $wpdb->get_results("
            SELECT
                short_string_1 AS src,
                short_string_2 AS tgt,
                COUNT(*) AS total,
                SUM(bool_1 = 1) AS translated,
                SUM(bool_1 = 0 OR bool_1 IS NULL) AS pending,
                SUM(bool_2 = 1) AS human_locked,
                SUM(
                    CASE WHEN long_string_2 IS NOT NULL AND long_string_2 != ''
                    THEN LENGTH(long_string_2) - LENGTH(REPLACE(long_string_2, ' ', '')) + 1
                    ELSE 0 END
                ) AS word_count
            FROM `{$table}`
            WHERE scheme_id = 'triad_v1'
            GROUP BY short_string_1, short_string_2
            ORDER BY short_string_1, short_string_2
        ", ARRAY_A);

        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'src'          => (string) ($row['src'] ?? ''),
                'tgt'          => (string) ($row['tgt'] ?? ''),
                'total'        => (int) ($row['total'] ?? 0),
                'translated'   => (int) ($row['translated'] ?? 0),
                'pending'      => (int) ($row['pending'] ?? 0),
                'human_locked' => (int) ($row['human_locked'] ?? 0),
                'word_count'   => (int) ($row['word_count'] ?? 0),
            ];
        }

        return $out;
    }

    public function findOrRegisterPending(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): ?string {
        $now    = \time();
        $window = $this->store->makeWindow($pair, $prev, $core, $next);

                                                                             
        if ($window->isEmptyCore()) {
            return null;
        }


                                                 
        $exact = $this->store->findExactOrNull($window, $pair, $now);
        if ($exact !== null) {
            $this->store->touchUsageIfNeeded($exact, $now);

            if ($exact->isTranslated()) {
                $t = $exact->targetTextOrNull();
                if (is_string($t)) {
                    return $t;
                }
            }

            return null;
        }

                                                       
        $this->store->ensurePendingRegistered($window, $pair, $now);
        return null;
    }

    private static function recordToRow(Data_RowRecordInterface $r): Data_TmRowInterface
    {
        return new Data_TmRow($r);
    }
}


   
                                                
  
                                          
   
class Data_TranslationMemoryInMemoryRepository implements Data_TranslationMemoryRepositoryInterface
{
    private Data_SchemeInterface $scheme;

                                                  
    protected static array $byId = [];

                                                      
    protected static array $byTriad = [];

    protected static int $nextId = 1;

    public function __construct(?Data_SchemeInterface $scheme = null)
    {
        $this->scheme = $scheme ?? new Data_SchemeV1();
    }

    public function ensureReady(): void
    {
                               
    }

    public function findById(int $id): ?Data_TmRowInterface
    {
        if ($id <= 0) {
            throw new Data_InvalidArgumentException('Invalid id.');
        }

        $r = self::$byId[$id] ?? null;
        if (!$r instanceof Data_RowRecordInterface) {
            return null;
        }

        return self::recordToRow($r);
    }

    public function findExactRecord(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): ?Data_TmRowInterface {
        $w   = $this->scheme->makeWindow($prev, $core, $next, $pair);
        $key = $this->scheme->buildRowKey($w, $pair);

        $id = self::$byTriad[\bin2hex($key->triadHash())] ?? 0;
        if ($id <= 0) {
            return null;
        }

        $r = self::$byId[$id] ?? null;
        if (!$r instanceof Data_RowRecordInterface) {
            return null;
        }

        return self::recordToRow($r);
    }

    public function findNeighborCandidates(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next,
        int $limit = 0,
        bool $allowHygiene = true
    ): array {
                                                                                                                                       
        $w = $this->scheme->makeWindow($prev, $core, $next, $pair);

        $out = [];
        foreach (self::$byId as $r) {
            if ($r->key()->srcLang() !== $pair->getSourceLanguage()->getCode()) {
                continue;
            }
            if ($r->key()->tgtLang() !== $pair->getTargetLanguage()->getCode()) {
                continue;
            }
            if ($r->key()->schemeId() !== $this->scheme->schemeId()) {
                continue;
            }

            if (!$this->scheme->isAcceptableNeighborMatch($r, $w)) {
                continue;
            }

            $out[] = self::recordToRow($r);

            if ($limit > 0 && \count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    public function touchUsageIfNeeded(int $id): bool
    {
        if ($id <= 0) {
            throw new Data_InvalidArgumentException('Invalid id.');
        }

        $r = self::$byId[$id] ?? null;
        if (!$r instanceof Data_RowRecordInterface) {
            return false;
        }

        $now = \time();

        if (!$this->scheme->shouldUpdateLastUsage($r->lastUsageEpochOrNull(), $now)) {
            return false;
        }

        $expiration = $this->scheme->computeExpirationEpoch($now);

        $updated = new Data_RowRecord(
            $r->id(),
            $r->key(),
            $r->srcXOrNull(),
            $r->srcY(),
            $r->srcZOrNull(),
            $r->isTranslated(),
            $r->isHumanLocked(),
            $r->targetTextOrNull(),
            ($r->payloadJson() === '') ? null : $r->payloadJson(),
            $now,
            $expiration
        );

        self::$byId[$id] = $updated;
        return true;
    }

    public function ensurePending(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): int {
        $now = \time();
        $w   = $this->scheme->makeWindow($prev, $core, $next, $pair);

        if ($w->isEmptyCore()) {
            throw new Data_InvalidArgumentException('ensurePending() called for ineligible core.');
        }


        $keyHex = \bin2hex($this->scheme->buildRowKey($w, $pair)->triadHash());
        $id     = self::$byTriad[$keyHex] ?? 0;

        if ($id > 0) {
            return $id;
        }

        $draft = $this->scheme->makeRowDraft($w, $pair);

        $id = self::$nextId++;
        $expiration = $this->scheme->computeExpirationEpoch($now);

        $key = $draft->key();

        $record = new Data_RowRecord(
            $id,
            $key,
            $draft->srcXOrNull(),
            $draft->srcY(),
            $draft->srcZOrNull(),
            false,
            false,
            null,
            ($draft->payloadJson() === '') ? null : $draft->payloadJson(),
            $now,
            $expiration
        );

        self::$byId[$id] = $record;
        self::$byTriad[$keyHex] = $id;

        return $id;
    }

    public function updateTranslation(
        int $id,
        string $translatedText,
        bool $humanTouched,
        ?array $extraMeta
    ): void {
        $r = self::$byId[$id] ?? null;
        if (!$r instanceof Data_RowRecordInterface) {

            throw new Data_InvalidArgumentException('Row not found id=' . $id);
        }

        if ($r->isHumanLocked() && !$humanTouched) {
            throw new Data_HumanLockedException('Row is human-locked; refusing auto overwrite.');
        }

        $now = \time();
        $expiration = $this->scheme->computeExpirationEpoch($now);

        $metaJson = null;
        if ($extraMeta !== null) {
            $json = \json_encode($extraMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($json)) {
                throw new Data_InvalidArgumentException('extraMeta could not be JSON-encoded.');
            }
            $metaJson = $json;
        } else {
            $metaJson = ($r->payloadJson() === '') ? null : $r->payloadJson();
        }

        $updated = new Data_RowRecord(
            $r->id(),
            $r->key(),
            $r->srcXOrNull(),
            $r->srcY(),
            $r->srcZOrNull(),
            true,
            $r->isHumanLocked() || $humanTouched,
            $translatedText,
            $metaJson,
            $now,
            $expiration
        );

        self::$byId[$id] = $updated;
    }

    public function markHumanTouched(int $id, bool $value): void
    {
        $r = self::$byId[$id] ?? null;
        if (!$r instanceof Data_RowRecordInterface) {

            throw new Data_InvalidArgumentException('Row not found id=' . $id);
        }

        $now = \time();
        $expiration = $this->scheme->computeExpirationEpoch($now);

        $updated = new Data_RowRecord(
            $r->id(),
            $r->key(),
            $r->srcXOrNull(),
            $r->srcY(),
            $r->srcZOrNull(),
            $r->isTranslated(),
            $value,
            $r->targetTextOrNull(),
            ($r->payloadJson() === '') ? null : $r->payloadJson(),
            $r->lastUsageEpochOrNull() ?? $now,
            $expiration
        );

        self::$byId[$id] = $updated;
    }

    public function deleteById(int $id): void
    {
        $r = self::$byId[$id] ?? null;
        if (!$r instanceof Data_RowRecordInterface) {
            return;
        }

        unset(self::$byId[$id]);

        $triadHex = \bin2hex($r->key()->triadHash());
        unset(self::$byTriad[$triadHex]);
    }

    public function deleteByWindow(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): void {
        $w   = $this->scheme->makeWindow($prev, $core, $next, $pair);
        $key = $this->scheme->buildRowKey($w, $pair);

        $id = self::$byTriad[\bin2hex($key->triadHash())] ?? 0;
        if ($id > 0) {
            $this->deleteById($id);
        }
    }

    public function fetchPending(
        Language_PairInterface $pair,
        int $limit,
        bool $allowHygiene = true
    ): array
    {
        $out = [];

        foreach (self::$byId as $r) {
            if ($r->key()->schemeId() !== $this->scheme->schemeId()) {
                continue;
            }
            if ($r->key()->srcLang() !== $pair->getSourceLanguage()->getCode()) {
                continue;
            }
            if ($r->key()->tgtLang() !== $pair->getTargetLanguage()->getCode()) {
                continue;
            }
            if ($r->isTranslated()) {
                continue;
            }

            $out[] = self::recordToRow($r);

            if (\count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    public function runHygiene(
        int $nowEpoch,
        int $maxLifetimeSeconds,
        int $pendingMaxAgeSeconds = 0,
        int $corruptedScanLimit = 500
    ): array {
        $deletedGeneric   = 0;
        $deletedPending   = 0;
        $deletedCorrupted = 0;
        $reregistered     = 0;

        $horizonFuture = $nowEpoch + $maxLifetimeSeconds;
        $horizonPast   = $nowEpoch - $maxLifetimeSeconds;

        foreach (array_keys(self::$byId) as $id) {
            $r = self::$byId[$id];

            $exp = $r->expirationEpochOrNull();
            $lastUsage = $r->lastUsageEpochOrNull();

                              
            if ($exp !== null && $exp < $nowEpoch) {
                $this->deleteById((int) $id);
                $deletedGeneric++;
                continue;
            }

                                        
            if ($exp !== null && $exp > $horizonFuture) {
                $updated = new Data_RowRecord(
                    $r->id(),
                    $r->key(),
                    $r->srcXOrNull(),
                    $r->srcY(),
                    $r->srcZOrNull(),
                    $r->isTranslated(),
                    $r->isHumanLocked(),
                    $r->targetTextOrNull(),
                    ($r->payloadJson() === '') ? null : $r->payloadJson(),
                    $lastUsage,
                    $horizonFuture
                );
                self::$byId[$id] = $updated;
            }

                                                                               
            if ($exp === null && $lastUsage !== null && $lastUsage < $horizonPast) {
                $this->deleteById((int) $id);
                $deletedGeneric++;
                continue;
            }
        }

        if ($pendingMaxAgeSeconds > 0) {
            $cutoff = $nowEpoch - $pendingMaxAgeSeconds;

            foreach (array_keys(self::$byId) as $id) {
                $r = self::$byId[$id];
                if ($r->isTranslated()) {
                    continue;
                }
                $lu = $r->lastUsageEpochOrNull() ?? 0;
                if ($lu > 0 && $lu < $cutoff) {
                    $this->deleteById((int) $id);
                    $deletedPending++;
                }
            }
        }

        return [
            'deleted_generic'    => (int) $deletedGeneric,
            'deleted_pending'    => (int) $deletedPending,
            'deleted_corrupted'  => (int) $deletedCorrupted,
            'reregistered'       => (int) $reregistered,
            'total_deleted'      => (int) ($deletedGeneric + $deletedPending + $deletedCorrupted),
        ];
    }

    public function getStatsByPair(): array
    {
                                                  
        $stats = [];
        foreach (self::$byId as $r) {
            $key = $r->key();
            $src = $key->srcLang();
            $tgt = $key->tgtLang();
            $k   = $src . '→' . $tgt;

            if (!isset($stats[$k])) {
                $stats[$k] = ['src' => $src, 'tgt' => $tgt, 'total' => 0, 'translated' => 0, 'pending' => 0, 'human_locked' => 0, 'word_count' => 0];
            }

            $stats[$k]['total']++;
            if ($r->isTranslated()) {
                $stats[$k]['translated']++;
            } else {
                $stats[$k]['pending']++;
            }
            if ($r->isHumanLocked()) {
                $stats[$k]['human_locked']++;
            }
            $src_text = $r->srcY();
            if ($src_text !== '') {
                $stats[$k]['word_count'] += \str_word_count($src_text);
            }
        }

        return \array_values($stats);
    }

    public function findOrRegisterPending(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): ?string {
        $w = $this->scheme->makeWindow($prev, $core, $next, $pair);

        if ($w->isEmptyCore()) {
            return null;
        }


        $exact = $this->findExactRecord($pair, $prev, $core, $next);
        if ($exact instanceof Data_TmRowInterface && $exact->isTranslated()) {
            $id = $exact->getId();
            if ($id > 0) {
                $this->touchUsageIfNeeded($id);
            }

            $t = $exact->getTargetCoreOrNull();
            if (is_string($t)) {
                return $t;
            }
        }

                                 
        $this->ensurePending($pair, $prev, $core, $next);
        return null;
    }

    protected static function recordToRow(Data_RowRecordInterface $r): Data_TmRowInterface
    {
        return new Data_TmRow($r);
    }
}

   
                                           
  
                                                              
                                                                        
                                          
   
final class Data_TranslationMemoryMock extends Data_TranslationMemoryInMemoryRepository
{
}


   
                                              
  
                                                                    
   
final class Data_TranslationMemoryRepository implements Data_TranslationMemoryRepositoryInterface
{
    private Data_TranslationMemoryRepositoryInterface $impl;

    public function __construct(
        ?Data_TranslationMemoryRepositoryInterface $impl = null
    ) {
        if ($impl !== null) {
            $this->impl = $impl;
            return;
        }

                                                                         
        $wpdbObj = null;
        if (isset($GLOBALS['wpdb']) && $GLOBALS['wpdb'] instanceof \wpdb) {
            $wpdbObj = $GLOBALS['wpdb'];
        }

        $this->impl = ($wpdbObj instanceof \wpdb)
            ? new Data_TranslationMemoryDbRepository()
            : new Data_TranslationMemoryMock();
    }

    public function ensureReady(): void
    {
        $this->impl->ensureReady();
    }

    public function findById(int $id): ?Data_TmRowInterface
    {
        return $this->impl->findById($id);
    }

    public function findExactRecord(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): ?Data_TmRowInterface {
        return $this->impl->findExactRecord($pair, $prev, $core, $next);
    }

    public function findNeighborCandidates(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next,
        int $limit = 0,
        bool $allowHygiene = true
    ): array {
        return $this->impl->findNeighborCandidates($pair, $prev, $core, $next, $limit, $allowHygiene);
    }

    public function touchUsageIfNeeded(int $id): bool
    {
        return $this->impl->touchUsageIfNeeded($id);
    }

    public function ensurePending(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): int {
        return $this->impl->ensurePending($pair, $prev, $core, $next);
    }

    public function updateTranslation(
        int $id,
        string $translatedText,
        bool $humanTouched,
        ?array $extraMeta
    ): void {
        $this->impl->updateTranslation($id, $translatedText, $humanTouched, $extraMeta);
    }

    public function markHumanTouched(int $id, bool $value): void
    {
        $this->impl->markHumanTouched($id, $value);
    }

    public function deleteById(int $id): void
    {
        $this->impl->deleteById($id);
    }

    public function deleteByWindow(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): void {
        $this->impl->deleteByWindow($pair, $prev, $core, $next);
    }

    public function fetchPending(
        Language_PairInterface $pair,
        int $limit,
        bool $allowHygiene = true
    ): array
    {
        return $this->impl->fetchPending($pair, $limit, $allowHygiene);
    }

    public function runHygiene(
        int $nowEpoch,
        int $maxLifetimeSeconds,
        int $pendingMaxAgeSeconds = 0,
        int $corruptedScanLimit = 500
    ): array {
        return $this->impl->runHygiene($nowEpoch, $maxLifetimeSeconds, $pendingMaxAgeSeconds, $corruptedScanLimit);
    }

    public function findOrRegisterPending(
        Language_PairInterface $pair,
        string $prev,
        string $core,
        string $next
    ): ?string {
        return $this->impl->findOrRegisterPending($pair, $prev, $core, $next);
    }

    public function getStatsByPair(): array
    {
        return $this->impl->getStatsByPair();
    }
}

                          
