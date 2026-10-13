(function (wp, config) {
  const { registerBlockType } = wp.blocks;
  const { createElement: el, Fragment } = wp.element;
  const { InspectorControls } = wp.blockEditor;
  const { PanelBody, TextControl, ToggleControl } = wp.components;
  const ServerSideRender = wp.serverSideRender;

  registerBlockType('dolet/language-menu', {
    title: config.title,
    icon: 'translation',
    category: 'widgets',
    description: config.description,
    attributes: {
      showCodes: { type: 'boolean', default: true },
      showInterlingua: { type: 'boolean', default: true },
      wrapperClass: { type: 'string', default: 'dolet-lang-menu' }
    },
    supports: { html: false },
    edit: function (props) {
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: config.title, initialOpen: true },
            el(ToggleControl, {
              label: config.showCodes,
              checked: props.attributes.showCodes,
              onChange: function (value) { props.setAttributes({ showCodes: value }); }
            }),
            el(ToggleControl, {
              label: config.showInterlingua,
              checked: props.attributes.showInterlingua,
              onChange: function (value) { props.setAttributes({ showInterlingua: value }); }
            }),
            el(TextControl, {
              label: config.wrapperClass,
              value: props.attributes.wrapperClass,
              onChange: function (value) { props.setAttributes({ wrapperClass: value }); }
            })
          )
        ),
        el(ServerSideRender, { block: 'dolet/language-menu', attributes: props.attributes })
      );
    },
    save: function () { return null; }
  });
})(window.wp, window.doletLanguageMenuBlock);
