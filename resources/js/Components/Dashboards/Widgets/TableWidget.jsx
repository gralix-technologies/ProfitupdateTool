import React, { useState, useEffect } from 'react';
import { IconSearch, IconChevronUp, IconChevronDown, IconExternalLink, IconDownload, IconFileSpreadsheet, IconFileText } from '@tabler/icons-react';
import { Link } from '@inertiajs/react';

export default function TableWidget({ widget, dashboardId, isEditing }) {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [sortField, setSortField] = useState('');
    const [sortDirection, setSortDirection] = useState('asc');
    const [currentPage, setCurrentPage] = useState(1);

    const { pageSize = 15, sortable = true, searchable = true } = widget.configuration.chart_options || {};

    useEffect(() => {
        if (!isEditing && dashboardId && widget.id) {
            fetchData();
        } else {
            // Show empty state in editing mode - no hardcoded data
            setData([]);
            setLoading(false);
        }
    }, [widget, dashboardId, isEditing]);

    const fetchData = async () => {
        try {
            setLoading(true);
            const response = await fetch(`/api/dashboards/${dashboardId}/widgets/${widget.id}/data`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch widget data');
            }
            
            const result = await response.json();
            if (result.success && result.data) {
                // Handle the correct data structure from the API
                const rawData = result.data.data || result.data || [];
                const validatedData = Array.isArray(rawData) ? rawData : [];
                setData(validatedData);
            } else {
                setData([]);
            }
        } catch (err) {
            console.error('Widget data fetch error:', err);
            setError(err.message);
            setData([]); // Ensure data is always an array
        } finally {
            setLoading(false);
        }
    };

    const handleSort = (field) => {
        if (!sortable) return;
        
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const getSortedAndFilteredData = () => {
        if (!Array.isArray(data) || data.length === 0) {
            return [];
        }
        
        let filteredData = data;

        // Apply search filter
        if (searchTerm && searchable) {
            filteredData = data.filter(row =>
                Object.values(row).some(value =>
                    value?.toString().toLowerCase().includes(searchTerm.toLowerCase())
                )
            );
        }

        // Apply sorting
        if (sortField && sortable) {
            filteredData = [...filteredData].sort((a, b) => {
                const aValue = a[sortField];
                const bValue = b[sortField];
                
                if (typeof aValue === 'number' && typeof bValue === 'number') {
                    return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
                }
                
                const aStr = aValue?.toString() || '';
                const bStr = bValue?.toString() || '';
                
                return sortDirection === 'asc' 
                    ? aStr.localeCompare(bStr)
                    : bStr.localeCompare(aStr);
            });
        }

        return filteredData;
    };

    const getPaginatedData = () => {
        const sortedData = getSortedAndFilteredData();
        const startIndex = (currentPage - 1) * pageSize;
        return sortedData.slice(startIndex, startIndex + pageSize);
    };

    const getTotalPages = () => {
        return Math.ceil(getSortedAndFilteredData().length / pageSize);
    };

    const formatValue = (value, column) => {
        if (typeof value === 'number') {
            return new Intl.NumberFormat('en-US').format(value);
        }
        return value;
    };

    const renderCellValue = (value, column) => {
        // Handle customer_id column with name and link
        if (column === 'customer_id' && value) {
            const customerName = data.find(row => row[column] === value)?.customer_name || 'Unknown Customer';
            return (
                <div className="d-flex align-items-center justify-content-between">
                    <div style={{ minWidth: 0, flex: 1 }}>
                        <div className="fw-medium text-truncate" title={value}>{value}</div>
                        <small className="text-muted text-truncate d-block" title={customerName}>{customerName}</small>
                    </div>
                    <Link 
                        href={`/customers/${value}`} 
                        className="ms-1 text-primary flex-shrink-0"
                        title="View Customer Details"
                    >
                        <IconExternalLink size={12} />
                    </Link>
                </div>
            );
        }
        
        // Handle loan_id with link to loan details
        if (column === 'loan_id' && value) {
            return (
                <div className="d-flex align-items-center justify-content-between">
                    <span className="fw-medium text-truncate" title={value}>{value}</span>
                    <Link 
                        href={`/loans/${value}`} 
                        className="ms-1 text-primary flex-shrink-0"
                        title="View Loan Details"
                    >
                        <IconExternalLink size={12} />
                    </Link>
                </div>
            );
        }
        
        // Handle currency columns
        if (column.includes('amount') || column.includes('exposure') || column.includes('ecl') || column.includes('balance')) {
            const formattedValue = formatValue(value);
            return (
                <span className="text-end d-block text-truncate" title={`ZMW{formattedValue}`}>
                    ZMW{formattedValue}
                </span>
            );
        }
        
        // Handle percentage columns
        if (column.includes('rate') || column.includes('ratio') || column.includes('default') || column.includes('lgd') || column.includes('pd')) {
            const percentageValue = typeof value === 'number' ? (value * 100).toFixed(2) + '%' : value;
            return (
                <span className="text-end d-block text-truncate" title={percentageValue}>
                    {percentageValue}
                </span>
            );
        }
        
        // Handle customer_name column
        if (column === 'customer_name' && value) {
            return (
                <span className="text-truncate d-block" title={value}>
                    {value}
                </span>
            );
        }
        
        return (
            <span className="text-truncate d-block" title={formatValue(value)}>
                {formatValue(value)}
            </span>
        );
    };

    const getColumns = () => {
        if (!Array.isArray(data) || data.length === 0) return [];
        return Object.keys(data[0]).filter(key => key !== 'id');
    };

    const exportToCSV = () => {
        const csvData = getSortedAndFilteredData();
        const csvContent = generateCSV(csvData, columns);
        downloadFile(csvContent, `${widget.title.replace(/[^a-zA-Z0-9]/g, '_')}.csv`, 'text/csv');
    };

    const exportToExcel = () => {
        const excelData = getSortedAndFilteredData();
        const excelContent = generateExcel(excelData, columns);
        downloadFile(excelContent, `${widget.title.replace(/[^a-zA-Z0-9]/g, '_')}.xlsx`, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    };

    const generateCSV = (data, columns) => {
        const headers = columns.map(col => col.replace(/_/g, ' ').toUpperCase()).join(',');
        const rows = data.map(row => 
            columns.map(col => {
                const value = row[col];
                if (typeof value === 'string' && value.includes(',')) {
                    return `"${value}"`;
                }
                return value || '';
            }).join(',')
        );
        return [headers, ...rows].join('\n');
    };

    const generateExcel = (data, columns) => {
        // For Excel export, we'll create a simple HTML table that Excel can import
        const headers = columns.map(col => `<th>${col.replace(/_/g, ' ').toUpperCase()}</th>`).join('');
        const rows = data.map(row => 
            `<tr>${columns.map(col => `<td>${row[col] || ''}</td>`).join('')}</tr>`
        ).join('');
        
        return `
            <html>
                <head>
                    <meta charset="utf-8">
                    <title>${widget.title}</title>
                </head>
                <body>
                    <table>
                        <thead><tr>${headers}</tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                </body>
            </html>
        `;
    };

    const downloadFile = (content, filename, mimeType) => {
        const blob = new Blob([content], { type: mimeType });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    };

    if (loading) {
        return (
            <div className="card h-100">
                <div className="card-header">
                    <h3 className="card-title">{widget.title}</h3>
                </div>
                <div className="card-body d-flex align-items-center justify-content-center">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="card h-100">
                <div className="card-header">
                    <h3 className="card-title">{widget.title}</h3>
                </div>
                <div className="card-body d-flex align-items-center justify-content-center">
                    <div className="text-center text-muted">
                        <div>Error loading data</div>
                        <small>{error}</small>
                    </div>
                </div>
            </div>
        );
    }

    const columns = getColumns();
    const paginatedData = getPaginatedData();
    const totalPages = getTotalPages();

    return (
        <div className="card h-100 shadow-sm">
            <div className="card-header bg-gradient-primary text-white">
                <div className="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 className="card-title mb-0 text-white">
                            {widget.title}
                        </h3>
                        <small className="text-white-50">
                            {getSortedAndFilteredData().length} records
                        </small>
                    </div>
                    <div className="d-flex align-items-center gap-2">
                        {/* Export Buttons */}
                        <div className="btn-group btn-group-sm" role="group">
                            <button
                                type="button"
                                className="btn btn-light btn-sm"
                                onClick={exportToCSV}
                                title="Export to CSV"
                            >
                                <IconFileText size={16} />
                            </button>
                            <button
                                type="button"
                                className="btn btn-light btn-sm"
                                onClick={exportToExcel}
                                title="Export to Excel"
                            >
                                <IconFileSpreadsheet size={16} />
                            </button>
                        </div>
                        
                        {/* Search */}
                        {searchable && (
                            <div className="input-group input-group-sm" style={{ width: '200px' }}>
                                <span className="input-group-text bg-white">
                                    <IconSearch size={16} />
                                </span>
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Search..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                        )}
                    </div>
                </div>
            </div>
            <div className="card-body p-0" style={{ overflow: 'hidden', minHeight: '400px', height: '100%' }}>
                <div className="table-responsive h-100" style={{ overflow: 'auto' }}>
                    <table className="table table-hover mb-0 table-striped" style={{ 
                        minWidth: '100%',
                        tableLayout: 'fixed',
                        width: '100%',
                        fontSize: '0.9rem'
                    }}>
                        <thead className="table-dark sticky-top" style={{ zIndex: 10 }}>
                            <tr>
                                {columns.map((column, index) => {
                                    // Calculate column width based on content type and position - INCREASED SIZES
                                    let columnWidth = 'auto';
                                    if (column.includes('customer')) {
                                        columnWidth = '220px';
                                    } else if (column.includes('loan_id')) {
                                        columnWidth = '140px';
                                    } else if (column.includes('amount') || column.includes('exposure') || column.includes('ecl')) {
                                        columnWidth = '160px';
                                    } else if (column.includes('probability') || column.includes('loss_given')) {
                                        columnWidth = '130px';
                                    } else if (column.includes('recovery')) {
                                        columnWidth = '140px';
                                    } else {
                                        columnWidth = '120px';
                                    }

                                    return (
                                        <th
                                            key={column}
                                            className={`${sortable ? 'cursor-pointer user-select-none' : ''} px-3 py-3`}
                                            onClick={() => handleSort(column)}
                                            style={{ 
                                                width: columnWidth,
                                                minWidth: columnWidth,
                                                maxWidth: columnWidth,
                                                whiteSpace: 'nowrap',
                                                overflow: 'hidden',
                                                textOverflow: 'ellipsis',
                                                borderRight: '1px solid #495057',
                                                padding: '12px 16px',
                                                backgroundColor: '#343a40',
                                                color: '#ffffff',
                                                fontWeight: '600',
                                                fontSize: '0.85rem',
                                                textTransform: 'uppercase',
                                                letterSpacing: '0.5px'
                                            }}
                                            title={column.replace(/_/g, ' ')}
                                        >
                                            <div className="d-flex align-items-center justify-content-between">
                                                <span className="text-capitalize fw-medium" style={{ 
                                                    fontSize: '0.9rem',
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis'
                                                }}>
                                                    {column.replace(/_/g, ' ')}
                                                </span>
                                                {sortable && sortField === column && (
                                                    <span className="ms-1 flex-shrink-0">
                                                        {sortDirection === 'asc' ? (
                                                            <IconChevronUp size={12} />
                                                        ) : (
                                                            <IconChevronDown size={12} />
                                                        )}
                                                    </span>
                                                )}
                                            </div>
                                        </th>
                                    );
                                })}
                            </tr>
                        </thead>
                        <tbody>
                            {paginatedData.map((row, index) => (
                                <tr key={row.id || index} className="border-bottom">
                                    {columns.map((column, colIndex) => {
                                        // Use same width calculation as header - INCREASED SIZES
                                        let columnWidth = 'auto';
                                        if (column.includes('customer')) {
                                            columnWidth = '220px';
                                        } else if (column.includes('loan_id')) {
                                            columnWidth = '140px';
                                        } else if (column.includes('amount') || column.includes('exposure') || column.includes('ecl')) {
                                            columnWidth = '160px';
                                        } else if (column.includes('probability') || column.includes('loss_given')) {
                                            columnWidth = '130px';
                                        } else if (column.includes('recovery')) {
                                            columnWidth = '140px';
                                        } else {
                                            columnWidth = '120px';
                                        }

                                        return (
                                            <td 
                                                key={column} 
                                                className="px-3 py-3 align-middle"
                                                style={{ 
                                                    width: columnWidth,
                                                    minWidth: columnWidth,
                                                    maxWidth: columnWidth,
                                                    whiteSpace: 'nowrap',
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis',
                                                    borderRight: '1px solid #dee2e6',
                                                    fontSize: '0.9rem',
                                                    padding: '12px 16px',
                                                    backgroundColor: 'transparent',
                                                    transition: 'background-color 0.2s ease'
                                                }}
                                                onMouseEnter={(e) => e.target.style.backgroundColor = '#f8f9fa'}
                                                onMouseLeave={(e) => e.target.style.backgroundColor = 'transparent'}
                                            >
                                                {column === 'status' ? (
                                                    <span className={`badge ${row[column] === 'Active' ? 'bg-success' : 'bg-secondary'}`}>
                                                        {row[column]}
                                                    </span>
                                                ) : (
                                                    renderCellValue(row[column], column)
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
            {totalPages > 1 && (
                <div className="card-footer bg-light">
                    <div className="d-flex align-items-center justify-content-between">
                        <div className="text-muted small fw-medium">
                            Showing {((currentPage - 1) * pageSize) + 1} to {Math.min(currentPage * pageSize, getSortedAndFilteredData().length)} of {getSortedAndFilteredData().length} entries
                        </div>
                        <nav>
                            <ul className="pagination pagination-sm mb-0">
                                <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                                    <button
                                        className="page-link border-0"
                                        onClick={() => setCurrentPage(currentPage - 1)}
                                        disabled={currentPage === 1}
                                        style={{ 
                                            backgroundColor: currentPage === 1 ? '#e9ecef' : '#ffffff',
                                            color: currentPage === 1 ? '#6c757d' : '#007bff'
                                        }}
                                    >
                                        Previous
                                    </button>
                                </li>
                                {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                                    <li key={page} className={`page-item ${currentPage === page ? 'active' : ''}`}>
                                        <button
                                            className="page-link border-0"
                                            onClick={() => setCurrentPage(page)}
                                            style={{ 
                                                backgroundColor: currentPage === page ? '#007bff' : '#ffffff',
                                                color: currentPage === page ? '#ffffff' : '#007bff'
                                            }}
                                        >
                                            {page}
                                        </button>
                                    </li>
                                ))}
                                <li className={`page-item ${currentPage === totalPages ? 'disabled' : ''}`}>
                                    <button
                                        className="page-link border-0"
                                        onClick={() => setCurrentPage(currentPage + 1)}
                                        disabled={currentPage === totalPages}
                                        style={{ 
                                            backgroundColor: currentPage === totalPages ? '#e9ecef' : '#ffffff',
                                            color: currentPage === totalPages ? '#6c757d' : '#007bff'
                                        }}
                                    >
                                        Next
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            )}
            {isEditing && (
                <div className="position-absolute top-0 end-0 m-2">
                    <span className="badge bg-secondary-lt">
                        No Data Available
                    </span>
                </div>
            )}
        </div>
    );
}