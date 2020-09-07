(function (wp, d) {
  const { registerPlugin } = wp.plugins;
  const { createElement } = wp.element;
  const { withSelect } = wp.data;
  const { compose } = wp.compose;

  var ModifyPostLinks = compose(
    withSelect((select) => {
      return {
        permalinkParts: select("core/editor").getPermalinkParts(),
      };
    })
  )(({ permalinkParts }) => {
    const links = d.querySelectorAll(".edit-post-post-link__link");
    links.forEach((e) => {
      let name = `${HeadlessWp.frontend_origin} / ${permalinkParts.postName}`;
      e.href = name;
      e.innerHTML = name;
    });
    return null;
  });

  registerPlugin("wp-boilerplate-nodes-editor-scripts", {
    render: () => createElement(ModifyPostLinks),
  });
})(window.wp, document);
