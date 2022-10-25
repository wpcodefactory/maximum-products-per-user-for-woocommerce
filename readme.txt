=== Maximum Products per User for WooCommerce ===
Contributors: wpcodefactory, Karzin, algoritmika, anbinder
Tags: woocommerce, product quantity, woo commerce
Requires at least: 4.4
Tested up to: 6.0
Stable tag: 3.7.7
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Limit number of items your WooCommerce customers can buy (lifetime or in selected date range).

== Description ==

**Maximum Products per User for WooCommerce** plugin lets you limit number of items your WooCommerce customers can buy (lifetime or in selected date range).

### &#9989; Main Features ###

* Set **maximum products** number **per user**.
* Select plugin mode: product **quantities**, product **orders**, product **prices** (including or excluding taxes), product **weights**, or product **volumes**.
* Set **date range** (for example: lifetime, this month, this year, last 30 days, last 365 days, or custom date range).
* Set on which **order statuses** product data should be updated.
* Set different maximum product limits for different **user roles**.
* Enable variable products **variations** usage.
* Customize **customer message** on frontend.
* **Block checkout page** on exceeded limits.
* **Exclude products** from plugin scope.
* **Edit** and **export** each user's **sales data**.
* Choose which **payment gateways** should update product data.
* **Display** remaining amount, maximum limits, etc. to the users in cart, checkout, single product page, "My account", or with shortcode anywhere on your site.
* **Identify guests by IP address** or **block guests** from buying products in your shop.
* **Hide products** with exceeded limits for the current user.
* Enable **multi-language** support (WPML, Polylang).
* And more...

### &#127942; Premium Version ###

Some [Maximum Products per User for WooCommerce Pro](https://wpfactory.com/item/maximum-products-per-user-for-woocommerce/) plugin features:

* Display the limit message on variations.
* Set maximum products per user:
  * Per product **category**.
  * Per product **tag**.
  * Per **individual** product.
  * By **formula**, for example: per **user ID**, per **membership** plan, per **payment method**, etc.
* More options oriented to guest users:
  * Block specific products from guests.
  * Change add to cart button text from blocked products.

= More =

* We are open to your suggestions and feedback. Thank you for using or trying out one of our plugins!
* [Visit plugin site](https://wpfactory.com/item/maximum-products-per-user-for-woocommerce/).

== Frequently Asked Questions ==
= Is it possible that the limits could work for different products at the same time instead of the same ones?
Yes, it's possible, but it's a bit of a workaround. You'll need to assign all your products to some category or tag. After that you need to enable "**Limits > Per product category**" (or "**Limits > Per product tag**"), and then set "**Limit per user**" option for that category (or tag) in "**Products > Categories > Your category > Edit**".

== Installation ==

1. Upload the entire plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Start by visiting plugin settings at "WooCommerce > Settings > Maximum Products per User".

== Changelog ==

= 3.7.7 - 25/10/2022 =
* Fix - Formula - Improve `is_downloadable` and `is_virtual` checks.
* Fix duplicated action links.
* WC tested up to: 7.0.

= 3.7.6 - 03/10/2022 =
* Fix - General - Multi-language - My account tab is empty on other languages.
* Fix - General - Date range is not working consistently with some options using "This".
* WC tested up to: 6.9.

= 3.7.5 - 06/09/2022 =
* Dev - Improve error message by showing a more relevant product title, including variation names.

= 3.7.4 - 05/09/2022 =
* Fix - Frontend - My account tab - Fix my account tab permalink on plugin activation.
* Fix - Shortcodes - Shortcode `[alg_wc_mppu_current_product_limit]` possibly triggers error on admin.

= 3.7.3 - 25/08/2022 =
* Fix - Shortcodes - Shortcode `[alg_wc_mppu_current_product_limit]` triggers error on admin.

= 3.7.2 - 24/08/2022 =
* Dev - Improve compatibility with previous WooCommerce versions.

= 3.7.1 - 22/08/2022 =
* Fix - Frontend - Improve `show_unbought` parameter performance from `[alg_wc_mppu_user_product_limits]` shortcode when used as `false`.

= 3.7.0 - 16/08/2022 =
* Dev - Formula - Add `limit_type` attribute to `[alg_wc_mppu]` shortcode. Allowed values: `grouped`, `individual`. Default: `individual`. It has to be used with multiple term ids or product ids. Example: `[alg_wc_mppu limit="5" limit_type="grouped" term_id="77,96"]`.
* Dev - Advanced - Create "Time offset" option as a way to compensate the time calculated by `alg_wc_mppu_date_to_check` filter.
* WC tested up to: 6.8.

= 3.6.9 - 02/08/2022 =
* Fix - Frontend - Shortcode `[alg_wc_mppu_user_product_limits]` won't show "remain" and "bought" columns.
* Fix - Possible PHP error:  "Uncaught TypeError: Unsupported operand types: int + string".
* Dev - Compatibility - WPC Composite Products - Add option to change add to cart button text from blocked guest user products.

= 3.6.8 - 21/07/2022 =
* Dev - Formula - Add `swpm_membership_id` param to `[alg_wc_mppu]` shortcode, adding compatibility to the [WordPress Membership Plugin](https://simple-membership-plugin.com/).
* Dev - Shortcodes - Add `bought_value` parameter to `[alg_wc_mppu_user_product_limits]` shortcode. Possible values: `smart`, `per_product`. Default value set as `smart`.
* Dev - Shortcodes - Add `show_unbought` parameter to `[alg_wc_mppu_user_product_limits]` shortcode. Default is `true`.
* Dev - Shortcodes - Add `show_unbought` parameter to `[alg_wc_mppu_user_terms_limits]` shortcode. Default is `true`.
* Dev - Shortcodes - Add `alg_wc_mppu_user_product_limits_item_validation` filter to `[alg_wc_mppu_user_product_limits]` shortcode.
* Dev - Shortcodes - Add `alg_wc_mppu_user_terms_limits_item_validation` filter to `[alg_wc_mppu_user_terms_limits]` shortcode.
* Dev - Guests - Hide products - Improve hiding.
* WC tested up to: 6.7.

= 3.6.7 - 11/07/2022 =
* Fix - Frontend - Product limit message - PHP Warning is displayed for guests.
* Fix - General - Hide products - Products do not get hidden from search results.
* Dev - Guests - Hide products - Hide on search results.
* Dev - Move guest options to a new section.

= 3.6.6 - 17/06/2022 =
* Fix - Shortcodes - Change `[alg_wc_mppu_current_product_limit]` shortcode param from 'template' to 'msg_template'.
* Fix - Shortcodes - Check if product id matches a real product.
* Dev - Shortcodes - Add `condition` to `alg_wc_mppu_current_product_limit` shortcode. Default is empty. Example: `%remaining% == 0`.
* Dev - Frontend - Product limit message - Add "condition" option allowing to specify a condition to display the message.
* Dev - Formula - Add `sumo_membership_plan` param to `[alg_wc_mppu]` shortcode.
* WC tested up to: 6.6.

= 3.6.5 - 02/06/2022 =
* Fix - General - Multilanguage - Limit checking - Product name gets translated to original language on notices.
* Fix - General - Multilanguage - Polylang option returns null or empty when there is no viable translated option.
* Dev - Formula - Add `memberpress_plan_id` param to `[alg_wc_mppu]` shortcode.
* WC tested up to: 6.5.
* Tested up to: 6.0.

= 3.6.4 - 06/05/2022 =
* Dev - Tools - Improve `delete_meta_data()` function.
* Dev - Tools - Advanced - Create option to delete sales data using async request.
* Dev - Tools - Debug - Show task time in seconds.
* Dev - Tools - Recalculate sales in background processing.
* Dev - Advanced - Background Processing - Add "Minimum amount" option.
* Dev - Advanced - Background Processing - Add "Send email" option.
* Dev - Advanced - Background Processing - Add "Email to" option.

= 3.6.3 - 15/04/2022 =
* Fix - Formula - Validate `is_product` boolean param from `[alg_wc_mppu_user_bought]` shortcode.
* Dev - Formula - Add `return` param to `[alg_wc_mppu_user_bought]` shortcode. Possible values: `amount_bought`, `boolean`.
* Dev - Formula - Allow fixed `Y-m-d` date on `date_range` parameter from `[alg_wc_mppu_user_bought]` shortcode.
* Dev - Allow `Alg_WC_MPPU_Core::get_date_to_check()` to check for a fixed `Y-m-d` date.
* Dev - WC tested up to: 6.4.

= 3.6.2 - 30/03/2022 =
* Fix - "Product orders" does not calculate sales data right when using per category or tag.
* Fix - "Product orders" mode doesn't allow to add the same product cart on add to cart validation.
* Fix - Frontend - Product limit message - Notice gets displayed empty sometimes.
* Fix - Unsupported operand types: string - int.
* Fix - Check status on order creation and not only on status transition.
* Dev - Add `alg_wc_mppu_validate_on_add_to_cart_quantity_do_add` filter.
* Dev - Add `alg_wc_mppu_totals_data_increase_qty` filter.
* Dev - Add `alg_wc_mppu_totals_data_decrease_qty` filter.
* Dev - Add `alg_wc_mppu_totals_data` filter.
* Dev - Add `alg_wc_mppu_orders_data_increase_qty` filter.

= 3.6.1 - 13/03/2022 =
* Dev - Notices about limits will be displayed as soon as users get to checkout page using the hook `woocommerce_before_checkout_form`.
* Dev - General - Orders above limits - Add option to allow users to place orders with exceeding limits.
* Dev - General - Orders above limits - Add option to change status from newly placed orders above limits.
* Dev - General - Orders above limits - Add option to change the custom status label from newly placed orders above limits.
* Dev - Add `alg_wc_mppu_output_notices_args` filter.
* WC tested up to: 6.3.
* Tested up to: 5.9.

= 3.6.0 - 19/01/2022 =
* Dev - Compatibility - Point of Sale for WooCommerce - Add option to check limits when creating orders via registers.
* Dev - Add compatibility section.
* WC tested up to: 6.1.

= 3.5.9 - 10/11/2021 =
* Dev - Get product id from default language on `max_qty_by_formula()` function.
* Dev - Create `hide_products_by_id` param for the `[alg_wc_mppu_user_product_limits]` shortcode allowing to hide the products from the list.
* WC tested up to: 5.9.

= 3.5.8 - 07/10/2021 =
* Fix - Remove unnecessary folder.

= 3.5.7 - 04/10/2021 =
* Dev - Create `alg_wc_mppu_user_terms_limits` shortcode with `taxonomy` parameter that can be set as `product_cat` or `product_tag`.
* Dev - Improve `is_plugin_active()` function.
* Fix - Frontend - `%first_order_date_exp_timeleft%` is not returning the correct time left for expiring.
* Improve readme.
* WC tested up to: 5.7.

= 3.5.6 - 29/07/2021 =
* Dev - General - Multi-language - Add option to use product ID from default language when checking product limits.
* Dev - Add github deploy setup.
* Dev - Use wpf-promoting-notice library to add notice on settings page regarding pro version.
* Dev - Free and pro plugins can't be active at the same time.
* WC tested up to: 5.5.
* Tested up to: 5.8.

= 3.5.5 - 10/05/2021 =
* Fix - Frontend - Product limit message - Variations - Fix text about pro version.
* Fix - Block guests according to limit options not working when blocking a variable product.
* Dev - Add wpml-config.xml file allowing to translate some admin settings.
* Dev - General - Block guests - Add to cart button text - Add "Change text on variations" option.
* Dev - General - Block guests - Add "Add to cart Redirect" option.
* Rearrange "Additional validation actions" option to Block checkout page section.
* WC tested up to: 5.2.

= 3.5.4 - 22/03/2021 =
* Fix - Block guest message gets displayed for unblocked products.
* Fix - Frontend - My account tab - Tab ID - Shortcode always return english version.
* Dev - Frontend - Product limit message > Add "Variations" option allowing to show limit message for variations.
* Dev - Shortcodes - Add `output_template` param to `alg_wc_mppu_current_product_limit` shortcode having `<span class="alg-wc-mppu-current-product-limit">{output_msg}</span>` as default value.
* WC tested up to: 5.0.
* Tested up to: 5.7.

= 3.5.3 - 03/02/2021 =
* Fix - Check "Guests" option on `is_product_blocked_for_guests()` function.
* Dev - Frontend - Create `[alg_wc_mppu_customer_msg]` shortcode with `bought_msg`, `not_bought_msg`, and `bought_msg_min` params.
* Dev - Frontend - Change "Customer message" option default value to `[alg_wc_mppu_customer_msg]` shortcode and allow its use there.
* Dev - General - Create "Do nothing but block guests from purchasing products beyond the limits" option.

= 3.5.2 - 28/01/2021 =
* Dev - Allow My account tab id to run a shortcode.
* Dev - General - Add "Hide products blocked from guest users" option.
* Dev - General - Add "Change add to cart button text from blocked products" option.
* Dev - General - Add "Custom add to cart button text" option.
* Dev - General - Add my account link to block message by default.

= 3.5.1 - 25/01/2021 =
* Fix - Frontend - "Block checkout page" option allows to place an order with a 100% coupon and shows an error at the same time.
* Dev - Frontend - Add - "Validation actions" option allowing to validate the limits using any WordPress action.
* Dev - General - Guests - Add "Block method" option.
* Dev - General - Guests - Add "Block guests" option related to "Limits > Per product", "Limits > Per product category", and "Limits > Per product tag".
* Dev - Developers - Add `alg_wc_mppu_is_product_blocked_for_guests` hook.
* Add FAQ question about the possibility of limiting different products at the same time.
* WC tested up to: 4.9

= 3.5.0 - 15/12/2020 =
* Fix - Mode - Product prices (incl. tax) - Validate on add to cart - Always including taxes in product price now.
* Dev - General - "Multi-language" option added ("WPML", "Polylang").
* Dev - General - Mode - "Product prices (excl. tax)" option added (and "Product prices" option renamed to "Product prices (incl. tax)").
* Dev - General - Mode - "Product orders" option added.
* Dev - General - Mode - "Product volumes" option added.
* Dev - General - User roles - "Enabled user roles" option added.
* Dev - General - "Count by current payment method" option added.
* Dev - Placeholders - `%payment_method_title%` placeholder added.
* Dev - Data - Saving order payment method in product in product/term sales data now.
* Dev - Formula - `payment_method` shortcode attribute added.
* Dev - Advanced - "Lifetime from totals" option added (defaults to `no`). This changes the previous behaviour in plugin, where lifetime data was always retrieved from totals.
* Dev - Developers - `alg_wc_mppu_data_product_or_term_id` filter added.
* Dev - Developers - `alg_wc_mppu_get_cart_item_amount_by_term` filter added.
* Dev - Developers - `alg_wc_mppu_get_cart_item_amount_by_parent` filter added.
* Dev - Developers - `alg_wc_mppu_cart_item_amount` filter added.
* Dev - Developers - `alg_wc_mppu_user_already_bought_do_count_order` filter added.
* Dev - Developers - `alg_wc_mppu_user_already_bought` filter added.
* Dev - Developers - `alg_wc_mppu_get_max_qty` - Filter applied to empty (i.e. zero) result as well now.
* Dev - Admin settings descriptions updated.
* Dev - Code refactoring.
* WC tested up to: 4.8.
* Tested up to: 5.6.

= 3.4.0 - 01/12/2020 =
* Fix - Formula - `product_sku` attribute - Product variable is now correctly reset for each new product check.
* Dev - General - Guests - "Identify guests by IP address" option added ("Block guests" option renamed to "Guests", option type changed from `checkbox` to `radio`).
* Dev - General - "Hide products" option added.
* Dev - General - Date range - "Custom date range unit" option added.
* Dev - Frontend - Cart notice - "Cart notice type" option added.
* Dev - Frontend - Cart notice - "As text" value added (option type changed from `checkbox` to `select`).
* Dev - Frontend - My Account - "Tab content" option added.
* Dev - Admin - "Export" section added, including "export sales data for all users" link and "export sales data" link (for "Editable sales data").
* Dev - Advanced - "Time function" defaults to "Local (WordPress) time" now (was defaulting to "Coordinated Universal Time (UTC)").
* Dev - Developers - `alg_wc_mppu_get_notice_placeholders` filter added.
* Dev - Localization - `load_plugin_textdomain` moved to the `init` hook.
* Dev - Admin settings descriptions updated.
* Dev - Code refactoring.
* Plugin author updated.
* WC tested up to: 4.7.

= 3.3.2 - 18/10/2020 =
* Fix - Shortcodes - `[alg_wc_mppu_user_product_limits]` - Negative values replaced with zero in "Remaining" and "Max" columns.
* Dev - Frontend - My Account - "Tab icon" option added.
* Dev - Frontend - My Account - Admin settings restyled.
* Dev - Minor code refactoring.
* WC tested up to: 4.6.

= 3.3.1 - 14/10/2020 =
* Dev - Frontend - My Account - Shortcodes are now processed in tab title.

= 3.3.0 - 08/10/2020 =
* Fix - Allowing negative value (i.e. `-1`) in all limits settings now.
* Fix - Variable products - If "Use variations" option is disabled, plugin was incorrectly counting variation's quantity for the initial order. This is fixed now.
* Dev - Formula - Math expressions are now evaluated in formulas.
* Dev - Formula - `[alg_wc_mppu_user_bought]` shortcode added.
* Dev - Frontend - Single product page (and `[alg_wc_mppu_current_product_limit]` shortcode) - Placeholders added: `%in_cart%`, `%bought_plus_in_cart%`, `%remaining_minus_in_cart%`; also (for consistency): `%adding%`, `%in_cart_plus_adding%`, `%bought_plus_in_cart_plus_adding%`, `%remaining_minus_in_cart_minus_adding%`.
* Dev - General - "Order statuses: Delete" option added.
* Dev - "Use variations" added to **per product** settings.
* Dev - Admin settings descriptions updated.
* Dev - Core - `alg_wc_mppu_get_first_order_date_exp` filter added.
* Dev - Code refactoring.
* Tested up to: 5.5.
* WC tested up to: 4.5.

= 3.2.5 - 23/07/2020 =
* Fix - Formula - "All products" limit value was used as a fallback for the formula even if "All products" option was disabled. This is fixed now.
* Dev - Frontend - "Multiple notices" option added.

= 3.2.4 - 21/07/2020 =
* Fix - Taxonomy filters fixed.
* Dev - Code refactoring.

= 3.2.3 - 11/07/2020 =
* Fix - Converting order date according to "Advanced > Time function" option now.
* Dev - Advanced - Time function - Options renamed ("Server time" to "Coordinated Universal Time (UTC)", and "WordPress time" to "Local (WordPress) time").
* Dev - Formula - `product_sku` attribute added to the `[alg_wc_mppu]` shortcode.
* Dev - Code refactoring.
* WC tested up to: 4.3.

= 3.2.2 - 03/07/2020 =
* Dev - Shortcodes - `[alg_wc_mppu_placeholder]` shortcode added.

= 3.2.1 - 22/06/2020 =
* Dev - Frontend - Placeholders - `%first_order_date_exp%`, `%first_order_date%` - Getting date and time format from WordPress settings now (i.e. instead of hard-coded `Y-m-d H:i:s`).

= 3.2.0 - 18/06/2020 =
* Dev - Frontend - Customer message (and `[alg_wc_mppu_current_product_limit]`, `[alg_wc_mppu_term_limit]` shortcodes) - `%first_order_date%`, `%first_order_amount%`, `%first_order_date_exp%`, `%first_order_date_exp_timeleft%` placeholders added.
* Dev - Frontend - Single product page - "Text in product description" option added.
* Dev - Formula - `[alg_wc_mppu]` - Getting time for `start_date` and `end_date` shortcode attributes according to "Advanced > Time function" option now.
* WC tested up to: 4.2.

= 3.1.1 - 22/05/2020 =
* Dev - General - Block guests - Outputting message on AJAX add to cart now.
* Dev - Frontend - Customer message - `%in_cart%`, `%bought_plus_in_cart%`, `%remaining_minus_in_cart%`, `%adding%`, `%in_cart_plus_adding%`, `%bought_plus_in_cart_plus_adding%`, `%remaining_minus_in_cart_minus_adding%`, `%term_name%` placeholders added.

= 3.1.0 - 08/05/2020 =
* Dev - Formula - `is_downloadable`, `is_virtual` attributes added to the `[alg_wc_mppu]` shortcode.
* Dev - Formula - `start_date`, `end_date`, `not_date_limit` attributes added to the `[alg_wc_mppu]` shortcode.
* Dev - Formula - Admin settings descriptions updated.
* Dev - `[alg_wc_mppu_term_limit]` shortcode added.
* Dev - `alg_wc_mppu_date_to_check` filter - `product_or_term_id`, `current_user_id` and `is_product` params added.
* Dev - Code refactoring.
* WC tested up to: 4.1.

= 3.0.1 - 29/04/2020 =
* Fix - Formula - Shortcode bug (`limit` attribute vs deprecated `max_qty` attribute) fixed.
* Dev - Frontend - Single product page - Default message updated (from "The remaining quantity for..." to "The remaining amount for...").
* Dev - Reports - Sales Data - Column title updated (from "Qty" to "Bought").

= 3.0.0 - 14/04/2020 =
* Dev - General - "Mode" option added (with possible options: "Product quantities" (default), "Product prices" and "Product weights").
* Dev - Frontend - Default values for the "Customer message" and "Single product page" messages updated (`pcs.` removed; placeholders changed (`%max_qty%` to `%limit`, `%qty_already_bought%` to `%bought%`, `%remaining_qty%` to `%remaining%`)).
* Dev - Frontend - My Account - "Tab id" option added (defaults to `product-limits`).
* Dev - Frontend - My Account - "Tab title" option added (defaults to `Product limits`).
* Dev - Frontend - My Account - Default tab title changed from "Quantities" to "Product limits".
* Dev - Formula - Shortcode renamed (from `[alg_wc_mppu_max_qty]` to `[alg_wc_mppu]`); `max_qty` attribute renamed to `limit`.
* Dev - `[alg_wc_mppu_user_product_quantities]` shortcode renamed to `[alg_wc_mppu_user_product_limits]`.
* Dev - `[alg_wc_mppu_current_product_quantity]` shortcode renamed to `[alg_wc_mppu_current_product_limit]`.
* Dev - Admin settings descriptions updated ("... quantities..." changed to "... limits..." etc.). "Quantities" section renamed to "Limits".
* Dev - "Reset settings" admin notice updated.
* Tested up to: 5.4.

= 2.6.0 - 23/03/2020 =
* Dev - General - "Block guests" options added.
* Dev - Admin settings restyled and descriptions updated.
* Dev - Minor code refactoring.

= 2.5.2 - 13/03/2020 =
* Dev - Advanced - "Duplicate product" option added (defaults to `no`).
* WC tested up to: 4.0.

= 2.5.1 - 05/03/2020 =
* Fix - General - Payment gateways - Now checking chosen payment gateway when validating quantities (in `Alg_WC_MPPU_Core::check_quantities()`).
* Dev - Frontend - "Cart notice" option added.
* Dev - Frontend - Single product page - "Text" option added (and option renamed from "Permanent notice").
* Dev - Frontend - Single product page - Now replacing negative max qty with zero.
* Dev - `[alg_wc_mppu_current_product_quantity]` shortcode added.

= 2.5.0 - 28/02/2020 =
* Fix - Core - `get_user_already_bought_qty()` - Making sure that the returned value is always numeric (i.e. returning zero instead of empty).
* Fix - Core - `alg_wc_mppu_check_quantities_for_product` filter - Now applying filter to `false` results also.
* Dev - Frontend - "My Account" options added.
* Dev - Frontend - "Permanent notice" option added.
* Dev - `[alg_wc_mppu_user_product_quantities]` shortcode added.
* Dev - General - Custom date range - Default value changed to `3600` (was `1`).
* Dev - Data - `alg_wc_mppu_save_quantities` filter added.
* Dev - Data - `alg_wc_mppu_calculate_data_wc_get_orders_args` filter added.
* Dev - Code refactoring.

= 2.4.3 - 18/02/2020 =
* Fix - Formula - It's no longer required to have at least one of the "Quantities" section checkboxes to be enabled for "Formula" to be active.
* Fix - Notices - Now always using the correct product ID (i.e. variation vs main variable product ID).
* Dev - General - "Payment gateways" option added.
* Dev - `alg_wc_mppu_check_quantities_for_product` filter added.

= 2.4.2 - 05/02/2020 =
* Dev - Formula - `membership_plan` shortcode attribute added.
* Dev - `alg_wc_mppu_get_cart_item_quantities` filter added.
* Dev - `alg_wc_mppu_validate_on_add_to_cart_quantity` filter added.
* Dev - `alg_wc_mppu_save_quantities_item_qty` filter added.
* WC tested up to: 3.9.

= 2.4.1 - 20/01/2020 =
* Dev - Replacing negative max qty with zero in notice now.
* Dev - Code refactoring.

= 2.4.0 - 28/12/2019 =
* Dev - Tools - "Delete sales data" tool added.
* Dev - Tools - Advanced - "Orders date range" option added.
* Dev - Tools - Advanced - "Query block size" option added.
* Dev - Tools - Advanced - "Time limit" option added.
* Dev - Tools - Advanced - "Loop function" option added.
* Dev - Tools - Advanced - "Debug" option added.
* Dev - Settings - General - Section split into two separate sections ("General" and "Frontend").
* Dev - Settings - Admin & Tools - Section split into two separate sections.
* Dev - Code refactoring.

= 2.3.1 - 25/12/2019 =
* Dev - General Options - Date range - "Custom" date range options added.
* Dev - "Advanced" options section added.

= 2.3.0 - 13/12/2019 =
* Fix - User roles - "Guest" user role removed from the lists.
* Dev - "Formula" (i.e. "Max Quantity by Formula") section added.
* Dev - Code refactoring.
* Tested up to: 5.3.

= 2.2.0 - 10/11/2019 =
* Dev - General Options - "Order statuses" option added.
* Dev - General Options - "User roles" option added.
* Dev - Admin Options - "Editable sales data" options added (sales data can now be edited in backend on user's edit page).
* Dev - `alg_wc_mppu_get_max_qty` filter added.
* Dev - Admin settings restyled and split into sections.
* Dev - Code refactoring.
* WC tested up to: 3.8.

= 2.1.0 - 01/10/2019 =
* Dev - General Options - Date range - "This hour", "This day", "This week", "Last hour", "Last 24 hours", "Last 7 days" ranges added.
* Dev - General Options - "Time function" option added.
* Dev - `alg_wc_mppu_date_range` filter added.
* Dev - `alg_wc_mppu_date_to_check` filter added.
* WC tested up to: 3.7.

= 2.0.0 - 28/07/2019 =
* Dev - Per Product Taxonomy - "Per product tag" option added.
* Dev - Per Product Taxonomy - "Per product category" option added.
* Dev - General Options - "Use variations" option added.
* Dev - General Options - "Validate on add to cart" option added.
* Dev - General Options - "Date range" option added.
* Dev - General Options - "Exclude products" option added.
* Dev - Tools - "Delete & recalculate sales data" tool added.
* Dev - Tools - Recalculate sales data - Button replaced with checkbox.
* Dev - `[alg_wc_mppu_translate]` shortcode added (for "Customer message").
* Dev - Major code refactoring.
* Tested up to: 5.2.

= 1.1.2 - 20/04/2019 =
* Dev - Admin settings descriptions updated.
* Dev - "WC tested up to" updated.

= 1.1.1 - 08/04/2019 =
* Dev - Code refactoring.
* Dev - Data sanitized and escaped.

= 1.1.0 - 22/10/2018 =
* Dev - Admin settings descriptions updated.
* Dev - Code refactoring.

= 1.0.0 - 18/06/2018 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
This is the first release of the plugin.
