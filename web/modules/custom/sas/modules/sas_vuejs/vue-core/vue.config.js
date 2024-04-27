module.exports = {
  pages: {
    'search-page': 'src/modules/search-module/main.js',
    'home-page': 'src/modules/home-module/main.js',
    'time-slot-schedule': 'src/modules/calendar-full/main.js',
    'directory-snp-calendar': 'src/modules/directory-snp-calendar/main.js',
    'aggreg-ps-calendar': 'src/modules/aggreg-ps-calendar/main.js',
    'header-searchbar': 'src/modules/header-searchbar-module/main.js',
    'faq-page': 'src/modules/faq-module/main.js',
    'faq-page-contact': 'src/modules/faq-contact-module/main.js',
    'formation-home-page': 'src/modules/formation-home-module/main.js',
    'formation-page': 'src/modules/formation-module/main.js',
    'user-dashboard-page': 'src/modules/dashboard-module/main.js',
  },
  runtimeCompiler: true,
  lintOnSave: false,
  filenameHashing: false,
  devServer: {
    watchOptions: {
      ignored: /node_modules/,
      aggregateTimeout: 300,
      poll: 1000,
    },
  },
};
