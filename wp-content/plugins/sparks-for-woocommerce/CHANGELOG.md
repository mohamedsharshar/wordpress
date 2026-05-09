##### [Version 2.0.2](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v2.0.1...v2.0.2) (2026-04-29)

- Fixed an edge case error thrown when using comparison table as block
- Added sanitization for comparison table block attributes
- Fixed amp_is_available deprecated warning
- Fixed issue when dashboard crashes with sprintf is not defined error on Product Comparison Configure page

##### [Version 2.0.1](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v2.0.0...v2.0.1) (2025-11-13)

### Fixes
- **Error** thrown when using wishlist header component in Neve Pro

#### [Version 2.0.0](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.11...v2.0.0) (2025-11-13)

### Wishlist
- **Added color controls** for the wishlist button
- **Added positioning controls** for the wishlist button
- **Fixed accessibility** issue with the Wishlist toast
- **Fixed wishlist button not appearing for Out-of-Stock products**

### Quick View
- **Added a third option for Quick View button positioning** at the end of the product card
- **Added a loader to the Quick View modal**
- **Fixed Quick view modal not opening** for products dynamically added to the DOM
- **Restyled the Quick View button to be more consistent across themes**
- **Fixed an issue where variation forms were not working in the Quick View window**

### Comparison Table
- **New option to Hide identical values** in the Comparison Table
- **Add a Return to shop button in the Comparison Table** when there are no products to compare
- **Do not show rows with blank values** in the Comparison Table
- **Fixed Comparison Tables on multisite environments**

### Multi-Announcement Bars
- **Added a Configure link** for users to quickly start adding Announcement Bars
- **Added a Display Locations column** to the list of Announcement Bars to locate them easier
- **Improved UX for magic tags** that can be used inside Announcement Bars by adding tooltips with conditions requirements for each magic tag
- **Make display locations and settings easier to locate** in the Announcement Bars editor

### Custom Thank you pages
- **Added support for creating Thank you pages with Elementor**
- **Added a Configure link** for users to quickly start adding Thank you pages
- **Added Categories, Payment Gateways and Shipping Methods columns** to the list of Thank you pages to locate them easier
- **Added a new Products section in the Thank you page editor**, so users are able to select a thank you page for multiple individual products, without the need to individually edit each product
- **Added a new Custom Redirect URL option** for users to be able to redirect to an external URL after a payment is completed

### Custom Product Tabs
- **Added support for creating global Custom Product Tabs with Elementor**
- **Reworked the UI / UX for individual and global Custom Product Tabs** to allow for more flexibility
- **New option to compare Custom Products Tabs** inside the Comparison Table

### Other fixes
- **Complete dashboard redesign for improved user experience**
- **Improved design compatibility across multiple themes**
- **Redirect to the Settings page** once the plugin is activated
- **Make WooCommerce required** when trying to activate Sparks
- **Fixed variations style not loading in RTL mode**
- **Added Author URI plugin header to fix wrong license renewal link**

##### [Version 1.1.11](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.10...v1.1.11) (2025-08-21)

- Enhanced security

##### [Version 1.1.10](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.9...v1.1.10) (2025-07-24)

- Fixed warning showing up on website

##### [Version 1.1.9](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.8...v1.1.9) (2025-06-05)

- Fixed compatibility with Neve White label module
- Updated broken link to license key when Neve is active with Sparks

##### [Version 1.1.8](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.7...v1.1.8) (2025-05-22)

- Updated dependencies
- Improve how the dashboard page is registered 
- Updated plugin description

##### [Version 1.1.7](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.6...v1.1.7) (2024-12-06)

- Fixed compatibility issue with WordPress 6.7

##### [Version 1.1.6](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.5...v1.1.6) (2024-04-03)

### Fixes
- Resolved compatibility issue with the product bundles plugin.
- Fixed an issue where scrolling was not possible in the Quick View Popup on mobile view.
- Updated internal dependencies for enhanced performance and stability.

##### [Version 1.1.5](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.4...v1.1.5) (2023-07-25)

- Compatibility with High-Performance Order Storage
- Compatibility improvements with Cart & Checkout Blocks

##### [Version 1.1.4](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.3...v1.1.4) (2023-06-06)

- Feat: Customizing the thumbnail image dimensions of the advanced product review module review images
- Feat: Added the ability to customize the existing Woocommerce tabs
- Fix: Double slashes on asset URLs
- Fix: Comparison table style settings were not working on the comparison table page
- Fix: Alignment issue of the button in the announcement bars

##### [Version 1.1.3](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.2...v1.1.3) (2023-03-21)

- Fix: Comparison sticky appears even on comparison page
- Fix: Renaming the custom Product Tab does not change its name on the product itself
- Fix: Two scrollbars present in the quick view with WC 7.2.1
- Fix: 'View cart' color is inherited from table text color in the comparison page
- Fix: Otter style isn't working in the Thank you pages
- Fix: Order preview feature of custom thank you page on admin editor
- Themeisle SDK version updated to v3.2.39

##### [Version 1.1.2](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.1...v1.1.2) (2023-02-21)

- [Fix] License activation issue has been fixed.

##### [Version 1.1.1](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.1.0...v1.1.1) (2023-02-15)

- [Fix] Polylang & other third-party plugins compatibility issue on WP Admin Product Terms screen.
- [Fix] The style of the color picker where in WP admin -> Products -> Attributes was fixed.
- [Fix] Bottom margin for the Comparison Table page.
- [Fix] Removing category from a Custom Product Tab doesn't remove the Tab from the products belonging to the category
- Store custom product tabs content as plain instead of base64 encoded
- Code improvements

#### [Version 1.1.0](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.10...v1.1.0) (2022-12-20)

- [Feat] Customizable icon support for the Add to Comparison Table

##### [Version 1.0.10](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.9...v1.0.10) (2022-12-07)

- [Fix] Variation Swatches - Displaying Products (cross-sells, when two or more rows of items are available)
- [Fix] Variation Swatches not working on the archive page while adding multiple products
- [Fix] After adding a variable product to the shop and the page reload; attributes of other products seem as selected

##### [Version 1.0.9](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.8...v1.0.9) (2022-11-21)

- Comparison Table block (as dependent on Sparks Comparison Table module) has been migrated from Otter Blocks into Sparks.
- Settings link added to plugin actions in the WP plugin list page.
- Tested WC version info was updated.

##### [Version 1.0.8](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.7...v1.0.8) (2022-09-16)

* Themeisle-SDK upgraded to latest version (v3.2.30)

##### [Version 1.0.7](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.6...v1.0.7) (2022-09-08)

- [Fix] PHP 7.2 Compatibility Issue (Fatal Error) has been fixed.

##### [Version 1.0.6](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.5...v1.0.6) (2022-09-01)

- [Fix] In WP Admin: Advanced Product Review settings were shown in all sections of WooCommerce Product Settings. That's fixed. [#85](https://github.com/Codeinwp/sparks-for-woocommerce/issues/85) 
- [Fix] Comparison Table page is not scrollable in some cases (on mobile devices or if the table layout is column-oriented) - [#130](https://github.com/Codeinwp/sparks-for-woocommerce/issues/130) , [#141](https://github.com/Codeinwp/sparks-for-woocommerce/issues/141)
- Sparks Logo added to Sparks dashboard.
- [Fix] Load module assets as conditionally. - [#76](https://github.com/Codeinwp/sparks-for-woocommerce/issues/76)
- [Fix] Load dynamic CSS styles as conditionally. - [#90](https://github.com/Codeinwp/sparks-for-woocommerce/issues/90)
- [Fix] Variation Swatches attributes selection form don't work on products shortcodes (loop products) if quick view is disabled - [#135](https://github.com/Codeinwp/sparks-for-woocommerce/issues/135)
- [Fix] Variation Swatches don't work in the product card for exclusive products/related products/upsells/cross-sells - [#136](https://github.com/Codeinwp/sparks-for-woocommerce/issues/136) , [#39](https://github.com/Codeinwp/sparks-for-woocommerce/issues/39)
- [Fix] Conditionally by permalink settings; 404 error when adding a Variable Product to Cart directly from the shop page - [#60](https://github.com/Codeinwp/sparks-for-woocommerce/issues/60)

##### [Version 1.0.5](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.4...v1.0.5) (2022-08-25)

- [Fix] Compatibility issue (fatal error) between Sparks Comparison Table & PopularFX Theme has been fixed.  - [#104](https://github.com/Codeinwp/sparks-for-woocommerce/issues/104) 
- Minimum WP version requirement has been updated as v5.5 - [#112](https://github.com/Codeinwp/sparks-for-woocommerce/issues/112) 
- [Fix] Misleading Dashboard notice for the Neve Pro version has been fixed. - [#114](https://github.com/Codeinwp/sparks-for-woocommerce/issues/114) 
- [Fix] The issue that is the image popup mechanism of the Advanced Product Review Module doesn't work if the Quick View Module is deactivated has been fixed. [#121](https://github.com/Codeinwp/sparks-for-woocommerce/issues/121) [#115](https://github.com/Codeinwp/sparks-for-woocommerce/issues/115) 
- [Fix] Some functions of the Quick View Module was running even if the Quick View button is positioned as none. That's fixed. [#117](https://github.com/Codeinwp/sparks-for-woocommerce/issues/117)
- [Fix] Sparks Docs URL where in the Sparks Dashboard has been fixed. [#111](https://github.com/Codeinwp/sparks-for-woocommerce/issues/111) 
- [Fix] Variation swatches are not working on archive pages with infinite scroll [#99](https://github.com/Codeinwp/sparks-for-woocommerce/issues/99) 
- [Fix] If the products archive page contains a variable product that has no variant; that was causing that product variants are not chosen on the shop page. That's fixed. [#127](https://github.com/Codeinwp/sparks-for-woocommerce/issues/127)

##### [Version 1.0.4](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.3...v1.0.4) (2022-08-05)

- [Fix] Unnecessarily having top padding in product action buttons of the products(related/upsell) in the single product has been fixed.
- [Fix] Missing WPML String Translation support has been added.
- [Fix] Sparks not working under php7.4 to php7.0 has been fixed.
- An enhancement for developers

##### [Version 1.0.3](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.2...v1.0.3) (2022-07-20)

- [Fix] Variation swatches regression
- [Fix] a discovered Neve theme style regression that related to colors
- Minor performance improvement
- [Fix] Visual regression on bottom positioned quick view button in the products archive

##### [Version 1.0.2](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.1...v1.0.2) (2022-07-14)

- [Fix] Some theme compatibility issues has been fixed. (especially affected the child theme users)
- [Fix] Quick view data pulling issue that occurs on Neve lite (without Neve Pro) has been fixed.
- [Fix] Missing .pot file added.

##### [Version 1.0.1](https://github.com/Codeinwp/sparks-for-woocommerce/compare/v1.0.0...v1.0.1) (2022-07-11)

- Minor refactors/fixes regarding with v1.0.0 release
- Improve compatibility with Neve theme

####   Version 1.0.0 (2022-07-06)

* Initial Release
