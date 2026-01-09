<?php
/**
 * Import AJAX Handler
 * Handles AJAX requests for import preview and execution
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_Import_Ajax {

    private $import_handler;

    public function __construct() {
        require_once 'class-import-handler.php';
        $this->import_handler = new Ensemble_Import_Handler();

        // Register AJAX actions
        add_action( 'wp_ajax_ensemble_import_preview', array( $this, 'handle_preview' ) );
        add_action( 'wp_ajax_ensemble_import_execute', array( $this, 'handle_import' ) );
    }

    /**
     * Handle preview AJAX request
     */
    public function handle_preview() {
        // Verify nonce
        if ( ! check_ajax_referer( 'ensemble_import_nonce', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => 'Invalid security token',
            ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array(
                'message' => 'Insufficient permissions',
            ) );
        }

        // Get source type
        $source_type = isset( $_POST['source_type'] ) ? sanitize_text_field( $_POST['source_type'] ) : '';

        if ( ! in_array( $source_type, array( 'url', 'file' ), true ) ) {
            wp_send_json_error( array(
                'message' => 'Invalid source type',
            ) );
        }

        // Get source
        $source = null;

        if ( $source_type === 'url' ) {
            $source = isset( $_POST['source'] ) ? esc_url_raw( $_POST['source'] ) : '';
            
            if ( empty( $source ) ) {
                wp_send_json_error( array(
                    'message' => 'URL is required',
                ) );
            }
        } elseif ( $source_type === 'file' ) {
            if ( ! isset( $_FILES['source'] ) || $_FILES['source']['error'] !== UPLOAD_ERR_OK ) {
                wp_send_json_error( array(
                    'message' => 'File upload failed',
                ) );
            }

            $source = $_FILES['source'];
        }

        // Execute preview
        $result = $this->import_handler->preview( $source_type, $source );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * Handle import execution AJAX request
     */
    public function handle_import() {
        // Verify nonce
        if ( ! check_ajax_referer( 'ensemble_import_nonce', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => 'Invalid security token',
            ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array(
                'message' => 'Insufficient permissions',
            ) );
        }

        // Get source type
        $source_type = isset( $_POST['source_type'] ) ? sanitize_text_field( $_POST['source_type'] ) : '';

        if ( ! in_array( $source_type, array( 'url', 'file' ), true ) ) {
            wp_send_json_error( array(
                'message' => 'Invalid source type',
            ) );
        }

        // Get source
        $source = null;

        if ( $source_type === 'url' ) {
            $source = isset( $_POST['source'] ) ? esc_url_raw( $_POST['source'] ) : '';
            
            if ( empty( $source ) ) {
                wp_send_json_error( array(
                    'message' => 'URL is required',
                ) );
            }
        } elseif ( $source_type === 'file' ) {
            if ( ! isset( $_FILES['source'] ) || $_FILES['source']['error'] !== UPLOAD_ERR_OK ) {
                wp_send_json_error( array(
                    'message' => 'File upload failed',
                ) );
            }

            $source = $_FILES['source'];
        }
        
        // Get import options
        $options = array();
        
        // Update mode: 'skip', 'update', or 'duplicate'
        if ( isset( $_POST['update_mode'] ) ) {
            $update_mode = sanitize_text_field( $_POST['update_mode'] );
            if ( in_array( $update_mode, array( 'skip', 'update', 'duplicate' ), true ) ) {
                $options['update_mode'] = $update_mode;
            }
        }
        
        // Selected UIDs (for selective import)
        if ( isset( $_POST['selected_uids'] ) && is_array( $_POST['selected_uids'] ) ) {
            $options['selected_uids'] = array_map( 'sanitize_text_field', $_POST['selected_uids'] );
        }

        // Execute import
        $result = $this->import_handler->import( $source_type, $source, $options );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ) );
        }

        wp_send_json_success( $result );
    }
}

// Initialize
new Ensemble_Import_Ajax();
