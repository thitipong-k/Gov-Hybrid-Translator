<?php
namespace GovHybridTranslator\Modules;

use GovHybridTranslator\Core\Loader;

/**
 * Activity Logger Class
 * 
 * คลาสสำหรับจัดการ Activity Logs (Audit Trail)
 * หน้าที่หลัก:
 * 1. บันทึกประวัติการทำงานของ User (Log Activity)
 * 2. ดึงข้อมูล Logs มาแสดงผลพร้อมตัวกรอง
 * 3. สร้างและจัดการตารางฐานข้อมูล activity_logs
 * 4. ลบ Log เก่าอัตโนมัติ (Cleanup)
 */
class ActivityLogger {

    private $table_name;
    private $db_version = '1.0.0'; // Version for the log table schema

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ght_activity_logs';
    }

    public function register() {
        // Register hooks if needed, e.g., auto-cleanup cron
        // add_action('gov_hybrid_translator_daily_event', [$this, 'cleanup_logs']);
    }

    /**
     * สร้างตารางฐานข้อมูลเมื่อติดตั้งหรืออัปเดตปลั๊กอิน
     * ใช้ dbDelta เพื่อตรวจสอบและแก้ไขโครงสร้างตาราง
     */
    public static function install_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ght_activity_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            action varchar(50) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id varchar(255) DEFAULT '',
            details text DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( 'gov_hybrid_translator_db_version', GOV_HYBRID_TRANSLATOR_VERSION );
    }

    /**
     * Log an activity
     * บันทึก Log ลงฐานข้อมูล
     * 
     * @param string $action      ชื่อ Action (เช่น 'translation_saved', 'glossary_deleted')
     * @param string $object_type ประเภทของ Object (เช่น 'post', 'term', 'setting')
     * @param mixed  $object_id   ID ของ Object (Optional)
     * @param array  $details     รายละเอียดเพิ่มเติม (เก็บเป็น JSON)
     * @return int|false          ID ของ Log ที่ถูกบันทึก หรือ false หากล้มเหลว
     */
    public function log( $action, $object_type, $object_id = '', $details = [] ) {
        global $wpdb;
        $user_id = get_current_user_id();
        
        // Handle IP address safely
        $ip_address = '';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip_address = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip_address = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip_address = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
        }

        return $wpdb->insert(
            $this->table_name,
            array(
                'user_id'     => $user_id,
                'action'      => $action,
                'object_type' => $object_type,
                'object_id'   => $object_id,
                'details'     => json_encode( $details, JSON_UNESCAPED_UNICODE ),
                'ip_address'  => $ip_address,
                'created_at'  => current_time( 'mysql' ),
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
    }

    /**
     * Get logs with filtering
     * ดึงข้อมูล Logs พร้อม Pagination และ Filter
     * 
     * @param array $args Filter arguments
     * @return array Logs and total count ['items' => array, 'total' => int, 'pages' => int]
     */
    public function get_logs( $args = [] ) {
        global $wpdb;

        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'user_id' => '',
            'action' => '',
            'date_from' => '',
            'date_to' => '',
        ];

        $args = wp_parse_args( $args, $defaults );
        
        $where = "WHERE 1=1";
        $query_args = [];

        if ( ! empty( $args['user_id'] ) ) {
            $where .= " AND user_id = %d";
            $query_args[] = $args['user_id'];
        }

        if ( ! empty( $args['action'] ) ) {
            $where .= " AND action = %s";
            $query_args[] = $args['action'];
        }

        if ( ! empty( $args['date_from'] ) ) {
            $where .= " AND created_at >= %s";
            $query_args[] = $args['date_from'] . ' 00:00:00';
        }

        if ( ! empty( $args['date_to'] ) ) {
            $where .= " AND created_at <= %s";
            $query_args[] = $args['date_to'] . ' 23:59:59';
        }

        // Safe order by
        $allowed_orderby = ['id', 'user_id', 'created_at', 'action'];
        $orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'created_at';
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $limit = absint( $args['limit'] );
        $offset = absint( $args['offset'] );
        
        $sql = "SELECT * FROM {$this->table_name} $where ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $query_args[] = $limit;
        $query_args[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $sql, $query_args ) );
        
        // Get total count for pagination
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} $where";
        // Remove limit/offset args for count query
        array_pop($query_args); 
        array_pop($query_args);
        
        $total = $wpdb->get_var( $wpdb->prepare( $count_sql, $query_args ) );

        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil( $total / $limit )
        ];
    }

    /**
     * ลบ Log เก่าที่เกินระยะเวลาที่กำหนด (Retention Period)
     * @param int $days จำนวนวันที่ต้องการเก็บไว้ (Default: 90 วัน)
     */
    public function cleanup_logs( $days = 90 ) {
        global $wpdb;
        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-$days days" ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE created_at < %s", $date_limit ) );
    }
}
