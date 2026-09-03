(function (blocks, blockEditor, components, element, i18n) {
  var el = element.createElement;
  var Fragment = element.Fragment;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var MediaUpload = blockEditor.MediaUpload;
  var MediaUploadCheck = blockEditor.MediaUploadCheck;
  var Button = components.Button;
  var PanelBody = components.PanelBody;
  var Placeholder = components.Placeholder;
  var SelectControl = components.SelectControl;
  var TextControl = components.TextControl;
  var TextareaControl = components.TextareaControl;

  function newCard(index) {
    return { id: 'card-' + Date.now() + '-' + index, title: '', tags: [], text: '' };
  }

  function updateCard(cards, index, changes) {
    return cards.map(function (card, cardIndex) {
      return cardIndex === index ? Object.assign({}, card, changes) : card;
    });
  }

  function CardEditor(props) {
    var card = props.card;
    var index = props.index;
    var cards = props.cards;
    var setAttributes = props.setAttributes;
    var update = function (changes) { setAttributes({ cards: updateCard(cards, index, changes) }); };
    var hasImage = !!(card.image && card.image.url);
    var hasIcon = !!card.icon;

    return el(
      PanelBody,
      { title: __('Card ' + (index + 1), 'headless-angular-schema'), initialOpen: index === 0 },
      el(TextControl, {
        label: __('Title', 'headless-angular-schema'), value: card.title || '',
        onChange: function (title) { update({ title: title }); }
      }),
      el(TextareaControl, {
        label: __('Text', 'headless-angular-schema'), value: card.text || '',
        onChange: function (text) { update({ text: text }); }
      }),
      el(TextControl, {
        label: __('Tags', 'headless-angular-schema'), help: __('Comma-separated tags', 'headless-angular-schema'),
        value: (card.tags || []).join(', '),
        onChange: function (tags) { update({ tags: tags.split(',').map(function (tag) { return tag.trim(); }).filter(Boolean) }); }
      }),
      el(TextControl, {
        label: __('Material icon name', 'headless-angular-schema'), value: card.icon || '',
        disabled: hasImage,
        help: hasImage ? __('Remove the image before choosing an icon.', 'headless-angular-schema') : __('Example: code', 'headless-angular-schema'),
        onChange: function (icon) { update({ icon: icon }); }
      }),
      el(MediaUploadCheck, {}, el(MediaUpload, {
        onSelect: function (media) {
          update({ image: { id: media.id, url: media.url, alt: media.alt || '', width: media.width, height: media.height }, icon: undefined });
        },
        allowedTypes: ['image'],
        value: hasImage ? card.image.id : undefined,
        render: function (mediaProps) { return el(Button, { variant: hasImage ? 'secondary' : 'primary', onClick: mediaProps.open }, hasImage ? __('Replace image', 'headless-angular-schema') : __('Choose image', 'headless-angular-schema')); }
      })),
      hasImage && el(Button, { variant: 'tertiary', isDestructive: true, onClick: function () { update({ image: undefined }); } }, __('Remove image', 'headless-angular-schema')),
      hasIcon && el(components.__experimentalVStack || components.BaseControl, { spacing: 2 },
        el('p', {}, __('Icon selected: ', 'headless-angular-schema'), card.icon),
        el(Button, { variant: 'tertiary', onClick: function () { update({ icon: undefined }); } }, __('Clear icon', 'headless-angular-schema'))
      )
    );
  }

  blocks.registerBlockType('headless-angular/featured-cards', {
    edit: function (props) {
      var cards = props.attributes.cards || [];
      var setAttributes = props.setAttributes;
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: __('Featured cards', 'headless-angular-schema'), initialOpen: true },
            el(SelectControl, {
              label: __('Card count', 'headless-angular-schema'), value: String(cards.length),
              options: Array.from({ length: Math.max(cards.length, 12) }, function (_, i) { return { label: String(i + 1), value: String(i + 1) }; }),
              onChange: function (value) {
                var count = parseInt(value, 10);
                var next = cards.slice(0, count);
                while (next.length < count) next.push(newCard(next.length));
                setAttributes({ cards: next });
              }
            })
          ),
          cards.map(function (card, index) { return el(CardEditor, { key: card.id || index, card: card, index: index, cards: cards, setAttributes: setAttributes }); })
        ),
        el('section', { className: 'wp-block-headless-angular-featured-cards' },
          cards.length === 0 && el(Placeholder, { label: __('Featured cards', 'headless-angular-schema') }, __('Add at least one card from the block settings.', 'headless-angular-schema')),
          cards.map(function (card, index) {
            return el('article', { className: 'headless-featured-card', key: card.id || index },
              el('div', { className: 'headless-featured-card__media' }, card.image && card.image.url ? el('img', { src: card.image.url, alt: card.image.alt || '' }) : el('span', { className: 'dashicons dashicons-' + (card.icon || 'star-filled') })),
              el('h3', {}, card.title || __('Card title', 'headless-angular-schema')),
              el('div', { className: 'headless-featured-card__tags' }, (card.tags || []).map(function (tag) { return el('span', { key: tag }, tag); })),
              el('p', {}, card.text || __('Card description', 'headless-angular-schema'))
            );
          })
        )
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n);
