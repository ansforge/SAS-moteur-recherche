export default {
    /**
     * Function used to transform standard streaming platform URLs into their embed variant.
     *
     * Supported platforms : Vimeo, Dailymotion and Youtube
     * Unsupported platforms are not processed
     *
     * @param url: streaming platform url
     */
    streamingPlatformEmbedUrlGenerator(url) {
        let result = url;

        /* eslint-disable no-useless-escape */
        const platformsRegex = [
            {
                name: 'dailymotion',
                rx: /(?:https?:\/\/)?(?:www\.)?dai\.?ly(?:motion)?(?:\.com)?\/?.*(?:video|embed)?(?:.*v=|v\/|\/)([a-z0-9]+)/i,
                embedUrlTemplate(id) {
                    return `https://www.dailymotion.com/embed/video/${ id }`;
                },
            },
            {
                name: 'vimeo',
                rx: /(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:[a-zA-Z0-9_\-]+)?/i,
                embedUrlTemplate(id) {
                    return `https://player.vimeo.com/video/${ id }`;
                },
            },
            {
                name: 'youtube',
                rx: /(?:https?:)?(?:\/\/)?(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube(?:-nocookie)?\.com\S*?[^\w\s-])([\w-]{11})(?=[^\w-]|$)(?![?=&+%\w.-]*(?:['"][^<>]*>|<\/a>))[?=&+%\w.-]*/i,
                embedUrlTemplate(id) {
                    return `https://www.youtube.com/embed/${ id }?enablejsapi=1&rel=0&playsinline=1`;
                },
            },
        ];
        /* eslint-enable no-useless-escape */

        // Usage of fori instead of forEach to be able to break if needed
        /* eslint-disable no-restricted-syntax */
        for (const platform of platformsRegex) {
            const match = url.match(platform.rx);

            if (match && match[1]) {
            result = platform.embedUrlTemplate(match[1]);

            // stop loop
            break;
            }
        }
        /* eslint-enable no-restricted-syntax */

        return result;
    },
};
