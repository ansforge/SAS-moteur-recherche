export default {
  allowedTags: [
    'address', 'article', 'aside', 'footer', 'header', 'h1', 'h2', 'h3', 'h4',
    'h5', 'h6', 'hgroup', 'main', 'nav', 'section', 'blockquote', 'dd', 'div',
    'dl', 'dt', 'figcaption', 'figure', 'hr', 'li', 'main', 'ol', 'p', 'pre',
    'ul', 'a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'dfn',
    'em', 'i', 'kbd', 'mark', 'q', 'rb', 'rp', 'rt', 'rtc', 'ruby', 's', 'samp',
    'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr', 'caption',
    'col', 'colgroup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'img', 'video', 'button',
  ],
  disallowedTagsMode: 'discard',
  allowedAttributes: {
    '*': ['class'],
    a: ['href', 'name', 'target'],
    // We don't currently allow img itself by default, but
    // these attributes would make sense if we did.
    img: ['src', 'srcset', 'alt', 'title', 'width', 'height', 'loading'],
    button: ['aria-controls', 'aria-expanded', 'data-track', 'data-once', 'type'],
    div: ['id', 'data-height'],
  },
  // Lots of these won't come up by default because we don't allow them
  selfClosing: ['img', 'br', 'hr', 'area', 'base', 'basefont', 'input', 'link', 'meta'],
  // URL schemes we permit
  allowedSchemes: ['http', 'https', 'ftp', 'mailto', 'tel'],
  allowedSchemesByTag: {},
  allowedSchemesAppliedToAttributes: ['href', 'src', 'cite'],
  allowProtocolRelative: true,
  enforceHtmlBoundary: false,
};
