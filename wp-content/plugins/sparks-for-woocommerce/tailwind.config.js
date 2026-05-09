const { scopedPreflightStyles, isolateInsideOfContainer } = require('tailwindcss-scoped-preflight')

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./includes/assets/tab_manager/js/src/**/*.jsx",
    "./includes/assets/custom_thank_you/react/src/**/*.jsx",
    "./includes/Modules/Tab_Manager/Product_Tabs_Cpt.php"
  ],
  important: '.spk-tw',
  theme: {
    extend: {
      colors: {
        'wp-blue': {
          50: '#e6f4fb',
          100: '#cce9f7',
          200: '#99d3ef',
          300: '#66bde7',
          400: '#33a7df',
          500: '#0073aa',  // WordPress admin blue
          600: '#005c88',
          700: '#004566',
          800: '#002e44',
          900: '#001722',
        },
      },
    },
  }, plugins: [
    scopedPreflightStyles({
      isolationStrategy: isolateInsideOfContainer('.spk-tw'),
    }),
  ],
}