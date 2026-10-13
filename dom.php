<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/contracts.php';

   
              
  
                                                    
  
                                                
                         
                               
                                  
                   
  
                                                                        
   

                                                                             
                              
                                                                             
                                                                      

                                                                             
         
                                                                             
         

                                                                             
        
                                                                             
         

                                                                             
          
                                                                             

                                                                              
final class Dom_ContextBoundaryRef
{
}

   
                                                                           
  
                                                                                 
                                 
   
final class Dom_InlinePhraseRef
{
       
                                                                                     
                                     
       
    public function __construct(private array $slots, private array $signature)
    {
    }

                                                                                   
    public function getSlots(): array
    {
        return $this->slots;
    }

                               
    public function getSignature(): array
    {
        return $this->signature;
    }
}

   
                                                
   
final class Dom_EngineNative implements Dom_EngineInterface
{
                            
    private const PHRASE_CONTAINERS = [
        'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'dt', 'dd',
        'figcaption', 'caption', 'th', 'td', 'label', 'button', 'legend', 'summary',
    ];

                            
    private const PHRASE_INLINE_ELEMENTS = [
        'a', 'abbr', 'b', 'bdi', 'bdo', 'cite', 'code', 'data', 'dfn', 'em', 'i',
        'kbd', 'mark', 'q', 'ruby', 'rt', 'rp', 's', 'samp', 'small', 'span',
        'strong', 'sub', 'sup', 'time', 'u', 'var', 'del', 'ins',
    ];

                            
    private const STRUCTURAL_ELEMENTS = [
        'html', 'body', 'main', 'div', 'section', 'article', 'aside', 'header',
        'footer', 'nav', 'ul', 'ol', 'dl', 'table', 'thead', 'tbody', 'tfoot',
        'tr', 'colgroup', 'form', 'fieldset', 'details', 'figure', 'blockquote',
        'address',
    ];

    private \DOMDocument $doc;

                                               
    private array $phraseParseCache = [];

    public function __construct(private bool $debugMode = false)
    {
    }

    public function loadFragment(string $html): void
    {
        $this->doc = new \DOMDocument('1.0', 'UTF-8');
        $this->doc->preserveWhiteSpace = true;
        $this->doc->formatOutput = false;
        $this->phraseParseCache = [];

                                                                                
        $prevUseErrors = \libxml_use_internal_errors(true);

                                                                                   
        $this->doc->loadHTML(
            '<?xml encoding="UTF-8"?>' . $html,
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD
        );

        \libxml_clear_errors();

                                              
        \libxml_use_internal_errors($prevUseErrors);

                                                                        
                                                                          
                                                                           
                                                                              
                                                                             
                                                                             
                                                                               
        foreach (\iterator_to_array($this->doc->childNodes) as $node) {
            if ($node instanceof \DOMProcessingInstruction && \strtolower($node->nodeName) === 'xml') {
                $this->doc->removeChild($node);
            }
        }
    }

    public function forEachTextNode(bool $translatePreTextarea, callable $callback): void
    {
        $xp = new \DOMXPath($this->doc);

                                               
                                                                      
        $skipAnc = $translatePreTextarea
            ? '(ancestor::script or ancestor::style or ancestor::head or ancestor::svg or ancestor::math)'
            : '(ancestor::script or ancestor::style or ancestor::head or ancestor::pre or ancestor::textarea or ancestor::svg or ancestor::math)';

                                                   
        $notranslateAnc = "ancestor::*[@translate='no' or @data-no-translate or contains(concat(' ', normalize-space(@class), ' '), ' notranslate ')]";

                                                                        
        $query = "//text()[not($skipAnc) and not($notranslateAnc)]";

        $nodes = $xp->query($query);
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $textNode) {
            $callback($textNode, (string)($textNode->nodeValue ?? ''));
        }
    }

    public function forEachBodyTranslationUnit(bool $translatePreTextarea, callable $callback): void
    {
        $lastWasBoundary = false;
        $emit = static function (mixed $nodeRef, string $text) use ($callback, &$lastWasBoundary): void {
            $isBoundary = $nodeRef instanceof Dom_ContextBoundaryRef;
            if ($isBoundary && $lastWasBoundary) {
                return;
            }
            $callback($nodeRef, $text);
            $lastWasBoundary = $isBoundary;
        };

        foreach (\iterator_to_array($this->doc->childNodes) as $child) {
            $this->walkTranslationUnits($child, $translatePreTextarea, $emit);
        }
    }

    private function walkTranslationUnits(\DOMNode $node, bool $translatePreTextarea, callable $emit): void
    {
        if ($node instanceof \DOMText) {
            $emit($node, (string)($node->nodeValue ?? ''));
            return;
        }
        if (!$node instanceof \DOMElement) {
            return;
        }

        $tag = \strtolower($node->tagName);
        if ($tag === 'head') {
            return;
        }
        if ($this->isProtectedElement($node)
            || \in_array($tag, ['script', 'style', 'svg', 'math'], true)
            || (!$translatePreTextarea && \in_array($tag, ['pre', 'textarea'], true))) {
            $emit(new Dom_ContextBoundaryRef(), '');
            return;
        }

        if (\in_array($tag, ['br', 'wbr', 'hr', 'img', 'input', 'source', 'track', 'iframe', 'object', 'embed', 'canvas', 'audio', 'video', 'select', 'picture'], true)
            || (!\in_array($tag, self::STRUCTURAL_ELEMENTS, true)
                && !\in_array($tag, self::PHRASE_INLINE_ELEMENTS, true)
                && !\in_array($tag, self::PHRASE_CONTAINERS, true))) {
            $emit(new Dom_ContextBoundaryRef(), '');
            foreach (\iterator_to_array($node->childNodes) as $child) {
                $this->walkTranslationUnits($child, $translatePreTextarea, $emit);
            }
            $emit(new Dom_ContextBoundaryRef(), '');
            return;
        }

        if (\in_array($tag, self::PHRASE_CONTAINERS, true) && !$this->isMenuPhraseCandidate($node)) {
            if (Structs_PureNumber::is((string)($node->textContent ?? ''))) {
                $emit(new Dom_ContextBoundaryRef(), '');
                return;
            }
            $phrase = $this->buildInlinePhrase($node);
            if ($phrase !== null) {
                $emit(
                    $phrase['all_numeric'] ? new Dom_ContextBoundaryRef() : $phrase['ref'],
                    $phrase['all_numeric'] ? '' : $phrase['canonical']
                );
                return;
            }
        }

        foreach (\iterator_to_array($node->childNodes) as $child) {
            $this->walkTranslationUnits($child, $translatePreTextarea, $emit);
        }
    }

    private function isProtectedElement(\DOMElement $element): bool
    {
        if (\strtolower(\trim($element->getAttribute('translate'))) === 'no'
            || $element->hasAttribute('data-no-translate')) {
            return true;
        }
        return \preg_match('/(?:^|\s)notranslate(?:\s|$)/u', $element->getAttribute('class')) === 1;
    }

    private function isMenuPhraseCandidate(\DOMElement $element): bool
    {
        for ($ancestor = $element; $ancestor instanceof \DOMElement; $ancestor = $ancestor->parentNode) {
            if (\strtolower($ancestor->tagName) === 'nav') {
                return true;
            }
            $role = \strtolower(\trim($ancestor->getAttribute('role')));
            if (\in_array($role, ['menu', 'menubar', 'navigation', 'tablist'], true)) {
                return true;
            }
        }
        if (\strtolower($element->tagName) !== 'li') {
            return false;
        }

        $meaningful = [];
        foreach (\iterator_to_array($element->childNodes) as $child) {
            if ($child instanceof \DOMComment
                || ($child instanceof \DOMText && \trim((string)$child->nodeValue) === '')) {
                continue;
            }
            $meaningful[] = $child;
        }
        return \count($meaningful) === 1
            && $meaningful[0] instanceof \DOMElement
            && \strtolower($meaningful[0]->tagName) === 'a';
    }

                                                                                        
    private function buildInlinePhrase(\DOMElement $container): ?array
    {
        $slots = [];
        $signature = [];
        $visible = '';
        $elementCount = 0;
        $numericOrdinal = 0;

        $encode = function (\DOMNode $parent) use (&$encode, &$slots, &$signature, &$visible, &$elementCount, &$numericOrdinal): ?string {
            $out = '';
            foreach (\iterator_to_array($parent->childNodes) as $child) {
                if ($child instanceof \DOMComment) {
                    return null;
                }
                if ($child instanceof \DOMText) {
                    $original = (string)($child->nodeValue ?? '');
                    $visible .= $original;
                    $token = null;
                    $encoded = $original;
                    if (Structs_PureNumber::is($original)) {
                        $token = '__DOLET_NUMBER_' . $numericOrdinal . '__';
                        $numericOrdinal++;
                        $encoded = $token;
                    }
                    $slots[] = ['node' => $child, 'original' => $original, 'numeric_token' => $token];
                    $signature[] = '#text';
                    $out .= \htmlspecialchars($encoded, \ENT_NOQUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
                    continue;
                }
                if (!$child instanceof \DOMElement) {
                    return null;
                }
                $tag = \strtolower($child->tagName);
                if (!\in_array($tag, self::PHRASE_INLINE_ELEMENTS, true) || $this->isProtectedElement($child)) {
                    return null;
                }
                $elementCount++;
                $signature[] = '<' . $tag . '>';
                $inner = $encode($child);
                if ($inner === null) {
                    return null;
                }
                $signature[] = '</' . $tag . '>';
                $out .= '<' . $tag . '>' . $inner . '</' . $tag . '>';
            }
            return $out;
        };

        $canonical = $encode($container);
        if ($canonical === null || $elementCount === 0 || \count($slots) < 2 || \trim($visible) === '') {
            return null;
        }
        return [
            'ref' => new Dom_InlinePhraseRef($slots, $signature),
            'canonical' => $canonical,
            'all_numeric' => Structs_PureNumber::is($visible),
        ];
    }

    public function isNodeTextValid(mixed $nodeRef, string $text): bool
    {
        return !$nodeRef instanceof Dom_InlinePhraseRef
            || $this->getParsedPhraseText($nodeRef, $text) !== null;
    }

    public function forEachDocumentTitleText(callable $callback): void
    {
        $xp = new \DOMXPath($this->doc);

                                                                              
                                                                             
        $asciiUpper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $asciiLower = 'abcdefghijklmnopqrstuvwxyz';
        $notranslateSelfOrAnc = "ancestor-or-self::*[translate(normalize-space(@translate), '$asciiUpper', '$asciiLower')='no' or @data-no-translate or contains(concat(' ', normalize-space(@class), ' '), ' notranslate ')]";
        $titles = $xp->query("(//head/title)[1][not($notranslateSelfOrAnc)]");
        if ($titles === false || $titles->length === 0) {
            return;
        }

        $title = $titles->item(0);
        if (!$title instanceof \DOMElement) {
            return;
        }

        $textNodes = $xp->query('.//text()', $title);
        if ($textNodes === false || $textNodes->length === 0) {
            return;
        }

        $nodeGroup = [];
        $text = '';
        foreach ($textNodes as $textNode) {
            if ($textNode instanceof \DOMText) {
                $nodeGroup[] = $textNode;
                $text .= (string)($textNode->nodeValue ?? '');
            }
        }

        $callback($nodeGroup, $text);
    }

    public function setNodeText(mixed $nodeRef, string $text): void
    {
        if ($nodeRef instanceof Dom_ContextBoundaryRef) {
            return;
        }

        if ($nodeRef instanceof Dom_InlinePhraseRef) {
            $values = $this->getParsedPhraseText($nodeRef, $text);
            if ($values === null) {
                return;
            }
            foreach ($nodeRef->getSlots() as $index => $slot) {
                $slot['node']->nodeValue = $values[$index];
            }
            return;
        }

        if (is_array($nodeRef)) {
            $first = true;
            foreach ($nodeRef as $textNode) {
                if (!$textNode instanceof \DOMText) {
                    continue;
                }
                $textNode->nodeValue = $first ? $text : '';
                $first = false;
            }
            return;
        }

        if ($nodeRef instanceof \DOMText) {
            $nodeRef->nodeValue = $text;
        }
    }

    public function restoreSearchQueryEcho(
        mixed $nodeRef,
        string $source,
        string $translated,
        string $query
    ): string {
        if ($query === '' || !$this->isSearchQueryEchoNode($nodeRef)) {
            return $translated;
        }

        $quotePairs = [
            ['“', '”'],
            ['«', '»'],
            ['‘', '’'],
            ['"', '"'],
        ];

        $sourceSlots = [];
        foreach ($quotePairs as [$open, $close]) {
            $offset = 0;
            while (($openAt = \strpos($source, $open, $offset)) !== false) {
                $contentAt = $openAt + \strlen($open);
                $closeAt = \strpos($source, $close, $contentAt);
                if ($closeAt === false) {
                    break;
                }
                $sourceSlots[] = [$contentAt, $closeAt, $open, $close];
                $offset = $closeAt + \strlen($close);
            }
        }

                                                                             
                                                                          
                                         
        if (\count($sourceSlots) !== 1) {
            return $translated;
        }

        [$sourceContentAt, $sourceCloseAt, $sourceOpen, $sourceClose] = $sourceSlots[0];
        if (\substr($source, $sourceContentAt, $sourceCloseAt - $sourceContentAt) !== $query) {
            return $translated;
        }
        foreach ($quotePairs as [$open, $close]) {
            if (\str_contains($query, $open) || \str_contains($query, $close)) {
                return $translated;
            }
        }

                                                                              
                                                                       
        $targetSlots = [];
        $offset = 0;
        while (($openAt = \strpos($translated, $sourceOpen, $offset)) !== false) {
            $contentAt = $openAt + \strlen($sourceOpen);
            $closeAt = \strpos($translated, $sourceClose, $contentAt);
            if ($closeAt === false) {
                break;
            }
            $targetSlots[] = [$contentAt, $closeAt];
            $offset = $closeAt + \strlen($sourceClose);
        }
        if (\count($targetSlots) !== 1) {
            return $translated;
        }

        [$contentAt, $closeAt] = $targetSlots[0];
        $targetContent = \substr($translated, $contentAt, $closeAt - $contentAt);
        foreach ($quotePairs as [$open, $close]) {
            if (\str_contains($targetContent, $open) || \str_contains($targetContent, $close)) {
                return $translated;
            }
        }
        return \substr($translated, 0, $contentAt)
            . $query
            . \substr($translated, $closeAt);
    }

    private function isSearchQueryEchoNode(mixed $nodeRef): bool
    {
        if (\is_array($nodeRef)) {
            foreach ($nodeRef as $node) {
                if ($node instanceof \DOMText
                    && $node->parentNode instanceof \DOMElement
                    && \strtolower($node->parentNode->tagName) === 'title') {
                    return true;
                }
            }
            return false;
        }

        if (!$nodeRef instanceof \DOMText || !$nodeRef->parentNode instanceof \DOMElement) {
            return false;
        }

        $parent = $nodeRef->parentNode;
        if (!\in_array(\strtolower($parent->tagName), ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
            return false;
        }

        return \preg_match('/(?:^|\s)wp-block-query-title(?:\s|$)/u', $parent->getAttribute('class')) === 1;
    }

                                    
    private function getParsedPhraseText(Dom_InlinePhraseRef $ref, string $text): ?array
    {
        $key = \spl_object_id($ref) . ':' . \hash('sha256', $text);
        if (!\array_key_exists($key, $this->phraseParseCache)) {
            $this->phraseParseCache[$key] = $this->parsePhraseText($ref, $text);
        }
        return $this->phraseParseCache[$key];
    }

                                    
    private function parsePhraseText(Dom_InlinePhraseRef $ref, string $text): ?array
    {
        $outerBefore = '';
        $debugPrefix = '';
        $debugSuffix = '';
        $outerAfter = '';
        if ($this->debugMode
            && \preg_match('/^([\s\p{Zs}\x{200B}\x{2060}\x{FEFF}]*)([✓✗] ➡️)(.*)(⬅️)([\s\p{Zs}\x{200B}\x{2060}\x{FEFF}]*)$/us', $text, $matches) === 1) {
            $outerBefore = $matches[1];
            $debugPrefix = $matches[2];
            $text = $matches[3];
            $debugSuffix = $matches[4];
            $outerAfter = $matches[5];
        }
        $text = $outerBefore . $text . $outerAfter;

        if (!$this->hasStrictlyBalancedMarkup($text)) {
            return null;
        }

        $parsed = new \DOMDocument('1.0', 'UTF-8');
        $parsed->preserveWhiteSpace = true;
        $previousErrors = \libxml_use_internal_errors(true);
        $loaded = $parsed->loadHTML(
            '<?xml encoding="UTF-8"?><div id="dolet-phrase-root">' . $text . '</div>',
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD | \LIBXML_NONET
        );
        \libxml_clear_errors();
        \libxml_use_internal_errors($previousErrors);
        if (!$loaded) {
            return null;
        }

        $xp = new \DOMXPath($parsed);
        $roots = $xp->query('//*[@id="dolet-phrase-root"]');
        $root = $roots === false ? null : $roots->item(0);
        if (!$root instanceof \DOMElement) {
            return null;
        }

        $signature = [];
        $values = [];
        $collect = function (\DOMNode $parent) use (&$collect, &$signature, &$values): void {
            foreach (\iterator_to_array($parent->childNodes) as $child) {
                if ($child instanceof \DOMText) {
                    $signature[] = '#text';
                    $values[] = (string)($child->nodeValue ?? '');
                } elseif ($child instanceof \DOMElement) {
                    $tag = \strtolower($child->tagName);
                    $signature[] = '<' . $tag . '>';
                    $collect($child);
                    $signature[] = '</' . $tag . '>';
                } else {
                    $signature[] = '#' . \strtolower($child->nodeName);
                }
            }
        };
        $collect($root);

        $slots = $ref->getSlots();
        if ($signature !== $ref->getSignature() || \count($values) !== \count($slots)) {
            return null;
        }

        $numericTokens = [];
        foreach ($slots as $slot) {
            if ($slot['numeric_token'] !== null) {
                $numericTokens[] = $slot['numeric_token'];
            }
        }
        foreach ($slots as $index => $slot) {
            $token = $slot['numeric_token'];
            if ($token !== null) {
                if (Structs_PureNumber::trimSpacing($values[$index]) !== $token) {
                    return null;
                }
                $values[$index] = $slot['original'];
                continue;
            }
            foreach ($numericTokens as $numericToken) {
                if (\str_contains($values[$index], $numericToken)) {
                    return null;
                }
            }
            if (\trim($slot['original']) !== '' && \trim($values[$index]) === '') {
                return null;
            }
        }

        $nonnumeric = [];
        foreach ($slots as $index => $slot) {
            if ($slot['numeric_token'] === null) {
                $nonnumeric[] = $index;
            }
        }
        if ($nonnumeric === []) {
            return null;
        }
        if ($debugPrefix !== '') {
            $first = $nonnumeric[0];
            if (\preg_match('/^(\s*)(.*)$/us', $values[$first], $firstParts) === 1) {
                $values[$first] = $firstParts[1] . $debugPrefix . $firstParts[2];
            }
            $last = $nonnumeric[\count($nonnumeric) - 1];
            if (\preg_match('/^(.*?)(\s*)$/us', $values[$last], $lastParts) === 1) {
                $values[$last] = $lastParts[1] . $debugSuffix . $lastParts[2];
            }
        }

        return $values;
    }

    private function hasStrictlyBalancedMarkup(string $markup): bool
    {
        $stack = [];
        $length = \strlen($markup);
        for ($offset = 0; $offset < $length;) {
            $open = \strpos($markup, '<', $offset);
            if ($open === false) {
                break;
            }

            $quote = null;
            $close = null;
            for ($i = $open + 1; $i < $length; $i++) {
                $char = $markup[$i];
                if ($quote !== null) {
                    if ($char === $quote) {
                        $quote = null;
                    }
                    continue;
                }
                if ($char === '"' || $char === "'") {
                    $quote = $char;
                } elseif ($char === '>') {
                    $close = $i;
                    break;
                }
            }
            if ($close === null || $quote !== null) {
                return false;
            }

            $inside = \trim(\substr($markup, $open + 1, $close - $open - 1));
            if ($inside === '' || $inside[0] === '!' || $inside[0] === '?') {
                return false;
            }
            if ($inside[0] === '/') {
                if (\preg_match('/^\/\s*([A-Za-z][A-Za-z0-9:-]*)\s*$/', $inside, $match) !== 1) {
                    return false;
                }
                $expected = \array_pop($stack);
                if ($expected === null || $expected !== \strtolower($match[1])) {
                    return false;
                }
            } else {
                if (\str_ends_with($inside, '/')
                    || \preg_match('/^([A-Za-z][A-Za-z0-9:-]*)(?:\s|$)/', $inside, $match) !== 1
                    || \str_contains($inside, '<')) {
                    return false;
                }
                $stack[] = \strtolower($match[1]);
            }
            $offset = $close + 1;
        }

        return $stack === [];
    }

    public function forEachAnchor(bool $translatePreTextarea, callable $callback): void
    {
        $xp = new \DOMXPath($this->doc);
                                                              
        $skipAnc = $translatePreTextarea
            ? '(ancestor::script or ancestor::style or ancestor::head or ancestor::svg or ancestor::math)'
            : '(ancestor::script or ancestor::style or ancestor::head or ancestor::pre or ancestor::textarea or ancestor::svg or ancestor::math)';

                                                   
        $notranslateAnc = "ancestor::*[@translate='no' or @data-no-translate or contains(concat(' ', normalize-space(@class), ' '), ' notranslate ')]";

                                                                                                
        $skipSelf = "(@data-dolet-preserve-l or @data-preserve-l or contains(concat(' ', normalize-space(@class), ' '), ' dolet-preserve-l '))";

                                                                                
        $query = "//a[@href][not($skipAnc) and not($notranslateAnc) and not($skipSelf)]";
        $nodes = $xp->query($query);
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $a) {
                                      
            $href = (string)$a->getAttribute('href');
            $callback($a, $href);
        }
    }

    public function setAnchorHref(mixed $nodeRef, string $href): void
    {
        if ($nodeRef instanceof \DOMElement && strtolower($nodeRef->nodeName) === 'a') {
            $nodeRef->setAttribute('href', $href);
        }
    }

    public function serializeFragment(): string
    {
        $out = $this->doc->saveHTML();
        if ($out === false) {
            return '';
        }

                                                                              
                                                                          
                                                                                
                                                                                  
                                 
        $clean = \preg_replace(
            '/<\?xml\s+encoding=(?:"|\')?utf-8(?:"|\')?\s*\?>\s*/i',
            '',
            $out
        );

        return ($clean !== null) ? $clean : $out;
    }
}

   
                                                                             
                                                                                 
   
final class Dom_Translatable implements Dom_TranslatableInterface
{
    private ?string $documentTitleStringsKeysHash = null;
    private ?Structs_TrimmableStringsArrayInterface $documentTitleTrimmables = null;

                                                  
    private array $documentTitleNodeMap = [];

    private ?string $translatableStringsKeysHash = null;
    private ?Structs_TrimmableStringsArrayInterface $trimmables = null;

                                                  
    private array $nodeMap = [];

    private Dom_EngineInterface $engine;

    private bool $mutated = false;

    public function __construct(
        private string $htmlFragment,
        private bool $translatePreTextarea = false,
        private bool $debugMode = false,
        private string $searchQueryEcho = ''
    ) {
        $this->engine = new Dom_EngineNative($this->debugMode);
        $this->engine->loadFragment($this->htmlFragment);
    }

    public function getOrderedTranslatableStrings(): ?Structs_FixedOrderedKeysArrayOfStringsInterface
    {
                                                                                    
        if ($this->mutated) {
            return null;
        }

                                                                                     
        if ($this->trimmables === null) {
            $stringsArray    = $this->getStringsArray();
            $this->trimmables = new Structs_TrimmableStringsArray($stringsArray);
        }

        $translatableStrings = $this->trimmables->getTrimmedStrings();
        $this->translatableStringsKeysHash = $translatableStrings->getKeysHash();

        return $translatableStrings;
    }

    public function getDocumentTitleTranslatableStrings(): ?Structs_FixedOrderedKeysArrayOfStringsInterface
    {
        if ($this->mutated) {
            return null;
        }

        if ($this->documentTitleTrimmables === null) {
            $raw = [];
            $this->documentTitleNodeMap = [];
            $i = 0;

            $this->engine->forEachDocumentTitleText(
                function ($node, string $text) use (&$raw, &$i): void {
                    $raw[$i] = $text;
                    $this->documentTitleNodeMap[$i] = $node;
                    $i++;
                }
            );

            $this->documentTitleTrimmables = new Structs_TrimmableStringsArray(
                new Structs_FixedOrderedKeysArrayOfStrings($raw)
            );
        }

        $translatableStrings = $this->documentTitleTrimmables->getTrimmedStrings();
        $this->documentTitleStringsKeysHash = $translatableStrings->getKeysHash();

        return $translatableStrings;
    }

    private function getStringsArray(): Structs_FixedOrderedKeysArrayOfStringsInterface
    {
        $raw = [];
        $this->nodeMap = [];
        $i = 0;

        $this->engine->forEachBodyTranslationUnit(
            $this->translatePreTextarea,
            function ($node, string $text) use (&$raw, &$i): void {
                                                                                
                $raw[$i] = $text;
                $this->nodeMap[$i] = $node;
                $i++;
            }
        );

        return new Structs_FixedOrderedKeysArrayOfStrings($raw);
    }

    public function substituteOrderedTranslatableStrings(
        Structs_FixedOrderedKeysArrayOfStringsInterface $substitutions
    ): bool {
        if ($this->trimmables === null || $this->translatableStringsKeysHash === null) {
            return false;                      
        }
        if ($substitutions->getKeysHash() !== $this->translatableStringsKeysHash) {
            return false;                 
        }
        $sourceCores = $this->trimmables->getTrimmedStrings();
        if (!$this->trimmables->setTrimmedStrings($substitutions)) {
            return false;                               
        }

        $rebuilt = $this->trimmables->getRebuiltArray();
        $invalidPhraseHashes = [];
        foreach ($this->nodeMap as $k => $node) {
            $text = (string)$rebuilt->getValue($k);
            if (!$this->engine->isNodeTextValid($node, $text)) {
                $invalidPhraseHashes[] = \hash('sha256', (string)$sourceCores->getValue($k));
            }
        }
        foreach ($this->nodeMap as $k => $node) {
            $text = (string)$rebuilt->getValue($k);
            if ($this->engine->isNodeTextValid($node, $text)) {
                if ($this->searchQueryEcho !== '') {
                    $text = $this->engine->restoreSearchQueryEcho(
                        $node,
                        (string)$sourceCores->getValue($k),
                        $text,
                        $this->searchQueryEcho
                    );
                }
                $this->engine->setNodeText($node, $text);
            }
        }
        if ($invalidPhraseHashes !== [] && \defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            \error_log('[Dolet WP] Rejected inline phrase translation(s): ' . \implode(',', $invalidPhraseHashes));
        }
        $this->mutated = true;
        return true;
    }

    public function substituteDocumentTitleTranslatableStrings(
        Structs_FixedOrderedKeysArrayOfStringsInterface $substitutions
    ): bool {
        if ($this->documentTitleTrimmables === null || $this->documentTitleStringsKeysHash === null) {
            return false;
        }
        if ($substitutions->getKeysHash() !== $this->documentTitleStringsKeysHash) {
            return false;
        }
        $sourceCores = $this->documentTitleTrimmables->getTrimmedStrings();
        if (!$this->documentTitleTrimmables->setTrimmedStrings($substitutions)) {
            return false;
        }

        $rebuilt = $this->documentTitleTrimmables->getRebuiltArray();
        foreach ($this->documentTitleNodeMap as $k => $node) {
            $text = (string)$rebuilt->getValue($k);
            if ($this->searchQueryEcho !== '') {
                $text = $this->engine->restoreSearchQueryEcho(
                    $node,
                    (string)$sourceCores->getValue($k),
                    $text,
                    $this->searchQueryEcho
                );
            }
            $this->engine->setNodeText($node, $text);
        }
        $this->mutated = true;
        return true;
    }

       
                                                                                                   
                   
                                                        
                                                               
                                                                            
                                               
                                                                                
                                                              
                                                                                  
      
                                                                                    
                                                              
       
    public function applyLanguageParameterToLinks(Language_OptionInterface $languageTarget): bool
    {
        try {
            $target   = $languageTarget->getCode();
            $siteUrl  = \function_exists('home_url') ? \home_url() : '';
            $siteHost = strtolower(ltrim((string)\wp_parse_url($siteUrl, \PHP_URL_HOST), ' '));
            $siteHost = (string)\preg_replace('/^www\./i', '', $siteHost);

            $assetExt = [
                'jpg','jpeg','png','gif','webp','svg',
                'pdf','zip','rar','7z','doc','docx','xls','xlsx','ppt','pptx',
                'mp3','mp4','webm','ogg','wav','mov','avi','mkv',
                'css','js','map','json','woff','woff2','ttf','otf','eot'
            ];

            $skipPaths = ['/wp-admin/', '/wp-login.php', '/wp-json/'];

            $changed = false;

            $this->engine->forEachAnchor(
                $this->translatePreTextarea,
                function ($aNode, string $href) use (
                    $target,
                    $siteHost,
                    $assetExt,
                    $skipPaths,
                    &$changed
                ): void {
                    $new = self::rewriteHrefWithLang($href, $target, $siteHost, $assetExt, $skipPaths);
                    if ($new !== null && $new !== $href) {
                        $this->engine->setAnchorHref($aNode, $new);
                        $changed = true;
                    }
                }
            );

            if ($changed) {
                $this->mutated = true;
            }
            return true;                                                 

        } catch (\Throwable $e) {
            if (\defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                \error_log('[Dolet WP] applyLanguageParameterToLinks error: ' . $e->getMessage());
            }
            return false;
        }
    }

       
                                                                         
               
                                        
                                                             
      
                                    
                                     
       
    private static function rewriteHrefWithLang(
        string $href,
        string $target,
        string $siteHost,
        array $assetExt,
        array $skipPaths
    ): ?string {
        $href = trim($href);
        if ($href === '') {
            return null;
        }

                                                   
        if ($href[0] === '#') {
            return null;
        }

                                 
        $lower = strtolower($href);
        if (
            str_starts_with($lower, 'mailto:')
            || str_starts_with($lower, 'tel:')
            || str_starts_with($lower, 'javascript:')
            || str_starts_with($lower, 'data:')
        ) {
            return null;
        }

                                                     
        $frag    = null;
        $hashPos = strpos($href, '#');
        if ($hashPos !== false) {
            $frag       = substr($href, $hashPos + 1);
            $hrefNoFrag = substr($href, 0, $hashPos);
        } else {
            $hrefNoFrag = $href;
        }

                      
        $base  = $hrefNoFrag;
        $query = '';
        $qPos  = strpos($hrefNoFrag, '?');
        if ($qPos !== false) {
            $base  = substr($hrefNoFrag, 0, $qPos);
            $query = substr($hrefNoFrag, $qPos + 1);
        }

                                        
        $isProtocolRelative = str_starts_with($base, '//');
        $isAbsolute         = $isProtocolRelative
            || preg_match('#^[a-z][a-z0-9+\-.]*://#i', $base) === 1;

        $host = '';
        $path = $base;
        if ($isAbsolute) {
            $tmp  = wp_parse_url($isProtocolRelative ? ('http:' . $base) : $base);
            $host = strtolower(preg_replace('/^www\./i', '', (string)($tmp['host'] ?? '')));
            $path = (string)($tmp['path'] ?? '/');
        } else {
                                                                        
            $path = ($base === '') ? '' : $base;
        }

                                      
        $isInternal = !$isAbsolute || ($host !== '' && $host === $siteHost);
        if (!$isInternal) {
            return null;
        }

                                      
        foreach ($skipPaths as $needle) {
            if ($needle !== '' && str_contains($path, $needle)) {
                return null;
            }
        }

                                     
        $ext = strtolower(pathinfo(wp_parse_url($base, PHP_URL_PATH) ?? $path, PATHINFO_EXTENSION));
        if ($ext !== '' && in_array($ext, $assetExt, true)) {
            return null;
        }

                             
        $params = [];
        if ($query !== '') {
                                                             
            parse_str($query, $params);
        }

        $paramName = Dom_Constants::LANGUAGE_URL_PARAMETER_NAME;

                                                      
                                                             
                                                                            
        if (array_key_exists($paramName, $params)) {
            $existing = (string)$params[$paramName];
            if ($existing !== '') {
                if (strcasecmp($existing, $target) === 0) {
                    return null;                                 
                }
                return null;                                              
            }
                                                                     
        }

                                                                        
        $params[$paramName] = $target;
        $newQuery           = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

                       
        $rebuilt = $base;
        if ($newQuery !== '') {
            $rebuilt .= '?' . $newQuery;
        }
        if ($frag !== null && $frag !== '') {
            $rebuilt .= '#' . $frag;
        }
        return $rebuilt;
    }

    public function getHtml(): string
    {
        return $this->engine->serializeFragment();
    }
}

   
                                                                    
  
                                                                    
                               
  
             
                                                                                   
                                                                           
                                                                          
                                                                            
                                                                                     
   
final class Dom_TranslatedStamp
{
       
                                                                                
                                                                                     
                                     
       
    public static function isStampedAs(string $html, Language_OptionInterface $language): bool
    {
        $prefix = \defined(Dom_Constants::class . '::PAGE_STAMP_PREFIX')
            ? Dom_Constants::PAGE_STAMP_PREFIX
            : 'DOLET_PAGE:';                                        

        $pattern = '/<!--\s*' . \preg_quote($prefix, '/') . '([A-Za-z0-9\-_]+)\s*-->/';
        if (!\preg_match_all($pattern, $html, $m) || empty($m[1])) {
            return false;
        }

                                                                                 
        $foundLang = (string) end($m[1]);
        $target    = (string) $language->getCode();

        return \strcasecmp($foundLang, $target) === 0;
    }

       
                                                
                                                 
                                                                                                            
                                                                           
       
    public static function stamp(string $html, Language_OptionInterface $language): string
    {
        $prefix = \defined(Dom_Constants::class . '::PAGE_STAMP_PREFIX')
            ? Dom_Constants::PAGE_STAMP_PREFIX
            : 'DOLET_PAGE:';                

        $langCode = (string) $language->getCode();

                                                                        
        $removePattern = '/<!--\s*' . \preg_quote($prefix, '/') . '[A-Za-z0-9\-_]+\s*-->/';
        $cleanHtml     = \preg_replace($removePattern, '', $html);

        if ($cleanHtml === null) {
                                                                                          
            return $html . '<!-- ' . $prefix . $langCode . ' -->';
        }

                                                                              
        $count  = 0;
        $result = \preg_replace(
            '/<\/body>/i',
            '<!-- ' . $prefix . $langCode . ' --></body>',
            $cleanHtml,
            1,
            $count
        );

        if ($result !== null && $count === 1) {
            return $result;
        }

                                              
        return $cleanHtml . '<!-- ' . $prefix . $langCode . ' -->';
    }
}

   
                                                                          
  
                                                                            
                                                                             
                                                                   
   
final class Dom_DocumentLanguage
{
                                                                                     
    private const RTL_BASE_LANGUAGES = [
        'ar', 'ckb', 'dv', 'fa', 'he', 'nqo', 'ps', 'sd', 'syr', 'ug', 'ur', 'yi',
    ];

                                                                                       
    private const RTL_SCRIPTS = [
        'adlm', 'arab', 'armi', 'avst', 'chrs', 'elym', 'gara', 'hatr', 'hebr', 'hung',
        'khar', 'lydi', 'mand', 'mani', 'mend', 'merc', 'mero', 'narb', 'nbat',
        'nkoo', 'orkh', 'palm', 'phli', 'phlp', 'phnx', 'prti', 'rohg', 'samr',
        'ougr', 'sarb', 'sogd', 'sogo', 'syrc', 'thaa', 'yezi',
    ];

    public static function apply(string $html, string $languageCode): string
    {
        $languageCode = trim(str_replace('_', '-', $languageCode));
        if ($html === '' || $languageCode === '') {
            return $html;
        }

                                                                               
                                                                              
                                         
        $result = preg_replace_callback(
            '/\A(?<prefix>(?:\xEF\xBB\xBF)?\s*(?:(?:<!doctype\b[^>]*>|<!--(?:(?!-->).)*-->)\s*)*)<html\b(?<attributes>(?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/is',
            static function (array $match) use ($languageCode): string {
                $attributes = (string) $match['attributes'];
                $attributes = (string) preg_replace(
                    '/\s+(?:lang|dir)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/i',
                    '',
                    $attributes
                );
                $attributes = rtrim($attributes);

                return (string) $match['prefix'] . '<html' . $attributes
                    . ' lang="' . esc_attr($languageCode) . '"'
                    . ' dir="' . self::directionFor($languageCode) . '">';
            },
            $html,
            1
        );

        return $result ?? $html;
    }

    public static function directionFor(string $languageCode): string
    {
        $parts = explode('-', str_replace('_', '-', trim($languageCode)));
        $base  = strtolower($parts[0]);

        $script = null;
        foreach (array_slice($parts, 1) as $part) {
                                                                             
                                                                             
            if (strlen($part) === 1) {
                break;
            }
            if (strlen($part) === 4 && ctype_alpha($part)) {
                $script = strtolower($part);
                break;
            }
        }

        $direction = $script !== null
            ? (in_array($script, self::RTL_SCRIPTS, true) ? 'rtl' : 'ltr')
            : (in_array($base, self::RTL_BASE_LANGUAGES, true) ? 'rtl' : 'ltr');

                                   
        $filtered = apply_filters('dolet_document_direction', $direction, $languageCode);
        return in_array($filtered, ['ltr', 'rtl'], true) ? $filtered : $direction;
    }
}

   
                                                                          
  
            
                                                           
                                                                         
                                                                              
   
final class Dom_HreflangInjector
{
       
                                            
      
                                                      
                                                                                
                                                                   
                                                                                          
       
    public static function inject(
        string $html,
        string $currentCode,
        array $allTargetCodes,
        string $currentUrl
    ): string {
                                                                             
                                                  
        if (stripos($html, '</head>') === false) {
            return $html;
        }

        $param = Dom_Constants::LANGUAGE_URL_PARAMETER_NAME;

                                                                            
                                                                          
                                                                               
        $canonicalBase = self::existingCanonicalUrl($html) ?? self::withoutTrackingArguments($currentUrl);

                                                                                             
        $html = (string) \preg_replace(
            '/<link\s[^>]*data-dolet-hreflang[^>]*>\s*/i',
            '',
            $html
        );

        $links = '';

                                                         
        $defaultUrl = \remove_query_arg($param, $canonicalBase);
        $links .= '<link rel="alternate" hreflang="x-default" href="'
            . \esc_url($defaultUrl) . '" data-dolet-hreflang />' . "\n";

                                              
        foreach ($allTargetCodes as $code) {
            $url = \add_query_arg($param, $code, $defaultUrl);
            $links .= '<link rel="alternate" hreflang="'
                . \esc_attr($code) . '" href="'
                . \esc_url($url) . '" data-dolet-hreflang />' . "\n";
        }

                                                                                 
        $canonicalUrl = strcasecmp($currentCode, Language_Constants::ORIGINAL) === 0
            ? $defaultUrl
            : \add_query_arg($param, $currentCode, $defaultUrl);
        $links .= '<link rel="canonical" href="'
            . \esc_url($canonicalUrl) . '" data-dolet-hreflang />' . "\n";

                                                                                               
        $html = (string) preg_replace_callback(
            '/<link\b[^>]*>\s*/i',
            static fn (array $match): string => self::linkTagIsCanonical($match[0]) ? '' : $match[0],
            $html
        );

                                
        $count  = 0;
        $result = \preg_replace(
            '/<\/head>/i',
            $links . '</head>',
            $html,
            1,
            $count
        );

        return ($result !== null && $count === 1) ? $result : $html;
    }

    private static function existingCanonicalUrl(string $html): ?string
    {
        if (!preg_match_all('/<link\b[^>]*>/i', $html, $matches)) {
            return null;
        }

        foreach ($matches[0] as $tag) {
            if (!preg_match('/\brel\s*=\s*(["\'])(?<rel>.*?)\1/i', $tag, $relMatch)) {
                continue;
            }
            $relations = preg_split('/\s+/', strtolower(trim((string) $relMatch['rel']))) ?: [];
            if (!in_array('canonical', $relations, true)) {
                continue;
            }
            if (preg_match('/\bhref\s*=\s*(["\'])(?<href>.*?)\1/i', $tag, $hrefMatch)) {
                $url = html_entity_decode((string) $hrefMatch['href'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $url !== '' ? $url : null;
            }
        }

        return null;
    }

    private static function linkTagIsCanonical(string $tag): bool
    {
        if (!preg_match('/\brel\s*=\s*(["\'])(?<rel>.*?)\1/i', $tag, $match)) {
            return false;
        }
        $relations = preg_split('/\s+/', strtolower(trim((string) $match['rel']))) ?: [];
        return in_array('canonical', $relations, true);
    }

    private static function withoutTrackingArguments(string $url): string
    {
        $parts = wp_parse_url($url);
        if (!is_array($parts)) {
            return $url;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        foreach (array_keys($query) as $key) {
            $lower = strtolower((string) $key);
            if (
                str_starts_with($lower, 'utm_')
                || in_array($lower, [
                    '_ga', 'dclid', 'fbclid', 'gclid', 'mc_cid', 'mc_eid',
                    'msclkid', 'ref', 'source',
                ], true)
            ) {
                unset($query[$key]);
            }
        }

        $authority = '';
        if (isset($parts['scheme'])) {
            $authority .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $authority .= $parts['user'];
            if (isset($parts['pass'])) {
                $authority .= ':' . $parts['pass'];
            }
            $authority .= '@';
        }
        $authority .= $parts['host'] ?? '';
        if (isset($parts['port'])) {
            $authority .= ':' . $parts['port'];
        }

        $clean = $authority . ($parts['path'] ?? '');
        if ($query !== []) {
            $clean .= '?' . http_build_query($query);
        }
        if (isset($parts['fragment'])) {
            $clean .= '#' . $parts['fragment'];
        }
        return $clean;
    }
}


   
                                                                  
   
final class Dom_TranslatedStampService implements Dom_TranslatedStampInterface
{
    public function isStampedAs(string $html, Language_OptionInterface $language): bool
    {
        return Dom_TranslatedStamp::isStampedAs($html, $language);
    }

    public function stamp(string $html, Language_OptionInterface $language): string
    {
        return Dom_TranslatedStamp::stamp($html, $language);
    }
}
