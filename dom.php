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

   
                                                                         
                                   
                                                     
               
             
                 
                  
                       
                                                                                                                                  
                        
                      
    
   
final class Dom_SourceTokenizer
{
    private const MAX_SOURCE_BYTES = 4194304;
    private const MAX_TOKENS = 16384;
    private const MAX_ATTRIBUTES_PER_TAG = 1024;
    private const MAX_OPEN_ELEMENTS = 256;
    private const ESTIMATED_TOKEN_BYTES = 1536;

                            
    private const PROTECTED_HTML_ELEMENTS = [
        'script', 'style', 'title', 'textarea', 'xmp', 'iframe',
        'noembed', 'noframes', 'noscript', 'plaintext',
    ];

                            
    private const VOID_HTML_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

                                                                  
    private const HEAD_ELEMENTS = [
        'base', 'basefont', 'bgsound', 'link', 'meta', 'title', 'noscript',
        'noframes', 'style', 'script', 'template',
    ];

                            
    private const SVG_INTEGRATION_POINTS = ['foreignobject', 'desc', 'title'];

                            
    private const MATH_INTEGRATION_POINTS = ['annotation-xml', 'mi', 'mo', 'mn', 'ms', 'mtext'];

                                                                             
    private const SVG_ELEMENTS = [
        'a', 'animate', 'animatemotion', 'animatetransform', 'circle', 'clippath',
        'defs', 'desc', 'ellipse', 'feblend', 'fecolormatrix', 'fecomponenttransfer',
        'fecomposite', 'fefunca', 'fefuncb', 'fefuncg', 'fefuncr', 'fegaussianblur',
        'femerge', 'femergenode', 'feoffset', 'feturbulence', 'filter',
        'foreignobject', 'g', 'image', 'line',
        'lineargradient', 'marker', 'mask', 'metadata', 'path', 'pattern', 'polygon',
        'polyline', 'radialgradient', 'rect', 'script', 'stop', 'style', 'svg',
        'switch', 'symbol', 'text', 'textpath', 'title', 'tspan', 'use', 'view',
    ];

                                                                            
    private const MATH_ELEMENTS = [
        'annotation', 'annotation-xml', 'math', 'menclose', 'merror', 'mfenced',
        'mfrac', 'mi', 'mmultiscripts', 'mn', 'mo', 'mover', 'mpadded', 'mphantom',
        'mroot', 'mrow', 'ms', 'mspace', 'msqrt', 'mstyle', 'msub', 'msubsup',
        'msup', 'mtable', 'mtd', 'mtext', 'mtr', 'munder', 'munderover', 'semantics', 'title',
    ];

                            
    private const FOREIGN_BREAKOUT_ELEMENTS = [
        'b', 'big', 'blockquote', 'body', 'br', 'center', 'code', 'dd', 'div',
        'dl', 'dt', 'em', 'embed', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head',
        'hr', 'i', 'img', 'li', 'listing', 'menu', 'meta', 'nobr', 'ol', 'p',
        'pre', 'ruby', 's', 'small', 'span', 'strong', 'strike', 'sub', 'sup',
        'table', 'tt', 'u', 'ul', 'var',
    ];

                                         
    public static function tokenize(string $html): ?array
    {
        $result = self::tokenizeResult($html);
        return \is_array($result) ? $result : null;
    }

                                                                
    public static function tokenizeResult(string $html): array|Core_HtmlRejectionInterface
    {
        $length = \strlen($html);
        $tokenBudget = self::runtimeTokenBudget();
        if ($length > self::MAX_SOURCE_BYTES) {
            return Core_HtmlRejection::Bytes;
        }

        $dryRun = null;
        $reason = self::tokenizePass($html, $tokenBudget, $dryRun);
        if ($reason !== null) {
            return $reason;
        }
                                             
        $tokens = [];
        $reason = self::tokenizePass($html, $tokenBudget, $tokens);
        if ($reason !== null) {
            return $reason;
        }
        return $tokens;
    }

       
                                                                                
                                                                                
                                                                              
      
                                                    
       
    private static function tokenizePass(string $html, int $tokenBudget, ?array &$tokens): ?Core_HtmlRejectionInterface
    {
                                                                              
        $stack = [];
        $cursor = 0;
        $tokenCount = 0;
        $length = \strlen($html);
        $templateDepth = 0;
        $foreignDepth = 0;

        while ($cursor < $length) {
            if ($tokenCount >= $tokenBudget) {
                return $tokenBudget < self::MAX_TOKENS ? Core_HtmlRejection::Memory : Core_HtmlRejection::Units;
            }
            $open = \strpos($html, '<', $cursor);
            if ($open === false) {
                self::emitToken($tokens, $tokenCount, 'text', $cursor, $length, '', false, [], $templateDepth, $foreignDepth);
                break;
            }
            if ($open > $cursor) {
                self::emitToken($tokens, $tokenCount, 'text', $cursor, $open, '', false, [], $templateDepth, $foreignDepth);
            }

            $commentEnd = self::commentEnd($html, $open, $foreignDepth > 0);
            if ($commentEnd !== null) {
                self::emitToken($tokens, $tokenCount, 'comment', $open, $commentEnd, '', false, [], $templateDepth, $foreignDepth);
                $cursor = $commentEnd;
                continue;
            }
            if (\substr($html, $open, 4) === '<!--'
                || ($foreignDepth > 0 && \substr($html, $open, 9) === '<![CDATA[')) {
                return Core_HtmlRejection::Lexical;
            }
            if (\substr($html, $open, 2) === '<!' || \substr($html, $open, 2) === '<?') {
                $end = self::whitelistedDeclarationEnd($html, $open);
                if ($end === null) {
                    return Core_HtmlRejection::Declaration;
                }
                self::emitToken($tokens, $tokenCount, 'declaration', $open, $end, '', false, [], $templateDepth, $foreignDepth);
                $cursor = $end;
                continue;
            }

            if (\substr($html, $open, 2) === '</'
                && isset($html[$open + 2])
                && !self::isAsciiLetter($html[$open + 2])) {
                $end = \strpos($html, '>', $open + 2);
                if ($end === false) {
                    return Core_HtmlRejection::Lexical;
                }
                self::emitToken($tokens, $tokenCount, 'comment', $open, $end + 1, '', false, [], $templateDepth, $foreignDepth);
                $cursor = $end + 1;
                continue;
            }

            $tag = self::tagAt($html, $open, $tokens !== null);
            if ($tag instanceof Core_HtmlRejectionInterface) {
                return $tag;
            }
            if ($tag === false) {
                self::emitToken($tokens, $tokenCount, 'text', $open, $open + 1, '', false, [], $templateDepth, $foreignDepth);
                $cursor = $open + 1;
                continue;
            }
            if ($tag['attribute_count'] > $tokenBudget - $tokenCount) {
                return $tokenBudget < self::MAX_TOKENS ? Core_HtmlRejection::Memory : Core_HtmlRejection::Units;
            }
            $tokenCount += $tag['attribute_count'];

            $name = $tag['name'];
            $closing = $tag['closing'];
            $currentForeign = $foreignDepth > 0;
            $parent = $stack === [] ? null : $stack[\count($stack) - 1];
            $integrationPoint = $currentForeign && $parent !== null && $parent['foreign']
                && self::isForeignIntegrationPoint($parent['name'], $stack);
            if ($currentForeign && !self::isWhitelistedForeignName($name, $stack)) {
                if ($integrationPoint || \in_array($name, self::FOREIGN_BREAKOUT_ELEMENTS, true)
                    || ($name === 'font' && !$closing && $tag['foreign_font_breakout'])) {
                    return Core_HtmlRejection::ForeignStructure;
                }
                for ($index = \count($stack) - 1; $index >= 0; $index--) {
                    if ($stack[$index]['name'] === 'svg') {
                        return Core_HtmlRejection::SvgVocabulary;
                    }
                    if ($stack[$index]['name'] === 'math') {
                        return Core_HtmlRejection::MathVocabulary;
                    }
                }
                return Core_HtmlRejection::ForeignStructure;
            }
            $foreignFontBreakout = !$closing && $name === 'font' && $tag['foreign_font_breakout'];
            if ($currentForeign && $closing && \in_array($name, ['p', 'br'], true)) {
                return Core_HtmlRejection::ForeignStructure;
            }
            if (!$closing && $currentForeign
                && ($integrationPoint
                    || \in_array($name, self::FOREIGN_BREAKOUT_ELEMENTS, true)
                    || $foreignFontBreakout)) {
                return Core_HtmlRejection::ForeignStructure;
            }
            $foreign = $currentForeign || $name === 'svg' || $name === 'math';
            $selfClosing = $tag['self_closing']
                && ($foreign || \in_array($name, self::VOID_HTML_ELEMENTS, true));
            self::emitToken(
                $tokens,
                $tokenCount,
                'tag',
                $open,
                $tag['end'],
                $name,
                $closing,
                $closing ? [] : $tag['attributes'],
                $templateDepth,
                $foreignDepth,
                $selfClosing
            );

            if ($closing) {
                self::closeStack($stack, $name, $templateDepth, $foreignDepth);
                $cursor = $tag['end'];
                continue;
            }

            if (!$selfClosing && !$foreign && \in_array($name, self::PROTECTED_HTML_ELEMENTS, true)) {
                if ($name === 'plaintext') {
                    return Core_HtmlRejection::Plaintext;
                }
                $rawEnd = $name === 'script'
                    ? self::scriptEnd($html, $tag['end'])
                    : self::rawEnd($html, $name, $tag['end']);
                if ($rawEnd instanceof Core_HtmlRejectionInterface) {
                    return $rawEnd;
                }
                if ($rawEnd === null) {
                    return Core_HtmlRejection::Raw;
                }
                if ($rawEnd['start'] > $tag['end']) {
                    self::emitToken(
                        $tokens,
                        $tokenCount,
                        'raw',
                        $tag['end'],
                        $rawEnd['start'],
                        $name,
                        false,
                        [],
                        $templateDepth,
                        $foreignDepth
                    );
                }
                self::emitToken(
                    $tokens,
                    $tokenCount,
                    'tag',
                    $rawEnd['start'],
                    $rawEnd['end'],
                    $name,
                    true,
                    [],
                    $templateDepth,
                    $foreignDepth
                );
                $cursor = $rawEnd['end'];
                continue;
            }

            if (!$selfClosing && !\in_array($name, self::VOID_HTML_ELEMENTS, true)) {
                if (\count($stack) >= self::MAX_OPEN_ELEMENTS) {
                    return Core_HtmlRejection::Depth;
                }
                $isTemplate = !$foreign && $name === 'template';
                $stack[] = [
                    'name' => $name,
                    'foreign' => $foreign,
                    'template' => $isTemplate,
                ];
                $templateDepth += $isTemplate ? 1 : 0;
                $foreignDepth += $foreign ? 1 : 0;
            }
            $cursor = $tag['end'];
        }

        if ($tokenCount > $tokenBudget) {
            return $tokenBudget < self::MAX_TOKENS ? Core_HtmlRejection::Memory : Core_HtmlRejection::Units;
        }
        return $templateDepth > 0 || $foreignDepth > 0 ? Core_HtmlRejection::Unfinished : null;
    }

       
                                               
                                                                                                                      
       
    public static function documentBounds(string $html, array $tokens): ?array
    {
        $result = self::documentBoundsResult($html, $tokens);
        return \is_array($result) ? $result : null;
    }

                                                
                                                                                                                                             
       
    public static function documentBoundsResult(string $html, array $tokens): array|Core_HtmlRejectionInterface
    {
        $sawDocumentTag = false;
        $htmlOpen = false;
        $htmlClose = false;
        $headOpenEnd = null;
        $headCloseStart = null;
        $bodyOpenEnd = null;
        $bodyCloseStart = null;
        $section = 'before-head';
        $significantBeforeHtml = false;
                                           
        $bodyStack = [];

        foreach ($tokens as $token) {
            if ($token['template_depth'] !== 0 || $token['foreign_depth'] !== 0) {
                continue;
            }
            $structural = $token['type'] === 'tag'
                && \in_array($token['name'], ['html', 'head', 'body'], true);
            if ($section === 'body' && $token['type'] === 'tag' && !$structural
                && !\in_array($token['name'], ['template', 'svg', 'math'], true)) {
                if ($token['closing']) {
                    $matching = \array_search($token['name'], \array_reverse($bodyStack, true), true);
                    if ($matching !== false) {
                        $bodyStack = \array_slice($bodyStack, 0, (int) $matching);
                    }
                } elseif (!$token['self_closing']
                    && !\in_array($token['name'], self::VOID_HTML_ELEMENTS, true)) {
                    $bodyStack[] = $token['name'];
                }
            }
            if (!$structural) {
                if (!$htmlOpen) {
                    if (\in_array($token['type'], ['comment', 'declaration'], true)
                        || ($token['type'] === 'text'
                            && self::isIgnorableDocumentPrefix(
                                \substr($html, $token['start'], $token['end'] - $token['start']),
                                $token['start']
                            ))) {
                        continue;
                    }
                    $significantBeforeHtml = true;
                    continue;
                }
                if ($section === 'body' || \in_array($token['type'], ['comment', 'declaration'], true)) {
                    continue;
                }
                if ($token['type'] === 'text'
                    && self::isHtmlSpaceOnly(
                        \substr($html, $token['start'], $token['end'] - $token['start'])
                    )) {
                    continue;
                }
                if ($section === 'head'
                    && (($token['type'] === 'tag' && \in_array($token['name'], self::HEAD_ELEMENTS, true))
                        || ($token['type'] === 'raw' && \in_array($token['name'], self::HEAD_ELEMENTS, true)))) {
                    continue;
                }
                return Core_HtmlRejection::Layout;
            }
            $sawDocumentTag = true;
            $name = $token['name'];
            if ($name === 'html') {
                if (!$token['closing']) {
                    if ($significantBeforeHtml) {
                        return Core_HtmlRejection::DocumentPrefix;
                    }
                    if ($htmlOpen || $htmlClose
                        || $headOpenEnd !== null || $bodyOpenEnd !== null) {
                        return Core_HtmlRejection::Layout;
                    }
                    $htmlOpen = true;
                    continue;
                }
                if (!$htmlOpen || $htmlClose || $bodyCloseStart === null) {
                    return Core_HtmlRejection::Layout;
                }
                $htmlClose = true;
                continue;
            }
            if ($name === 'head') {
                if (!$token['closing']) {
                    if (!$htmlOpen || $htmlClose || $headOpenEnd !== null || $bodyOpenEnd !== null) {
                        return Core_HtmlRejection::Layout;
                    }
                    $headOpenEnd = $token['end'];
                    $section = 'head';
                    continue;
                }
                if ($headOpenEnd === null || $headCloseStart !== null || $bodyOpenEnd !== null) {
                    return Core_HtmlRejection::Layout;
                }
                $headCloseStart = $token['start'];
                $section = 'after-head';
                continue;
            }
            if (!$token['closing']) {
                if ($htmlClose || $bodyOpenEnd !== null
                    || ($headOpenEnd !== null && $headCloseStart === null)) {
                    return Core_HtmlRejection::Layout;
                }
                $bodyOpenEnd = $token['end'];
                $section = 'body';
                continue;
            }
            if ($bodyOpenEnd === null || $bodyCloseStart !== null || $htmlClose || $bodyStack !== []) {
                return Core_HtmlRejection::Layout;
            }
            $bodyCloseStart = $token['start'];
        }

        if (!$sawDocumentTag) {
            return [
                'full' => false,
                'head_open_end' => null,
                'head_close_start' => null,
                'body_open_end' => null,
                'body_close_start' => null,
            ];
        }
        if (!$htmlOpen) {
            if ($headOpenEnd !== null || $headCloseStart !== null
                || $bodyOpenEnd === null || $bodyCloseStart === null) {
                return Core_HtmlRejection::Layout;
            }
            return [
                'full' => false,
                'head_open_end' => null,
                'head_close_start' => null,
                'body_open_end' => $bodyOpenEnd,
                'body_close_start' => $bodyCloseStart,
            ];
        }
        if (!$htmlClose || $bodyOpenEnd === null || $bodyCloseStart === null
            || (($headOpenEnd === null) !== ($headCloseStart === null))) {
            return Core_HtmlRejection::Layout;
        }
        return [
            'full' => true,
            'head_open_end' => $headOpenEnd,
            'head_close_start' => $headCloseStart,
            'body_open_end' => $bodyOpenEnd,
            'body_close_start' => $bodyCloseStart,
        ];
    }

    private static function runtimeTokenBudget(): int
    {
        $rawLimit = \trim((string) \ini_get('memory_limit'));
        $unit = \strtolower(\substr($rawLimit, -1));
        $number = (int) $rawLimit;
        $multiplier = match ($unit) {
            'g' => 1073741824,
            'm' => 1048576,
            'k' => 1024,
            default => 1,
        };
        if ($number <= 0) {
            return self::MAX_TOKENS;
        }
        $limit = $number * $multiplier;
        $reserve = \max(8388608, intdiv($limit, 4));
        $available = $limit - \memory_get_usage(true) - $reserve;
        if ($available <= 0) {
            return 0;
        }
        return \min(self::MAX_TOKENS, intdiv($available, self::ESTIMATED_TOKEN_BYTES));
    }

       
                                                    
                                                                                                           
       
    private static function emitToken(
        ?array &$tokens,
        int &$tokenCount,
        string $type,
        int $start,
        int $end,
        string $name,
        bool $closing,
        array $attributes,
        int $templateDepth,
        int $foreignDepth,
        bool $selfClosing = false
    ): void {
        $tokenCount++;
        if ($tokens === null) {
            return;
        }
        $tokens[] = [
            'type' => $type,
            'start' => $start,
            'end' => $end,
            'name' => $name,
            'closing' => $closing,
            'self_closing' => $selfClosing,
            'attributes' => $attributes,
            'template_depth' => $templateDepth,
            'foreign_depth' => $foreignDepth,
        ];
    }

                                                                            
    private static function closeStack(array &$stack, string $name, int &$templateDepth, int &$foreignDepth): void
    {
        for ($index = \count($stack) - 1; $index >= 0; $index--) {
            if ($stack[$index]['name'] === $name) {
                while (\count($stack) > $index) {
                    $closed = \array_pop($stack);
                    $templateDepth -= $closed['template'] ? 1 : 0;
                    $foreignDepth -= $closed['foreign'] ? 1 : 0;
                }
                return;
            }
            if ($stack[$index]['template']) {
                return;
            }
        }
    }

    private static function whitelistedDeclarationEnd(string $html, int $start): ?int
    {
        $doctype = \substr($html, $start, 15);
        if (\strcasecmp($doctype, '<!doctype html>') === 0) {
            return $start + 15;
        }
        return null;
    }

                                                                            
    private static function isForeignIntegrationPoint(string $name, array $stack): bool
    {
        for ($index = \count($stack) - 1; $index >= 0; $index--) {
            if ($stack[$index]['name'] === 'svg') {
                return \in_array($name, self::SVG_INTEGRATION_POINTS, true);
            }
            if ($stack[$index]['name'] === 'math') {
                return \in_array($name, self::MATH_INTEGRATION_POINTS, true);
            }
        }
        return false;
    }

                                                                            
    private static function isWhitelistedForeignName(string $name, array $stack): bool
    {
        for ($index = \count($stack) - 1; $index >= 0; $index--) {
            if ($stack[$index]['name'] === 'svg') {
                return \in_array($name, self::SVG_ELEMENTS, true);
            }
            if ($stack[$index]['name'] === 'math') {
                return \in_array($name, self::MATH_ELEMENTS, true);
            }
        }
        return false;
    }

    private static function commentEnd(string $html, int $start, bool $foreign): ?int
    {
        if ($foreign && \substr($html, $start, 9) === '<![CDATA[') {
            $end = \strpos($html, ']]>', $start + 9);
            return $end === false ? null : $end + 3;
        }
        if (\substr($html, $start, 4) !== '<!--') {
            return null;
        }
        if (\substr($html, $start, 5) === '<!-->') {
            return $start + 5;
        }
        if (\substr($html, $start, 6) === '<!--->') {
            return $start + 6;
        }
        $cursor = $start + 4;
        while (($closer = \strpos($html, '--', $cursor)) !== false) {
            if (\substr($html, $closer, 4) === '--!>') {
                return $closer + 4;
            }
            if (\substr($html, $closer, 3) === '-->') {
                return $closer + 3;
            }
                                                                              
            $cursor = $closer + 1;
        }
        return null;
    }

       
                     
                                                            
                                                                                                      
                                                       
                                          
       
    private static function tagAt(string $html, int $open, bool $retainAttributes = true): array|false|Core_HtmlRejectionInterface
    {
                                                 
        $length = \strlen($html);
        $cursor = $open + 1;
        if ($cursor >= $length) {
            return Core_HtmlRejection::Lexical;
        }
        $closing = $html[$cursor] === '/';
        $cursor += $closing ? 1 : 0;
        if ($cursor >= $length) {
            return Core_HtmlRejection::Lexical;
        }
        if (!self::isAsciiLetter($html[$cursor])) {
            return false;
        }

        $tagName = self::tagNameState($html, $cursor);
        $tail = self::beforeAttributeNameState($html, $tagName['cursor'], $retainAttributes);
        if ($tail === null) {
            return Core_HtmlRejection::Lexical;
        }
        if ($tail instanceof Core_HtmlRejectionInterface) {
            return $tail;
        }
        return [
            'end' => $tail['end'],
            'name' => $tagName['name'],
            'closing' => $closing,
            'self_closing' => $tail['self_closing'],
            'attributes' => $tail['attributes'],
            'attribute_count' => $tail['attribute_count'],
            'foreign_font_breakout' => $tail['foreign_font_breakout'],
        ];
    }

                                                
    private static function tagNameState(string $html, int $cursor): array
    {
                               
        $start = $cursor;
        $length = \strlen($html);
        while ($cursor < $length
            && !self::isHtmlSpace($html[$cursor])
            && $html[$cursor] !== '/'
            && $html[$cursor] !== '>') {
            $cursor++;
        }
        return ['name' => \strtolower(\substr($html, $start, $cursor - $start)), 'cursor' => $cursor];
    }

       
                     
                                   
                                                                                                      
                                                       
                                         
       
    private static function beforeAttributeNameState(string $html, int $cursor, bool $retainAttributes): array|Core_HtmlRejectionInterface|null
    {
                                            
        $length = \strlen($html);
        $attributes = [];
        $attributeCount = 0;
        $foreignFontBreakout = false;
        while ($cursor < $length) {
            while ($cursor < $length && self::isHtmlSpace($html[$cursor])) {
                $cursor++;
            }
            if ($cursor >= $length) {
                return null;
            }
            if ($html[$cursor] === '>') {
                return [
                    'end' => $cursor + 1,
                    'self_closing' => false,
                    'attributes' => $attributes,
                    'attribute_count' => $attributeCount,
                    'foreign_font_breakout' => $foreignFontBreakout,
                ];
            }
            if ($html[$cursor] === '/') {
                $selfClosingEnd = self::selfClosingStartTagState($html, $cursor);
                if ($selfClosingEnd === null) {
                    return null;
                }
                if ($selfClosingEnd !== false) {
                    return [
                        'end' => $selfClosingEnd,
                        'self_closing' => true,
                        'attributes' => $attributes,
                        'attribute_count' => $attributeCount,
                        'foreign_font_breakout' => $foreignFontBreakout,
                    ];
                }
                $cursor++;
                continue;
            }

            $attributeName = self::attributeNameState($html, $cursor);
            $attributeCount++;
            if ($attributeCount > self::MAX_ATTRIBUTES_PER_TAG) {
                return Core_HtmlRejection::Attributes;
            }
            $foreignFontBreakout = $foreignFontBreakout
                || \in_array($attributeName['name'], ['color', 'face', 'size'], true);
            $cursor = $attributeName['cursor'];
            while ($cursor < $length && self::isHtmlSpace($html[$cursor])) {
                $cursor++;
            }
            if ($cursor >= $length) {
                return null;
            }
            if ($html[$cursor] !== '=') {
                if ($retainAttributes) {
                    $attributes[] = [
                        'name' => $attributeName['name'],
                        'value' => '',
                        'quote' => '',
                        'value_start' => null,
                        'value_end' => $cursor,
                        'source_start' => $attributeName['start'],
                        'source_end' => $attributeName['end'],
                    ];
                }
                continue;
            }

            $value = self::beforeAttributeValueState($html, $cursor + 1);
            if ($value === null) {
                return null;
            }
            if ($retainAttributes) {
                $attributes[] = [
                    'name' => $attributeName['name'],
                    'value' => $value['value'],
                    'quote' => $value['quote'],
                    'value_start' => $value['value_start'],
                    'value_end' => $value['value_end'],
                    'source_start' => $attributeName['start'],
                    'source_end' => $value['cursor'],
                ];
            }
            $cursor = $value['cursor'];
        }
        return null;
    }

                                                                  
    private static function attributeNameState(string $html, int $cursor): array
    {
                                                                              
                                                            
        $start = $cursor++;
        $length = \strlen($html);
        while ($cursor < $length
            && !self::isHtmlSpace($html[$cursor])
            && !\in_array($html[$cursor], ['=', '>', '/'], true)) {
            $cursor++;
        }
        return [
            'name' => \strtolower(\substr($html, $start, $cursor - $start)),
            'cursor' => $cursor,
            'start' => $start,
            'end' => $cursor,
        ];
    }

       
                                                                                             
       
    private static function beforeAttributeValueState(string $html, int $cursor): ?array
    {
                                             
        $length = \strlen($html);
        while ($cursor < $length && self::isHtmlSpace($html[$cursor])) {
            $cursor++;
        }
        if ($cursor >= $length) {
            return null;
        }
        if ($html[$cursor] === '"' || $html[$cursor] === "'") {
            return self::attributeValueQuotedState($html, $cursor);
        }
        return self::attributeValueUnquotedState($html, $cursor);
    }

                                                                                                 
    private static function attributeValueQuotedState(string $html, int $cursor): ?array
    {
                                                                     
        $quote = $html[$cursor++];
        $start = $cursor;
        $length = \strlen($html);
        while ($cursor < $length && $html[$cursor] !== $quote) {
            $cursor++;
        }
        if ($cursor >= $length) {
            return null;
        }
        return [
            'value' => \substr($html, $start, $cursor - $start),
            'quote' => $quote,
            'value_start' => $start,
            'value_end' => $cursor,
            'cursor' => $cursor + 1,
        ];
    }

                                                                                            
    private static function attributeValueUnquotedState(string $html, int $cursor): array
    {
                                                                               
                                                                    
        $start = $cursor;
        $length = \strlen($html);
        while ($cursor < $length
            && !self::isHtmlSpace($html[$cursor])
            && $html[$cursor] !== '>') {
            $cursor++;
        }
        return [
            'value' => \substr($html, $start, $cursor - $start),
            'quote' => '',
            'value_start' => $start,
            'value_end' => $cursor,
            'cursor' => $cursor,
        ];
    }

    private static function selfClosingStartTagState(string $html, int $cursor): int|false|null
    {
                                             
        if ($cursor + 1 >= \strlen($html)) {
            return null;
        }
        if ($html[$cursor + 1] !== '>') {
            return false;
        }
        return $cursor + 2;
    }

    private static function isAsciiLetter(string $character): bool
    {
        return ($character >= 'A' && $character <= 'Z') || ($character >= 'a' && $character <= 'z');
    }

    public static function isHtmlSpace(string $character): bool
    {
        return \str_contains(" \t\n\f\r", $character);
    }

    public static function isHtmlSpaceOnly(string $value): bool
    {
        $length = \strlen($value);
        for ($offset = 0; $offset < $length; $offset++) {
            if (!self::isHtmlSpace($value[$offset])) {
                return false;
            }
        }
        return true;
    }

    public static function isIgnorableDocumentPrefix(string $value, int $sourceOffset): bool
    {
        if ($sourceOffset === 0 && \str_starts_with($value, "\xEF\xBB\xBF")) {
            $value = \substr($value, 3);
        }
        return self::isHtmlSpaceOnly($value);
    }

    public static function trimHtmlSpace(string $value): string
    {
        $start = 0;
        $end = \strlen($value);
        while ($start < $end && self::isHtmlSpace($value[$start])) {
            $start++;
        }
        while ($end > $start && self::isHtmlSpace($value[$end - 1])) {
            $end--;
        }
        return \substr($value, $start, $end - $start);
    }

    public static function trimHtmlSpaceLeft(string $value): string
    {
        $start = 0;
        $length = \strlen($value);
        while ($start < $length && self::isHtmlSpace($value[$start])) {
            $start++;
        }
        return \substr($value, $start);
    }

    public static function trimHtmlSpaceRight(string $value): string
    {
        $end = \strlen($value);
        while ($end > 0 && self::isHtmlSpace($value[$end - 1])) {
            $end--;
        }
        return \substr($value, 0, $end);
    }

                               
    public static function splitHtmlSpace(string $value): array
    {
        $tokens = [];
        $start = null;
        $length = \strlen($value);
        for ($offset = 0; $offset <= $length; $offset++) {
            $space = $offset === $length || self::isHtmlSpace($value[$offset]);
            if ($space) {
                if ($start !== null) {
                    $tokens[] = \substr($value, $start, $offset - $start);
                    $start = null;
                }
                continue;
            }
            $start ??= $offset;
        }
        return $tokens;
    }

    public static function hasHtmlSpaceSeparatedToken(string $value, string $needle): bool
    {
        return \in_array($needle, self::splitHtmlSpace($value), true);
    }

                                                                            
    private static function rawEnd(string $html, string $name, int $offset): array|Core_HtmlRejectionInterface|null
    {
        $cursor = $offset;
        while (($candidate = \stripos($html, '</' . $name, $cursor)) !== false) {
            $delimiter = $candidate + 2 + \strlen($name);
            if (!isset($html[$delimiter])
                || (!self::isHtmlSpace($html[$delimiter]) && !\in_array($html[$delimiter], ['/', '>'], true))) {
                $cursor = $candidate + 2;
                continue;
            }
            $tag = self::tagAt($html, $candidate);
            if ($tag instanceof Core_HtmlRejectionInterface) {
                return $tag;
            }
            if ($tag !== false && $tag['closing'] && $tag['name'] === $name) {
                return ['start' => $candidate, 'end' => $tag['end']];
            }
            $cursor = $candidate + 2;
        }
        return null;
    }

                                                                            
    private static function scriptEnd(string $html, int $offset): array|Core_HtmlRejectionInterface|null
    {
        $state = 'unescaped';
        $length = \strlen($html);
        $cursor = $offset;
        while ($cursor < $length) {
            $cursor += \strcspn($html, '-<', $cursor);
            if ($cursor + 2 < $length && \substr($html, $cursor, 3) === '-->') {
                $cursor += 3;
                $state = 'unescaped';
                continue;
            }
            if ($cursor >= $length) {
                break;
            }
            if ($html[$cursor] !== '<') {
                $cursor++;
                continue;
            }
            $tagStart = $cursor++;
            if ($cursor + 2 < $length && \substr($html, $cursor, 3) === '!--') {
                if ($state === 'unescaped') {
                    $state = 'escaped';
                }
                                                                           
                                                                            
                $cursor++;
                continue;
            }
            $closing = $cursor < $length && $html[$cursor] === '/';
            $nameStart = $cursor + ($closing ? 1 : 0);
            if ($nameStart + 6 >= $length
                || \strncasecmp(\substr($html, $nameStart, 6), 'script', 6) !== 0
                || (!self::isHtmlSpace($html[$nameStart + 6])
                    && !\in_array($html[$nameStart + 6], ['/', '>'], true))) {
                continue;
            }
            if ($state === 'escaped' && !$closing) {
                $state = 'double-escaped';
                $cursor = $nameStart + 6;
                continue;
            }
            if ($state === 'double-escaped' && $closing) {
                $state = 'escaped';
                $cursor = $nameStart + 6;
                continue;
            }
            if ($closing) {
                $tag = self::tagAt($html, $tagStart);
                if ($tag instanceof Core_HtmlRejectionInterface) {
                    return $tag;
                }
                if ($tag !== false && $tag['closing'] && $tag['name'] === 'script') {
                    return ['start' => $tagStart, 'end' => $tag['end']];
                }
            }
            $cursor = $nameStart + 6;
        }
        return null;
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

                            
    private const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

                                                                                                 
    private const ACTIVE_FORMATTING_ELEMENTS = [
        'a', 'b', 'big', 'code', 'em', 'font', 'i', 'nobr', 's', 'small',
        'strike', 'strong', 'tt', 'u',
    ];

    private \DOMDocument $doc;

    private string $sourceHtml = '';

                                                                                              
    private array $sourceTextSpans = [];

                                                                                   
    private array $sourceHrefSpans = [];

                                                                                 
    private array $sourcePatches = [];

    private bool $sourceMutationFailed = false;

    private ?string $sourceMutationFailure = null;
    private ?Core_HtmlRejectionInterface $sourceRejection = null;

                                               
    private array $phraseParseCache = [];

    public function __construct(private bool $debugMode = false)
    {
    }

    public function loadFragment(string $html): void
    {
        $this->sourceHtml = $html;
        $this->sourceTextSpans = [];
        $this->sourceHrefSpans = [];
        $this->sourcePatches = [];
        $this->sourceMutationFailed = false;
        $this->sourceMutationFailure = null;
        $this->sourceRejection = null;
        $this->doc = new \DOMDocument('1.0', 'UTF-8');
        $this->doc->preserveWhiteSpace = true;
        $this->doc->formatOutput = false;
        $this->phraseParseCache = [];

        $tokens = Dom_SourceTokenizer::tokenizeResult($html);
        $parserHtml = \is_array($tokens) ? $this->parserView($html, $tokens) : '';
        if (\str_starts_with($parserHtml, "\xEF\xBB\xBF")) {
            $parserHtml = \substr($parserHtml, 3);
        }
        if ($tokens instanceof Core_HtmlRejectionInterface) {
            $this->failSourceMutation('The source tokenizer rejected incomplete or ambiguous markup.', $tokens);
        } else {
            foreach ($tokens as $token) {
                if ($token['type'] === 'tag' && $token['name'] === 'html' && !$token['closing']
                    && $token['foreign_depth'] === 0 && $token['template_depth'] === 0) {
                    $bounds = Dom_SourceTokenizer::documentBoundsResult($html, $tokens);
                    if ($bounds instanceof Core_HtmlRejectionInterface) {
                        $this->failSourceMutation('The full document layout is not supported.', $bounds);
                    }
                    break;
                }
            }
        }

                                                                                
        $prevUseErrors = \libxml_use_internal_errors(true);

                                                                                   
        $this->doc->loadHTML(
            '<?xml encoding="UTF-8"?>' . $parserHtml,
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD
        );

        \libxml_clear_errors();

                                              
        \libxml_use_internal_errors($prevUseErrors);

                                                                        
                                                                          
                                                                           
                                                                              
                                                                             
                                                                             
                                                                               
        foreach (\iterator_to_array($this->doc->childNodes) as $node) {
            if ($node instanceof \DOMProcessingInstruction && \strtolower($node->nodeName) === 'xml') {
                $this->doc->removeChild($node);
            }
        }

        $this->mapSourceSpans();
    }

    public function forEachTextNode(bool $translatePreTextarea, callable $callback): void
    {
        if ($this->sourceMutationFailed) {
            return;
        }
        $xp = new \DOMXPath($this->doc);

                                               
                                                                      
        $protectedAncestors = 'ancestor::script or ancestor::style or ancestor::xmp'
            . ' or ancestor::iframe or ancestor::noembed or ancestor::noframes'
            . ' or ancestor::noscript or ancestor::plaintext';
        $skipAnc = $translatePreTextarea
            ? '(' . $protectedAncestors . ' or ancestor::head or ancestor::svg or ancestor::math)'
            : '(' . $protectedAncestors . ' or ancestor::head or ancestor::pre'
                . ' or ancestor::textarea or ancestor::svg or ancestor::math)';

                                                                              
                                                                             
        $query = "//text()[not($skipAnc)]";

        $nodes = $xp->query($query);
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $textNode) {
            if (!$textNode instanceof \DOMText
                || $this->hasProtectedSelfOrAncestor($textNode->parentNode, true)) {
                continue;
            }
            $callback($textNode, (string)($textNode->nodeValue ?? ''));
        }
    }

    public function forEachBodyTranslationUnit(bool $translatePreTextarea, callable $callback): void
    {
        if ($this->sourceMutationFailed) {
            return;
        }
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
            || \in_array($tag, [
                'script', 'style', 'xmp', 'iframe', 'noembed', 'noframes',
                'noscript', 'plaintext', 'svg', 'math',
            ], true)
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
        $tag = \strtolower($element->tagName);
        if (\strtolower($element->getAttribute('id')) === 'wpadminbar'
            || ($tag === 'option' && !$element->hasAttribute('value'))
            || \strtolower(Dom_SourceTokenizer::trimHtmlSpace($element->getAttribute('translate'))) === 'no'
            || $element->hasAttribute('data-no-translate')) {
            return true;
        }
        return Dom_SourceTokenizer::hasHtmlSpaceSeparatedToken($element->getAttribute('class'), 'notranslate');
    }

    private function isMenuPhraseCandidate(\DOMElement $element): bool
    {
        for ($ancestor = $element; $ancestor instanceof \DOMElement; $ancestor = $ancestor->parentNode) {
            if (\strtolower($ancestor->tagName) === 'nav') {
                return true;
            }
            $role = \strtolower(Dom_SourceTokenizer::trimHtmlSpace($ancestor->getAttribute('role')));
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
                || ($child instanceof \DOMText
                    && Dom_SourceTokenizer::isHtmlSpaceOnly((string) $child->nodeValue))) {
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
        if ($canonical === null || $elementCount === 0 || \count($slots) < 2
            || Dom_SourceTokenizer::isHtmlSpaceOnly($visible)) {
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
        if ($this->sourceMutationFailed) {
            return;
        }
        $xp = new \DOMXPath($this->doc);

                                                                              
                                                                             
        $titles = $xp->query('(//head/title)[1]');
        if ($titles === false || $titles->length === 0) {
            return;
        }

        $title = $titles->item(0);
        if (!$title instanceof \DOMElement) {
            return;
        }
        if ($this->hasProtectedSelfOrAncestor($title, true)) {
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
                $this->recordTextPatch($slot['node'], $values[$index]);
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
                $replacement = $first ? $text : '';
                $this->recordTextPatch($textNode, $replacement);
                $textNode->nodeValue = $replacement;
                $first = false;
            }
            return;
        }

        if ($nodeRef instanceof \DOMText) {
            $this->recordTextPatch($nodeRef, $text);
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

        return Dom_SourceTokenizer::hasHtmlSpaceSeparatedToken(
            $parent->getAttribute('class'),
            'wp-block-query-title'
        );
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
            && \preg_match('/^([\x09\x0A\x0C\x0D\x20\p{Zs}\x{200B}\x{2060}\x{FEFF}]*)([✓✗] ➡️)(.*)(⬅️)([\x09\x0A\x0C\x0D\x20\p{Zs}\x{200B}\x{2060}\x{FEFF}]*)$/us', $text, $matches) === 1) {
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
            if (!Dom_SourceTokenizer::isHtmlSpaceOnly($slot['original'])
                && Dom_SourceTokenizer::isHtmlSpaceOnly($values[$index])) {
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
            if (\preg_match('/^([\x09\x0A\x0C\x0D\x20]*)(.*)$/us', $values[$first], $firstParts) === 1) {
                $values[$first] = $firstParts[1] . $debugPrefix . $firstParts[2];
            }
            $last = $nonnumeric[\count($nonnumeric) - 1];
            if (\preg_match('/^(.*?)([\x09\x0A\x0C\x0D\x20]*)$/us', $values[$last], $lastParts) === 1) {
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

            $inside = Dom_SourceTokenizer::trimHtmlSpace(\substr($markup, $open + 1, $close - $open - 1));
            if ($inside === '' || $inside[0] === '!' || $inside[0] === '?') {
                return false;
            }
            if ($inside[0] === '/') {
                if (\preg_match('/^\/[\x09\x0A\x0C\x0D\x20]*([A-Za-z][A-Za-z0-9:-]*)[\x09\x0A\x0C\x0D\x20]*$/', $inside, $match) !== 1) {
                    return false;
                }
                $expected = \array_pop($stack);
                if ($expected === null || $expected !== \strtolower($match[1])) {
                    return false;
                }
            } else {
                if (\str_ends_with($inside, '/')
                    || \preg_match('/^([A-Za-z][A-Za-z0-9:-]*)(?:[\x09\x0A\x0C\x0D\x20]|$)/', $inside, $match) !== 1
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
        if ($this->sourceMutationFailed) {
            return;
        }
        $xp = new \DOMXPath($this->doc);
                                                              
        $skipAnc = $translatePreTextarea
            ? '(ancestor::script or ancestor::style or ancestor::head or ancestor::svg or ancestor::math)'
            : '(ancestor::script or ancestor::style or ancestor::head or ancestor::pre or ancestor::textarea or ancestor::svg or ancestor::math)';

                                                                              
                                                                          
        $query = "//a[@href][not($skipAnc)]";
        $nodes = $xp->query($query);
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $a) {
            if (!$a instanceof \DOMElement
                || $this->hasProtectedSelfOrAncestor($a, true)
                || $this->anchorPreservesLanguage($a)) {
                continue;
            }
            $href = (string)$a->getAttribute('href');
            $callback($a, $href);
        }
    }

    private function anchorPreservesLanguage(\DOMElement $anchor): bool
    {
        return $anchor->hasAttribute('data-dolet-preserve-l')
            || $anchor->hasAttribute('data-preserve-l')
            || Dom_SourceTokenizer::hasHtmlSpaceSeparatedToken(
                $anchor->getAttribute('class'),
                'dolet-preserve-l'
            );
    }

    private function hasProtectedSelfOrAncestor(?\DOMNode $node, bool $includeSelf): bool
    {
        $current = $includeSelf ? $node : $node?->parentNode;
        while ($current instanceof \DOMElement) {
            if ($this->isProtectedElement($current)) {
                return true;
            }
            $current = $current->parentNode;
        }
        return false;
    }

    public function setAnchorHref(mixed $nodeRef, string $href): void
    {
        if ($nodeRef instanceof \DOMElement && strtolower($nodeRef->nodeName) === 'a') {
            $span = $this->sourceHrefSpans[$nodeRef->getNodePath()] ?? null;
            if ($span === null) {
                $this->failSourceMutation('An eligible link could not be mapped uniquely to its source attribute.');
                return;
            }
            $originalDecoded = \html_entity_decode($span['original'], \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            $leftTrimmed = Dom_SourceTokenizer::trimHtmlSpaceLeft($originalDecoded);
            $leadingBytes = \strlen($originalDecoded) - \strlen($leftTrimmed);
            $decodedHref = Dom_SourceTokenizer::trimHtmlSpace($originalDecoded);
            $fragmentOffset = \strpos($decodedHref, '#');
            $fragment = $fragmentOffset === false ? false : \substr($decodedHref, $fragmentOffset);
            $decodedBase = $fragmentOffset === false ? $decodedHref : \substr($decodedHref, 0, $fragmentOffset);
            $newFragment = \strchr($href, '#');
            $newBase = $newFragment === false ? $href : \substr($href, 0, -\strlen($newFragment));
            if ($newFragment === $fragment && \str_starts_with($newBase, $decodedBase)) {
                $insertion = \substr($newBase, \strlen($decodedBase));
                $isTerminalQueryInsertion = \str_ends_with($decodedBase, '?')
                    && \preg_match('/\Al=(?:[A-Za-z0-9._~-]|%[0-9A-F]{2})+\z/', $insertion) === 1;
                if ($isTerminalQueryInsertion
                    || \preg_match('/\A[?&]l=(?:[A-Za-z0-9._~-]|%[0-9A-F]{2})+\z/', $insertion) === 1) {
                    $decodedInsertAt = $leadingBytes
                        + ($fragmentOffset === false ? \strlen($decodedHref) : $fragmentOffset);
                    $rawInsertAt = $this->rawOffsetForDecodedOffset($span['original'], $decodedInsertAt);
                    if ($rawInsertAt === null) {
                        $this->failSourceMutation('An eligible link insertion could not be mapped to exact source bytes.');
                        return;
                    }
                    $insertAt = $span['start'] + $rawInsertAt;
                    $encodedInsertion = \str_starts_with($insertion, '&')
                        ? '&amp;' . \substr($insertion, 1)
                        : $insertion;
                    $this->recordPatch($insertAt, $insertAt, '', $encodedInsertion);
                    $nodeRef->setAttribute('href', $href);
                    return;
                }
            }
            if (\preg_match('/(?:^|[?&])l(?=&|#|$)/', $originalDecoded, $valuelessMatch, \PREG_OFFSET_CAPTURE) === 1) {
                $token = $valuelessMatch[0][0];
                $languageAt = \strrpos($token, 'l');
                $decodedValueAt = $valuelessMatch[0][1] + ($languageAt === false ? 0 : $languageAt + 1);
                $rawValueAt = $this->rawOffsetForDecodedOffset($span['original'], $decodedValueAt);
                if ($rawValueAt !== null) {
                    $targetQuery = (string)\wp_parse_url($href, \PHP_URL_QUERY);
                    if (\preg_match('/(?:^|&)l=([^&]*)/', $targetQuery, $targetMatch) === 1
                        && \preg_match('/\A(?:[A-Za-z0-9._~-]|%[0-9A-F]{2})+\z/', $targetMatch[1]) === 1
                        && $href === Dom_SourceTokenizer::trimHtmlSpace(
                            \substr($originalDecoded, 0, $decodedValueAt)
                            . '=' . $targetMatch[1] . \substr($originalDecoded, $decodedValueAt)
                        )) {
                        $absolute = $span['start'] + $rawValueAt;
                        $this->recordPatch($absolute, $absolute, '', '=' . $targetMatch[1]);
                        $nodeRef->setAttribute('href', $href);
                        return;
                    }
                }
            }
            if (\preg_match('/(?:^|[?&])l=(&|#|$)/', $originalDecoded, $emptyMatch, \PREG_OFFSET_CAPTURE) === 1) {
                $token = $emptyMatch[0][0];
                $languageAt = \strpos($token, 'l=');
                $decodedValueAt = $emptyMatch[0][1] + ($languageAt === false ? 0 : $languageAt + 2);
                $rawValueAt = $this->rawOffsetForDecodedOffset($span['original'], $decodedValueAt);
                if ($rawValueAt !== null) {
                    $targetQuery = (string)\wp_parse_url($href, \PHP_URL_QUERY);
                    if (\preg_match('/(?:^|&)l=([^&]*)/', $targetQuery, $targetMatch) === 1
                        && \preg_match('/\A(?:[A-Za-z0-9._~-]|%[0-9A-F]{2})+\z/', $targetMatch[1]) === 1
                        && $href === Dom_SourceTokenizer::trimHtmlSpace(
                            \substr($originalDecoded, 0, $decodedValueAt)
                            . $targetMatch[1] . \substr($originalDecoded, $decodedValueAt)
                        )) {
                        $absolute = $span['start'] + $rawValueAt;
                        $this->recordPatch($absolute, $absolute, '', $targetMatch[1]);
                        $nodeRef->setAttribute('href', $href);
                        return;
                    }
                }
            }
            $this->failSourceMutation('An eligible link rewrite could not be limited to exact language-parameter bytes.');
        }
    }

    private function rawOffsetForDecodedOffset(string $raw, int $decodedOffset): ?int
    {
        $rawOffset = 0;
        $decodedBytes = 0;
        $length = \strlen($raw);
        while ($rawOffset < $length) {
            if ($decodedBytes === $decodedOffset) {
                return $rawOffset;
            }
            if ($raw[$rawOffset] === '&') {
                $semicolon = \strpos($raw, ';', $rawOffset + 1);
                if ($semicolon !== false) {
                    $entity = \substr($raw, $rawOffset, $semicolon - $rawOffset + 1);
                    $decoded = \html_entity_decode($entity, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
                    if ($decoded !== $entity) {
                        $decodedBytes += \strlen($decoded);
                        $rawOffset = $semicolon + 1;
                        continue;
                    }
                }
            }
            $decodedBytes++;
            $rawOffset++;
        }
        return $decodedBytes === $decodedOffset ? $rawOffset : null;
    }

    public function serializeFragment(): string
    {
        if ($this->sourceMutationFailed || $this->sourcePatches === []) {
            return $this->sourceHtml;
        }

        $patches = $this->sourcePatches;
        \usort(
            $patches,
            static fn (array $left, array $right): int => $right['start'] <=> $left['start']
        );
        $lastStart = \strlen($this->sourceHtml);
        $output = $this->sourceHtml;
        foreach ($patches as $patch) {
            if ($patch['end'] > $lastStart
                || \substr($this->sourceHtml, $patch['start'], $patch['end'] - $patch['start']) !== $patch['expected']) {
                $this->failSourceMutation('An authorized source patch no longer matched its expected bytes.');
                return $this->sourceHtml;
            }
            $output = \substr($output, 0, $patch['start'])
                . $patch['replacement']
                . \substr($output, $patch['end']);
            $lastStart = $patch['start'];
        }
        return $output;
    }

    public function getSourceMutationFailure(): ?string
    {
        return $this->sourceMutationFailure;
    }

    public function getRejection(): ?Core_HtmlRejectionInterface
    {
        return $this->sourceRejection;
    }

    private function recordTextPatch(\DOMText $node, string $text): void
    {
        $span = $this->sourceTextSpans[$node->getNodePath()] ?? null;
        if ($span === null) {
            $this->failSourceMutation('An eligible text node could not be mapped uniquely to its source span.');
            return;
        }
        if ((string)($node->nodeValue ?? '') === $text) {
            return;
        }
        $replacement = \htmlspecialchars($text, \ENT_NOQUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
        $this->recordPatch($span['start'], $span['end'], $span['original'], $replacement);
    }

    private function recordPatch(int $start, int $end, string $expected, string $replacement): void
    {
        foreach ($this->sourcePatches as $index => $patch) {
            if ($patch['start'] === $start && $patch['end'] === $end) {
                $this->sourcePatches[$index] = \compact('start', 'end', 'expected', 'replacement');
                return;
            }
            if ($start < $patch['end'] && $end > $patch['start']) {
                $this->failSourceMutation('Authorized source mutations overlap.');
                return;
            }
        }
        $this->sourcePatches[] = \compact('start', 'end', 'expected', 'replacement');
    }

    private function mapSourceSpans(): void
    {
        $scan = $this->scanSource($this->sourceHtml);

        $bodyNodes = [];
        $this->forEachTextNode(true, static function ($node) use (&$bodyNodes): void {
            if ($node instanceof \DOMText) {
                $bodyNodes[] = $node;
            }
        });
        if (!$this->alignTextNodes($bodyNodes, $scan['body_text'])) {
            $this->failSourceMutation('Body text and source spans do not have a one-to-one byte mapping.');
        }

        $titleNodes = [];
        $this->forEachDocumentTitleText(static function ($nodes) use (&$titleNodes): void {
            if (!\is_array($nodes)) {
                return;
            }
            foreach ($nodes as $node) {
                if ($node instanceof \DOMText) {
                    $titleNodes[] = $node;
                }
            }
        });
        if (!$this->alignTextNodes($titleNodes, $scan['title_text'])) {
            $this->failSourceMutation('Document title and source spans do not have a one-to-one byte mapping.');
        }

        $anchorNodes = [];
        $this->forEachAnchor(true, static function ($node, string $href) use (&$anchorNodes): void {
            if ($node instanceof \DOMElement) {
                $anchorNodes[] = ['node' => $node, 'href' => $href];
            }
        });
        if (\count($anchorNodes) !== \count($scan['hrefs'])) {
            $this->failSourceMutation('Eligible links and source attributes do not have a one-to-one mapping.');
            return;
        }
        foreach ($anchorNodes as $index => $anchor) {
            $span = $scan['hrefs'][$index];
            if ($anchor['href'] !== $span['decoded'] || $span['quote'] === '') {
                $this->failSourceMutation('An eligible link value is ambiguous or unquoted in the source.');
                return;
            }
            $this->sourceHrefSpans[$anchor['node']->getNodePath()] = [
                'start' => $span['start'],
                'end' => $span['end'],
                'original' => $span['original'],
                'quote' => $span['quote'],
            ];
        }
    }

       
                                                                           
                                                                          
      
                         
                                                                                       
                                        
                                                                                                      
                                             
                 
       
    private function parserView(string $html, array $tokens): string
    {
        $rawTokens = \array_values(\array_filter(
            $tokens,
            static fn (array $token): bool => $token['type'] === 'raw'
                && !\in_array($token['name'], ['title', 'textarea'], true)
        ));
        \usort($rawTokens, static fn (array $left, array $right): int => $right['start'] <=> $left['start']);
        foreach ($rawTokens as $token) {
            $html = \substr($html, 0, $token['start'])
                . \str_repeat(' ', $token['end'] - $token['start'])
                . \substr($html, $token['end']);
        }
        return $html;
    }

       
                                   
                                                                                                          
       
    private function alignTextNodes(array $nodes, array $spans): bool
    {
        if (\count($nodes) !== \count($spans)) {
            return false;
        }
        foreach ($nodes as $index => $node) {
            $span = $spans[$index];
            if ((string)($node->nodeValue ?? '') !== $span['decoded']) {
                return false;
            }
            $this->sourceTextSpans[$node->getNodePath()] = [
                'start' => $span['start'],
                'end' => $span['end'],
                'original' => $span['original'],
                'context' => $span['context'],
            ];
        }
        return true;
    }

       
                     
                                                                                                         
                                                                                                          
                                                                                         
        
       
    private function scanSource(string $html): array
    {
        $bodyText = [];
        $titleText = [];
        $hrefs = [];
                                                                                                   
        $stack = [];
        $headTitleSeen = false;
        $tokens = Dom_SourceTokenizer::tokenizeResult($html);
        if ($tokens instanceof Core_HtmlRejectionInterface) {
            $this->failSourceMutation('The source tokenizer rejected incomplete or ambiguous markup.', $tokens);
            return ['body_text' => [], 'title_text' => [], 'hrefs' => []];
        }

        foreach ($tokens as $token) {
            if ($token['type'] === 'text' || $token['type'] === 'raw') {
                $top = $stack === [] ? null : $stack[\count($stack) - 1]['tag'];
                if ($token['type'] === 'text' || \in_array($top, ['title', 'textarea'], true)) {
                    $this->appendSourceText(
                        $html,
                        $token['start'],
                        $token['end'],
                        $stack,
                        $bodyText,
                        $titleText
                    );
                }
                continue;
            }
            if ($token['type'] !== 'tag') {
                continue;
            }

            $tag = $token['name'];
            if ($token['closing']) {
                                                                                  
                                                                           
                if ($token['foreign_depth'] === 0 && \in_array($tag, ['script', 'style'], true)
                    && \preg_match(
                        '~^</[a-z]+[\x09\x0a\x0c\x0d\x20]*>$~iD',
                        \substr($html, $token['start'], $token['end'] - $token['start'])
                    ) !== 1) {
                    $this->failSourceMutation('A raw-text end tag has unexpected syntax.');
                    return ['body_text' => [], 'title_text' => [], 'hrefs' => []];
                }
                $matchingIndex = null;
                for ($index = \count($stack) - 1; $index >= 0; $index--) {
                    if ($stack[$index]['tag'] === $tag) {
                        $matchingIndex = $index;
                        break;
                    }
                    if ($stack[$index]['template']) {
                        break;
                    }
                }
                if ($matchingIndex !== null) {
                    for ($index = \count($stack) - 1; $index > $matchingIndex; $index--) {
                        if ($stack[$index]['protected']
                            && \in_array($stack[$index]['tag'], self::ACTIVE_FORMATTING_ELEMENTS, true)) {
                            $this->failSourceMutation(
                                'Protected formatting scope was closed by misnested markup.'
                            );
                            return ['body_text' => [], 'title_text' => [], 'hrefs' => []];
                        }
                    }
                    while (\count($stack) > $matchingIndex) {
                        \array_pop($stack);
                    }
                }
                continue;
            }
            if ($tag === 'body') {
                for ($index = \count($stack) - 1; $index >= 0; $index--) {
                    if ($stack[$index]['template']) {
                        break;
                    }
                    if ($stack[$index]['tag'] === 'head') {
                        while (\count($stack) > $index) {
                            \array_pop($stack);
                        }
                        break;
                    }
                }
            }
            $attributes = $token['attributes'];
            $parentProtected = $stack !== [] && $stack[\count($stack) - 1]['protected'];
            $protected = $parentProtected || $this->sourceElementIsProtected($tag, $attributes);
            $documentTitle = false;
            if ($tag === 'title'
                && !$headTitleSeen
                && $this->stackHasTag($stack, ['head'])
                && !$this->stackHasTag($stack, ['svg', 'math'])) {
                $headTitleSeen = true;
                $documentTitle = true;
            }
            if ($tag === 'a'
                && !$protected
                && !$this->sourceAnchorPreservesLanguage($attributes)
                && !$this->stackHasTag($stack, ['head', 'script', 'style', 'svg', 'math'])) {
                foreach ($attributes as $attribute) {
                    if ($attribute['name'] === 'href' && $attribute['value_start'] !== null) {
                        $hrefs[] = [
                            'start' => $attribute['value_start'],
                            'end' => $attribute['value_end'],
                            'original' => $attribute['value'],
                            'decoded' => \html_entity_decode($attribute['value'], \ENT_QUOTES | \ENT_HTML5, 'UTF-8'),
                            'quote' => $attribute['quote'],
                        ];
                        break;
                    }
                }
            }
            if (!$token['self_closing'] && !\in_array($tag, self::VOID_ELEMENTS, true)) {
                $stack[] = [
                    'tag' => $tag,
                    'protected' => $protected,
                    'document_title' => $documentTitle,
                    'template' => $tag === 'template',
                ];
            }
        }

        return ['body_text' => $bodyText, 'title_text' => $titleText, 'hrefs' => $hrefs];
    }

       
                                                                                             
                                                                                                             
                                                                                                              
       
    private function appendSourceText(
        string $html,
        int $start,
        int $end,
        array $stack,
        array &$bodyText,
        array &$titleText
    ): void {
        if ($end <= $start || $stack === []) {
            return;
        }
        $original = \substr($html, $start, $end - $start);
        $decoded = \html_entity_decode($original, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $current = $stack[\count($stack) - 1];
        if ($current['document_title']
            && !$this->stackIsProtected($stack)
        ) {
                                                                          
                                                                  
            if (\preg_match('~<[a-z!/?]~i', $original) === 1) {
                $this->failSourceMutation('The document title contains ambiguous markup.');
                return;
            }
            $titleText[] = \compact('start', 'end', 'original', 'decoded') + ['context' => 'rcdata'];
            return;
        }
        if ($this->stackIsProtected($stack)
            || $this->stackHasTag($stack, ['head', 'script', 'style', 'svg', 'math'])) {
            return;
        }
        $bodyText[] = \compact('start', 'end', 'original', 'decoded') + ['context' => 'data'];
    }

                                                                                                               
    private function sourceElementIsProtected(string $tag, array $attributes): bool
    {
        $values = [];
        foreach ($attributes as $attribute) {
            if (!\array_key_exists($attribute['name'], $values)) {
                $values[$attribute['name']] = \html_entity_decode($attribute['value'], \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            }
        }
        return ($tag === 'div' && \strtolower($values['id'] ?? '') === 'wpadminbar')
            || ($tag === 'option' && !\array_key_exists('value', $values))
            || \strtolower(Dom_SourceTokenizer::trimHtmlSpace($values['translate'] ?? '')) === 'no'
            || \array_key_exists('data-no-translate', $values)
            || Dom_SourceTokenizer::hasHtmlSpaceSeparatedToken($values['class'] ?? '', 'notranslate');
    }

                                                                                                               
    private function sourceAnchorPreservesLanguage(array $attributes): bool
    {
        $values = [];
        foreach ($attributes as $attribute) {
            if (!\array_key_exists($attribute['name'], $values)) {
                $values[$attribute['name']] = \html_entity_decode($attribute['value'], \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            }
        }
        return \array_key_exists('data-dolet-preserve-l', $values)
            || \array_key_exists('data-preserve-l', $values)
            || Dom_SourceTokenizer::hasHtmlSpaceSeparatedToken($values['class'] ?? '', 'dolet-preserve-l');
    }

       
                                                                                             
                                                           
       
    private function stackHasTag(array $stack, array $tags): bool
    {
        foreach ($stack as $open) {
            if (\in_array($open['tag'], $tags, true)) {
                return true;
            }
        }
        return false;
    }

                                                                                                 
    private function stackIsProtected(array $stack): bool
    {
        foreach ($stack as $open) {
            if ($open['protected']) {
                return true;
            }
        }
        return false;
    }

    private function failSourceMutation(string $reason, ?Core_HtmlRejectionInterface $cause = null): void
    {
        $this->sourceMutationFailed = true;
        if ($this->sourceMutationFailure === null) {
            $this->sourceRejection = $cause ?? Core_HtmlRejection::Mapping;
        }
        $this->sourceMutationFailure ??= $reason;
    }
}

                                                                                
final class Dom_RawStructureGuard implements Dom_RawStructureGuardInterface
{
                            
    private const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];
                            
    private const ACTIVE_FORMATTING_ELEMENTS = [
        'a', 'b', 'big', 'code', 'em', 'font', 'i', 'nobr', 's', 'small',
        'strike', 'strong', 'tt', 'u',
    ];
    public function findFirstLoss(string $sourceHtml, string $serializedHtml): ?string
    {
        $result = $this->findFirstLossResult($sourceHtml, $serializedHtml);
        return $result instanceof Core_HtmlRejectionInterface ? Dom_Constants::TOKENIZER_REJECTION_DETAIL : $result;
    }

    public function findFirstLossResult(string $sourceHtml, string $serializedHtml): string|Core_HtmlRejectionInterface|null
    {
        if ($sourceHtml === $serializedHtml) {
            return null;
        }
        $source = $this->signature($sourceHtml);
        if ($source instanceof Core_HtmlRejectionInterface) {
            return $source;
        }
        $serialized = $this->signature($serializedHtml);
        if ($serialized instanceof Core_HtmlRejectionInterface) {
            return new Core_HtmlCandidateRejection($serialized);
        }

        if (count($source) !== count($serialized)) {
            return 'Element topology changed from ' . count($source) . ' to ' . count($serialized) . ' structure events.';
        }

        foreach ($source as $index => $expected) {
            $actual = $serialized[$index];
            if ($expected['type'] !== $actual['type'] || $expected['tag'] !== $actual['tag']) {
                $kind = $expected['type'] === 'close' ? 'closing' : 'opening';
                return 'Element <' . $expected['tag'] . '> at ' . $kind . ' event position ' . $index . ' was lost or reordered.';
            }
            if ($expected['type'] === 'open') {
                foreach ($expected['attributes'] as $attribute) {
                    if (!in_array($attribute, $actual['attributes'], true)) {
                        return 'Attribute ' . $attribute . ' on <' . $expected['tag'] . '> at opening event position ' . $index . ' was lost.';
                    }
                }
            }
        }

        return null;
    }

                                                                                                                  
    private function signature(string $html): array|Core_HtmlRejectionInterface
    {
        $tokens = Dom_SourceTokenizer::tokenizeResult($html);
        if ($tokens instanceof Core_HtmlRejectionInterface) {
            return $tokens;
        }
        $signature = [];
        $stack = [];
        $openCounts = [];
        $pendingFormatting = [];

        foreach ($tokens as $token) {
            if ($token['type'] === 'text') {
                if ($pendingFormatting !== []
                    && !Dom_SourceTokenizer::isHtmlSpaceOnly(
                        substr($html, $token['start'], $token['end'] - $token['start'])
                    )) {
                    $this->markFormattingReconstruction($signature, $pendingFormatting);
                }
                continue;
            }
            if ($token['type'] !== 'tag') {
                continue;
            }

            $tag = $token['name'];
            if ($token['closing']) {
                $pendingIndex = array_search($tag, array_reverse($pendingFormatting, true), true);
                if ($pendingIndex !== false) {
                    unset($pendingFormatting[$pendingIndex]);
                    $pendingFormatting = array_values($pendingFormatting);
                    if (($openCounts[$tag] ?? 0) > 0) {
                        $pendingFormatting[] = $tag;
                    }
                } else {
                    $this->closeThroughExplicit(
                        $tag,
                        $stack,
                        $openCounts,
                        $signature,
                        $pendingFormatting
                    );
                }
                continue;
            }

            if ($this->startClosesFormattingScope($tag, $openCounts)) {
                $pendingFormatting = [];
            }
            if ($pendingFormatting !== []) {
                $this->markFormattingReconstruction($signature, $pendingFormatting);
            }
            $this->closeOptionalBeforeStart($tag, $stack, $openCounts, $signature);
            $signature[] = [
                'type' => 'open',
                'tag' => $tag,
                'attributes' => array_map(
                    static fn (array $attribute): string => $attribute['name'],
                    $token['attributes']
                ),
            ];
            $stack[] = $tag;
            $openCounts[$tag] = ($openCounts[$tag] ?? 0) + 1;

            if ($token['self_closing'] || in_array($tag, self::VOID_ELEMENTS, true)) {
                $signature[] = ['type' => 'close', 'tag' => $tag, 'attributes' => []];
                $this->popOpenTag($stack, $openCounts);
            }
        }

        while ($stack !== []) {
            $tag = (string) array_pop($stack);
            $signature[] = ['type' => 'close', 'tag' => $tag, 'attributes' => []];
        }

        return $signature;
    }

       
                                 
                                           
                                                                                            
       
    private function closeOptionalBeforeStart(
        string $next,
        array &$stack,
        array &$openCounts,
        array &$signature
    ): void
    {
        while ($stack !== []) {
            $open = $stack[array_key_last($stack)];
            if ($this->optionalEndClosesOnStart($open, $next)) {
                $this->popOpenTag($stack, $openCounts);
                $signature[] = ['type' => 'close', 'tag' => $open, 'attributes' => []];
                continue;
            }

            $tableAncestor = $this->tableOptionalAncestorIndex($next, $stack, $openCounts);
            if ($tableAncestor === null) {
                return;
            }
            while (count($stack) > $tableAncestor) {
                $closed = $this->popOpenTag($stack, $openCounts);
                $signature[] = ['type' => 'close', 'tag' => $closed, 'attributes' => []];
            }
        }
    }

       
                                 
                                           
       
    private function tableOptionalAncestorIndex(string $next, array $stack, array $openCounts): ?int
    {
        $tableOptional = match ($next) {
            'td', 'th' => ['td', 'th'],
            'tr' => ['td', 'th', 'tr'],
            'thead', 'tbody', 'tfoot' => ['td', 'th', 'tr', 'thead', 'tbody', 'tfoot'],
            default => [],
        };
        $hasClosableAncestor = false;
        foreach ($tableOptional as $candidate) {
            if (($openCounts[$candidate] ?? 0) > 0) {
                $hasClosableAncestor = true;
                break;
            }
        }
        if (!$hasClosableAncestor) {
            return null;
        }
        for ($index = count($stack) - 2; $index >= 0; $index--) {
            $open = $stack[$index];
            if (
                in_array($open, $tableOptional, true)
                && $this->optionalEndClosesOnStart($open, $next)
            ) {
                return $index;
            }
        }
        return null;
    }

    private function optionalEndClosesOnStart(string $open, string $next): bool
    {
        if ($open === 'li') {
            return $next === 'li';
        }
        if ($open === 'dt' || $open === 'dd') {
            return $next === 'dt' || $next === 'dd';
        }
        if ($open === 'rt' || $open === 'rp') {
            return $next === 'rt' || $next === 'rp';
        }
        if ($open === 'option') {
            return $next === 'option' || $next === 'optgroup';
        }
        if ($open === 'optgroup') {
            return $next === 'optgroup';
        }
        if ($open === 'tr') {
            return in_array($next, ['tr', 'tbody', 'thead', 'tfoot'], true);
        }
        if ($open === 'td' || $open === 'th') {
            return in_array($next, ['td', 'th', 'tr', 'tbody', 'thead', 'tfoot'], true);
        }
        if (in_array($open, ['thead', 'tbody', 'tfoot'], true)) {
            return in_array($next, ['thead', 'tbody', 'tfoot'], true);
        }
        if ($open === 'head') {
            return $next === 'body';
        }
        if ($open === 'caption') {
            return in_array($next, ['colgroup', 'tbody', 'tfoot', 'thead', 'tr'], true);
        }
        if ($open !== 'p') {
            return false;
        }
        return in_array($next, [
            'address', 'article', 'aside', 'blockquote', 'details', 'dialog',
            'div', 'dl', 'dt', 'dd', 'fieldset', 'figcaption', 'figure', 'footer', 'form',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr',
            'li', 'main', 'menu', 'nav', 'ol', 'p', 'pre', 'search', 'section',
            'table', 'ul',
        ], true);
    }

       
                                 
                                           
                                                                                            
                                             
       
    private function closeThroughExplicit(
        string $target,
        array &$stack,
        array &$openCounts,
        array &$signature,
        array &$pendingFormatting
    ): void
    {
        if (($openCounts[$target] ?? 0) === 0) {
            return;
        }
        $clearsActiveFormatting = $this->explicitCloseClearsFormattingScope($target, $stack);
        if ($clearsActiveFormatting) {
            $pendingFormatting = [];
        }
        do {
            $open = $this->popOpenTag($stack, $openCounts);
            if (
                $open !== $target
                && !$clearsActiveFormatting
                && in_array($open, self::ACTIVE_FORMATTING_ELEMENTS, true)
            ) {
                $pendingFormatting[] = $open;
            }
            $signature[] = ['type' => 'close', 'tag' => $open, 'attributes' => []];
        } while ($open !== $target);
    }

                                               
    private function startClosesFormattingScope(string $next, array $openCounts): bool
    {
        if (!in_array($next, ['caption', 'colgroup', 'tbody', 'tfoot', 'thead', 'tr', 'td', 'th'], true)) {
            return false;
        }
        return ($openCounts['caption'] ?? 0) > 0
            || ($openCounts['td'] ?? 0) > 0
            || ($openCounts['th'] ?? 0) > 0;
    }

                                     
    private function explicitCloseClearsFormattingScope(string $target, array $stack): bool
    {
        for ($index = count($stack) - 1; $index >= 0; $index--) {
            if ($stack[$index] !== $target) {
                continue;
            }
            for ($scopeIndex = $index; $scopeIndex < count($stack); $scopeIndex++) {
                if (in_array($stack[$scopeIndex], ['caption', 'td', 'th'], true)) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

       
                                                                                            
                                             
       
    private function markFormattingReconstruction(array &$signature, array &$pendingFormatting): void
    {
        $signature[] = [
            'type' => 'close',
            'tag' => '__dolet_active_formatting_boundary__',
            'attributes' => [],
        ];
        $pendingFormatting = [];
    }

       
                                 
                                           
       
    private function popOpenTag(array &$stack, array &$openCounts): string
    {
        $tag = (string) array_pop($stack);
        $remaining = ($openCounts[$tag] ?? 1) - 1;
        if ($remaining === 0) {
            unset($openCounts[$tag]);
        } else {
            $openCounts[$tag] = $remaining;
        }
        return $tag;
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

    private Dom_RawStructureGuardInterface $structureGuard;

    private bool $mutated = false;

                            
    private array $rejectedInlinePhraseHashes = [];

    public function __construct(
        private string $htmlFragment,
        private bool $translatePreTextarea = false,
        private bool $debugMode = false,
        private string $searchQueryEcho = '',
        ?Dom_RawStructureGuardInterface $structureGuard = null
    ) {
        $this->engine = new Dom_EngineNative($this->debugMode);
        $this->structureGuard = $structureGuard ?? new Dom_RawStructureGuard();
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
        $this->rejectedInlinePhraseHashes = \array_values(\array_unique($invalidPhraseHashes));
        $this->mutated = true;
        return $invalidPhraseHashes === [];
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
        $href = Dom_SourceTokenizer::trimHtmlSpace($href);
        if ($href === '') {
            return null;
        }

                                                   
        if ($href[0] === '#') {
            return null;
        }

                                                                          
                                                                        
                                                                       
        $lower = strtolower($href);
        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $lower, $schemeMatch) === 1
            && !in_array(strtolower($schemeMatch[1]), ['http', 'https'], true)) {
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

        $paramName = Dom_Constants::LANGUAGE_URL_PARAMETER_NAME;
        $parts = $query === '' ? [] : \explode('&', $query);
        $emptyLanguageIndex = null;
        foreach ($parts as $index => $part) {
            [$rawName, $rawValue] = \array_pad(\explode('=', $part, 2), 2, '');
            $decodedName = \rawurldecode(\str_replace('+', ' ', $rawName));
            if ($decodedName !== $paramName) {
                continue;
            }
            $decodedValue = \rawurldecode(\str_replace('+', ' ', $rawValue));
            if ($decodedValue !== '') {
                return null;
            }
            $emptyLanguageIndex ??= $index;
        }
        $languageToken = $paramName . '=' . \rawurlencode($target);
        if ($emptyLanguageIndex === null) {
            $parts[] = $languageToken;
        } else {
            $parts[$emptyLanguageIndex] = $languageToken;
        }
        $newQuery = \implode('&', $parts);

                       
        $rebuilt = $base . '?' . $newQuery;
        if ($frag !== null) {
            $rebuilt .= '#' . $frag;
        }
        return $rebuilt;
    }

    public function getHtml(): string
    {
        return $this->engine->serializeFragment();
    }

    public function findStructureLoss(string $serializedHtml): ?string
    {
        $loss = $this->findStructureLossResult($serializedHtml);
        return $loss instanceof Core_HtmlRejectionInterface
            ? ($this->engine->getSourceMutationFailure() ?? Dom_Constants::TOKENIZER_REJECTION_DETAIL) : $loss;
    }

    public function findStructureLossResult(string $serializedHtml): string|Core_HtmlRejectionInterface|null
    {
        $mappingFailure = $this->engine->getSourceMutationFailure();
        if ($mappingFailure !== null) {
            return $this->engine->getRejection() ?? $mappingFailure;
        }
        return $this->structureGuard->findFirstLossResult($this->htmlFragment, $serializedHtml);
    }

    public function getRejection(): ?Core_HtmlRejectionInterface
    {
        return $this->engine->getRejection();
    }

    public function getRejectedInlinePhraseHashes(): array
    {
        return $this->rejectedInlinePhraseHashes;
    }

}

   
                                                                    
  
                                                                    
                               
  
             
                                                                                   
                                                                           
                                                                          
                                                                            
                                                                                     
   
final class Dom_TranslatedStamp
{
       
                                                                                
                                                                                     
                                     
       
    public static function isStampedAs(string $html, Language_OptionInterface $language): bool
    {
        $prefix = \defined(Dom_Constants::class . '::PAGE_STAMP_PREFIX')
            ? Dom_Constants::PAGE_STAMP_PREFIX
            : 'DOLET_PAGE:';                                        

        $scan = self::scan($html, $prefix);
        if ($scan === null || $scan['markers'] === []) {
            return false;
        }

        $placement = $scan['body_close'] ?? \strlen($html);
        $placed = \array_values(\array_filter(
            $scan['markers'],
            static fn (array $marker): bool => $marker['end'] === $placement
        ));
        if ($placed === []) {
            return false;
        }

        $foundLang = $placed[\count($placed) - 1]['language'];
        $target    = (string) $language->getCode();

        return \strcasecmp($foundLang, $target) === 0;
    }

       
                                                
                                                 
                                                                                                            
                                                                           
       
    public static function stamp(string $html, Language_OptionInterface $language): string
    {
        $prefix = \defined(Dom_Constants::class . '::PAGE_STAMP_PREFIX')
            ? Dom_Constants::PAGE_STAMP_PREFIX
            : 'DOLET_PAGE:';                

        $langCode = \rawurlencode($language->getCode());

        $scan = self::scan($html, $prefix);
        if ($scan === null) {
            return $html;
        }
        $bodyClose = $scan['body_close'];
        $hasFragmentContent = $scan['has_fragment_content'];
        $cleanHtml = $html;
        $markers = $scan['markers'];
        \usort($markers, static fn (array $left, array $right): int => $right['start'] <=> $left['start']);
        foreach ($markers as $marker) {
            $length = $marker['end'] - $marker['start'];
            $cleanHtml = \substr($cleanHtml, 0, $marker['start'])
                . \substr($cleanHtml, $marker['end']);
            if ($bodyClose !== null && $marker['start'] < $bodyClose) {
                $bodyClose -= $length;
            }
        }

        $marker = '<!-- ' . $prefix . $langCode . ' -->';
        if ($bodyClose === null && !$hasFragmentContent) {
            return $html;
        }
        if ($bodyClose === null) {
            return $cleanHtml . $marker;
        }
        return \substr($cleanHtml, 0, $bodyClose) . $marker . \substr($cleanHtml, $bodyClose);
    }

       
                     
                                                                
                             
                                  
             
       
    private static function scan(string $html, string $prefix): ?array
    {
        $markers = [];
        $bodyClose = null;
        $hasFragmentContent = false;
        $markerPattern = '/\A<!--[\x09\x0A\x0C\x0D\x20]*'
            . \preg_quote($prefix, '/')
            . '((?:[A-Za-z0-9._~-]|%[0-9A-F]{2})+)[\x09\x0A\x0C\x0D\x20]*-->\z/';
        $tokens = Dom_SourceTokenizer::tokenize($html);
        if ($tokens === null) {
            return null;
        }
        $bounds = Dom_SourceTokenizer::documentBounds($html, $tokens);
        if ($bounds === null) {
            return null;
        }
        $bodyClose = $bounds['body_close_start'];
        foreach ($tokens as $token) {
            if ($bounds['full'] || $bounds['body_open_end'] !== null) {
                if ($bounds['body_open_end'] !== null && $token['start'] >= $bounds['body_open_end']
                    && ($bounds['body_close_start'] === null || $token['start'] < $bounds['body_close_start'])
                    && ($token['type'] === 'tag'
                        || (\in_array($token['type'], ['text', 'raw'], true)
                            && !Dom_SourceTokenizer::isHtmlSpaceOnly(
                                \substr($html, $token['start'], $token['end'] - $token['start'])
                            )))) {
                    $hasFragmentContent = true;
                }
            } elseif (($token['type'] === 'tag' && !$token['closing']
                    && !\in_array($token['name'], ['title', 'meta', 'base', 'link'], true))
                || ($token['type'] === 'text'
                    && !Dom_SourceTokenizer::isHtmlSpaceOnly(
                        \substr($html, $token['start'], $token['end'] - $token['start'])
                    ))
                || ($token['type'] === 'raw' && $token['name'] !== 'title')) {
                $hasFragmentContent = true;
            }
            if ($token['template_depth'] !== 0 || $token['foreign_depth'] !== 0) {
                continue;
            }
            if ($token['type'] === 'comment') {
                $comment = \substr($html, $token['start'], $token['end'] - $token['start']);
                if (\preg_match($markerPattern, $comment, $match) === 1) {
                    $markers[] = [
                        'start' => $token['start'],
                        'end' => $token['end'],
                        'language' => \rawurldecode($match[1]),
                    ];
                }
                continue;
            }
        }
        return [
            'markers' => $markers,
            'body_close' => $bodyClose,
            'has_fragment_content' => $hasFragmentContent,
        ];
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
        return self::applyResolved($html, $languageCode, self::directionFor($languageCode));
    }

    public static function applyResolved(string $html, string $languageCode, string $direction): string
    {
        $languageCode = trim(str_replace('_', '-', $languageCode));
        if ($html === '' || $languageCode === '') {
            return $html;
        }
        $direction = in_array($direction, ['ltr', 'rtl'], true) ? $direction : 'ltr';

        $tokens = Dom_SourceTokenizer::tokenize($html);
        if ($tokens === null) {
            return $html;
        }
        foreach ($tokens as $token) {
            if ($token['type'] === 'text'
                && Dom_SourceTokenizer::isIgnorableDocumentPrefix(
                    substr($html, $token['start'], $token['end'] - $token['start']),
                    $token['start']
                )) {
                continue;
            }
            if (in_array($token['type'], ['comment', 'declaration'], true)) {
                continue;
            }
            if ($token['type'] !== 'tag'
                || $token['closing']
                || $token['name'] !== 'html'
                || $token['template_depth'] !== 0
                || $token['foreign_depth'] !== 0) {
                return $html;
            }

            $opening = substr($html, $token['start'], $token['end'] - $token['start']);
            $remove = [];
            foreach ($token['attributes'] as $attribute) {
                if (!in_array($attribute['name'], ['lang', 'dir'], true)) {
                    continue;
                }
                $start = $attribute['source_start'] - $token['start'];
                while ($start > 0 && Dom_SourceTokenizer::isHtmlSpace($opening[$start - 1])) {
                    $start--;
                }
                $remove[] = ['start' => $start, 'end' => $attribute['source_end'] - $token['start']];
            }
            usort($remove, static fn (array $left, array $right): int => $right['start'] <=> $left['start']);
            foreach ($remove as $span) {
                $opening = substr($opening, 0, $span['start']) . substr($opening, $span['end']);
            }
            $opening = Dom_SourceTokenizer::trimHtmlSpaceRight(substr($opening, 0, -1))
                . ' lang="' . htmlspecialchars($languageCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
                . ' dir="' . $direction . '">';

            return substr($html, 0, $token['start']) . $opening . substr($html, $token['end']);
        }
        return $html;
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
        $head = self::headMetadata($html);
        if ($head === null) {
            return $html;
        }
        $headLinks = $head['links'];

        $param = Dom_Constants::LANGUAGE_URL_PARAMETER_NAME;

                                                                            
                                                                          
                                                                               
        $canonicalBase = self::existingCanonicalUrl($headLinks) ?? self::withoutTrackingArguments($currentUrl);

        $links = '';

                                                         
        $defaultUrl = \remove_query_arg($param, $canonicalBase);
        $links .= '<link rel="alternate" hreflang="x-default" href="'
            . \esc_url($defaultUrl) . '" data-dolet-hreflang />' . "\n";

                                              
        foreach ($allTargetCodes as $code) {
            $url = \add_query_arg($param, \rawurlencode($code), $defaultUrl);
            $links .= '<link rel="alternate" hreflang="'
                . \esc_attr($code) . '" href="'
                . \esc_url($url) . '" data-dolet-hreflang />' . "\n";
        }

                                                                                 
        $canonicalUrl = \strcasecmp($currentCode, Language_Constants::ORIGINAL) === 0
            ? $defaultUrl
            : \add_query_arg($param, \rawurlencode($currentCode), $defaultUrl);
        $links .= '<link rel="canonical" href="'
            . \esc_url($canonicalUrl) . '" data-dolet-hreflang />' . "\n";

        $remove = [];
        foreach ($headLinks as $link) {
            if (self::tagHasAttribute($link['attributes'], 'data-dolet-hreflang')
                || self::linkTagIsCanonical($link['attributes'])) {
                $remove[] = $link;
            }
        }
        $headClose = $head['close_start'];
        \usort($remove, static fn (array $left, array $right): int => $right['start'] <=> $left['start']);
        foreach ($remove as $link) {
            $html = \substr($html, 0, $link['start']) . \substr($html, $link['end']);
            $headClose -= $link['end'] - $link['start'];
        }

        return \substr($html, 0, $headClose)
            . $links
            . \substr($html, $headClose);
    }

                                                                                                                                                    
    private static function existingCanonicalUrl(array $links): ?string
    {
        foreach ($links as $link) {
            $rel = self::tagAttributeValue($link['attributes'], 'rel');
            if ($rel === null) {
                continue;
            }
            $decodedRel = self::decodeAttributeReferences($rel);
            $relations = Dom_SourceTokenizer::splitHtmlSpace(\strtolower($decodedRel));
            if (!\in_array('canonical', $relations, true)) {
                continue;
            }
            $href = self::tagAttributeValue($link['attributes'], 'href');
            if ($href !== null) {
                $url = \html_entity_decode($href, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
                return $url !== '' ? $url : null;
            }
        }

        return null;
    }

       
                     
                         
                                                                                                                                          
             
       
    private static function headMetadata(string $html): ?array
    {
        $tokens = Dom_SourceTokenizer::tokenize($html);
        if ($tokens === null) {
            return null;
        }
        $bounds = Dom_SourceTokenizer::documentBounds($html, $tokens);
        if ($bounds === null || !$bounds['full']
            || $bounds['head_open_end'] === null || $bounds['head_close_start'] === null) {
            return null;
        }
        $headStart = (int) $bounds['head_open_end'];
        $headClose = (int) $bounds['head_close_start'];
        $links = [];
        foreach ($tokens as $token) {
            if ($token['template_depth'] !== 0 || $token['foreign_depth'] !== 0) {
                continue;
            }
            if ($token['start'] < $headStart) {
                continue;
            }
            if ($token['start'] >= $headClose) {
                break;
            }
                                                                           
                                                                              
            if ($token['type'] === 'raw' && $token['name'] === 'noscript'
                && !Dom_SourceTokenizer::isHtmlSpaceOnly(
                    \substr($html, $token['start'], $token['end'] - $token['start'])
                )) {
                return null;
            }
            if ($token['type'] !== 'tag') {
                continue;
            }
            if (!$token['closing'] && $token['name'] === 'link') {
                $links[] = [
                    'start' => $token['start'],
                    'end' => $token['end'],
                    'attributes' => $token['attributes'],
                ];
            }
        }

        return ['close_start' => $headClose, 'links' => $links];
    }

                                                                                                               
    private static function linkTagIsCanonical(array $attributes): bool
    {
        $rel = self::tagAttributeValue($attributes, 'rel');
        if ($rel === null) {
            return false;
        }
        $decodedRel = self::decodeAttributeReferences($rel);
        $relations = Dom_SourceTokenizer::splitHtmlSpace(\strtolower($decodedRel));
        return \in_array('canonical', $relations, true);
    }

                                                                                                               
    private static function tagAttributeValue(array $attributes, string $name): ?string
    {
        foreach ($attributes as $attribute) {
            if ($attribute['name'] === \strtolower($name)) {
                return $attribute['value_start'] === null ? null : $attribute['value'];
            }
        }
        return null;
    }

                                                                                                               
    private static function tagHasAttribute(array $attributes, string $name): bool
    {
        foreach ($attributes as $attribute) {
            if ($attribute['name'] === \strtolower($name)) {
                return true;
            }
        }
        return false;
    }

    private static function decodeAttributeReferences(string $value): string
    {
        $numeric = \preg_replace_callback(
            '/&#(?:[xX]([0-9A-Fa-f]+)|([0-9]+));?/',
            static function (array $match): string {
                $hex = (string) $match[1];
                $decimal = (string) ($match[2] ?? '');
                $code = $hex !== ''
                    ? (int) \hexdec($hex)
                    : (int) $decimal;
                if ($code <= 0 || $code > 0x10FFFF || ($code >= 0xD800 && $code <= 0xDFFF)) {
                    return "\xEF\xBF\xBD";
                }
                return \html_entity_decode('&#' . $code . ';', \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            },
            $value
        );
        return \html_entity_decode($numeric ?? $value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
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
