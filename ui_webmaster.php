<?php
declare(strict_types=1);
namespace DoletWP;

if (! defined('ABSPATH')) {
    exit;
}

   
                                                                            
  
                                                                    
                                                                        
  
                                                                     
                                                              
                                                                  
                    
   

require_once __DIR__ . '/contracts.php';

                                                                             
          
                                                                             

   
                                                                   
  
                                                            
                                                                       
                                         
   
final class UiWebmaster_PaletteItemView implements UiWebmaster_PaletteItemViewInterface
{
    public function __construct(
        private string $code,
        private string $label,
        private bool $disabled
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}


   
                                                                   
  
                                                                   
                                                   
   
final class UiWebmaster_DebugToggleView implements UiWebmaster_DebugToggleViewInterface
{
    private string $optionName = '';
    private bool $checked = false;
    private string $labelHtml = '';

    public function setOptionName(string $optionName): void
    {
        $this->optionName = $optionName;
    }

    public function setChecked(bool $checked): void
    {
        $this->checked = $checked;
    }

    public function setLabel(string $labelHtml): void
    {
        $this->labelHtml = $labelHtml;
    }

    public function render(): string
    {
        $checked = $this->checked ? 'checked' : '';
        $name    = esc_attr($this->optionName);

        return '<div style="margin-top:1.25rem;padding-top:.75rem;border-top:1px solid #eee;">'
            . '<label style="display:inline-flex;gap:.5rem;align-items:center;">'
            . '<input type="checkbox" name="' . $name . '" value="1" ' . $checked . '>'
            . '<span>' . $this->labelHtml . '</span>'
            . '</label></div>';
    }
}


   
                                                                        
  
                                                                     
                                                                      
   
final class UiWebmaster_LanguagesBuilderView implements UiWebmaster_LanguagesBuilderViewInterface
{
                     
    private ?string $labelSource        = null;
    private ?string $labelTargets       = null;
    private ?string $labelPalette       = null;
    private ?string $labelAddCustom     = null;
    private ?string $hintSource         = null;
    private ?string $hintTargets        = null;
    private ?string $placeholderSearch  = null;
    private ?string $titleChipAdd       = null;
    private ?string $titleChipDisabled  = null;

                      
    private string $sourceHiddenName    = '';
    private string $sourceHiddenValue   = 'original';
    private bool   $sourceIsOriginal    = true;
    private bool   $sourceIsSite        = false;
    private bool   $sourceIsSpecific    = false;
    private string $siteCode            = '';
                                    
    private array $catalogNoOriginal    = [];

                       
    private string $customizationFieldBase = '';                                                      
    private string $sourceDisplayCode      = 'original';
    private string $sourceDefaultName      = '';
    private string $sourceCurrentName      = '';
    private string $sourceCurrentNote      = '';

              
                        
    private array  $targetsSorted            = [];
    private string $customizationsOptionName = '';                                                      
                                    
    private array  $catalogForDefaults       = [];
    private string $targetsFieldName         = '';                                               

                                 
                        
    private array  $customCodes              = [];
    private string $customListOptionName     = '';                                                         

              
                                                      
    private array $palette = [];

              
    private string $targetsHolderDomId = 'dolet_targets_holder';

               
    private string $paletteAddTitle      = '';
    private string $paletteAlreadyTitle  = '';

                                                                             
                     
                                                                             

    public function setLabelSource(string $labelHtml): void
    {
        $this->labelSource = $labelHtml;
    }

    public function setHintSource(string $hintHtml): void
    {
        $this->hintSource = $hintHtml;
    }

    public function setLabelTargets(string $labelHtml): void
    {
        $this->labelTargets = $labelHtml;
    }

    public function setHintTargets(string $hintHtml): void
    {
        $this->hintTargets = $hintHtml;
    }

    public function setLabelPalette(string $labelHtml): void
    {
        $this->labelPalette = $labelHtml;
    }

    public function setLabelAddCustom(string $labelHtml): void
    {
        $this->labelAddCustom = $labelHtml;
    }

    public function setPlaceholderSearch(string $placeholder): void
    {
        $this->placeholderSearch = $placeholder;
    }

    public function setTitleChipAdd(string $title): void
    {
        $this->titleChipAdd = $title;
    }

    public function setTitleChipDisabled(string $title): void
    {
        $this->titleChipDisabled = $title;
    }

                                                                             
                      
                                                                             

    public function setSourceHiddenName(string $fieldName): void
    {
        $this->sourceHiddenName = $fieldName;
    }

    public function setSourceHiddenValue(string $value): void
    {
        $this->sourceHiddenValue = $value;
    }

    public function setSourceModeIsOriginal(bool $isOriginal): void
    {
        $this->sourceIsOriginal = $isOriginal;
    }

    public function setSourceModeIsSite(bool $isSite): void
    {
        $this->sourceIsSite = $isSite;
    }

    public function setSourceModeIsSpecific(bool $isSpecific): void
    {
        $this->sourceIsSpecific = $isSpecific;
    }

    public function setSiteCode(string $siteCode): void
    {
        $this->siteCode = $siteCode;
    }

       
                                           
       
    public function setCatalogNoOriginal(array $catalog): void
    {
        $this->catalogNoOriginal = $catalog;
    }

                                                                             
                       
                                                                             

    public function setSourceCustomizationFieldBase(string $fieldBase): void
    {
        $this->customizationFieldBase = $fieldBase;
    }

    public function setSourceDisplayCode(string $code): void
    {
        $this->sourceDisplayCode = $code;
    }

    public function setSourceDefaultName(string $name): void
    {
        $this->sourceDefaultName = $name;
    }

    public function setSourceCurrentName(string $name): void
    {
        $this->sourceCurrentName = $name;
    }

    public function setSourceCurrentNote(string $note): void
    {
        $this->sourceCurrentNote = $note;
    }

                                                                             
              
                                                                             

    public function setTargetsHolderDomId(string $domId): void
    {
        $this->targetsHolderDomId = $domId;
    }

       
                                      
       
    public function setTargetsSorted(array $codes): void
    {
        $this->targetsSorted = $codes;
    }

    public function setLanguageCustomizationsOptionName(string $optionName): void
    {
        $this->customizationsOptionName = $optionName;
    }

       
                                           
       
    public function setCatalogForDefaults(array $catalog): void
    {
        $this->catalogForDefaults = $catalog;
    }

       
                                      
       
    public function setCustomCodes(array $codes): void
    {
        $this->customCodes = $codes;
    }

    public function setCustomListOptionName(string $optionName): void
    {
        $this->customListOptionName = $optionName;
    }

                                                                             
              
                                                                             

    public function addPaletteItem(UiWebmaster_PaletteItemViewInterface $item): void
    {
        $this->palette[] = $item;
    }

    public function setPaletteAddTitle(string $title): void
    {
        $this->paletteAddTitle = $title;
    }

    public function setPaletteAlreadyTitle(string $title): void
    {
        $this->paletteAlreadyTitle = $title;
    }

                                                                             
              
                                                                             

    private static function e(?string $s): string
    {
        return esc_html($s ?? '');
    }

    private static function a(?string $s): string
    {
        return esc_attr($s ?? '');
    }

                                                                             
             
                                                                             

    public function render(): string
    {
                          
        if ($this->labelSource === null || $this->labelTargets === null || $this->labelPalette === null) {
            throw new \RuntimeException('UiWebmaster_LanguagesBuilderView: missing required labels.');
        }
                                
        if ($this->customizationsOptionName === '' || $this->customizationFieldBase === '') {
            throw new \RuntimeException('UiWebmaster_LanguagesBuilderView: missing customization option names.');
        }

                                                              
        if ($this->targetsFieldName === '') {
            $this->targetsFieldName = Language_AdminOptionNames::TARGET_LANGUAGES;
        }

        $sourceCheckedOriginal = $this->sourceIsOriginal ? 'checked' : '';
        $sourceCheckedSite     = $this->sourceIsSite ? 'checked' : '';
        $sourceCheckedSpecific = $this->sourceIsSpecific ? 'checked' : '';
        $siteDisabled          = $this->siteCode === '' ? 'disabled' : '';
        $siteLabel             = $this->siteCode
            ? ($this->siteCode . ' — ' . ($this->catalogNoOriginal[$this->siteCode] ?? $this->siteCode))
            : Core_Strings::get('site_language_unavailable');

                                 
        $optionsHtml = '';
        foreach ($this->catalogNoOriginal as $code => $name) {
            $sel    = ($this->sourceIsSpecific && $this->sourceHiddenValue === $code) ? ' selected' : '';
            $codeA  = self::a($code);
            $label  = self::e($code . ' — ' . $name);
            $optionsHtml .= "<option value=\"{$codeA}\"{$sel}>{$label}</option>";
        }

                                                        
        $existingRaw = get_option($this->customizationsOptionName, []);
        $existingRaw = is_array($existingRaw) ? $existingRaw : [];

        $normalizeCode = function (string $code): string {
            $code = str_replace('_', '-', trim($code));
            if ($code === '') {
                return $code;
            }
            $parts    = explode('-', $code);
            $parts[0] = strtolower($parts[0]);
            for ($i = 1; $i < count($parts); $i++) {
                $p         = $parts[$i];
                $len       = strlen($p);
                $parts[$i] = ($len === 4) ? ucfirst(strtolower($p)) : strtoupper($p);
            }

            return implode('-', $parts);
        };

        $existing = [];
        foreach ($existingRaw as $k => $row) {
            $nk = ($k === 'original') ? 'original' : $normalizeCode((string) $k);
            if (!is_array($row)) {
                continue;
            }
            $name = isset($row['name']) ? (string) $row['name'] : '';
            $note = isset($row['note']) ? (string) $row['note'] : '';
            $r    = [];
            if (trim($name) !== '') {
                $r['name'] = $name;
            }
            if (trim($note) !== '') {
                $r['note'] = $note;
            }
            if (!empty($r)) {
                $existing[$nk] = $r;
            }
        }

                                                                
        $targetRows    = '';
        $targetsSorted = $this->targetsSorted;
        sort($targetsSorted, SORT_STRING);

        foreach ($targetsSorted as $code) {
            $codeA       = self::a($code);
            $defaultName = $this->catalogForDefaults[$code] ?? $code;

                                                                  
            $currentName = isset($existing[$code]['name']) ? (string) $existing[$code]['name'] : '';
            $currentNote = isset($existing[$code]['note']) ? (string) $existing[$code]['note'] : '';

            $nameField   = $this->customizationsOptionName . '[' . $code . '][name]';
            $noteField   = $this->customizationsOptionName . '[' . $code . '][note]';
            $nameFieldA  = self::a($nameField);
            $noteFieldA  = self::a($noteField);
            $placeholderName = self::a($defaultName);

            $valueNameA  = self::a($currentName);
            $valueNoteA  = self::a($currentNote);
            $notePlaceholderA = self::a(Core_Strings::get('note_placeholder'));

            $targetRows .= '<tr data-code="' . $codeA . '">'
                . '<td><code>' . $codeA . '</code></td>'
                . '<td><input type="text" style="width:100%" name="' . $nameFieldA
                . '" value="' . $valueNameA . '" placeholder="' . $placeholderName . '"></td>'
                . '<td><input type="text" style="width:100%" name="' . $noteFieldA
                . '" value="' . $valueNoteA . '" placeholder="' . $notePlaceholderA . '"></td>'
                . '<td style="width:80px;"><button type="button" class="button link-delete dolet-remove-row">×</button></td>'
                . '</tr>';
        }

                                     
        $customHidden = '';
        foreach ($this->customCodes as $cc) {
            $ccA   = self::a($cc);
            $nameA = self::a($this->customListOptionName) . '[]';
            $customHidden .= "<input type=\"hidden\" name=\"{$nameA}\" value=\"{$ccA}\" class=\"dolet-custom-code\">";
        }

                  
        $paletteButtons   = '';
        $btnTitleAdd      = self::a($this->paletteAddTitle);
        $btnTitleDisabled = self::a($this->paletteAlreadyTitle);

        foreach ($this->palette as $it) {
            $disabled = $it->isDisabled();
            $cls      = $disabled ? 'button disabled dolet-chip' : 'button dolet-chip';
            $title    = $disabled
                ? ($this->titleChipDisabled ?? 'Already added or selected as Source')
                : ($this->titleChipAdd ?? 'Add');
            $titleA   = self::a($title);
            $codeA    = self::a($it->getCode());
            $label    = self::e($it->getLabel());
            $disAttr  = $disabled ? 'disabled' : '';

            $paletteButtons .= '<button type="button" class="' . self::a($cls)
                . '" data-code="' . $codeA
                . '" data-title-add="' . $btnTitleAdd
                . '" data-title-disabled="' . $btnTitleDisabled . '" '
                . $disAttr . ' title="' . $titleA . '">' . $label . '</button>';
        }

                                                                    
        $rootDataA = [
            'customizationsOption' => self::a($this->customizationsOptionName),
            'customListOption'     => self::a($this->customListOptionName),
            'targetsHolderId'      => self::a($this->targetsHolderDomId),
            'targetsField'         => self::a($this->targetsFieldName),
        ];

        $labelSource  = self::e($this->labelSource);
        $labelTargets = self::e($this->labelTargets);
        $labelPalette = self::e($this->labelPalette);
        $labelAdd     = self::e($this->labelAddCustom ?? 'Add custom language');

        $hintSource = $this->hintSource
            ? '<p style="margin:.25rem 0 1rem;opacity:.8">' . self::e($this->hintSource) . '</p>'
            : '';

        $hintTargets = $this->hintTargets
            ? '<p style="margin:.25rem 0 1rem;opacity:.8">' . self::e($this->hintTargets) . '</p>'
            : '';

        $placeholder = self::a($this->placeholderSearch ?? Core_Strings::get('search_placeholder'));

        $sourceHiddenNameA = self::a($this->sourceHiddenName);
        $sourceHiddenValA  = self::a($this->sourceHiddenValue);
        $siteCodeA         = self::a($this->siteCode);
        $siteLabelE        = self::e($siteLabel);
        $customFieldBaseA  = self::a($this->customizationFieldBase);
        $srcDisplayCodeA   = self::a($this->sourceDisplayCode);
        $srcDefaultA       = self::a($this->sourceDefaultName);
        $srcNameValA       = self::a($this->sourceCurrentName);
        $srcNoteValA       = self::a($this->sourceCurrentNote);
        $targetsHolderIdA  = self::a($this->targetsHolderDomId);

        $strOriginalE      = self::e(Core_Strings::get('original_option'));
        $strOriginalHintE  = self::e(Core_Strings::get('original_option_hint'));
        $strSiteLangE      = self::e(Core_Strings::get('site_language_option'));
        $strSelectDefaultE = self::e(Core_Strings::get('select_default'));
        $strColCodeE       = self::e(Core_Strings::get('col_code'));
        $strColNameE       = self::e(Core_Strings::get('col_display_name'));
        $strColNoteE       = self::e(Core_Strings::get('col_note'));
        $strColRemoveE     = self::e(Core_Strings::get('col_remove'));
        $strNotePlaceholderA = self::a(Core_Strings::get('note_placeholder'));
        $strSpecifySourceE   = self::e(Core_Strings::get('specify_source_option'));
        $strSrcNameLabelE    = self::e(Core_Strings::get('source_display_name_label'));
        $strSrcNoteLabelE    = self::e(Core_Strings::get('source_note_label'));
        $strSrcNotePlaceholderA = self::a(Core_Strings::get('source_note_placeholder'));

        return strtr(<<<'HTML'
<div id="dolet-builder-root"
     data-customizations-option="{$rootDataA['customizationsOption']}"
     data-custom-list-option="{$rootDataA['customListOption']}"
     data-targets-holder-id="{$rootDataA['targetsHolderId']}"
     data-targets-field="{$rootDataA['targetsField']}"
     data-str-note-placeholder="{$strNotePlaceholderA}">

  <div class="card" style="margin:1rem 0;padding:1rem;">
    <h3 style="margin:.2rem 0 1rem;">{$labelSource}</h3>
    {$hintSource}
    <input type="hidden" id="dolet_source_code" name="{$sourceHiddenNameA}" value="{$sourceHiddenValA}">
    <div style="display:flex;flex-direction:column;gap:.5rem;">
      <label style="display:flex;align-items:center;gap:.5rem;">
        <input type="radio" name="dolet_source_mode" value="original" {$sourceCheckedOriginal}>
        <span><strong>{$strOriginalE}</strong> <span style="opacity:.7;">({$strOriginalHintE})</span></span>
      </label>
      <label style="display:flex;align-items:center;gap:.5rem;">
        <input type="radio" name="dolet_source_mode" value="site" data-site-code="{$siteCodeA}" {$siteDisabled} {$sourceCheckedSite}>
        <span><strong>{$strSiteLangE}</strong> <span style="opacity:.7;">({$siteLabelE})</span></span>
      </label>
      <div style="display:flex;align-items:center;gap:.5rem;">
        <label style="display:flex;align-items:center;gap:.5rem;margin:0;">
          <input type="radio" name="dolet_source_mode" value="specific" {$sourceCheckedSpecific}>
          <span><strong>{$strSpecifySourceE}</strong></span>
        </label>
        <select id="dolet-source-select" style="min-width:260px;">
          <option value="">{$strSelectDefaultE}</option>
          {$optionsHtml}
        </select>
      </div>
    </div>
    <div style="margin-top:.75rem;display:grid;grid-template-columns:1fr 1fr;gap:.75rem;max-width:880px;">
      <div>
        <label style="display:block;margin-bottom:.25rem;">{$strSrcNameLabelE}</label>
        <input type="text" style="width:100%" id="dolet-source-name"
               data-field-base="{$customFieldBaseA}"
               name="{$customFieldBaseA}[{$srcDisplayCodeA}][name]"
               value="{$srcNameValA}" placeholder="{$srcDefaultA}">
      </div>
      <div>
        <label style="display:block;margin-bottom:.25rem;">{$strSrcNoteLabelE}</label>
        <input type="text" style="width:100%" id="dolet-source-note"
               data-field-base="{$customFieldBaseA}"
               name="{$customFieldBaseA}[{$srcDisplayCodeA}][note]"
               value="{$srcNoteValA}" placeholder="{$strSrcNotePlaceholderA}">
      </div>
    </div>
  </div>

  <div class="card" style="margin:1rem 0;padding:1rem;">
    <h3 style="margin:.2rem 0 1rem;">{$labelTargets}</h3>
    {$hintTargets}
    <div id="{$targetsHolderIdA}"></div>
    {$customHidden}
    <table class="widefat striped" id="dolet-active-table">
      <thead><tr><th>{$strColCodeE}</th><th>{$strColNameE}</th><th>{$strColNoteE}</th><th>{$strColRemoveE}</th></tr></thead>
      <tbody>
        {$targetRows}
      </tbody>
    </table>
  </div>

  <div style="margin:.5rem 0 1rem;">
    <button type="button" class="button" id="dolet-add-custom">{$labelAdd}</button>
  </div>

  <div class="card" style="margin:1rem 0;padding:1rem;">
    <h3 style="margin:.2rem 0 .75rem;">{$labelPalette}</h3>
    <input type="text" id="dolet-filter" placeholder="{$placeholder}" style="width:100%;max-width:420px;margin:0 0 .75rem;">
    <div id="dolet-palette" style="display:flex;flex-wrap:wrap;gap:.5rem;max-height:300px;overflow:auto;">
      {$paletteButtons}
    </div>
  </div>
</div>
HTML,
            [
                '{$customFieldBaseA}' => $customFieldBaseA,
                '{$customHidden}' => $customHidden,
                '{$hintSource}' => $hintSource,
                '{$hintTargets}' => $hintTargets,
                '{$labelAdd}' => $labelAdd,
                '{$labelPalette}' => $labelPalette,
                '{$labelSource}' => $labelSource,
                '{$labelTargets}' => $labelTargets,
                '{$optionsHtml}' => $optionsHtml,
                '{$paletteButtons}' => $paletteButtons,
                '{$placeholder}' => $placeholder,
                '{$rootDataA[\'customListOption\']}' => $rootDataA['customListOption'],
                '{$rootDataA[\'customizationsOption\']}' => $rootDataA['customizationsOption'],
                '{$rootDataA[\'targetsField\']}' => $rootDataA['targetsField'],
                '{$rootDataA[\'targetsHolderId\']}' => $rootDataA['targetsHolderId'],
                '{$siteCodeA}' => $siteCodeA,
                '{$siteDisabled}' => $siteDisabled,
                '{$siteLabelE}' => $siteLabelE,
                '{$sourceCheckedOriginal}' => $sourceCheckedOriginal,
                '{$sourceCheckedSite}' => $sourceCheckedSite,
                '{$sourceCheckedSpecific}' => $sourceCheckedSpecific,
                '{$sourceHiddenNameA}' => $sourceHiddenNameA,
                '{$sourceHiddenValA}' => $sourceHiddenValA,
                '{$srcDefaultA}' => $srcDefaultA,
                '{$srcDisplayCodeA}' => $srcDisplayCodeA,
                '{$srcNameValA}' => $srcNameValA,
                '{$srcNoteValA}' => $srcNoteValA,
                '{$strColCodeE}' => $strColCodeE,
                '{$strColNameE}' => $strColNameE,
                '{$strColNoteE}' => $strColNoteE,
                '{$strColRemoveE}' => $strColRemoveE,
                '{$strNotePlaceholderA}' => $strNotePlaceholderA,
                '{$strOriginalE}' => $strOriginalE,
                '{$strOriginalHintE}' => $strOriginalHintE,
                '{$strSelectDefaultE}' => $strSelectDefaultE,
                '{$strSiteLangE}' => $strSiteLangE,
                '{$strSpecifySourceE}' => $strSpecifySourceE,
                '{$strSrcNameLabelE}' => $strSrcNameLabelE,
                '{$strSrcNoteLabelE}' => $strSrcNoteLabelE,
                '{$strSrcNotePlaceholderA}' => $strSrcNotePlaceholderA,
                '{$targetRows}' => $targetRows,
                '{$targetsHolderIdA}' => $targetsHolderIdA,
            ]
        );
    }
}
