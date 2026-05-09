/**
 * Custom Webpack configuration for a WordPress plugin.
 * 
 * - Consolidates all wp-scripts commands from package.json into a single webpack config
 * - Preserves existing build paths and entry points
 * - Supports both development and production builds
 * 
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  entry: {
    // Dashboard
    'dashboard': path.resolve(__dirname, 'includes/assets/dashboard/js/src/app.js'),
    
    // Custom Thank You
    'custom_thank_you': path.resolve(__dirname, 'includes/assets/custom_thank_you/react/src/main.js'),
    
    // Advanced Product Review
    'advanced_product_review': path.resolve(__dirname, 'includes/assets/advanced_product_review/js/src/app.js'),
    
    // Variation Swatches - React
    'variation_swatches/components': path.resolve(__dirname, 'includes/assets/variation_swatches/react/src/app.js'),
    
    // Variation Swatches - JS
    'variation_swatches/frontend': path.resolve(__dirname, 'includes/assets/variation_swatches/js/src/app.js'),
    
    // Tab Manager - Multiple entry points
    'tab_manager/tab-manager-global': path.resolve(__dirname, 'includes/assets/tab_manager/js/src/tab-manager-global.js'),
    'tab_manager/tab-edit-page': path.resolve(__dirname, 'includes/assets/tab_manager/js/src/tab-edit-page.js'),
    'tab_manager/tab-manager-product': path.resolve(__dirname, 'includes/assets/tab_manager/js/src/tab-manager-product.js'),
    
    // Core
    'core': path.resolve(__dirname, 'includes/assets/core/js/src/app.js'),
    
    // Cart Notices
    'cart-notices': path.resolve(__dirname, 'includes/assets/cart_notices/src/cart-notices-block.js'),
    
    // Comparison Table
    'comparison_table': path.resolve(__dirname, 'includes/assets/comparison_table/js/src/app.js'),
    
    // Quick View
    'quick_view': path.resolve(__dirname, 'includes/assets/quick_view/js/src/app.js'),
    
    // Wish List
    'wish_list': path.resolve(__dirname, 'includes/assets/wish_list/js/src/app.js'),
    
    // Blocks
    'blocks': path.resolve(__dirname, 'includes/assets/blocks/js/src/index.js'),


    // Tab manager - CSS:
    'tw': path.resolve(__dirname, 'includes/assets/tw.scss'),

  },
  output: {
    ...defaultConfig.output,
    filename: '[name].js',
    path: path.resolve(__dirname, 'includes/assets/build'),
  },
};