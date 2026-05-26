<?php
/**
 * registrations-export.php
 */

if (!defined('ABSPATH')) exit;

final class LVC_Registrations_Export {

    public static function admin_menu(): void {
        add_submenu_page(
            'lestravida',
            'Rekap Pendaftaran',
            'Rekap Pendaftaran',
            'manage_woocommerce',
            'lvc-registrations',
            [__CLASS__, 'page']
        );
    }

    private static function allowed_statuses(): array {
        return ['wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending'];
    }

    private static function per_page(): int {
        $per_page = isset($_GET['per_page'])
            ? sanitize_text_field(wp_unslash($_GET['per_page']))
            : '20';

        if ($per_page === 'all') {
            return -1;
        }

        $per_page = absint($per_page);

        return in_array($per_page, [20, 50, 100], true) ? $per_page : 20;
    }

    private static function current_page(): int {
        return max(1, absint($_GET['paged'] ?? 1));
    }

    private static function get_orders(array $args = []) {
        return wc_get_orders(array_merge([
            'limit'   => 20,
            'paged'   => 1,
            'status'  => self::allowed_statuses(),
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'objects',
        ], $args));
    }

    private static function is_registration_closed(int $product_id): bool {
        $product = wc_get_product($product_id);

        if (!$product) {
            return true;
        }

        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return true;
        }

        if (class_exists('LVK_Helper')) {
            $tanggal = get_post_meta($product_id, LVK_Helper::META_TANGGAL, true);

            if ($tanggal) {
                try {
                    $event_date = new DateTimeImmutable($tanggal, wp_timezone());
                    $today = new DateTimeImmutable('today', wp_timezone());

                    return $event_date < $today;
                } catch (Exception $e) {
                    return false;
                }
            }
        }

        return false;
    }

    private static function order_has_product(WC_Order $order, int $product_id): bool {
        foreach ($order->get_items() as $item) {
            if ((int) $item->get_product_id() === $product_id) {
                return true;
            }
        }

        return false;
    }

    private static function order_product_ids(WC_Order $order): array {
        $ids = [];

        foreach ($order->get_items() as $item) {
            $product_id = (int) $item->get_product_id();

            if ($product_id) {
                $ids[] = $product_id;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function page(): void {
        $product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;

        echo '<div class="wrap">';
        echo '<h1>Rekap Pendaftaran</h1>';

        if ($product_id) {
            self::render_detail($product_id);
        } else {
            self::render_summary();
        }

        echo '</div>';
    }

    private static function render_summary(): void {
        $summary = [];

        foreach (self::get_orders(['limit' => -1]) as $order) {
            foreach (self::order_product_ids($order) as $product_id) {
                if (!self::is_registration_closed($product_id)) {
                    continue;
                }

                if (!isset($summary[$product_id])) {
                    $ctx = self::product_context($product_id);

                    $summary[$product_id] = [
                        'title'      => $ctx['title'],
                        'category'   => $ctx['category'],
                        'chapter'    => $ctx['chapter'],
                        'batch'      => $ctx['batch'],
                        'event_date' => $ctx['event_date'],
                        'total'      => 0,
                        'completed'  => 0,
                        'processing' => 0,
                        'pending'    => 0,
                        'on-hold'    => 0,
                    ];
                }

                $summary[$product_id]['total']++;

                $status = $order->get_status();

                if (isset($summary[$product_id][$status])) {
                    $summary[$product_id][$status]++;
                }
            }
        }

        echo '<p class="description">Rekap hanya ditampilkan untuk event yang pendaftarannya sudah tutup.</p>';
        echo '<h2>Daftar Event Tertutup</h2>';

        $orderby = sanitize_key($_GET['orderby'] ?? 'title');
        $order   = strtolower(sanitize_key($_GET['order'] ?? 'asc'));

        uasort($summary, function ($a, $b) use ($orderby, $order) {
            $av = $a[$orderby] ?? '';
            $bv = $b[$orderby] ?? '';

            $result = (is_numeric($av) && is_numeric($bv))
                ? $av <=> $bv
                : strnatcasecmp((string) $av, (string) $bv);

            return $order === 'desc' ? -$result : $result;
        });

        echo '<table class="widefat striped lvc-recap-table">';
        echo '<thead><tr>';
        echo '<th>' . self::sort_link('Judul Event', 'title') . '</th>';
        echo '<th>' . self::sort_link('Kategori', 'category') . '</th>';
        echo '<th>' . self::sort_link('Chapter', 'chapter') . '</th>';
        echo '<th>' . self::sort_link('Batch', 'batch') . '</th>';
        echo '<th>' . self::sort_link('Tanggal Acara', 'event_date') . '</th>';
        echo '<th>' . self::sort_link('Total Peserta', 'total') . '</th>';
        echo '<th>Completed</th>';
        echo '<th>Processing</th>';
        echo '<th>Pending</th>';
        echo '<th>On Hold</th>';
        echo '<th>Aksi</th>';
        echo '</tr></thead><tbody>';

        foreach ($summary as $product_id => $row) {
            $detail_url = admin_url('admin.php?page=lvc-registrations&product_id=' . $product_id);

            $export_url = wp_nonce_url(
                admin_url('admin-post.php?action=lvc_export_csv&product_id=' . $product_id),
                'lvc_export_csv'
            );

            echo '<tr>';
            echo '<td>' . esc_html($row['title']) . '</td>';
            echo '<td>' . esc_html($row['category']) . '</td>';
            echo '<td>' . esc_html($row['chapter']) . '</td>';
            echo '<td>' . esc_html($row['batch']) . '</td>';
            echo '<td>' . esc_html($row['event_date']) . '</td>';
            echo '<td>' . esc_html($row['total']) . '</td>';
            echo '<td>' . esc_html($row['completed']) . '</td>';
            echo '<td>' . esc_html($row['processing']) . '</td>';
            echo '<td>' . esc_html($row['pending']) . '</td>';
            echo '<td>' . esc_html($row['on-hold']) . '</td>';
            echo '<td>';
            echo '<a class="button" href="' . esc_url($detail_url) . '">Lihat Peserta</a> ';
            echo '<a class="button button-primary" href="' . esc_url($export_url) . '">Export CSV</a>';
            echo '</td>';
            echo '</tr>';
        }

        if (empty($summary)) {
            echo '<tr><td colspan="11">Belum ada event yang pendaftarannya sudah tutup.</td></tr>';
        }

        echo '</tbody></table>';
    }

    private static function render_detail(int $product_id): void {
        echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=lvc-registrations')) . '">← Kembali ke daftar event</a></p>';

        if (!self::is_registration_closed($product_id)) {
            echo '<div class="notice notice-warning"><p><strong>Rekap belum tersedia.</strong> Pendaftaran event ini masih dibuka.</p></div>';
            return;
        }

        $ctx = self::product_context($product_id);

        $title_parts = ['Daftar Peserta'];

        if ($ctx['category'] !== '') {
            $title_parts[] = $ctx['category'];
        }

        if ($ctx['chapter'] !== '') {
            $title_parts[] = 'Chapter ' . $ctx['chapter'];
        }

        if ($ctx['batch'] !== '') {
            $title_parts[] = 'Batch ' . $ctx['batch'];
        }

        echo '<h2>' . esc_html(implode(' ', $title_parts)) . '</h2>';

        $export_url = wp_nonce_url(
            admin_url('admin-post.php?action=lvc_export_csv&product_id=' . $product_id),
            'lvc_export_csv'
        );

        echo '<p><a class="button button-primary" href="' . esc_url($export_url) . '">Export CSV</a></p>';

        self::render_per_page_selector($product_id);

        $per_page = self::per_page();
        $paged    = self::current_page();

        $orders = self::get_orders([
            'limit' => $per_page,
            'paged' => $paged,
        ]);

        $rows = [];

        foreach ($orders as $order) {
            if (!self::order_has_product($order, $product_id)) {
                continue;
            }

            $rows[] = self::row_from_order($order, $product_id);
        }

        self::render_rows_table($rows);

        if ($per_page !== -1) {
            self::render_pagination($product_id, $paged);
        }
    }

    private static function render_per_page_selector(int $product_id): void {
        $current = self::per_page();

        echo '<form method="get" style="margin:12px 0;">';
        echo '<input type="hidden" name="page" value="lvc-registrations">';
        echo '<input type="hidden" name="product_id" value="' . esc_attr($product_id) . '">';
        echo '<label> tampilkan ';
        echo '<select name="per_page" onchange="this.form.submit()">';

        foreach ([20 => '20', 50 => '50', 100 => '100', -1 => 'All'] as $value => $label) {
            echo '<option value="' . esc_attr($value === -1 ? 'all' : $value) . '" ' . selected($current, $value, false) . '>' . esc_html($label) . '</option>';
        }

        echo '</select> peserta per halaman</label>';
        echo '</form>';
    }

    private static function render_pagination(int $product_id, int $paged): void {
        $per_page = self::per_page() === -1 ? 'all' : self::per_page();

        $base = admin_url('admin.php?page=lvc-registrations&product_id=' . $product_id . '&per_page=' . $per_page);

        echo '<p style="margin-top:14px;">';

        if ($paged > 1) {
            echo '<a class="button" href="' . esc_url(add_query_arg('paged', $paged - 1, $base)) . '">← Sebelumnya</a> ';
        }

        echo '<span class="button disabled">Halaman ' . esc_html($paged) . '</span> ';
        echo '<a class="button" href="' . esc_url(add_query_arg('paged', $paged + 1, $base)) . '">Berikutnya →</a>';

        echo '</p>';
    }

    private static function drive_files_for_product(WC_Order $order, int $product_id): array {
        $event_drive = $order->get_meta('_lvc_event_drive_files');

        if (is_array($event_drive) && !empty($event_drive[$product_id]) && is_array($event_drive[$product_id])) {
            return [
                'bukti_lv'       => $event_drive[$product_id]['bukti_lestravida_token'] ?? '',
                'bukti_lvdaerah' => $event_drive[$product_id]['bukti_lvdaerah_token'] ?? '',
                'bukti_share'    => $event_drive[$product_id]['bukti_share_token'] ?? '',
            ];
        }

        return [
            'bukti_lv'       => $order->get_meta('_lvc_drive_bukti_lestravida'),
            'bukti_lvdaerah' => $order->get_meta('_lvc_drive_bukti_lvdaerah'),
            'bukti_share'    => $order->get_meta('_lvc_drive_bukti_share'),
        ];
    }

    private static function row_from_order(WC_Order $order, int $product_id): array {
        $drive = self::drive_files_for_product($order, $product_id);

        return [
            'order'       => $order->get_id(),
            'nama'        => $order->get_formatted_billing_full_name(),
            'email'       => $order->get_billing_email(),
            'whatsapp'    => $order->get_billing_phone(),
            'umur'        => $order->get_meta('_billing_age'),
            'sekolah'     => $order->get_meta('_billing_school'),
            'instagram'   => $order->get_meta('_billing_instagram'),
            'sumber_info' => $order->get_meta('_billing_source_info'),
            'tanggal_txt' => $order->get_date_created() ? wc_format_datetime($order->get_date_created()) : '-',
            'status'      => wc_get_order_status_name($order->get_status()),
            'bukti_lv'    => $drive['bukti_lv'],
            'bukti_lvdaerah' => $drive['bukti_lvdaerah'],
            'bukti_share' => $drive['bukti_share'],
        ];
    }

    private static function render_rows_table(array $rows): void {
        echo '<table class="widefat striped lvc-recap-table">';
        echo '<thead><tr>';
        echo '<th>No</th><th>Order</th><th>Nama</th><th>Email</th><th>WhatsApp</th><th>Umur</th><th>Sekolah</th><th>Instagram</th><th>Sumber Info</th><th>Tanggal</th><th>Status</th><th>Bukti LV</th><th>Bukti LV Daerah</th><th>Bukti Share</th>';
        echo '</tr></thead><tbody>';

        $no = 1;

        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($no++) . '</td>';
            echo '<td><a href="' . esc_url(admin_url('post.php?post=' . $row['order'] . '&action=edit')) . '">#' . esc_html($row['order']) . '</a></td>';
            echo '<td>' . esc_html($row['nama']) . '</td>';
            echo '<td>' . esc_html($row['email']) . '</td>';
            echo '<td>' . esc_html($row['whatsapp']) . '</td>';
            echo '<td>' . esc_html($row['umur']) . '</td>';
            echo '<td>' . esc_html($row['sekolah']) . '</td>';
            echo '<td>' . esc_html($row['instagram']) . '</td>';
            echo '<td>' . esc_html($row['sumber_info']) . '</td>';
            echo '<td>' . esc_html($row['tanggal_txt']) . '</td>';
            echo '<td>' . esc_html($row['status']) . '</td>';

            self::print_url_cell($row['bukti_lv']);
            self::print_url_cell($row['bukti_lvdaerah']);
            self::print_url_cell($row['bukti_share']);

            echo '</tr>';
        }

        if (empty($rows)) {
            echo '<tr><td colspan="14">Tidak ada peserta pada halaman ini.</td></tr>';
        }

        echo '</tbody></table>';
    }

    private static function product_context(int $product_id): array {
        $product = wc_get_product($product_id);

        $context = [
            'title'      => $product ? $product->get_name() : '-',
            'category'   => '-',
            'chapter'    => '-',
            'batch'      => '-',
            'event_date' => self::event_date($product_id),
        ];

        $known_events = [
            'muda-mudi-mengabdi'    => 'Muda Mudi Mengabdi',
            'lestra-vida-mengabdi'  => 'Lestra Vida Mengabdi',
            'leaders-roundtable'    => 'Leaders Roundtable',
            'beasiswa-mera-sekolah' => 'Beasiswa Mera Sekolah',
        ];

        $terms = get_the_terms($product_id, 'product_cat');

        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $check = $term;

                while ($check && !is_wp_error($check)) {
                    if (isset($known_events[$check->slug])) {
                        $context['category'] = $known_events[$check->slug];
                        break 2;
                    }

                    if (empty($check->parent)) {
                        break;
                    }

                    $check = get_term($check->parent, 'product_cat');
                }
            }

            foreach ($terms as $term) {
                if (isset($known_events[$term->slug])) {
                    continue;
                }

                if (
                    $context['category'] === 'Muda Mudi Mengabdi'
                    && class_exists('LVK_Helper')
                    && method_exists('LVK_Helper', 'is_mmm_top_level')
                    && LVK_Helper::is_mmm_top_level($term)
                ) {
                    $context['chapter'] = $term->name;
                    break;
                }
            }
        }

        if (class_exists('LVK_Helper')) {
            $batch = (int) get_post_meta($product_id, LVK_Helper::META_BATCH, true);

            if ($batch > 0) {
                $context['batch'] = (string) $batch;
            }
        }

        return $context;
    }

    private static function print_url_cell(string $url): void {
        echo '<td>';

        if ($url) {
            echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">Buka</a>';
        } else {
            echo '-';
        }

        echo '</td>';
    }

    private static function sort_link(string $label, string $key): string {
        $current_orderby = sanitize_key($_GET['orderby'] ?? '');
        $current_order   = strtolower(sanitize_key($_GET['order'] ?? 'asc'));

        $next_order = ($current_orderby === $key && $current_order === 'asc')
            ? 'desc'
            : 'asc';

        $url = add_query_arg([
            'orderby' => $key,
            'order'   => $next_order,
        ]);

        $arrow = '';

        if ($current_orderby === $key) {
            $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
        }

        return '<a class="lvc-sort" href="' . esc_url($url) . '">' . esc_html($label . $arrow) . '</a>';
    }

    private static function event_date(int $product_id): string {
        if (!class_exists('LVK_Helper')) {
            return '-';
        }

        $tanggal = get_post_meta($product_id, LVK_Helper::META_TANGGAL, true);

        if (!$tanggal) {
            return '-';
        }

        try {
            $dt = new DateTimeImmutable($tanggal, wp_timezone());
            return wp_date('d M Y', $dt->getTimestamp());
        } catch (Exception $e) {
            return '-';
        }
    }

    public static function export_csv(): void {
        if (
            !current_user_can('manage_woocommerce')
            || !check_admin_referer('lvc_export_csv')
        ) {
            wp_die('Unauthorized');
        }

        $product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;

        if ($product_id && !self::is_registration_closed($product_id)) {
            wp_die('Rekap belum tersedia karena pendaftaran event ini masih dibuka.');
        }

        $filename = 'rekap-pendaftaran-' . date('Y-m-d-His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Order ID',
            'Nama',
            'Email',
            'WhatsApp',
            'Umur',
            'Sekolah',
            'Instagram',
            'Sumber Informasi',
            'Event',
            'Tanggal',
            'Status',
            'Bukti Follow Lestravida',
            'Bukti Follow Lestravida Daerah',
            'Bukti Share Kegiatan',
        ]);

        foreach (self::get_orders(['limit' => -1]) as $order) {
            foreach (self::order_product_ids($order) as $order_product_id) {
                if ($product_id && $order_product_id !== $product_id) {
                    continue;
                }

                if (!self::is_registration_closed($order_product_id)) {
                    continue;
                }

                $drive = self::drive_files_for_product($order, $order_product_id);
                $ctx   = self::product_context($order_product_id);

                fputcsv($output, [
                    $order->get_id(),
                    $order->get_formatted_billing_full_name(),
                    $order->get_billing_email(),
                    $order->get_billing_phone(),
                    $order->get_meta('_billing_age'),
                    $order->get_meta('_billing_school'),
                    $order->get_meta('_billing_instagram'),
                    $order->get_meta('_billing_source_info'),
                    $ctx['title'],
                    wc_format_datetime($order->get_date_created()),
                    wc_get_order_status_name($order->get_status()),
                    $drive['bukti_lv'],
                    $drive['bukti_lvdaerah'],
                    $drive['bukti_share'],
                ]);
            }
        }

        fclose($output);
        exit;
    }

    public static function enqueue_admin_assets($hook): void {
        if ($hook !== 'woocommerce_page_lvc-registrations') {
            return;
        }

        $css_path = LVC_DIR . '/assets/css/admin.css';

        $css_version = file_exists($css_path)
            ? filemtime($css_path)
            : null;

        wp_enqueue_style(
            'lvc-admin',
            LVC_URL . 'assets/css/admin.css',
            [],
            $css_version
        );
    }

    public static function hooks(): void {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_post_lvc_export_csv', [__CLASS__, 'export_csv']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    }
}

LVC_Registrations_Export::hooks();