<?php
/**
 * Fuzzy Location Matcher
 * Matches location strings to existing Ensemble location posts
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_Location_Matcher {

    /**
     * Match confidence threshold (0-100)
     */
    private $confidence_threshold = 60;

    /**
     * Find best matching location for a location string
     *
     * @param string $location_string Location name from iCal
     * @return array Match result with 'location_id', 'confidence', 'matched_title'
     */
    public function find_match( $location_string ) {
        if ( empty( $location_string ) ) {
            return array(
                'location_id' => null,
                'confidence' => 0,
                'matched_title' => '',
                'status' => 'empty',
            );
        }

        // Get all locations
        $locations = get_posts( array(
            'post_type' => 'ensemble_location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ) );

        if ( empty( $locations ) ) {
            return array(
                'location_id' => null,
                'confidence' => 0,
                'matched_title' => '',
                'status' => 'no_locations',
            );
        }

        $best_match = null;
        $best_score = 0;

        foreach ( $locations as $location ) {
            $score = $this->calculate_similarity( $location_string, $location->post_title );
            
            if ( $score > $best_score ) {
                $best_score = $score;
                $best_match = $location;
            }
        }

        // Determine status based on confidence
        $status = 'no_match';
        $location_id = null;

        if ( $best_score >= $this->confidence_threshold ) {
            $status = 'matched';
            $location_id = $best_match->ID;
        } elseif ( $best_score > 0 ) {
            $status = 'low_confidence';
        }

        return array(
            'location_id' => $location_id,
            'confidence' => round( $best_score ),
            'matched_title' => $best_match ? $best_match->post_title : '',
            'status' => $status,
            'original' => $location_string,
        );
    }

    /**
     * Calculate similarity score between two strings
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score (0-100)
     */
    private function calculate_similarity( $str1, $str2 ) {
        // Normalize strings
        $str1 = $this->normalize_string( $str1 );
        $str2 = $this->normalize_string( $str2 );

        // Exact match
        if ( $str1 === $str2 ) {
            return 100;
        }

        // Levenshtein distance (with length limit for performance)
        $max_length = 255;
        $str1_trunc = substr( $str1, 0, $max_length );
        $str2_trunc = substr( $str2, 0, $max_length );

        $lev_distance = levenshtein( $str1_trunc, $str2_trunc );
        $max_len = max( strlen( $str1_trunc ), strlen( $str2_trunc ) );
        
        if ( $max_len === 0 ) {
            return 0;
        }

        // Convert distance to similarity percentage
        $lev_similarity = ( 1 - ( $lev_distance / $max_len ) ) * 100;

        // Similar text percentage
        similar_text( $str1, $str2, $similar_percent );

        // Weighted average (favor similar_text slightly)
        $score = ( $lev_similarity * 0.4 ) + ( $similar_percent * 0.6 );

        // Bonus for substring match
        if ( strpos( $str1, $str2 ) !== false || strpos( $str2, $str1 ) !== false ) {
            $score = min( 100, $score + 10 );
        }

        // Bonus for word overlap
        $words1 = explode( ' ', $str1 );
        $words2 = explode( ' ', $str2 );
        $common_words = array_intersect( $words1, $words2 );
        
        if ( ! empty( $common_words ) ) {
            $word_overlap = ( count( $common_words ) / max( count( $words1 ), count( $words2 ) ) ) * 15;
            $score = min( 100, $score + $word_overlap );
        }

        return $score;
    }

    /**
     * Normalize string for comparison
     *
     * @param string $str Input string
     * @return string Normalized string
     */
    private function normalize_string( $str ) {
        // Convert to lowercase
        $str = strtolower( $str );
        
        // Remove special characters but keep spaces
        $str = preg_replace( '/[^a-z0-9\s]/', '', $str );
        
        // Collapse multiple spaces
        $str = preg_replace( '/\s+/', ' ', $str );
        
        // Trim
        $str = trim( $str );

        return $str;
    }

    /**
     * Batch match multiple locations
     *
     * @param array $location_strings Array of location strings
     * @return array Array of match results (indexed by original array index)
     */
    public function batch_match( $location_strings ) {
        $matches = array();

        foreach ( $location_strings as $index => $location_string ) {
            $matches[ $index ] = $this->find_match( $location_string );
        }

        return $matches;
    }

    /**
     * Set confidence threshold
     *
     * @param int $threshold Threshold value (0-100)
     */
    public function set_confidence_threshold( $threshold ) {
        $this->confidence_threshold = max( 0, min( 100, intval( $threshold ) ) );
    }

    /**
     * Get current confidence threshold
     *
     * @return int Threshold value
     */
    public function get_confidence_threshold() {
        return $this->confidence_threshold;
    }
}
