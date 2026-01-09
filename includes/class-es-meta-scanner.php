<?php
/**
 * Meta Field Scanner
 * Scans post types for native meta fields (non-ACF)
 *
 * @package Ensemble
 * @since 1.7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Meta_Scanner {
    
    /**
     * Scan a post type for all meta fields
     * 
     * @param string $post_type Post type to scan
     * @param int $limit Number of posts to scan (default 50)
     * @return array Array of meta fields with info
     */
    public static function scan_post_type($post_type, $limit = 50) {
        if (!post_type_exists($post_type)) {
            return array();
        }
        
        $meta_fields = array();
        
        // Get sample posts
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => $limit,
            'post_status' => 'any',
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
        
        if (empty($posts)) {
            return array();
        }
        
        // Collect all meta keys from these posts
        $all_meta_keys = array();
        
        foreach ($posts as $post) {
            $post_meta = get_post_meta($post->ID);
            
            foreach ($post_meta as $key => $values) {
                // Skip WordPress internal fields
                if (self::is_internal_field($key)) {
                    continue;
                }
                
                // Skip ACF fields (they start with _ or are ACF specific)
                if (self::is_acf_field($key)) {
                    continue;
                }
                
                // Track this meta key
                if (!isset($all_meta_keys[$key])) {
                    $all_meta_keys[$key] = array(
                        'count' => 0,
                        'sample_values' => array(),
                        'types' => array(),
                    );
                }
                
                $all_meta_keys[$key]['count']++;
                
                // Store sample values (max 3)
                $value = maybe_unserialize($values[0]);
                if (count($all_meta_keys[$key]['sample_values']) < 3) {
                    $all_meta_keys[$key]['sample_values'][] = $value;
                }
                
                // Detect value type
                $type = self::detect_value_type($value);
                if (!in_array($type, $all_meta_keys[$key]['types'])) {
                    $all_meta_keys[$key]['types'][] = $type;
                }
            }
        }
        
        // Build result array
        foreach ($all_meta_keys as $key => $data) {
            $meta_fields[] = array(
                'key' => $key,
                'name' => $key,
                'label' => self::humanize_field_name($key),
                'type' => self::guess_field_type($key, $data),
                'source' => 'meta',
                'usage_count' => $data['count'],
                'sample_values' => $data['sample_values'],
            );
        }
        
        // Sort by usage count (most used first)
        usort($meta_fields, function($a, $b) {
            return $b['usage_count'] - $a['usage_count'];
        });
        
        return $meta_fields;
    }
    
    /**
     * Check if field is WordPress internal
     */
    private static function is_internal_field($key) {
        // WordPress core meta fields to skip
        $internal_keys = array(
            '_edit_lock',
            '_edit_last',
            '_wp_page_template',
            '_wp_attached_file',
            '_wp_attachment_metadata',
            '_thumbnail_id',
            '_pingme',
            '_encloseme',
        );
        
        // Skip if starts with _wp_ or is in internal list
        if (strpos($key, '_wp_') === 0 || in_array($key, $internal_keys)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if field is ACF-related
     */
    private static function is_acf_field($key) {
        // ACF stores actual values without underscore
        // and field references with underscore
        
        // If starts with underscore, check if it's an ACF reference
        if (strpos($key, '_') === 0) {
            // ACF field references start with _ and contain field_
            if (strpos($key, 'field_') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect value type from sample value
     */
    private static function detect_value_type($value) {
        if (is_numeric($value)) {
            return 'number';
        }
        
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_array($value)) {
            return 'array';
        }
        
        // Check if it looks like a date
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return 'date';
        }
        
        // Check if it looks like a URL
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return 'url';
        }
        
        return 'text';
    }
    
    /**
     * Guess field type based on key name and data
     */
    private static function guess_field_type($key, $data) {
        $key_lower = strtolower($key);
        $types = $data['types'];
        
        // Date fields
        if (strpos($key_lower, 'date') !== false || 
            strpos($key_lower, 'datum') !== false ||
            in_array('date', $types)) {
            return 'date';
        }
        
        // Time fields
        if (strpos($key_lower, 'time') !== false || 
            strpos($key_lower, 'uhrzeit') !== false) {
            return 'time';
        }
        
        // Number fields
        if (strpos($key_lower, 'price') !== false || 
            strpos($key_lower, 'preis') !== false ||
            strpos($key_lower, 'count') !== false ||
            strpos($key_lower, 'anzahl') !== false ||
            in_array('number', $types)) {
            return 'number';
        }
        
        // URL fields
        if (strpos($key_lower, 'url') !== false || 
            strpos($key_lower, 'link') !== false ||
            in_array('url', $types)) {
            return 'url';
        }
        
        // Boolean fields
        if (in_array('boolean', $types)) {
            return 'boolean';
        }
        
        // Default to text
        return 'text';
    }
    
    /**
     * Convert field name to human-readable label
     */
    private static function humanize_field_name($name) {
        // Remove underscores and hyphens
        $label = str_replace(array('_', '-'), ' ', $name);
        
        // Capitalize words
        $label = ucwords($label);
        
        return $label;
    }
    
    /**
     * Get combined fields: ACF + Native Meta
     * 
     * @param string $post_type Post type to scan
     * @return array Combined array of all available fields
     */
    public static function get_all_available_fields($post_type) {
        $all_fields = array();
        
        // 1. Get ACF Fields (exclude Ensemble core groups)
        if (function_exists('acf_get_field_groups')) {
            $all_groups = acf_get_field_groups();
            foreach ($all_groups as $group) {
                // Skip Ensemble core field groups
                if (in_array($group['key'], array('group_ensemble_event', 'group_ensemble_artist', 'group_ensemble_location'))) {
                    continue;
                }
                
                $fields = acf_get_fields($group['key']);
                if ($fields) {
                    foreach ($fields as $field) {
                        // Skip structural fields
                        $structural_types = array('tab', 'message', 'accordion', 'group', 'repeater', 'clone');
                        if (!in_array($field['type'], $structural_types)) {
                            $all_fields[] = array(
                                'key' => $field['key'],
                                'name' => $field['name'],
                                'label' => $field['label'],
                                'type' => $field['type'],
                                'source' => 'acf',
                                'group' => $group['title'],
                            );
                        }
                    }
                }
            }
        }
        
        // 2. Get Native Meta Fields
        $meta_fields = self::scan_post_type($post_type);
        $all_fields = array_merge($all_fields, $meta_fields);
        
        return $all_fields;
    }
}
