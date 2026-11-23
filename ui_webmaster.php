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

        return '<div class="dolet-settings-toggle">'
            . '<label class="dolet-settings-toggle-label">'
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
            $code = (string) $code;
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
            $name = isset($row['name']) && \is_string($row['name']) ? $row['name'] : '';
            $note = isset($row['note']) && \is_string($row['note']) ? $row['note'] : '';
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
                . '<td><input type="text" class="dolet-field-input" name="' . $nameFieldA
                . '" value="' . $valueNameA . '" placeholder="' . $placeholderName . '"></td>'
                . '<td><input type="text" class="dolet-field-input" name="' . $noteFieldA
                . '" value="' . $valueNoteA . '" placeholder="' . $notePlaceholderA . '"></td>'
                . '<td class="dolet-remove-column"><button type="button" class="button link-delete dolet-remove-row">×</button></td>'
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
            ? '<p class="dolet-builder-hint">' . self::e($this->hintSource) . '</p>'
            : '';

        $hintTargets = $this->hintTargets
            ? '<p class="dolet-builder-hint">' . self::e($this->hintTargets) . '</p>'
            : '';

        $placeholder = self::a($this->placeholderSearch ?? Core_Strings::get('search_placeholder'));
        $searchLabelE = self::e(Core_Strings::get('search_label'));
        $chooserSummaryE = self::e(Core_Strings::get('choose_languages_summary'));
        $noTargetsE = self::e(Core_Strings::get('no_targets_selected'));
        $noTargetsA = self::a(Core_Strings::get('no_targets_selected'));
        $targetTableClassA = $targetRows === '' ? 'widefat striped is-empty' : 'widefat striped';
        $targetEmptyRow = $targetRows === ''
            ? '<tr data-dolet-empty-targets><td colspan="4" class="dolet-empty-targets">' . $noTargetsE . '</td></tr>'
            : '';

        $sourceHiddenNameA = self::a($this->sourceHiddenName);
        $sourceHiddenValA  = self::a($this->sourceHiddenValue);
        $siteCodeA         = self::a($this->siteCode);
        $siteLabelE        = self::e($siteLabel);
        $customFieldBaseA  = self::a($this->customizationFieldBase);
        $srcDisplayCodeA   = self::a($this->sourceDisplayCode);
        $srcDefaultA       = self::a($this->sourceDefaultName);
        $srcNameValA       = self::a($this->sourceCurrentName);
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

        return strtr(<<<'HTML'
<div id="dolet-builder-root"
     data-customizations-option="{$rootDataA['customizationsOption']}"
     data-custom-list-option="{$rootDataA['customListOption']}"
     data-targets-holder-id="{$rootDataA['targetsHolderId']}"
     data-targets-field="{$rootDataA['targetsField']}"
     data-str-note-placeholder="{$strNotePlaceholderA}"
     data-str-no-targets="{$noTargetsA}">

  <div class="card dolet-builder-card">
    <h3 class="dolet-builder-heading">{$labelSource}</h3>
    {$hintSource}
    <input type="hidden" id="dolet_source_code" name="{$sourceHiddenNameA}" value="{$sourceHiddenValA}">
    <div class="dolet-source-options">
      <label class="dolet-source-choice">
        <input type="radio" name="dolet_source_mode" value="original" {$sourceCheckedOriginal}>
        <span><strong>{$strOriginalE}</strong> <span class="dolet-builder-muted">({$strOriginalHintE})</span></span>
      </label>
      <label class="dolet-source-choice">
        <input type="radio" name="dolet_source_mode" value="site" data-site-code="{$siteCodeA}" {$siteDisabled} {$sourceCheckedSite}>
        <span><strong>{$strSiteLangE}</strong> <span class="dolet-builder-muted">({$siteLabelE})</span></span>
      </label>
      <div class="dolet-source-specific">
        <label class="dolet-source-choice dolet-source-choice-specific">
          <input type="radio" name="dolet_source_mode" value="specific" {$sourceCheckedSpecific}>
          <span><strong>{$strSpecifySourceE}</strong></span>
        </label>
        <select id="dolet-source-select" class="dolet-source-select">
          <option value="">{$strSelectDefaultE}</option>
          {$optionsHtml}
        </select>
      </div>
    </div>
    <div class="dolet-source-fields">
      <div>
        <label class="dolet-field-label">{$strSrcNameLabelE}</label>
        <input type="text" class="dolet-field-input" id="dolet-source-name"
               data-field-base="{$customFieldBaseA}"
               name="{$customFieldBaseA}[{$srcDisplayCodeA}][name]"
               value="{$srcNameValA}" placeholder="{$srcDefaultA}">
      </div>
    </div>
  </div>

  <div class="card dolet-builder-card">
    <h3 class="dolet-builder-heading">{$labelTargets}</h3>
    {$hintTargets}
    <div id="{$targetsHolderIdA}"></div>
    {$customHidden}
    <table id="dolet-active-table" class="{$targetTableClassA}">
      <thead><tr><th>{$strColCodeE}</th><th>{$strColNameE}</th><th>{$strColNoteE}</th><th>{$strColRemoveE}</th></tr></thead>
      <tbody>
        {$targetRows}
        {$targetEmptyRow}
      </tbody>
    </table>
  </div>

  <div class="dolet-builder-actions">
    <button type="button" class="button" id="dolet-add-custom">{$labelAdd}</button>
  </div>

  <div class="card dolet-builder-card">
    <h3 class="dolet-builder-heading dolet-builder-heading-compact">{$labelPalette}</h3>
    <details class="dolet-language-chooser">
      <summary>{$chooserSummaryE}</summary>
      <label for="dolet-filter" class="dolet-field-label">{$searchLabelE}</label>
      <input type="search" id="dolet-filter" class="dolet-palette-filter" placeholder="{$placeholder}" aria-controls="dolet-palette" autocomplete="off">
      <div id="dolet-palette" class="dolet-palette">
        {$paletteButtons}
      </div>
    </details>
  </div>
</div>
HTML,
            [
                '{$customFieldBaseA}' => $customFieldBaseA,
                '{$customHidden}' => $customHidden,
                '{$chooserSummaryE}' => $chooserSummaryE,
                '{$hintSource}' => $hintSource,
                '{$hintTargets}' => $hintTargets,
                '{$labelAdd}' => $labelAdd,
                '{$labelPalette}' => $labelPalette,
                '{$labelSource}' => $labelSource,
                '{$labelTargets}' => $labelTargets,
                '{$optionsHtml}' => $optionsHtml,
                '{$paletteButtons}' => $paletteButtons,
                '{$placeholder}' => $placeholder,
                '{$noTargetsA}' => $noTargetsA,
                '{$searchLabelE}' => $searchLabelE,
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
                '{$targetRows}' => $targetRows,
                '{$targetEmptyRow}' => $targetEmptyRow,
                '{$targetTableClassA}' => $targetTableClassA,
                '{$targetsHolderIdA}' => $targetsHolderIdA,
            ]
        );
    }
}
