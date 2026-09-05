(function (wp) {
  const { addFilter } = wp.hooks;
  const { createHigherOrderComponent } = wp.compose;
  const { InspectorControls } = wp.blockEditor;
  const { getBlockVariations, registerBlockVariation } = wp.blocks;
  const { PanelBody, RangeControl, ToggleControl } = wp.components;
  const { Fragment, createElement } = wp.element;
  const { useSelect } = wp.data;
  const domReady = wp.domReady;

  const defaults = {
    headlessSliderEnabled: false,
    headlessMinColumnWidth: 280,
    headlessSliderNavigation: true,
    headlessSliderPagination: true,
    headlessSliderLoop: false,
    headlessSliderAutoplay: false,
    headlessSliderAutoplayDelay: 5000,
  };

  domReady(() => {
    const variations = getBlockVariations('core/group') || [];
    if (!variations.some((variation) => variation.name === 'group-grid')) {
      registerBlockVariation('core/group', {
        name: 'group-grid',
        title: 'Grid',
        icon: 'grid-view',
        description: 'Arrange blocks in a responsive grid.',
        attributes: { layout: { type: 'grid' } },
        scope: ['block', 'inserter', 'transform'],
        isActive: (attributes) => attributes.layout?.type === 'grid',
      });
    }
  });

  const withHeadlessGridControls = createHigherOrderComponent((BlockEdit) => (props) => {
    const isGrid = props.name === 'core/group' && props.attributes?.layout?.type === 'grid';
    if (!isGrid) return createElement(BlockEdit, props);

    const gridChildren = useSelect((select) => select('core/block-editor').getBlocks(props.clientId), [props.clientId]);
    const hasUnsupportedPlacement = gridChildren.some((child) => {
      const layout = child.attributes?.style?.layout || {};
      return ['columnSpan', 'rowSpan', 'columnStart', 'rowStart'].some((key) => layout[key] !== undefined);
    });
    const attrs = { ...defaults, ...(props.attributes || {}) };
    const set = (key, value) => props.setAttributes({ [key]: value });

    return createElement(Fragment, null,
      createElement(BlockEdit, props),
      createElement(InspectorControls, null,
        createElement(PanelBody, { title: 'Headless Renderer', initialOpen: false },
          createElement(ToggleControl, { label: 'Enable Slider', checked: !!attrs.headlessSliderEnabled, onChange: (value) => set('headlessSliderEnabled', value) }),
          createElement(RangeControl, { label: 'Minimum Slide Width', value: attrs.headlessMinColumnWidth, min: 120, max: 800, step: 1, onChange: (value) => set('headlessMinColumnWidth', value || 280), help: 'Swiper activates only when the Grid can no longer preserve this width.' }),
          hasUnsupportedPlacement && createElement('p', { className: 'components-base-control__help' }, 'Slider unavailable while a Grid child uses custom row or column placement.'),
          createElement(ToggleControl, { label: 'Navigation', checked: !!attrs.headlessSliderNavigation, onChange: (value) => set('headlessSliderNavigation', value) }),
          createElement(ToggleControl, { label: 'Pagination', checked: !!attrs.headlessSliderPagination, onChange: (value) => set('headlessSliderPagination', value) }),
          createElement(ToggleControl, { label: 'Loop', checked: !!attrs.headlessSliderLoop, onChange: (value) => set('headlessSliderLoop', value) }),
          createElement(ToggleControl, { label: 'Autoplay', checked: !!attrs.headlessSliderAutoplay, onChange: (value) => set('headlessSliderAutoplay', value) }),
          attrs.headlessSliderAutoplay && createElement(RangeControl, { label: 'Autoplay Delay (ms)', value: attrs.headlessSliderAutoplayDelay, min: 1000, max: 60000, step: 500, onChange: (value) => set('headlessSliderAutoplayDelay', value || 5000) })
        )
      )
    );
  }, 'withHeadlessGridControls');

  addFilter('editor.BlockEdit', 'headless-angular/responsive-grid', withHeadlessGridControls);
})(window.wp);
