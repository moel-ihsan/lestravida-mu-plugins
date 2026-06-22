<?php
/**
 * counter.php
 */

if (!defined('ABSPATH')) exit;

final class LVK_Counter {

    const CACHE_PREFIX = 'lvk_count_';
    const CACHE_VERSION_OPTION = 'lvk_count_cache_version';

    private static $need_js = false;

    private static function cache_version() {
        $version = get_option(self::CACHE_VERSION_OPTION, '1');
        return $version ?: '1';
    }

    public static function count_products_in_cat($slug, $mode = 'active', $cache_secs = 3600) {

        $slug = sanitize_title($slug);
        $mode = ($mode === 'all') ? 'all' : 'active';
        $cache_secs = (int) $cache_secs;

        if ($slug === '') {
            return 0;
        }

        $cache_key = self::CACHE_PREFIX . md5(
            self::cache_version() . '_' . $slug . '_' . $mode
        );

        if ($cache_secs > 0) {
            $cached = get_transient($cache_key);

            if ($cached !== false) {
                return (int) $cached;
            }
        }

        $args = [
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => [[
                'taxonomy'         => 'product_cat',
                'field'            => 'slug',
                'terms'            => $slug,
                'include_children' => true,
            ]],
        ];

        if (
            $mode === 'active'
            && class_exists('LVK_Rules')
            && method_exists('LVK_Rules', 'active_meta_query')
        ) {
            $args['meta_query'] = LVK_Rules::active_meta_query();
        }

        $query = new WP_Query($args);
        $count = (int) $query->found_posts;

        wp_reset_postdata();

        if ($cache_secs > 0) {
            set_transient($cache_key, $count, $cache_secs);
        }

        return $count;
    }

    public static function clear_cache($post_id = 0) {

        if ($post_id && wp_is_post_revision($post_id)) {
            return;
        }

        update_option(
            self::CACHE_VERSION_OPTION,
            (string) time(),
            false
        );
    }

    private static function get_shortcode_atts($atts, $shortcode) {

        return shortcode_atts([
            'cat'       => '',
            'label'     => '',
            'mode'      => 'active',
            'duration'  => '2000',
            'from'      => '0',
            'delimiter' => '.',
            'prefix'    => '',
            'suffix'    => '',
            'cache'     => '3600',
            'class'     => '',
        ], $atts, $shortcode);
    }

    private static function render_counter_number($a, $count, $from) {
        ?>
        <span class="elementor-counter-number-prefix">
            <?php echo esc_html($a['prefix']); ?>
        </span>

        <span
            class="elementor-counter-number lvjs-counter"
            data-duration="<?php echo esc_attr($a['duration']); ?>"
            data-to-value="<?php echo esc_attr($count); ?>"
            data-from-value="<?php echo esc_attr($from); ?>"
            data-delimiter="<?php echo esc_attr($a['delimiter']); ?>"
        >
            <?php echo esc_html(number_format_i18n($count)); ?>
        </span>

        <span class="elementor-counter-number-suffix">
            <?php echo esc_html($a['suffix']); ?>
        </span>
        <?php
    }

    private static function render_label($label) {

        if (!strlen(trim((string) $label))) {
            return;
        }
        ?>
        <div class="elementor-counter-title">
            <?php echo esc_html($label); ?>
        </div>
        <?php
    }

    public static function shortcode_counter($atts) {

        self::$need_js = true;

        $a = self::get_shortcode_atts($atts, 'lv_counter');

        if (empty($a['cat'])) {
            return '';
        }

        $count = self::count_products_in_cat(
            $a['cat'],
            $a['mode'],
            (int) $a['cache']
        );

        $from = (int) $a['from'];

        ob_start();
        ?>
        <div class="elementor-counter <?php echo esc_attr($a['class']); ?> lv-counter">
            <div class="elementor-counter-number-wrapper">
                <?php self::render_counter_number($a, $count, $from); ?>
            </div>

            <?php self::render_label($a['label']); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function shortcode_counter_number($atts) {

        self::$need_js = true;

        $a = self::get_shortcode_atts($atts, 'lv_counter_number');

        if (empty($a['cat'])) {
            return '';
        }

        $count = self::count_products_in_cat(
            $a['cat'],
            $a['mode'],
            (int) $a['cache']
        );

        $from = (int) $a['from'];

        ob_start();
        ?>
        <div class="elementor-counter-number-wrapper <?php echo esc_attr($a['class']); ?>">
            <?php self::render_counter_number($a, $count, $from); ?>
        </div>

        <?php self::render_label($a['label']); ?>
        <?php
        return ob_get_clean();
    }

    public static function print_js_if_needed() {

        if (!self::$need_js) {
            return;
        }
        ?>
        <script>
        (function(){
            function formatNumber(n, delim){
                var s = Math.floor(n).toString();
                return s.replace(/\B(?=(\d{3})+(?!\d))/g, delim || '.');
            }

            function animate(el){
                if (el.dataset.lvDone) return;

                var dur   = parseInt(el.dataset.duration || '2000', 10);
                var from  = parseFloat(el.dataset.fromValue || '0');
                var to    = parseFloat(el.dataset.toValue || '0');
                var delim = el.dataset.delimiter || '.';
                var start = null;

                function step(ts){
                    if (!start) start = ts;

                    var p = Math.min((ts - start) / dur, 1);
                    var eased = 1 - Math.pow(1 - p, 3);
                    var val = from + (to - from) * eased;

                    el.textContent = formatNumber(val, delim);

                    if (p < 1) {
                        requestAnimationFrame(step);
                    } else {
                        el.textContent = formatNumber(to, delim);
                        el.dataset.lvDone = '1';
                    }
                }

                requestAnimationFrame(step);
            }

            function init(){
                var els = document.querySelectorAll('.lvjs-counter');

                if (!els.length) return;

                els.forEach(function(el){
                    if ('IntersectionObserver' in window) {
                        var io = new IntersectionObserver(function(entries, obs){
                            entries.forEach(function(entry){
                                if (entry.isIntersecting) {
                                    animate(el);
                                    obs.unobserve(el);
                                }
                            });
                        }, { threshold: 0.3 });

                        io.observe(el);
                    } else {
                        animate(el);
                    }
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
        </script>
        <?php
    }

    public static function hooks() {
        if (!class_exists('WooCommerce')) return;

        add_shortcode('lv_counter', [__CLASS__, 'shortcode_counter']);
        add_shortcode('lv_counter_number', [__CLASS__, 'shortcode_counter_number']);

        add_action('wp_footer', [__CLASS__, 'print_js_if_needed'], 99);

        add_action('save_post_product', [__CLASS__, 'clear_cache'], 20);
        add_action('woocommerce_update_product', [__CLASS__, 'clear_cache'], 20);
    }
}

add_action('init', ['LVK_Counter', 'hooks']);