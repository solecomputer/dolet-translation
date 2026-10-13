<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                               
  
                                                              
                                                                                 
                                                                                              
  
                                                
                                                     
                                            
  
   


require_once __DIR__ . '/contracts.php';


                                                                             
             
                                                                             
                                                       


                                                                             
         
                                                                             
         


                                                                             
        
                                                                             
         


                                                                             
            
                                                                             
                                                         


                                                                             
          
                                                                             

                                                                              
final class Structs_PureNumber
{
    public static function is(string $text): bool
    {
        $collapsed = \preg_replace('/[\s\p{Zs}\x{200B}\x{2060}\x{FEFF}]+/u', '', $text) ?? $text;
        $collapsed = \trim($collapsed);

        return $collapsed !== ''
            && \preg_match('/^[+-]?\p{Nd}+(?:[.,]\p{Nd}+)*(?:[%])?$/u', $collapsed) === 1;
    }

    public static function trimSpacing(string $text): string
    {
        return \preg_replace(
            '/^[\s\p{Zs}\x{200B}\x{2060}\x{FEFF}]+|[\s\p{Zs}\x{200B}\x{2060}\x{FEFF}]+$/u',
            '',
            $text
        ) ?? $text;
    }
}

   
                                                             
  
                                                                              
                                               
   
final class Structs_FixedOrderedKeysArrayOfStrings implements Structs_FixedOrderedKeysArrayOfStringsInterface
{
       
                                    
       
    private array $data;

    private string $keysHash;

       
                                           
       
    public function __construct(array $data)
    {
        $this->data = $data;

                                                            
        $this->keysHash = md5(serialize(array_keys($this->data)));

        $this->stringify();
    }

       
                                               
       
    private function stringify(): void
    {
        foreach ($this->data as $key => $value) {
            $this->data[$key] = (string) $value;
        }
    }

       
                                       
       
    public function asArray(): array
    {
        return $this->data;
    }

       
                                                                          
      
                                                
       
    public function setFromArray(array $newValues): void
    {
        foreach ($newValues as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                $this->data[$key] = (string) $value;
            }
        }

                                                              
        $this->stringify();
    }

    public function setFromSibling(
        Structs_FixedOrderedKeysArrayOfStringsInterface $sibling
    ): bool {
        if ($this->getKeysHash() !== $sibling->getKeysHash()) {
            return false;
        }

        $this->setFromArray($sibling->asArray());
        return true;
    }

    public function getKeysHash(): string
    {
        return $this->keysHash;
    }

    public function getValue(int|string $key): ?string
    {
        if (!array_key_exists($key, $this->data)) {
            return null;
        }

        return $this->data[$key];
    }

    public function makeCopy(): Structs_FixedOrderedKeysArrayOfStringsInterface
    {
        $copy = new self($this->data);

                                                        
        if ($this->getKeysHash() !== $copy->getKeysHash()) {
            throw new \RuntimeException(
                'Keys hash mismatch when copying Structs_FixedOrderedKeysArrayOfStrings.'
            );
        }

        return $copy;
    }
}


   
                                                                                      
  
                                         
                                         
                                          
  
                                                  
                                                         
   
final class Structs_TrimmableStringsArray implements Structs_TrimmableStringsArrayInterface
{
       
                                
                                                
                                               
                                                      
                                                                                  
       
    private const WS_CLASS = '\h\v\p{Zs}\x{00A0}\x{2007}\x{202F}';
    private const RE_LEAD  = '/^[' . self::WS_CLASS . ']*/u';
    private const RE_TAIL  = '/[' . self::WS_CLASS . ']*$/u';

    public function __construct(
        private Structs_FixedOrderedKeysArrayOfStringsInterface $rawStrings,
    ) {
        $this->calculateBefore();
        $this->calculateAfter();
        $this->calculateCores();
    }

    protected Structs_FixedOrderedKeysArrayOfStringsInterface $before;
    protected Structs_FixedOrderedKeysArrayOfStringsInterface $after;
    protected Structs_FixedOrderedKeysArrayOfStringsInterface $cores;

       
                                                                          
       
    protected function calculateBefore(): Structs_FixedOrderedKeysArrayOfStringsInterface
    {
        $raw    = $this->rawStrings->asArray();
        $before = [];

        foreach ($raw as $key => $text) {
            [$lead, , ] = self::splitWhitespaceShell((string) $text);
            $before[$key] = $lead;
        }

        $this->before = new Structs_FixedOrderedKeysArrayOfStrings($before);
        return $this->before;
    }

       
                                                                          
       
    protected function calculateAfter(): Structs_FixedOrderedKeysArrayOfStringsInterface
    {
        $raw   = $this->rawStrings->asArray();
        $after = [];

        foreach ($raw as $key => $text) {
            [ , , $tail] = self::splitWhitespaceShell((string) $text);
            $after[$key] = $tail;
        }

        $this->after = new Structs_FixedOrderedKeysArrayOfStrings($after);
        return $this->after;
    }

       
                                                                             
                                                                                      
       
    protected function calculateCores(): Structs_FixedOrderedKeysArrayOfStringsInterface
    {
        $raw   = $this->rawStrings->asArray();
        $cores = [];

        foreach ($raw as $key => $text) {
            [ , $core, ] = self::splitWhitespaceShell((string) $text);
            $cores[$key] = $core;
        }

        $this->cores = new Structs_FixedOrderedKeysArrayOfStrings($cores);
        return $this->cores;
    }

    public function getTrimmedStrings(): Structs_FixedOrderedKeysArrayOfStringsInterface
    {
        return $this->cores->makeCopy();
    }

    public function setTrimmedStrings(
        Structs_FixedOrderedKeysArrayOfStringsInterface $modifiedTrimmedParts
    ): bool {
        return $this->cores->setFromSibling($modifiedTrimmedParts);
    }

    public function getRebuiltArray(): Structs_FixedOrderedKeysArrayOfStringsInterface
    {
                                                    
                                         
        $reconstruction = $this->before->asArray();

        foreach ($reconstruction as $key => $value) {
            $reconstruction[$key] = $value . (string) $this->cores->getValue($key);
        }

        foreach ($reconstruction as $key => $value) {
            $reconstruction[$key] = $value . (string) $this->after->getValue($key);
        }

        $copy = $this->rawStrings->makeCopy();
        $copy->setFromArray($reconstruction);

        return $copy;
    }

       
                                                                         
                                                                            
      
                                                
       
    private static function splitWhitespaceShell(string $s): array
    {
        if ($s === '') {
            return ['', '', ''];
        }

                             
        $lead = '';
        if (preg_match(self::RE_LEAD, $s, $mLead)) {
            $lead = $mLead[0];
        }

                         
        $noLead = ($lead === '') ? $s : substr($s, strlen($lead));

                                                                                           
        $tail = '';
        if ($noLead !== '' && preg_match(self::RE_TAIL, $noLead, $mTail)) {
            $tail = $mTail[0];
        }

                              
        $coreLen = strlen($noLead) - strlen($tail);
        $core    = ($coreLen > 0) ? substr($noLead, 0, $coreLen) : '';

                                                                            
        if ($core === '' && ($lead !== '' || $tail !== '')) {
            if ($lead . $tail === $s) {
                return [$s, '', ''];
            }
        }

        return [$lead, $core, $tail];
    }
}
