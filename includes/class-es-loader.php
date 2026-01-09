<?php
/**
 * Hook Loader
 * 
 * Maintains list of all hooks registered throughout the plugin,
 * and registers them with WordPress API.
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Loader {
    
    /**
     * Array of actions registered
     * @var array
     */
    protected $actions;
    
    /**
     * Array of filters registered
     * @var array
     */
    protected $filters;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }
    
    /**
     * Add a new action to the collection
     *
     * @param string $hook The name of the WordPress action
     * @param object $component Reference to the instance of the object
     * @param string $callback The name of the function definition on the $component
     * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
     * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add a new filter to the collection
     *
     * @param string $hook The name of the WordPress filter
     * @param object $component Reference to the instance of the object
     * @param string $callback The name of the function definition on the $component
     * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
     * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Utility function for registering hooks
     *
     * @param array $hooks Current hooks array
     * @param string $hook The name of the WordPress hook
     * @param object $component Reference to the instance of the object
     * @param string $callback The name of the function definition on the $component
     * @param int $priority The priority at which the function should be fired
     * @param int $accepted_args The number of arguments that should be passed to the $callback
     * @return array The updated hooks array
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        return $hooks;
    }
    
    /**
     * Register all filters and actions with WordPress
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
