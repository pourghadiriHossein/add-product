<?php

/**
 * Plugin Name: AminH Product Add
 * Description: This plugin is for add new Product.
 * Version: 1.0.2
 * Author: AminH Company
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('AminHAddProduct')) {
    class AminHAddProduct
    {

        public function __construct()
        {
            if (!defined('AminHAddProduct_PATH')) define('AminHAddProduct_PATH', plugin_dir_path(__FILE__));
            if (!defined('AminHAddProduct_URL'))  define('AminHAddProduct_URL',  plugin_dir_url(__FILE__));

            if (!session_id()) {
                session_start();
            }

            // WP hooks
            add_action('init', [$this, 'initialize']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_styles_and_scripts']);

            add_action('admin_init', [$this, 'ensure_attributes_and_terms'], 20);
            add_action('init', [$this, 'register_attribute_taxonomies_if_needed'], 11);
            add_action('init', [$this, 'ensure_terms_if_needed'], 99);

            add_action('admin_init', [$this, 'clear_session_messages']);
        }

        public function initialize()
        {
            add_action('admin_menu', [$this, 'register_admin_pages']);
            add_filter('post_row_actions', [$this, 'add_simple_edit_link'], 10, 2);
            add_action('admin_post_aminh_add_product', [$this, 'handle_form_submission']);
            add_action('admin_notices', [$this, 'admin_notices']);

            add_action('admin_head', [$this, 'add_admin_styles']);
            add_action('wp_head', [$this, 'add_custom_css_in_head']);
            add_action('admin_head', [$this, 'add_custom_admin_css']);
        }

        public function add_custom_css_in_head()
        {
            echo '<style>
                    .product_title {
                        direction: ltr !important;
                    }

                    .wd-last {
                        direction: ltr !important;
                    }
                </style>';
        }

        public function add_custom_admin_css()
        {
            echo '<style>
                    #titlediv #title {
                        direction: ltr !important;
                        text-align: right !important;
                    }
                </style>';
        }

        public function add_admin_styles()
        {
            if (isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
                echo '<style>
                        .wp-list-table .column-name {
                            direction: ltr !important;
                            text-align: right !important;
                        }
                        #titlediv #title {
                            direction: ltr !important;
                            text-align: right !important;
                        }
                    </style>';
            }
        }

        public function enqueue_styles_and_scripts($hook_suffix = '')
        {
            if (strpos($hook_suffix, 'aminh-simple-add') !== false) {
                wp_enqueue_style('aminh-product-add', AminHAddProduct_URL . 'aminh-product-add.css');
                wp_enqueue_script('aminh-product-add', AminHAddProduct_URL . 'aminh-product-add.js', [], false, true);
            }
        }

        public function register_admin_pages()
        {
            add_submenu_page(
                'edit.php?post_type=product',
                'افزودن ساده',
                'افزودن ساده',
                'manage_woocommerce',
                'aminh-simple-add',
                [$this, 'render_simple_add_page'],
                2
            );
        }

        public function render_simple_add_page()
        {
            $product_id = isset($_GET['edit_product']) ? intval($_GET['edit_product']) : 0;
            $product = $product_id ? wc_get_product($product_id) : null;

            $categories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ]);
?>
            <div class="aminh-simple-add-wrapper">
                <h1><?php echo $product_id ? 'ویرایش سیم کارت' : 'افزودن سیم کارت جدید'; ?></h1>
                <form id="aminh-simple-add-form" method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('aminh_simple_add_product', 'aminh_simple_add_nonce'); ?>
                    <input type="hidden" name="action" value="aminh_add_product">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                    <div class="form-group full-width">
                        <label for="product_title">شماره سیم کارت</label>
                        <input type="text" id="product_title" name="product_title" class="ltr-input" value="<?php echo $product ? esc_attr($product->get_name()) : ''; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="regular_price">قیمت عادی</label>
                        <input type="text" id="regular_price" name="_regular_price" value="<?php echo $product ? esc_attr($product->get_regular_price()) : ''; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="sale_price">قیمت با تخفیف</label>
                        <input type="text" id="sale_price" name="_sale_price" value="<?php echo $product ? esc_attr($product->get_sale_price()) : ''; ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="product_category">دسته بندی</label>
                            <select id="product_category" name="product_cat[]">
                                <option value="">انتخاب کنید</option>
                                <?php
                                if (!empty($categories) && !is_wp_error($categories)) {
                                    $product_cats = $product ? $product->get_category_ids() : [];
                                    foreach ($categories as $cat) {
                                        $selected = in_array($cat->term_id, $product_cats) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($cat->term_id) . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <?php
                        $attributes = $this->attributes_spec();
                        $attr = $attributes[0];
                        $taxonomy = 'pa_' . sanitize_title($attr['slug']);
                        $terms = get_terms([
                            'taxonomy' => $taxonomy,
                            'hide_empty' => false,
                        ]);
                        $selected_term = '';
                        if ($product) {
                            $product_terms = wp_get_post_terms($product->get_id(), $taxonomy, ['fields' => 'slugs']);
                            if (!empty($product_terms)) {
                                $selected_term = $product_terms[0];
                            }
                        }
                        ?>
                        <div class="form-group col-6">
                            <label for="<?php echo esc_attr($taxonomy); ?>"><?php echo esc_html($attr['label']); ?></label>
                            <select id="<?php echo esc_attr($taxonomy); ?>" name="aminh_attr[<?php echo esc_attr($taxonomy); ?>]">
                                <option value="">انتخاب کنید</option>
                                <?php
                                foreach ($terms as $term) {
                                    $selected = ($selected_term === $term->slug) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($term->slug) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <?php
                        for ($i = 1; $i <= 2; $i++) {
                            $attr = $attributes[$i];
                            $taxonomy = 'pa_' . sanitize_title($attr['slug']);
                            $terms = get_terms([
                                'taxonomy' => $taxonomy,
                                'hide_empty' => false,
                            ]);
                            $selected_term = '';
                            if ($product) {
                                $product_terms = wp_get_post_terms($product->get_id(), $taxonomy, ['fields' => 'slugs']);
                                if (!empty($product_terms)) {
                                    $selected_term = $product_terms[0];
                                }
                            }
                        ?>
                            <div class="form-group col-6">
                                <label for="<?php echo esc_attr($taxonomy); ?>"><?php echo esc_html($attr['label']); ?></label>
                                <select id="<?php echo esc_attr($taxonomy); ?>" name="aminh_attr[<?php echo esc_attr($taxonomy); ?>]">
                                    <option value="">انتخاب کنید</option>
                                    <?php
                                    foreach ($terms as $term) {
                                        $selected = ($selected_term === $term->slug) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($term->slug) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="form-row">
                        <?php
                        for ($i = 3; $i <= 4; $i++) {
                            $attr = $attributes[$i];
                            $taxonomy = 'pa_' . sanitize_title($attr['slug']);
                            $terms = get_terms([
                                'taxonomy' => $taxonomy,
                                'hide_empty' => false,
                            ]);
                            $selected_term = '';
                            if ($product) {
                                $product_terms = wp_get_post_terms($product->get_id(), $taxonomy, ['fields' => 'slugs']);
                                if (!empty($product_terms)) {
                                    $selected_term = $product_terms[0];
                                }
                            }
                        ?>
                            <div class="form-group col-6">
                                <label for="<?php echo esc_attr($taxonomy); ?>"><?php echo esc_html($attr['label']); ?></label>
                                <select id="<?php echo esc_attr($taxonomy); ?>" name="aminh_attr[<?php echo esc_attr($taxonomy); ?>]">
                                    <option value="">انتخاب کنید</option>
                                    <?php
                                    foreach ($terms as $term) {
                                        $selected = ($selected_term === $term->slug) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($term->slug) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="form-group">
                        <label>تصویر سیم کارت</label>
                        <div id="drop-area">
                            <p id="drop-text">عکس را اینجا بکشید یا کلیک کنید</p>
                            <input type="file" id="product_image" name="product_image" accept="image/*">
                            <?php if ($product && has_post_thumbnail($product->get_id())): ?>
                                <?php $image_url = get_the_post_thumbnail_url($product->get_id(), 'thumbnail'); ?>
                                <img id="preview-image" src="<?php echo esc_url($image_url); ?>" alt="Preview">
                                <button type="button" id="remove-image">حذف تصویر</button>
                            <?php else: ?>
                                <img id="preview-image" alt="Preview" style="display:none;">
                                <button type="button" id="remove-image" style="display:none;">حذف تصویر</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="button button-primary"><?php echo $product_id ? 'بروزرسانی سیم کارت' : 'افزودن سیم کارت'; ?></button>
                </form>
            </div>
<?php
        }

        public function add_simple_edit_link($actions, $post)
        {
            if ($post->post_type === 'product') {
                $url = admin_url('admin.php?page=aminh-simple-add&edit_product=' . $post->ID);
                $actions['aminh_simple_edit'] = '<a href="' . esc_url($url) . '">ویرایش ساده</a>';
            }
            return $actions;
        }

        public function handle_form_submission()
        {
            if (!isset($_POST['aminh_simple_add_nonce']) || !wp_verify_nonce($_POST['aminh_simple_add_nonce'], 'aminh_simple_add_product')) {
                wp_die('Security check failed');
            }

            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $action_state = $product_id == 0 ? 'added' : 'updated';
            $title = sanitize_text_field($_POST['product_title'] ?? '');
            $regular_price = floatval($_POST['_regular_price'] ?? 0);
            $sale_price = floatval($_POST['_sale_price'] ?? 0);
            $stock_status = sanitize_text_field('instock');
            $categories = array_map('intval', $_POST['product_cat'] ?? []);

            if (empty($title)) {
                set_transient('aminh_notices', [
                    'type' => 'error',
                    'message' => 'شماره سیم کارت نمی‌تواند خالی باشد.'
                ], 30);
                wp_redirect(wp_get_referer());
                exit;
            }

            if (empty($title)) {
                $_SESSION['aminh_notices'] = [
                    'type' => 'error',
                    'message' => 'شماره سیم کارت نمی‌تواند خالی باشد.'
                ];
                wp_redirect(wp_get_referer());
                exit;
            }

            $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

            $product->set_name($title);
            $product->set_regular_price($regular_price);
            if ($sale_price > 0) {
                $product->set_sale_price($sale_price);
            }
            $product->set_stock_status($stock_status);
            $product->set_category_ids($categories);

            $product_id = $product->save();

            if (!empty($_FILES['product_image']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('product_image', $product_id);
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($product_id, $attachment_id);
                }
            }

            if (!empty($_POST['aminh_attr']) && is_array($_POST['aminh_attr'])) {
                $attributes_meta = array();
                foreach ($_POST['aminh_attr'] as $taxonomy => $term_slug) {
                    if ($term_slug) {
                        wp_set_object_terms($product_id, $term_slug, $taxonomy, false);

                        $attr_name = str_replace('pa_', '', $taxonomy);
                        $attributes_meta[$attr_name] = array(
                            'name'         => $taxonomy,
                            'value'        => '',
                            'is_visible'   => 1,
                            'is_variation' => 0,
                            'is_taxonomy'  => 1
                        );
                    } else {
                        wp_set_object_terms($product_id, [], $taxonomy, false);
                    }
                }
                foreach ($attributes_meta as $attr_name => &$meta) {
                    $taxonomy = $meta['name'];
                    $terms = wp_get_object_terms($product_id, $taxonomy, array('fields' => 'names'));
                    $meta['value'] = isset($terms[0]) ? $terms[0] : '';
                }
                unset($meta);
                update_post_meta($product_id, '_product_attributes', $attributes_meta);
            }

            wp_redirect(add_query_arg('aminh_success', $action_state, wp_get_referer()));
            exit;
        }

        public function admin_notices()
        {
            if (isset($_GET['aminh_success'])) {
                $message = '';
                $type = 'success';

                switch ($_GET['aminh_success']) {
                    case 'added':
                        $message = 'سیم کارت با موفقیت اضافه شد.';
                        break;
                    case 'updated':
                        $message = 'سیم کارت با موفقیت بروزرسانی شد.';
                        break;
                }

                if ($message) {
                    echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
                    echo '<p>' . esc_html($message) . '</p>';
                    echo '</div>';
                }
            }

            if (isset($_GET['aminh_error'])) {
                $message = '';
                $type = 'error';

                switch ($_GET['aminh_error']) {
                    case 'empty_title':
                        $message = 'شماره سیم کارت نمی‌تواند خالی باشد.';
                        break;
                }

                if ($message) {
                    echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
                    echo '<p>' . esc_html($message) . '</p>';
                    echo '</div>';
                }
            }
        }

        public function clear_session_messages()
        {
            if (isset($_SESSION['aminh_notices'])) {
                unset($_SESSION['aminh_notices']);
            }
        }

        /** ---------- Attributes Spec ---------- */
        private function attributes_spec()
        {
            return [
                [
                    'label' => 'نوع قیمت',
                    'slug'  => 'price_type',
                    'terms' => [
                        ['name' => 'قیمت دار',     'slug' => 'priced'],
                        ['name' => 'توافقی',       'slug' => 'negotiable'],
                        ['name' => 'تماس بگیرید',  'slug' => 'call_for_price'],
                    ],
                ],
                [
                    'label' => 'وضعیت',
                    'slug'  => 'status',
                    'terms' => [
                        ['name' => 'صفر به نام', 'slug' => 'zero_to_name'],
                        ['name' => 'صفر پک',     'slug' => 'zero_pack'],
                        ['name' => 'کار کرده',   'slug' => 'used'],
                    ],
                ],
                [
                    'label' => 'نوع سیم کارت',
                    'slug'  => 'sim_card_type',
                    'terms' => [
                        ['name' => 'دائمی',  'slug' => 'permanent'],
                        ['name' => 'اعتباری', 'slug' => 'prepaid'],
                    ],
                ],
                [
                    'label' => 'شرایط فروش',
                    'slug'  => 'sale_condition',
                    'terms' => [
                        ['name' => 'نقد',           'slug' => 'cash'],
                        ['name' => 'اقساط',        'slug' => 'installment'],
                        ['name' => 'نقد و اقساط',  'slug' => 'cash_and_installment'],
                    ],
                ],
                [
                    'label' => 'تماس آگهی',
                    'slug'  => 'contact',
                    'terms' => [
                        ['name' => '09123100700', 'slug' => '09123100700'],
                        ['name' => '09124545745', 'slug' => '09124545745'],
                    ],
                ],
            ];
        }

        /** ---------- Activation helper runs once ---------- */
        public static function on_activate()
        {
            if (version_compare(PHP_VERSION, '7.4', '<')) {
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die(__('این پلاگین نیاز دارد php شما از نسخه 7 به بالا باشد', 'AminHAddProduct'));
            }

            $self = new self();
            $self->create_missing_attributes();
            delete_transient('wc_attribute_taxonomies');
            flush_rewrite_rules();
        }

        /** ---------- Always-on safety: create attrs & terms if missing ---------- */
        public function ensure_attributes_and_terms()
        {
            if (!current_user_can('manage_woocommerce')) return;
            $this->create_missing_attributes();
            $this->register_attribute_taxonomies_if_needed();
            $this->ensure_terms_if_needed();
        }

        /** Create attribute rows in DB (idempotent) */
        private function create_missing_attributes()
        {
            global $wpdb;

            foreach ($this->attributes_spec() as $attr) {
                $slug = sanitize_title($attr['slug']);
                $label = $attr['label'];

                if (function_exists('wc_attribute_taxonomy_id_by_name') && function_exists('wc_create_attribute')) {
                    $attr_id = wc_attribute_taxonomy_id_by_name($slug);
                    if (!$attr_id) {
                        wc_create_attribute([
                            'slug'    => $slug,
                            'name'    => $label,
                            'type'    => 'select',
                            'orderby' => 'menu_order',
                            'has_archives' => true,
                        ]);
                        delete_transient('wc_attribute_taxonomies');
                    }
                } else {
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
                        $slug
                    ));
                    if (!$exists) {
                        $wpdb->insert(
                            $wpdb->prefix . 'woocommerce_attribute_taxonomies',
                            [
                                'attribute_label'   => $label,
                                'attribute_name'    => $slug,
                                'attribute_type'    => 'select',
                                'attribute_orderby' => 'menu_order',
                                'attribute_public'  => 1
                            ],
                            ['%s', '%s', '%s', '%s', '%d']
                        );
                        delete_transient('wc_attribute_taxonomies');
                    }
                }
            }
        }

        /** Ensure taxonomies exist (either by WC dynamic or manual) */
        public function register_attribute_taxonomies_if_needed()
        {
            foreach ($this->attributes_spec() as $attr) {
                $taxonomy = 'pa_' . sanitize_title($attr['slug']);
                if (!taxonomy_exists($taxonomy)) {
                    register_taxonomy(
                        $taxonomy,
                        ['product'],
                        [
                            'hierarchical' => true,
                            'label'        => $attr['label'],
                            'query_var'    => true,
                            'rewrite'      => ['slug' => $taxonomy],
                            'show_admin_column' => false,
                            'show_ui'      => true,
                            'show_in_quick_edit' => false,
                        ]
                    );
                }
            }
        }

        /** Insert terms for each taxonomy if missing */
        public function ensure_terms_if_needed()
        {
            foreach ($this->attributes_spec() as $attr) {
                $taxonomy = 'pa_' . sanitize_title($attr['slug']);
                if (!taxonomy_exists($taxonomy)) continue;

                foreach ($attr['terms'] as $term) {
                    $slug = sanitize_title($term['slug']);
                    $exists = term_exists($slug, $taxonomy);
                    if (!$exists) {
                        wp_insert_term($term['name'], $taxonomy, ['slug' => $slug]);
                    }
                }
            }
        }
    }

    // Boot plugin
    $aminh_plugin_instance = new AminHAddProduct();

    // Activation hook
    register_activation_hook(__FILE__, ['AminHAddProduct', 'on_activate']);
}
