<?php
declare(strict_types=1);
namespace DoletWP;

\defined('ABSPATH') || exit;

   
                                                             
  
                                                                               
                                                                              
                                                       
   

require_once __DIR__ . '/contracts.php';


                                                                             
                              
                                                                             
  
                                                                            
                                           
                                         
                                             
  
                                                 


                                                                             
         
                                                                             
  
         


                                                                             
        
                                                                             
  
         


                                                                             
          
                                                                             

                                                                             
final class UiVisitor_FloatingLanguageMenuView implements UiVisitor_FloatingLanguageMenuViewInterface
{
                                                         
    private array $items = [];
    private string $accessibleName = '';
    private string $summaryLabel = '';

    public function setAccessibleName(string $accessibleName): void
    {
        $this->accessibleName = $accessibleName;
    }

    public function setSummaryLabel(string $summaryLabel): void
    {
        $this->summaryLabel = $summaryLabel;
    }

    public function addItem(UiVisitor_LanguageMenuItemViewInterface $item): void
    {
        $this->items[] = $item;
    }

    public function render(): string
    {
        if ($this->items === []) {
            return '';
        }

        \usort(
            $this->items,
            static function (
                UiVisitor_LanguageMenuItemViewInterface $a,
                UiVisitor_LanguageMenuItemViewInterface $b
            ): int {
                if ($a->getCode() === Language_Constants::ORIGINAL) {
                    return -1;
                }
                if ($b->getCode() === Language_Constants::ORIGINAL) {
                    return 1;
                }
                return \strcasecmp($a->getCode(), $b->getCode());
            }
        );

        $currentLabel = '';
        $summaryAccessibleName = $this->summaryLabel;
        foreach ($this->items as $item) {
            if ($item->isActive()) {
                $currentLabel = $item->getEndonym();
                $summaryAccessibleName .= ': ' . $currentLabel;
                break;
            }
        }

        $bufferLevel = \ob_get_level();
        \ob_start();
        try { ?>
<div class="dolet-floating-selector notranslate" translate="no" data-no-translate="1">
  <details class="dolet-floating-disclosure" data-dolet-opening="up">
    <summary class="dolet-floating-summary" aria-label="<?php echo \esc_attr($summaryAccessibleName); ?>">
      <span class="dolet-floating-globe" aria-hidden="true"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" focusable="false"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3c3 3.4 3 14.6 0 18M12 3c-3 3.4-3 14.6 0 18"></path></svg></span>
      <span class="dolet-floating-prompt"><?php echo \esc_html($this->summaryLabel); ?>:</span>
      <?php if ($currentLabel !== ''): ?>
        <span class="dolet-floating-summary-label"><?php echo \esc_html($currentLabel); ?></span>
      <?php endif; ?>
      <span class="dolet-floating-marker" aria-hidden="true">&#9662;</span>
    </summary>
    <nav class="dolet-floating-menu" aria-label="<?php echo \esc_attr($this->accessibleName); ?>">
      <ul class="dolet-floating-list">
        <?php foreach ($this->items as $item): ?>
          <li class="dolet-floating-item">
            <?php if ($item->isActive()): ?>
              <span class="dolet-floating-choice is-active" aria-current="true">
                <span class="dolet-floating-endonym"><?php echo \esc_html($item->getEndonym()); ?></span>
                <code class="dolet-floating-code"><?php echo \esc_html($item->getCode()); ?></code>
                <?php if ($item->getInterlinguaNameOrNull() !== null && $item->getInterlinguaNameOrNull() !== ''): ?>
                  <span class="dolet-floating-ia">(<?php echo \esc_html((string) $item->getInterlinguaNameOrNull()); ?>)</span>
                <?php endif; ?>
              </span>
            <?php else: ?>
              <a class="dolet-floating-choice" href="<?php echo \esc_url($item->getUrl()); ?>" data-dolet-preserve-l="1">
                <span class="dolet-floating-endonym"><?php echo \esc_html($item->getEndonym()); ?></span>
                <code class="dolet-floating-code"><?php echo \esc_html($item->getCode()); ?></code>
                <?php if ($item->getInterlinguaNameOrNull() !== null && $item->getInterlinguaNameOrNull() !== ''): ?>
                  <span class="dolet-floating-ia">(<?php echo \esc_html((string) $item->getInterlinguaNameOrNull()); ?>)</span>
                <?php endif; ?>
              </a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </details>
</div>
<?php
            return (string) \ob_get_clean();
        } catch (\Throwable $exception) {
            while (\ob_get_level() > $bufferLevel) {
                \ob_end_clean();
            }
            throw $exception;
        }
    }
}

   
                                                              
  
                                                                      
   
final class UiVisitor_LanguageMenuItemView implements UiVisitor_LanguageMenuItemViewInterface
{
    public function __construct(
        private string $code,
        private string $endonym,
        private ?string $interlinguaName,
        private string $url,
        private bool $active
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getEndonym(): string
    {
        return $this->endonym;
    }

    public function getInterlinguaNameOrNull(): ?string
    {
        return $this->interlinguaName;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}


   
                                                    
  
                                                                    
   
final class UiVisitor_LanguageMenuView implements UiVisitor_LanguageMenuViewInterface
{
                                                         
    private array $items = [];

    private string $wrapperClass = 'dolet-lang-menu';
    private bool   $showCodes    = true;

    public function setWrapperClass(string $class): void
    {
        $this->wrapperClass = $class;
    }

    public function setShowCodes(bool $showCodes): void
    {
        $this->showCodes = $showCodes;
    }

    public function addItem(UiVisitor_LanguageMenuItemViewInterface $item): void
    {
        $this->items[] = $item;
    }

    public function render(): string
    {
        if ($this->items === []) {
            return '';
        }

                                                                                
        \usort(
            $this->items,
               
                                                                
                                                                
               
            function (UiVisitor_LanguageMenuItemViewInterface $a, UiVisitor_LanguageMenuItemViewInterface $b): int {
                if ($a->getCode() === Language_Constants::ORIGINAL) {
                    return -1;
                }
                if ($b->getCode() === Language_Constants::ORIGINAL) {
                    return 1;
                }
                return \strcasecmp($a->getCode(), $b->getCode());
            }
        );

        $bufferLevel = \ob_get_level();
        \ob_start();
        try { ?>
<nav class="<?php echo \esc_attr($this->wrapperClass); ?> notranslate" translate="no" data-no-translate="1" aria-label="<?php echo \esc_attr(Core_Strings::get('nav_aria_label')); ?>">
  <ul class="dolet-lang-list">
    <?php foreach ($this->items as $it): ?>
      <li class="dolet-lang-item">
        <?php if ($it->isActive()): ?>
        <span class="dolet-lang-choice is-active" aria-current="true">
        <?php else: ?>
        <a class="dolet-lang-choice" href="<?php echo \esc_url($it->getUrl()); ?>" data-dolet-preserve-l="1">
        <?php endif; ?>
          <?php if ($this->showCodes): ?>
            <code class="dolet-lang-code"><?php echo \esc_html($it->getCode()); ?></code>
          <?php endif; ?>
          <span class="dolet-lang-endonym"><?php echo \esc_html($it->getEndonym()); ?></span>
        <?php if ($it->isActive()): ?>
        </span>
        <?php else: ?>
        </a>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
<?php
            return (string) \ob_get_clean();
        } catch (\Throwable $e) {
            while (\ob_get_level() > $bufferLevel) {
                \ob_end_clean();
            }
            throw $e;
        }
    }
}


   
                                                         
  
                                                                        
                                                                         
  
                                                                               
                                                            
   
final class UiVisitor_LanguageMenuWidget extends \WP_Widget implements UiVisitor_LanguageMenuWidgetInterface
{
    public function __construct()
    {
        parent::__construct(
            'dolet_lang_menu',
            Core_Strings::get('widget_title'),
            ['description' => Core_Strings::get('widget_description')]
        );
    }

       
                                       
                                           
       
    public function widget($args, $instance): void
    {
        $showCodes       = !\array_key_exists('show_codes', $instance) || (bool) $instance['show_codes'];
        $wrapperClass    = isset($instance['wrapper_class']) ? (string) $instance['wrapper_class'] : 'dolet-lang-menu';
        $menu = Routines_PublicLanguageMenu::renderMenuStatic($showCodes, $wrapperClass);
        if ($menu === '') {
            return;
        }

        echo \wp_kses((string) ($args['before_widget'] ?? ''), \wp_kses_allowed_html('post'));

                                                                              
        echo Routines_PublicLanguageMenu::isDeferredMenuMarkup($menu)
            ? $menu
            : self::sanitizeMenu($menu);


        echo \wp_kses((string) ($args['after_widget'] ?? ''), \wp_kses_allowed_html('post'));
    }

                                               
    public function form($instance): void
    {
        $showCodes       = !\array_key_exists('show_codes', $instance) || (bool) $instance['show_codes'];
        $wrapperClass    = isset($instance['wrapper_class']) ? (string) $instance['wrapper_class'] : 'dolet-lang-menu';
        ?>
        <p>
          <label>
            <input type="checkbox" name="<?php echo \esc_attr($this->get_field_name('show_codes')); ?>" value="1" <?php \checked($showCodes); ?> />
            <?php echo \esc_html(Core_Strings::get('menu_show_codes')); ?>
          </label>
        </p>
        <p>
          <label for="<?php echo \esc_attr($this->get_field_id('wrapper_class')); ?>"><?php echo \esc_html(Core_Strings::get('menu_wrapper_class')); ?></label>
          <input class="widefat" id="<?php echo \esc_attr($this->get_field_id('wrapper_class')); ?>" name="<?php echo \esc_attr($this->get_field_name('wrapper_class')); ?>" type="text" value="<?php echo \esc_attr($wrapperClass); ?>" />
        </p>
        <?php
    }

                                                   
    private static function languageMenuAllowedHtml(): array
    {
        return [
            'nav' => [
                'aria-label' => true,
                'class' => true,
                'data-no-translate' => true,
                'translate' => true,
            ],
            'ul' => ['class' => true],
            'li' => ['class' => true],
            'span' => [
                'aria-current' => true,
                'class' => true,
                'data-dolet-language-menu' => true,
                'data-no-translate' => true,
            ],
            'a' => ['class' => true, 'data-dolet-preserve-l' => true, 'href' => true],
            'code' => ['class' => true],
        ];
    }

    public static function sanitizeMenu(string $html): string
    {
        return \wp_kses($html, self::languageMenuAllowedHtml());
    }

       
                                               
                                               
                                  
       
    public function update($new_instance, $old_instance): array
    {
        return [
            'show_codes'       => !empty($new_instance['show_codes']),
            'wrapper_class'    => isset($new_instance['wrapper_class'])
                ? \sanitize_text_field((string) $new_instance['wrapper_class'])
                : 'dolet-lang-menu',
        ];
    }
}
