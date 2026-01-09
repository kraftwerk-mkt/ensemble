/**
 * Layout Sets Editor JavaScript
 * Handles CodeMirror initialization, preview, and editor interactions
 * 
 * @package Ensemble
 */

let htmlEditor = null;
let cssEditor = null;

/**
 * Open the template editor modal
 */
function openEditor() {
    const modal = document.getElementById('editorModal');
    modal.style.display = 'block';
    
    // Reset form
    document.getElementById('template_id').value = '';
    document.getElementById('template_name').value = '';
    
    setTimeout(function() {
        if (typeof CodeMirror === 'undefined') {
            alert('CodeMirror could not be loaded. Please reload the page.');
            return;
        }
        
        // HTML Editor
        if (!htmlEditor) {
            htmlEditor = CodeMirror.fromTextArea(document.getElementById('template_html'), {
                mode: 'application/x-httpd-php',
                theme: 'material-darker',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 4,
                tabSize: 4
            });
        }
        
        // CSS Editor
        if (!cssEditor) {
            cssEditor = CodeMirror.fromTextArea(document.getElementById('template_css'), {
                mode: 'css',
                theme: 'material-darker',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 4,
                tabSize: 4
            });
        }
        
        // Refresh editors
        if (htmlEditor) htmlEditor.refresh();
        if (cssEditor) cssEditor.refresh();
        
        // Initial preview
        refreshPreview();
        
    }, 100);
}

/**
 * Close the template editor modal
 */
function closeEditor() {
    // Save CodeMirror values to textareas
    if (htmlEditor) {
        htmlEditor.save();
        htmlEditor.toTextArea();
        htmlEditor = null;
    }
    if (cssEditor) {
        cssEditor.save();
        cssEditor.toTextArea();
        cssEditor = null;
    }
    
    document.getElementById('editorModal').style.display = 'none';
}

/**
 * Edit an existing template
 */
function editTemplate(template) {
    const modal = document.getElementById('editorModal');
    modal.style.display = 'block';
    
    // Fill form
    document.getElementById('template_id').value = template.id;
    document.getElementById('template_name').value = template.name;
    document.getElementById('template_html').value = template.html;
    document.getElementById('template_css').value = template.css;
    
    setTimeout(function() {
        if (typeof CodeMirror === 'undefined') {
            alert('CodeMirror could not be loaded. Please reload the page.');
            return;
        }
        
        // HTML Editor
        if (!htmlEditor) {
            htmlEditor = CodeMirror.fromTextArea(document.getElementById('template_html'), {
                mode: 'application/x-httpd-php',
                theme: 'material-darker',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 4,
                tabSize: 4
            });
        } else {
            htmlEditor.setValue(template.html);
        }
        
        // CSS Editor
        if (!cssEditor) {
            cssEditor = CodeMirror.fromTextArea(document.getElementById('template_css'), {
                mode: 'css',
                theme: 'material-darker',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 4,
                tabSize: 4
            });
        } else {
            cssEditor.setValue(template.css);
        }
        
        // Refresh editors
        if (htmlEditor) htmlEditor.refresh();
        if (cssEditor) cssEditor.refresh();
        
        // Update preview
        refreshPreview();
        
    }, 100);
}

/**
 * Switch between HTML, CSS, and Reference tabs
 */
function showTab(tab) {
    // Hide all editors
    document.getElementById('htmlEditor').style.display = 'none';
    document.getElementById('cssEditor').style.display = 'none';
    document.getElementById('referencePanel').style.display = 'none';
    
    // Reset button classes
    document.getElementById('tabHtml').classList.remove('active');
    document.getElementById('tabCss').classList.remove('active');
    document.getElementById('tabReference').classList.remove('active');
    
    // Show selected tab
    if (tab === 'html') {
        document.getElementById('htmlEditor').style.display = 'block';
        document.getElementById('tabHtml').classList.add('active');
        if (htmlEditor) htmlEditor.refresh();
    } else if (tab === 'css') {
        document.getElementById('cssEditor').style.display = 'block';
        document.getElementById('tabCss').classList.add('active');
        if (cssEditor) cssEditor.refresh();
    } else if (tab === 'reference') {
        document.getElementById('referencePanel').style.display = 'block';
        document.getElementById('tabReference').classList.add('active');
    }
}

/**
 * Refresh the preview iframe with current template content
 */
function refreshPreview() {
    // Get current HTML and CSS
    let html = htmlEditor ? htmlEditor.getValue() : document.getElementById('template_html').value;
    let css = cssEditor ? cssEditor.getValue() : document.getElementById('template_css').value;
    
    // Create preview HTML with sample data
    const previewHTML = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #fff;
        }
        ${css}
    </style>
</head>
<body>
    ${convertPHPtoPreview(html)}
</body>
</html>
    `;
    
    // Update iframe
    const iframe = document.getElementById('templatePreview');
    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(previewHTML);
    iframeDoc.close();
}

/**
 * Convert PHP template tags to preview HTML with sample data
 */
function convertPHPtoPreview(html) {
    // Replace common WordPress functions with sample data
    html = html.replace(/<?php\s+the_title\(\);\s+\?>/g, 'Sample Event Title');
    html = html.replace(/<?php\s+the_content\(\);\s+\?>/g, '<p>This is sample event content. Your actual event description will appear here.</p>');
    
    // Handle date meta
    html = html.replace(/<?php\s+if\s+\(\$event_date[^>]+>\s+<div[^>]+>\s+<?php[^>]+>\s+<\/div>\s+<?php\s+endif;\s+\?>/gs, 
        '<div class="es-event-date">2025-12-24 18:00</div>');
    
    // Remove remaining PHP tags
    html = html.replace(/<\?php[^>]*\?>/g, '');
    
    return html;
}

// ESC key to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('editorModal');
        if (modal && modal.style.display === 'block') {
            closeEditor();
        }
    }
});

// Before form submit - save CodeMirror to textareas
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('templateForm');
    if (form) {
        form.addEventListener('submit', function() {
            if (htmlEditor) htmlEditor.save();
            if (cssEditor) cssEditor.save();
        });
    }
});