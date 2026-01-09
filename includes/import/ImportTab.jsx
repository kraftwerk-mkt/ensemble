import React, { useState, useCallback } from 'react';

const ImportTab = () => {
    const [importSource, setImportSource] = useState('url'); // 'url' or 'file'
    const [icalUrl, setIcalUrl] = useState('');
    const [selectedFile, setSelectedFile] = useState(null);
    const [previewData, setPreviewData] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [importResult, setImportResult] = useState(null);
    const [error, setError] = useState(null);

    // Handle file selection
    const handleFileChange = useCallback((e) => {
        const file = e.target.files[0];
        if (file) {
            if (!file.name.endsWith('.ics')) {
                setError('Please select a valid .ics file');
                return;
            }
            setSelectedFile(file);
            setError(null);
        }
    }, []);

    // Fetch preview
    const handlePreview = async () => {
        setError(null);
        setPreviewData(null);
        setImportResult(null);

        // Validation
        if (importSource === 'url' && !icalUrl.trim()) {
            setError('Please enter an iCal URL');
            return;
        }

        if (importSource === 'file' && !selectedFile) {
            setError('Please select a file');
            return;
        }

        setIsLoading(true);

        try {
            const formData = new FormData();
            formData.append('action', 'ensemble_import_preview');
            formData.append('nonce', ensembleCalendar.nonce);
            formData.append('source_type', importSource);

            if (importSource === 'url') {
                formData.append('source', icalUrl);
            } else {
                formData.append('source', selectedFile);
            }

            const response = await fetch(ensembleCalendar.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data?.message || 'Preview failed');
            }

            setPreviewData(data.data);
        } catch (err) {
            setError(err.message);
        } finally {
            setIsLoading(false);
        }
    };

    // Execute import
    const handleImport = async () => {
        if (!previewData) {
            setError('No preview data available');
            return;
        }

        setError(null);
        setImportResult(null);
        setIsLoading(true);

        try {
            const formData = new FormData();
            formData.append('action', 'ensemble_import_execute');
            formData.append('nonce', ensembleCalendar.nonce);
            formData.append('source_type', importSource);

            if (importSource === 'url') {
                formData.append('source', icalUrl);
            } else {
                formData.append('source', selectedFile);
            }

            const response = await fetch(ensembleCalendar.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data?.message || 'Import failed');
            }

            setImportResult(data.data);
            setPreviewData(null); // Clear preview after successful import
        } catch (err) {
            setError(err.message);
        } finally {
            setIsLoading(false);
        }
    };

    // Reset form
    const handleReset = () => {
        setIcalUrl('');
        setSelectedFile(null);
        setPreviewData(null);
        setImportResult(null);
        setError(null);
    };

    // Location match status badge
    const LocationMatchBadge = ({ match }) => {
        if (!match) return <span className="es-badge es-badge--gray">No Location</span>;

        switch (match.status) {
            case 'matched':
                return (
                    <span className="es-badge es-badge--success" title={`${match.confidence}% match`}>
                        ✓ {match.matched_title}
                    </span>
                );
            case 'low_confidence':
                return (
                    <span className="es-badge es-badge--warning" title={`${match.confidence}% match - Low confidence`}>
                        ? {match.matched_title}
                    </span>
                );
            default:
                return <span className="es-badge es-badge--gray">No Match</span>;
        }
    };

    return (
        <div className="es-import-tab">
            <div className="es-import-header">
                <h2>Import Events from iCal</h2>
                <p>Import events from .ics files or iCal URLs (Google Calendar, Outlook, etc.)</p>
            </div>

            {/* Source Selection */}
            <div className="es-import-source">
                <div className="es-source-toggle">
                    <button
                        className={`es-btn es-btn--toggle ${importSource === 'url' ? 'es-btn--active' : ''}`}
                        onClick={() => setImportSource('url')}
                        disabled={isLoading}
                    >
                        📡 iCal URL
                    </button>
                    <button
                        className={`es-btn es-btn--toggle ${importSource === 'file' ? 'es-btn--active' : ''}`}
                        onClick={() => setImportSource('file')}
                        disabled={isLoading}
                    >
                        📁 Upload File
                    </button>
                </div>

                {/* URL Input */}
                {importSource === 'url' && (
                    <div className="es-input-group">
                        <label htmlFor="ical-url">iCal URL</label>
                        <input
                            id="ical-url"
                            type="url"
                            className="es-input"
                            placeholder="https://calendar.google.com/calendar/ical/..."
                            value={icalUrl}
                            onChange={(e) => setIcalUrl(e.target.value)}
                            disabled={isLoading}
                        />
                        <small>Example: Google Calendar → Settings → Secret address in iCal format</small>
                    </div>
                )}

                {/* File Upload */}
                {importSource === 'file' && (
                    <div className="es-input-group">
                        <label htmlFor="ical-file">Upload .ics File</label>
                        <div className="es-file-upload">
                            <input
                                id="ical-file"
                                type="file"
                                accept=".ics"
                                onChange={handleFileChange}
                                disabled={isLoading}
                            />
                            {selectedFile && (
                                <div className="es-file-name">
                                    📄 {selectedFile.name}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Preview Button */}
                <div className="es-import-actions">
                    <button
                        className="es-btn es-btn--primary"
                        onClick={handlePreview}
                        disabled={isLoading}
                    >
                        {isLoading ? '⏳ Loading...' : '🔍 Preview Events'}
                    </button>

                    {(previewData || importResult) && (
                        <button
                            className="es-btn es-btn--secondary"
                            onClick={handleReset}
                            disabled={isLoading}
                        >
                            🔄 Reset
                        </button>
                    )}
                </div>
            </div>

            {/* Error Message */}
            {error && (
                <div className="es-alert es-alert--error">
                    <strong>Error:</strong> {error}
                </div>
            )}

            {/* Import Result */}
            {importResult && (
                <div className="es-alert es-alert--success">
                    <h3>✅ Import Complete</h3>
                    <div className="es-import-stats">
                        <div className="es-stat">
                            <span className="es-stat-value">{importResult.success}</span>
                            <span className="es-stat-label">Imported</span>
                        </div>
                        <div className="es-stat">
                            <span className="es-stat-value">{importResult.skipped}</span>
                            <span className="es-stat-label">Skipped</span>
                        </div>
                        {importResult.failed > 0 && (
                            <div className="es-stat es-stat--error">
                                <span className="es-stat-value">{importResult.failed}</span>
                                <span className="es-stat-label">Failed</span>
                            </div>
                        )}
                    </div>

                    {importResult.errors && importResult.errors.length > 0 && (
                        <div className="es-import-errors">
                            <h4>Errors:</h4>
                            <ul>
                                {importResult.errors.map((err, idx) => (
                                    <li key={idx}>
                                        <strong>{err.event}:</strong> {err.error}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            )}

            {/* Preview Table */}
            {previewData && (
                <div className="es-preview-section">
                    <div className="es-preview-header">
                        <h3>Preview: {previewData.total} Events Found</h3>
                        <button
                            className="es-btn es-btn--primary es-btn--large"
                            onClick={handleImport}
                            disabled={isLoading}
                        >
                            {isLoading ? '⏳ Importing...' : `📥 Import ${previewData.total} Events`}
                        </button>
                    </div>

                    <div className="es-preview-table-wrapper">
                        <table className="es-preview-table">
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                {previewData.events.map((event, idx) => (
                                    <tr key={idx}>
                                        <td>
                                            <strong>{event.title}</strong>
                                            {event.description && (
                                                <div className="es-event-desc">{event.description}</div>
                                            )}
                                        </td>
                                        <td>
                                            <div className="es-event-date">
                                                {new Date(event.date).toLocaleDateString('de-DE', {
                                                    day: '2-digit',
                                                    month: '2-digit',
                                                    year: 'numeric',
                                                })}
                                                <br />
                                                <small>
                                                    {new Date(event.date).toLocaleTimeString('de-DE', {
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <LocationMatchBadge match={event.location_match} />
                                            {event.location_raw && (
                                                <div className="es-location-raw">
                                                    <small>{event.location_raw}</small>
                                                </div>
                                            )}
                                        </td>
                                        <td>
                                            {event.status === 'confirmed' && (
                                                <span className="es-badge es-badge--success">Confirmed</span>
                                            )}
                                            {event.status === 'tentative' && (
                                                <span className="es-badge es-badge--warning">Tentative</span>
                                            )}
                                            {event.status === 'cancelled' && (
                                                <span className="es-badge es-badge--error">Cancelled</span>
                                            )}
                                        </td>
                                        <td>
                                            {event.is_recurring ? (
                                                <span className="es-badge es-badge--purple">🔁 Recurring</span>
                                            ) : (
                                                <span className="es-badge es-badge--gray">Single</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ImportTab;
