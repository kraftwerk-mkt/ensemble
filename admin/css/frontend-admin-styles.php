<?php
/**
 * Frontend Admin Styles
 * 
 * Common styles for all frontend admin tabs
 * 
 * @package Ensemble
 * @since 2.9.3
 */

if (!defined('ABSPATH')) exit;
?>

<style>
/**
 * Ensemble Frontend - Dark Theme
 * 
 * @package Ensemble
 */

/* ========================================
   MAIN WRAP
======================================== */

.es-frontend-wrap {
    background: var(--es-background, #1e1e1e);
    min-height: 100vh;
    margin-left: -20px;
    margin-right: -20px;
    padding: 20px;
}

.es-frontend-wrap h1 {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--es-primary);
    margin: 0 0 25px 0;
    background: linear-gradient(135deg, var(--es-primary), var(--es-primary-hover));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.es-frontend-wrap h1 .dashicons {
    font-size: 2.5rem;
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, var(--es-primary), var(--es-primary-hover));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ========================================
   CONTAINER
======================================== */

.es-frontend-container {
    background: var(--es-surface, #2c2c2c);
    border: 1px solid var(--es-border, #404040);
    border-radius: 8px;
    overflow: hidden;
}

/* ========================================
   TAB NAVIGATION
======================================== */

.es-frontend-tabs {
    display: flex;
    gap: 0;
    background: var(--es-background, #1e1e1e);
    border-bottom: 1px solid var(--es-border, #404040);
    padding: 15px 20px;
}

.es-tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: transparent;
    border: 1px solid var(--es-border, #404040);
    color: var(--es-text-secondary, #a0a0a0);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 500;
    margin-right: 8px;
    border-radius: 6px;
    position: relative;
}

.es-tab-btn:hover:not(.es-tab-disabled) {
    background: var(--es-surface, #2c2c2c);
    color: var(--es-text, #e0e0e0);
    border-color: var(--es-primary, #3582c4);
}

.es-tab-btn.active {
    background: var(--es-primary, #3582c4);
    border-color: var(--es-primary, #3582c4);
    color: white;
}

.es-tab-btn.es-tab-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.es-tab-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.es-badge-soon {
    display: inline-block;
    padding: 2px 8px;
    background: var(--es-warning, #f0b849);
    color: var(--es-background, #1e1e1e);
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    margin-left: 4px;
}

/* ========================================
   SHORTCODES SECTION
======================================== */

.es-shortcodes-section {
    padding: 30px;
}

.es-section-intro {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--es-border, #404040);
}

.es-section-intro h2 {
    font-size: 24px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    margin: 0 0 8px 0;
}

.es-section-intro .es-description {
    font-size: 14px;
    color: var(--es-text-secondary, #a0a0a0);
    margin: 0;
    line-height: 1.5;
}

/* ========================================
   SHORTCODE GRID
======================================== */

.es-shortcode-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

/* ========================================
   SHORTCODE CARD
======================================== */

.es-shortcode-card {
    background: var(--es-surface-secondary, #383838);
    border: 1px solid var(--es-border, #404040);
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.es-shortcode-card:hover {
    border-color: var(--es-primary, #3582c4);
    box-shadow: 0 4px 12px rgba(53, 130, 196, 0.15);
}

/* Section Divider for Add-on Shortcodes */
.es-section-divider {
    text-align: center;
    padding: 20px 0;
}

.es-section-divider .es-divider-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    border-radius: 12px;
    margin-bottom: 15px;
}

.es-section-divider .es-divider-icon svg {
    width: 24px;
    height: 24px;
    stroke: white;
}

.es-section-divider h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    margin: 0 0 8px 0;
}

.es-section-divider p {
    font-size: 14px;
    color: var(--es-text-secondary, #a0a0a0);
    margin: 0;
}

/* Card Header */
.es-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: var(--es-background, #1e1e1e);
    border-bottom: 1px solid var(--es-border, #404040);
}

.es-card-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--es-primary, #3582c4);
    border-radius: 8px;
    color: white;
}

/* Add-on Card Icon - Purple Gradient */
.es-card-icon.es-card-icon-addon {
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
}

.es-card-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.es-card-icon svg {
    width: 22px;
    height: 22px;
    stroke: white;
}

.es-card-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    margin: 0;
}

/* Card Body */
.es-card-body {
    padding: 20px;
}

.es-card-description {
    font-size: 14px;
    color: var(--es-text-secondary, #a0a0a0);
    margin: 0 0 20px 0;
    line-height: 1.5;
}

/* Shortcode Box */
.es-shortcode-box {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--es-background, #1e1e1e);
    border: 1px solid var(--es-border, #404040);
    border-radius: 6px;
    padding: 12px 15px;
    margin-bottom: 20px;
}

.es-shortcode-box code {
    flex: 1;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    color: var(--es-primary, #3582c4);
    background: transparent;
    padding: 0;
}

.es-copy-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    background: var(--es-surface, #2c2c2c);
    border: 1px solid var(--es-border, #404040);
    border-radius: 4px;
    color: var(--es-text-secondary, #a0a0a0);
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-copy-btn:hover {
    background: var(--es-primary, #3582c4);
    border-color: var(--es-primary, #3582c4);
    color: white;
}

.es-copy-btn.es-copied {
    background: var(--es-success, #4caf50);
    border-color: var(--es-success, #4caf50);
    color: white;
}

.es-copy-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Parameters */
.es-parameters {
    margin-bottom: 20px;
}

.es-parameters h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    margin: 0 0 12px 0;
}

.es-parameters ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.es-parameters li {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid var(--es-border, #404040);
    font-size: 13px;
}

.es-parameters li:last-child {
    border-bottom: none;
}

.es-parameters code {
    font-family: 'Courier New', monospace;
    background: var(--es-background, #1e1e1e);
    padding: 2px 6px;
    border-radius: 3px;
    color: var(--es-primary, #3582c4);
    font-weight: 600;
}

.es-param-type {
    display: inline-block;
    padding: 2px 8px;
    background: var(--es-background, #1e1e1e);
    color: var(--es-warning, #f0b849);
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.es-param-desc {
    color: var(--es-text-secondary, #a0a0a0);
    flex: 1;
    min-width: 200px;
}

.es-param-default {
    color: var(--es-text-secondary, #a0a0a0);
    font-style: italic;
    font-size: 12px;
}

/* Examples */
.es-examples h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    margin: 0 0 12px 0;
}

.es-example-code {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--es-background, #1e1e1e);
    border: 1px solid var(--es-border, #404040);
    border-radius: 4px;
    padding: 8px 12px;
    margin-bottom: 8px;
}

.es-example-code code {
    flex: 1;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: var(--es-text-secondary, #a0a0a0);
    background: transparent;
}

.es-copy-btn-small {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4px 8px;
    background: transparent;
    border: none;
    color: var(--es-text-secondary, #a0a0a0);
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-copy-btn-small:hover {
    color: var(--es-primary, #3582c4);
}

.es-copy-btn-small .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Card Footer */
.es-card-footer {
    padding: 15px 20px;
    background: var(--es-background, #1e1e1e);
    border-top: 1px solid var(--es-border, #404040);
    display: flex;
    justify-content: flex-end;
}

.es-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.es-status-badge .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.es-status-active {
    background: rgba(76, 175, 80, 0.15);
    color: var(--es-success, #4caf50);
    border: 1px solid var(--es-success, #4caf50);
}

.es-status-coming {
    background: rgba(240, 184, 73, 0.15);
    color: var(--es-warning, #f0b849);
    border: 1px solid var(--es-warning, #f0b849);
}

/* ========================================
   INFO BOX
======================================== */

.es-frontend-info-box {
    display: flex;
    gap: 15px;
    background: rgba(53, 130, 196, 0.1);
    border: 1px solid var(--es-primary, #3582c4);
    border-radius: 6px;
    padding: 20px;
}

.es-frontend-info-box .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: var(--es-primary, #3582c4);
    flex-shrink: 0;
}

.es-frontend-info-box strong {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    margin-bottom: 5px;
}

.es-frontend-info-box p {
    font-size: 13px;
    color: var(--es-text-secondary, #a0a0a0);
    margin: 0;
    line-height: 1.5;
}

/* ========================================
   COMING SOON
======================================== */

.es-coming-soon {
    text-align: center;
    padding: 100px 20px;
}

.es-coming-soon .dashicons {
    font-size: 80px;
    width: 80px;
    height: 80px;
    color: var(--es-primary, #3582c4);
    margin-bottom: 20px;
    opacity: 0.5;
}

.es-coming-soon h2 {
    font-size: 28px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    margin: 0 0 15px 0;
}

.es-coming-soon p {
    font-size: 16px;
    color: var(--es-text-secondary, #a0a0a0);
    margin: 0;
}

/* ========================================
   RESPONSIVE
======================================== */

@media screen and (max-width: 1200px) {
    .es-shortcode-grid {
        grid-template-columns: 1fr;
    }
}

@media screen and (max-width: 782px) {
    .es-frontend-wrap {
        margin-left: -10px;
        margin-right: -10px;
        padding: 10px;
    }
    
    .es-frontend-tabs {
        flex-direction: column;
        padding: 10px;
    }
    
    .es-tab-btn {
        margin-right: 0;
        margin-bottom: 8px;
        justify-content: center;
    }
    
    .es-shortcodes-section {
        padding: 20px;
    }
    
    .es-parameters li {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* ========================================
   Designer Tab Styles
   ======================================== */

.es-designer-section {
    padding: 24px;
}

.es-template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.es-template-card {
    background: var(--es-card-bg, #2c2c2c);
    border: 2px solid var(--es-border, #404040);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.es-template-card:hover {
    border-color: var(--es-primary, #3582c4);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.es-template-card.active {
    border-color: var(--es-success, #46b450);
    background: var(--es-background, #1e1e1e);
}

.es-template-preview {
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: 100%;
}

.es-color-preview {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.es-color-swatch {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.es-template-card h4 {
    margin: 0;
    color: var(--es-text, #ffffff);
    font-size: 16px;
    font-weight: 600;
}

.es-template-description {
    color: var(--es-text-secondary, #a0a0a0);
    font-size: 13px;
    line-height: 1.5;
    margin: 0;
    flex: 1;
}

.es-template-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
}

.es-template-badge.es-active {
    background: var(--es-success, #46b450);
    color: #fff;
}

.es-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.es-settings-group {
    background: var(--es-background, #1e1e1e);
    border-radius: 8px;
    padding: 20px;
}

.es-settings-group h4 {
    margin: 0 0 16px 0;
    color: var(--es-text, #ffffff);
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.es-settings-group h4 .dashicons {
    color: var(--es-primary, #3582c4);
    font-size: 18px;
}

.es-color-list,
.es-setting-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.es-color-item,
.es-setting-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
}

.es-color-dot {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.es-color-label,
.es-setting-label {
    color: var(--es-text-secondary, #a0a0a0);
    min-width: 100px;
}

.es-setting-value {
    color: var(--es-text, #ffffff);
    font-weight: 500;
}

.es-color-item code,
.es-setting-item code {
    margin-left: auto;
    background: var(--es-card-bg, #2c2c2c);
    padding: 4px 8px;
    border-radius: 4px;
    color: var(--es-primary, #3582c4);
    font-size: 12px;
}

.es-usage-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.es-usage-list > li {
    background: var(--es-background, #1e1e1e);
    padding: 16px;
    border-radius: 8px;
    border-left: 3px solid var(--es-primary, #3582c4);
}

.es-usage-list strong {
    color: var(--es-primary, #3582c4);
    display: block;
    margin-bottom: 8px;
}

.es-code-example {
    background: var(--es-card-bg, #2c2c2c);
    padding: 12px;
    border-radius: 6px;
    margin-top: 12px;
    font-family: 'Courier New', monospace;
}

.es-code-example code {
    color: var(--es-success, #46b450);
    font-weight: 600;
}

.es-template-list {
    list-style: none;
    padding: 0;
    margin: 12px 0 0 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
}

.es-template-list li {
    background: var(--es-card-bg, #2c2c2c);
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
}

.es-template-list code {
    color: var(--es-primary, #3582c4);
    font-weight: 600;
}

.es-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.es-btn-primary {
    background: var(--es-primary, #3582c4);
    color: #fff;
}

.es-btn-primary:hover {
    background: var(--es-primary-hover, #2271b1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(53, 130, 196, 0.4);
}

@media (max-width: 768px) {
    .es-template-grid {
        grid-template-columns: 1fr;
    }
    
    .es-settings-grid {
        grid-template-columns: 1fr;
    }
}

/* ========================================
   DESIGNER EDITOR
   ======================================== */

.es-designer-form {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.es-designer-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 0;
    border-bottom: 2px solid var(--es-border, #3c3c3c);
    padding: 0 20px;
    background: var(--es-bg, #1e1e1e);
}

.es-designer-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--es-text-secondary, #a0a0a0);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: -2px;
}

.es-designer-tab:hover {
    color: var(--es-text, #e0e0e0);
    background: rgba(255, 255, 255, 0.05);
}

.es-designer-tab.active {
    color: var(--es-primary, #3582c4);
    border-bottom-color: var(--es-primary, #3582c4);
    background: rgba(53, 130, 196, 0.1);
}

.es-designer-tab .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.es-designer-content {
    display: none;
    padding: 30px 20px;
}

.es-designer-content.active {
    display: block;
}

/* Light/Dark Mode Toggle Bar */
.es-mode-toggle-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #1e1e2e 0%, #252536 100%);
    border-radius: 12px;
    margin-bottom: 24px;
    border: 1px solid #3f3f5a;
}

.es-mode-toggle-label {
    font-size: 13px;
    font-weight: 600;
    color: #a1a1b5;
    white-space: nowrap;
}

.es-mode-toggle-buttons {
    display: flex;
    background: #18181b;
    border-radius: 8px;
    padding: 4px;
    gap: 4px;
}

.es-mode-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #71717a;
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-mode-btn:hover {
    color: #a1a1aa;
    background: rgba(255,255,255,0.05);
}

.es-mode-btn.active {
    background: #3f3f46;
    color: #fafafa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.es-mode-btn.active[data-mode="light"] {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    color: #ffffff;
}

.es-mode-btn.active[data-mode="dark"] {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #ffffff;
}

.es-mode-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.es-mode-hint {
    margin-left: auto;
    font-size: 12px;
    color: #52525b;
    font-style: italic;
}

.es-mode-colors {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.es-designer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.es-designer-section-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 25px 0 15px 0;
    padding: 12px 0;
    border-top: 1px solid var(--es-border, #3c3c3c);
    font-size: 13px;
    font-weight: 600;
    color: var(--es-text-secondary, #a0a0a0);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.es-designer-section-divider .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: var(--es-primary, #3582c4);
}

.es-field-hint {
    font-size: 11px;
    color: var(--es-text-muted, #888);
    font-weight: 400;
}

.es-designer-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.es-designer-field label {
    font-size: 13px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-color-input {
    width: 60px;
    height: 40px;
    padding: 4px;
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 8px;
    background: var(--es-input-bg, #2c2c2c);
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-color-input:hover {
    border-color: var(--es-primary, #3582c4);
}

.es-color-text {
    padding: 8px 12px;
    background: var(--es-input-bg, #2c2c2c);
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 8px;
    color: var(--es-text, #e0e0e0);
    font-size: 13px;
    font-family: 'Courier New', monospace;
}

.es-number-input,
.es-select-input {
    padding: 8px 12px;
    background: var(--es-input-bg, #2c2c2c);
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 8px;
    color: var(--es-text, #e0e0e0);
    font-size: 14px;
    transition: all 0.2s ease;
}

.es-number-input:focus,
.es-select-input:focus {
    outline: none;
    border-color: var(--es-primary, #3582c4);
    box-shadow: 0 0 0 3px rgba(53, 130, 196, 0.1);
}

.es-designer-actions {
    margin-top: 30px;
    padding: 20px;
    background: rgba(53, 130, 196, 0.1);
    border: 2px solid var(--es-primary, #3582c4);
    border-radius: 12px;
    text-align: center;
}

.es-designer-actions button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
}

.es-designer-actions .description {
    margin-top: 10px;
    font-size: 13px;
    color: var(--es-text-secondary, #a0a0a0);
}

.es-info-simple p {
    margin: 0 0 15px 0;
    line-height: 1.6;
}

.es-info-simple p:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .es-designer-tabs {
        overflow-x: auto;
        padding: 0 10px;
    }
    
    .es-designer-grid {
        grid-template-columns: 1fr;
    }
}

/* Font Select Hint */
.es-field-hint {
    display: block;
    margin-top: 6px;
    font-size: 11px;
    color: #888;
}

/* =====================================================
   FONT PICKER - Custom Dropdown with Preview
   ===================================================== */
.es-font-picker-field {
    position: relative;
}

.es-font-picker {
    position: relative;
}

/* Dropdown Trigger - shows font name in its own font */
.es-font-dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 12px 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-font-dropdown-trigger:hover,
.es-font-dropdown-trigger:focus {
    border-color: rgba(102, 126, 234, 0.5);
    background: rgba(255, 255, 255, 0.08);
    outline: none;
}

.es-font-picker.open .es-font-dropdown-trigger {
    border-color: #667eea;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

.es-selected-font-name {
    font-size: 15px;
    font-weight: 500;
    color: #fff;
}

.es-font-dropdown-trigger svg {
    color: rgba(255, 255, 255, 0.5);
    transition: transform 0.2s ease;
}

.es-font-picker.open .es-font-dropdown-trigger svg {
    transform: rotate(180deg);
}

/* Dropdown Panel */
.es-font-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #1a1a2e;
    border: 1px solid #667eea;
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    z-index: 1000;
    display: none;
    max-height: 350px;
    overflow: hidden;
}

.es-font-picker.open .es-font-dropdown {
    display: block;
}

/* Search Input */
.es-font-search {
    width: 100%;
    padding: 10px 12px;
    border: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 13px;
    outline: none;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
}

.es-font-search::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.es-font-search:focus {
    background: rgba(255, 255, 255, 0.08);
}

/* Font List */
.es-font-list {
    max-height: 290px;
    overflow-y: auto;
}

.es-font-list::-webkit-scrollbar {
    width: 6px;
}

.es-font-list::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.es-font-list::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

/* Font Group */
.es-font-group {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.es-font-group:last-child {
    border-bottom: none;
}

.es-font-group-label {
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.03);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255, 255, 255, 0.5);
    position: sticky;
    top: 0;
}

/* Font Option */
.es-font-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    cursor: pointer;
    transition: background 0.15s ease;
}

.es-font-option:hover {
    background: rgba(102, 126, 234, 0.15);
}

.es-font-option.selected {
    background: #667eea;
}

.es-font-option-name {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.9);
}

.es-font-option.selected .es-font-option-name {
    color: #fff;
}

.es-font-option-sample {
    font-size: 20px;
    color: rgba(255, 255, 255, 0.4);
}

.es-font-option.selected .es-font-option-sample {
    color: rgba(255, 255, 255, 0.8);
}

/* Hide options when searching */
.es-font-option.hidden {
    display: none;
}

.es-font-group.hidden {
    display: none;
}
</style>
