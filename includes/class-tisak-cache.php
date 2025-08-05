<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Tisak_Cache {
   
    const CACHE_GROUP = 'tisak_lokator';
   
    /**
     * Get cached data
     */
    public static function get( $key, $default = null ) {
        $cache_key = self::get_cache_key( $key );
        $cached_value = get_transient( $cache_key );
       
        if ( false === $cached_value ) {
            return $default;
        }
       
        return $cached_value;
    }
   
    /**
     * Set cached data
     */
    public static function set( $key, $value, $expiration = null ) {
        if ( null === $expiration ) {
            // Default cache for 2 hours
            $expiration = 2 * HOUR_IN_SECONDS;
        }
       
        $cache_key = self::get_cache_key( $key );
        return set_transient( $cache_key, $value, $expiration );
    }
   
    /**
     * Delete cached data
     */
    public static function delete( $key ) {
        $cache_key = self::get_cache_key( $key );
        return delete_transient( $cache_key );
    }
   
    /**
     * Get cache key with prefix
     */
    private static function get_cache_key( $key ) {
        return self::CACHE_GROUP . '_' . md5( $key );
    }
   
    /**
     * Cache stores by city
     */
    public static function get_stores_by_city( $city ) {
        return self::get( 'stores_city_' . strtolower( $city ) );
    }
   
    /**
     * Set stores by city cache
     */
    public static function set_stores_by_city( $city, $stores ) {
        return self::set( 'stores_city_' . strtolower( $city ), $stores );
    }
   
    /**
     * Cache store details
     */
    public static function get_store_details( $store_code ) {
        return self::get( 'store_details_' . $store_code );
    }
   
    /**
     * Set store details cache
     */
    public static function set_store_details( $store_code, $details ) {
        // Cache store details for 4 hours
        return self::set( 'store_details_' . $store_code, $details, 4 * HOUR_IN_SECONDS );
    }
   
    /**
     * Clear all plugin cache
     */
    public static function clear_all() {
        global $wpdb;
       
        // Delete all transients with our prefix
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 OR option_name LIKE %s",
                '_transient_' . self::CACHE_GROUP . '_%',
                '_transient_timeout_' . self::CACHE_GROUP . '_%'
            )
        );
       
        return true;
    }
   
    /**
     * Get cache info for admin
     */
    public static function get_cache_info() {
        global $wpdb;
       
        $cache_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 AND option_name NOT LIKE %s",
                '_transient_' . self::CACHE_GROUP . '_%',
                '_transient_timeout_%'
            )
        );
       
        return array(
            'cached_items' => intval( $cache_count ),
            'cache_group' => self::CACHE_GROUP
        );
    }
}