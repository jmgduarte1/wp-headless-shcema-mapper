(function (blocks, blockEditor, components, element, i18n) {
  var el = element.createElement;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var RichText = blockEditor.RichText;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var TextControl = components.TextControl;

  blocks.registerBlockType('headless-angular/hero', {
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var layout = attributes.layout || {};

      return el(
        'section',
        { className: 'wp-block-headless-angular-hero' },
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: __('Hero layout', 'headless-angular-schema'), initialOpen: true },
            el(SelectControl, {
              label: __('Media placement', 'headless-angular-schema'),
              value: attributes.media && attributes.media.placement ? attributes.media.placement : '',
              options: [
                { label: __('None', 'headless-angular-schema'), value: '' },
                { label: __('Background', 'headless-angular-schema'), value: 'background' },
                { label: __('Start', 'headless-angular-schema'), value: 'start' },
                { label: __('End', 'headless-angular-schema'), value: 'end' }
              ],
              onChange: function (placement) {
                setAttributes({
                  media: placement ? Object.assign({}, attributes.media || {}, { placement: placement }) : undefined
                });
              }
            }),
            el(SelectControl, {
              label: __('Content alignment', 'headless-angular-schema'),
              value: layout.contentAlignment || '',
              options: [
                { label: __('Default', 'headless-angular-schema'), value: '' },
                { label: __('Start', 'headless-angular-schema'), value: 'start' },
                { label: __('Center', 'headless-angular-schema'), value: 'center' },
                { label: __('End', 'headless-angular-schema'), value: 'end' }
              ],
              onChange: function (contentAlignment) {
                setAttributes({ layout: Object.assign({}, layout, { contentAlignment: contentAlignment || undefined }) });
              }
            }),
            el(TextControl, {
              label: __('Stable block id', 'headless-angular-schema'),
              value: attributes.id || '',
              onChange: function (id) {
                setAttributes({ id: id });
              }
            })
          )
        ),
        el(RichText, {
          tagName: 'p',
          className: 'wp-block-headless-angular-hero__eyebrow',
          placeholder: __('Eyebrow', 'headless-angular-schema'),
          value: attributes.eyebrow,
          onChange: function (eyebrow) {
            setAttributes({ eyebrow: eyebrow });
          }
        }),
        el(RichText, {
          tagName: 'h1',
          className: 'wp-block-headless-angular-hero__title',
          placeholder: __('Hero title', 'headless-angular-schema'),
          value: attributes.title,
          allowedFormats: [],
          onChange: function (title) {
            setAttributes({ title: title });
          }
        }),
        el(RichText, {
          tagName: 'p',
          className: 'wp-block-headless-angular-hero__subtitle',
          placeholder: __('Subtitle', 'headless-angular-schema'),
          value: attributes.subtitle,
          onChange: function (subtitle) {
            setAttributes({ subtitle: subtitle });
          }
        })
      );
    },
    save: function () {
      return null;
    }
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n);
