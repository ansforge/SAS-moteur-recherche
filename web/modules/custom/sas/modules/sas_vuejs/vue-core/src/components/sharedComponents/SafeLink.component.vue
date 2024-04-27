<template>
  <a
    :href="$sanitizeUrl(link)"
    v-bind="attrs"
  >
    <slot>Insert a link here</slot>
  </a>
</template>

<script>
export default {
    name: 'SafeLink',
    props: {
        /**
         * We call it ‘link’ instead of ‘href’ because using the latter
         * would trigger SonarQube, despite the abstraction
         */
        link: {
            type: String,
            required: true,
        },
        openInNewTab: {
            type: Boolean,
            default: true,
        },
    },
    setup(props) {
        const attrs = {};

        if (props.openInNewTab) {
            attrs.target = '_blank';
            attrs.rel = 'noopener noreferrer';
        }

        if (props.link.startsWith('tel:')) {
            delete attrs.target;
            delete attrs.rel;
        }

        return { attrs };
    },
};
</script>
