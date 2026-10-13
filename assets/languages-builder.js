(function(){
  class DoletLanguagesBuilder {
    constructor(root) {
      this.root = root;
      this.cfg = {
        customizations: root.dataset.customizationsOption || '',
        customList:     root.dataset.customListOption || '',
        holderId:       root.dataset.targetsHolderId || 'dolet_targets_holder',
        targetsField:   root.dataset.targetsField || '',
        strNotePlaceholder: root.dataset.strNotePlaceholder || ''
      };
              
      this.$ = (q,ctx=document)=>ctx.querySelector(q);
      this.$$= (q,ctx=document)=>Array.from(ctx.querySelectorAll(q));
      this.sourceHidden    = this.$('#dolet_source_code');
      this.sourceModes     = this.$$('input[name="dolet_source_mode"]');
      this.sourceSelect    = this.$('#dolet-source-select');
      this.siteRadio       = this.sourceModes.find(r=>r.value==='site');
      this.activeBody      = this.$('#dolet-active-table tbody');
      this.holder          = document.getElementById(this.cfg.holderId);
      this.palette         = this.$('#dolet-palette');
      this.filter          = this.$('#dolet-filter');
      this.addCustomBtn    = this.$('#dolet-add-custom');
      this.sourceNameInp   = this.$('#dolet-source-name');
      this.sourceNoteInp   = this.$('#dolet-source-note');
    }
    init() {
                  
      this.sourceModes.forEach(r=>r.addEventListener('change', ()=>this.updateSourceHidden()));
      if (this.sourceSelect) this.sourceSelect.addEventListener('change', ()=>this.updateSourceHidden());
      this.activeBody.addEventListener('click', (e)=>this.handleRemoveRow(e));
      this.palette.addEventListener('click', (e)=>this.handlePaletteClick(e));
      this.filter.addEventListener('input', ()=>this.filterPalette());
      this.addCustomBtn.addEventListener('click', ()=>this.addTargetRow('', '', '', true));
      document.addEventListener('submit', (e)=>this.handleSubmit(e));
                     
      this.updateSourceHidden();
    }

    rebindCustomizationInputs(tr, code) {
      tr.querySelectorAll('input').forEach(inp => {
        if (!inp.name) return;
        if (!inp.name.startsWith(this.cfg.customizations + '[')) return;
        if (inp.classList.contains('dolet-code-input')) return;

        if (inp.name.endsWith('][name]')) {
          inp.name = `${this.cfg.customizations}[${code}][name]`;
          return;
        }
        if (inp.name.endsWith('][note]')) {
          inp.name = `${this.cfg.customizations}[${code}][note]`;
        }
      });
    }

    commitPendingCustoms() {
      this.$$('.dolet-code-input', this.activeBody).forEach(inp => {
        const tr = inp.closest('tr');
        if (!tr) return;
        const val = (inp.value || '').trim();
        if (!val) return;

        tr.setAttribute('data-code', val);

        this.rebindCustomizationInputs(tr, val);

        const form = this.root.closest('form') || document.forms[0];
        if (form && !form.querySelector(`input.dolet-custom-code[value="${CSS.escape(val)}"]`)) {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = this.cfg.customList + '[]';
          hidden.value = val;
          hidden.className = 'dolet-custom-code';
          form.appendChild(hidden);
        }
      });
    }

    currentSourceCode() {
      const mode = (this.sourceModes.find(r=>r.checked)||{}).value || 'original';
      if (mode === 'original') return 'original';
      if (mode === 'site') {
        const code = this.siteRadio ? (this.siteRadio.getAttribute('data-site-code')||'') : '';
        return code || 'original';
      }
      const val = (this.sourceSelect && this.sourceSelect.value) ? this.sourceSelect.value : '';
      return val || 'original';
    }
    updateSourceHidden() {
      if (!this.sourceHidden) return;
      this.sourceHidden.value = this.currentSourceCode();
      this.updateSourceCustomizationBindings();
      this.refreshPaletteDisabled();
    }
    updateSourceCustomizationBindings() {
      if (!this.sourceNameInp || !this.sourceNoteInp) return;
      const code = this.sourceHidden && this.sourceHidden.value ? this.sourceHidden.value : 'original';
      const base = this.sourceNameInp.getAttribute('data-field-base') || '';
      this.sourceNameInp.name = `${base}[${this.escapeAttr(code)}][name]`;
      this.sourceNoteInp.name = `${base}[${this.escapeAttr(code)}][note]`;
    }
    refreshPaletteDisabled() {
      const src = this.sourceHidden && this.sourceHidden.value ? this.sourceHidden.value : 'original';
      const set = new Set(this.$$('#dolet-active-table tbody tr')
        .map(tr=>(tr.getAttribute('data-code')||'').trim()).filter(Boolean));
      this.$$('.dolet-chip', this.palette).forEach(btn=>{
        const code = btn.getAttribute('data-code') || '';
        const dis = set.has(code) || code === src;
        btn.disabled = dis;
        btn.classList.toggle('disabled', dis);
        btn.title = dis ? (btn.getAttribute('data-title-disabled')||'')
                        : (btn.getAttribute('data-title-add')||'');
      });
    }
    handleRemoveRow(e) {
      const btn = e.target.closest('.dolet-remove-row');
      if (!btn) return;
      const tr = btn.closest('tr');
      const code = tr.getAttribute('data-code');
      tr.remove();
      const chip = this.palette.querySelector(`.dolet-chip[data-code="${CSS.escape(code)}"]`);
      if (chip) { chip.disabled = false; chip.classList.remove('disabled'); chip.title = chip.getAttribute('data-title-add'); }
    }
    handlePaletteClick(e) {
      const chip = e.target.closest('.dolet-chip');
      if (!chip || chip.disabled) return;
      const code = chip.getAttribute('data-code');
      this.addTargetRow(code, '', '');
      chip.disabled = true; chip.classList.add('disabled'); chip.title = chip.getAttribute('data-title-disabled');
    }
    filterPalette() {
      const t = (this.filter.value||'').toLowerCase();
      this.$$('.dolet-chip', this.palette).forEach(btn=>{
        const txt = (btn.textContent||'').toLowerCase();
        btn.style.display = txt.includes(t) ? '' : 'none';
      });
    }
    addTargetRow(code, name, note, isCustom=false) {
      const tr = document.createElement('tr');
      tr.setAttribute('data-code', code || '');
      const tdCode = document.createElement('td');
      tdCode.innerHTML = code ? `<code>${this.escapeHtml(code)}</code>`
                              : '<input type="text" class="dolet-code-input" placeholder="code" style="width:120px;">';
      const tdName = document.createElement('td');
      tdName.innerHTML = `<input type="text" style="width:100%" name="${this.cfg.customizations}[${this.escapeAttr(code)}][name]" value="${this.escapeAttr(name)}" placeholder="">`;
      const tdNote = document.createElement('td');
      tdNote.innerHTML = `<input type="text" style="width:100%" name="${this.cfg.customizations}[${this.escapeAttr(code)}][note]" value="${this.escapeAttr(note)}" placeholder="${this.escapeAttr(this.cfg.strNotePlaceholder)}">`;
      const tdRemove = document.createElement('td');
      tdRemove.style.width = '80px';
      tdRemove.innerHTML = '<button type="button" class="button link-delete dolet-remove-row">×</button>';
      tr.appendChild(tdCode); tr.appendChild(tdName); tr.appendChild(tdNote); tr.appendChild(tdRemove);
      this.activeBody.appendChild(tr);
      if (isCustom) {
        const codeInput = tr.querySelector('.dolet-code-input');
                                                                         
                                                                           
                                                                         
                                                                        
                                                                              
                                                                         
                                                                              
                                                                        
        codeInput && codeInput.addEventListener('blur', ()=>{
          const val = (codeInput.value || '').trim();
          if (!val) return;

          tr.setAttribute('data-code', val);

          this.rebindCustomizationInputs(tr, val);

          const form = this.root.closest('form') || document.forms[0];
          if (form && !form.querySelector(`input.dolet-custom-code[value="${CSS.escape(val)}"]`)) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = this.cfg.customList + '[]';
            hidden.value = val;
            hidden.className = 'dolet-custom-code';
            form.appendChild(hidden);
          }

          tdCode.innerHTML = `<code>${this.escapeHtml(val)}</code>`;
          this.refreshPaletteDisabled();
        });
      }
      this.refreshPaletteDisabled();
    }
    handleSubmit(e) {
      const form = e.target.closest && e.target.closest('form');
      if (!form) return;
      if (!form.contains(this.activeBody)) return;
      this.commitPendingCustoms();
      this.updateSourceHidden();
      if (!this.holder) return;
      this.holder.innerHTML = '';
      const seen = new Set();
      this.$$('#dolet-active-table tbody tr').forEach(tr=>{
        const code = (tr.getAttribute('data-code') || '').trim();
        if (!code) return;
        seen.add(code);
      });
      Array.from(seen).sort().forEach(code=>{
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = this.cfg.targetsField + '[]';
        inp.value = code;
        this.holder.appendChild(inp);
      });
    }
            
    escapeHtml(s){ return (s||'').replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c])); }
    escapeAttr(s){ return this.escapeHtml(s); }
  }
  const root = document.getElementById('dolet-builder-root');
  if (root) new DoletLanguagesBuilder(root).init();
})();
