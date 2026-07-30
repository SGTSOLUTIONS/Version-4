@extends('layouts.office')

@section('title', 'Data Variation Report')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* ─── ROOT VARIABLES ─── */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --success-color: #16a34a;
            --success-light: #dcfce7;
            --danger-color: #dc2626;
            --danger-light: #fee2e2;
            --warning-color: #f59e0b;
            --warning-light: #fef3c7;
            --info-color: #0891b2;
            --info-light: #ecfeff;
            --purple-color: #7c3aed;
            --purple-light: #f5f3ff;
            --pink-color: #db2777;
            --pink-light: #fdf2f8;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
        }

        /* ─── PAGE HEADER ─── */
        .page-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: var(--radius);
            padding: 24px 32px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: -60%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .page-header .header-left {
            position: relative;
            z-index: 1;
        }

        .page-header .header-left h4 {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .page-header .header-left h4 i {
            color: var(--primary-color);
            background: rgba(37, 99, 235, 0.2);
            padding: 8px;
            border-radius: 10px;
            margin-right: 12px;
        }

        .page-header .header-left .subtitle {
            color: var(--gray-400);
            font-size: 0.85rem;
            margin: 4px 0 0 0;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-header .header-left .subtitle span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .page-header .header-left .subtitle .badge-zone {
            background: rgba(37, 99, 235, 0.2);
            color: var(--primary-light);
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .page-header .header-right {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .page-header .header-right .btn {
            font-weight: 600;
            font-size: 0.8rem;
            padding: 8px 18px;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }

        .page-header .header-right .btn-back {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .page-header .header-right .btn-back:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ─── EXPORT BUTTONS ─── */
        .btn-export-group {
            display: flex;
            gap: 6px;
        }

        .btn-export {
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.75rem;
            padding: 8px 16px;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-export-excel {
            background: #217346;
            color: #fff;
        }

        .btn-export-excel:hover {
            background: #1a5c38;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33, 115, 70, 0.4);
        }

        .btn-export-pdf {
            background: #dc3545;
            color: #fff;
        }

        .btn-export-pdf:hover {
            background: #b02a37;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        .btn-export-csv {
            background: #0d6efd;
            color: #fff;
        }

        .btn-export-csv:hover {
            background: #0b5ed7;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
        }

        .btn-export-filtered {
            background: #6c5ce7;
            color: #fff;
        }

        .btn-export-filtered:hover {
            background: #5a4bd1;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 92, 231, 0.4);
        }

        /* ─── STATISTICS CARDS ─── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            cursor: default;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .stat-card .stat-icon.blue {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .stat-card .stat-icon.green {
            background: var(--success-light);
            color: var(--success-color);
        }

        .stat-card .stat-icon.red {
            background: var(--danger-light);
            color: var(--danger-color);
        }

        .stat-card .stat-icon.amber {
            background: var(--warning-light);
            color: var(--warning-color);
        }

        .stat-card .stat-icon.cyan {
            background: var(--info-light);
            color: var(--info-color);
        }

        .stat-card .stat-icon.purple {
            background: var(--purple-light);
            color: var(--purple-color);
        }

        .stat-card .stat-icon.pink {
            background: var(--pink-light);
            color: var(--pink-color);
        }

        .stat-card .stat-content {
            flex: 1;
            min-width: 0;
        }

        .stat-card .stat-content .stat-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
            margin: 0;
        }

        .stat-card .stat-content .stat-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.2;
            margin: 2px 0 0 0;
        }

        .stat-card .stat-content .stat-value .trend {
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 6px;
        }

        .stat-card .stat-content .stat-value .trend.up {
            color: var(--success-color);
        }

        .stat-card .stat-content .stat-value .trend.down {
            color: var(--danger-color);
        }

        /* ─── FILTER CARD ─── */
        .filter-card {
            background: #fff;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .filter-card:hover {
            box-shadow: var(--shadow-md);
        }

        .filter-card .filter-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-card .filter-title i {
            color: var(--primary-color);
        }

        .filter-card .form-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .filter-card .form-select,
        .filter-card .form-control {
            font-size: 0.85rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            padding: 8px 12px;
            transition: var(--transition);
            background: #fff;
        }

        .filter-card .form-select:focus,
        .filter-card .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-card .form-select:hover,
        .filter-card .form-control:hover {
            border-color: var(--gray-300);
        }

        .filter-card .btn-filter {
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.8rem;
            padding: 8px 18px;
            transition: var(--transition);
        }

        .filter-card .btn-filter-primary {
            background: var(--primary-color);
            color: #fff;
            border: none;
        }

        .filter-card .btn-filter-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .filter-card .btn-filter-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }

        .filter-card .btn-filter-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-2px);
        }

        .filter-card .filter-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-card .filter-footer {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .filter-card .filter-footer .info-text {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .filter-card .filter-footer .info-text strong {
            color: var(--gray-800);
        }

        /* ─── TABLE CONTAINER ─── */
        .table-container {
            background: #fff;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            box-shadow: var(--shadow);
            position: relative;
        }

        .table-container .table-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: var(--gray-50);
        }

        .table-container .table-header h5 {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-container .table-header h5 i {
            color: var(--primary-color);
        }

        .table-container .table-header .record-count {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .table-container .table-responsive {
            overflow-x: auto;
        }

        .table-container table {
            margin-bottom: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-container table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-container table thead th {
            background: var(--gray-100);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-600);
            padding: 12px 16px;
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
            text-align: left;
        }

        .table-container table tbody tr {
            transition: var(--transition);
            cursor: default;
        }

        .table-container table tbody tr:hover {
            background: var(--primary-light);
        }

        .table-container table tbody td {
            padding: 12px 16px;
            font-size: 0.85rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-700);
        }

        .table-container table tbody tr:last-child td {
            border-bottom: none;
        }

        /* ─── BADGE STYLES ─── */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            white-space: nowrap;
        }

        .badge-status.match {
            background: var(--success-light);
            color: var(--success-color);
        }

        .badge-status.variation {
            background: var(--danger-light);
            color: var(--danger-color);
        }

        .badge-status.partial {
            background: var(--warning-light);
            color: var(--warning-color);
        }

        .badge-status.building-only {
            background: var(--info-light);
            color: var(--info-color);
        }

        .badge-status.assessment-only {
            background: var(--purple-light);
            color: var(--purple-color);
        }

        .badge-status.no-data {
            background: var(--gray-200);
            color: var(--gray-500);
        }

        .badge-area {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.65rem;
        }

        .badge-area.match {
            background: var(--success-light);
            color: var(--success-color);
        }

        .badge-area.variation {
            background: var(--warning-light);
            color: var(--warning-color);
        }

        .badge-assessment-type {
            font-size: 0.6rem;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .badge-assessment-type.old {
            background: var(--warning-light);
            color: #92400e;
        }

        .badge-assessment-type.new {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        /* ─── USAGE DETAIL BOX ─── */
        .usage-detail-box {
            background: var(--gray-50);
            border-radius: var(--radius-sm);
            padding: 4px 12px;
            display: inline-block;
            font-size: 0.78rem;
            border: 1px solid var(--gray-200);
        }

        .usage-detail-box .label {
            color: var(--gray-400);
            font-weight: 600;
            font-size: 0.65rem;
            text-transform: uppercase;
        }

        .usage-detail-box .value {
            color: var(--gray-800);
            font-weight: 700;
        }

        .usage-detail-box .value.mismatch {
            color: var(--danger-color);
        }

        .usage-detail-box .value.match {
            color: var(--success-color);
        }

        .usage-detail-box .value.null-value {
            color: var(--gray-400);
            font-style: italic;
        }

        /* ─── PROGRESS BAR ─── */
        .variation-progress {
            width: 50px;
            height: 5px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }

        .variation-progress .bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .variation-progress .bar-success {
            background: var(--success-color);
        }

        .variation-progress .bar-danger {
            background: var(--danger-color);
        }

        .variation-progress .bar-warning {
            background: var(--warning-color);
        }

        /* ─── ACTION BUTTONS ─── */
        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: var(--transition);
            font-size: 0.85rem;
            text-decoration: none;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-action.view {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .btn-action.view:hover {
            background: var(--primary-color);
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-action.pdf {
            background: var(--danger-light);
            color: var(--danger-color);
        }

        .btn-action.pdf:hover {
            background: var(--danger-color);
            color: #fff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        /* ─── PAGINATION ─── */
        .pagination-container {
            padding: 16px 24px;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .pagination-container .pagination {
            margin: 0;
            gap: 2px;
        }

        .pagination-container .pagination .page-link {
            padding: 6px 14px;
            font-size: 0.85rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            transition: var(--transition);
            background: #fff;
        }

        .pagination-container .pagination .page-link:hover {
            background: var(--primary-light);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .pagination-container .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        .pagination-container .pagination .page-item.disabled .page-link {
            color: var(--gray-400);
            cursor: not-allowed;
        }

        .pagination-container .pagination .page-item.disabled .page-link:hover {
            background: #fff;
            border-color: var(--gray-200);
            color: var(--gray-400);
        }

        .pagination-info {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .pagination-info strong {
            color: var(--gray-800);
        }

        /* ─── EMPTY STATE ─── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state .empty-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 16px;
        }

        .empty-state h5 {
            color: var(--gray-700);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--gray-500);
            font-size: 0.9rem;
            margin: 0;
        }

        /* ─── LOADING OVERLAY ─── */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            border-radius: var(--radius);
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-overlay .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid var(--gray-200);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ─── DETAIL MODAL ─── */
        .detail-modal-content {
            max-height: 600px;
            overflow-y: auto;
            padding: 4px;
        }

        .detail-modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .detail-modal-content::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }

        .detail-modal-content::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }

        .detail-modal-content::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        .detail-section {
            background: var(--gray-50);
            border-radius: var(--radius-sm);
            padding: 16px 20px;
            margin-bottom: 16px;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .detail-section:hover {
            box-shadow: var(--shadow-sm);
        }

        .detail-section.warning {
            border-left-color: var(--warning-color);
        }

        .detail-section.danger {
            border-left-color: var(--danger-color);
        }

        .detail-section.success {
            border-left-color: var(--success-color);
        }

        .detail-section.info {
            border-left-color: var(--info-color);
        }

        .detail-section h6 {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--gray-800);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-section h6 i {
            font-size: 1rem;
        }

        .detail-section .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.82rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .detail-section .detail-item:last-child {
            border-bottom: none;
        }

        .detail-section .detail-item .label {
            color: var(--gray-500);
            font-weight: 500;
        }

        .detail-section .detail-item .value {
            font-weight: 600;
            color: var(--gray-800);
            text-align: right;
        }

        .detail-section .detail-item .value code {
            background: var(--gray-200);
            padding: 1px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        .assessment-points-list {
            max-height: 150px;
            overflow-y: auto;
        }

        .assessment-points-list .point-item {
            padding: 6px 12px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.78rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
            background: #fff;
            border-radius: 4px;
            margin-bottom: 4px;
        }

        .assessment-points-list .point-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .assessment-points-list .point-item .point-assessment {
            font-weight: 700;
            color: var(--gray-800);
        }

        .assessment-points-list .point-item .point-details {
            color: var(--gray-500);
            font-size: 0.7rem;
        }

        .assessment-points-list .point-item .point-badge {
            font-size: 0.6rem;
            padding: 2px 8px;
            border-radius: 12px;
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .page-header {
                padding: 20px;
            }

            .page-header .header-left h4 {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 16px 20px;
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .page-header .header-left .subtitle {
                justify-content: center;
            }

            .page-header .header-right {
                justify-content: center;
                flex-wrap: wrap;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 14px 16px;
            }

            .stat-card .stat-content .stat-value {
                font-size: 1.1rem;
            }

            .filter-card {
                padding: 16px;
            }

            .filter-card .filter-footer {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .table-container .table-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .pagination-container .d-flex {
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }

            .btn-export-group {
                flex-wrap: wrap;
                justify-content: center;
            }

            .btn-export {
                font-size: 0.7rem;
                padding: 6px 12px;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .page-header .header-left h4 {
                font-size: 1rem;
            }

            .page-header .header-left .subtitle {
                font-size: 0.75rem;
                flex-direction: column;
                gap: 4px;
            }

            .filter-card .row>div {
                margin-bottom: 12px;
            }

            .filter-card .row>div:last-child {
                margin-bottom: 0;
            }

            .table-container table thead th {
                font-size: 0.6rem;
                padding: 8px 10px;
            }

            .table-container table tbody td {
                font-size: 0.75rem;
                padding: 8px 10px;
            }

            .usage-detail-box {
                font-size: 0.7rem;
                padding: 2px 8px;
            }

            .badge-status {
                font-size: 0.6rem;
                padding: 2px 10px;
            }
        }

        /* ─── SCROLLBAR STYLING ─── */
        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* ─── UTILITY ─── */
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .gap-1 {
            gap: 4px;
        }

        .gap-2 {
            gap: 8px;
        }

        .gap-3 {
            gap: 12px;
        }

        .fw-600 {
            font-weight: 600;
        }

        .fw-700 {
            font-weight: 700;
        }

        .fw-800 {
            font-weight: 800;
        }

        .text-gray-400 {
            color: var(--gray-400);
        }

        .text-gray-500 {
            color: var(--gray-500);
        }

        .text-gray-600 {
            color: var(--gray-600);
        }

        .text-gray-700 {
            color: var(--gray-700);
        }

        .text-gray-800 {
            color: var(--gray-800);
        }

        .bg-gray-50 {
            background: var(--gray-50);
        }

        .clickable-row {
            cursor: pointer;
        }

        .clickable-row:hover {
            background: var(--primary-light) !important;
        }

        .transition-all {
            transition: var(--transition);
        }

        .shadow-hover:hover {
            box-shadow: var(--shadow-md);
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid mt-4">

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="mb-1 fw-bold">
                    <i class="bi bi-tags text-primary me-2"></i>
                    Data Variation Report - Ward {{ $ward->ward_no ?? 'N/A' }}
                </h4>
                <p class="text-muted small mb-0">
                    Zone: {{ $zone->zone_name ?? 'N/A' }} | {{ now()->format('l, d F Y') }}
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <div class="export-buttons-group">
                    <a href="{{ route('data-variation.export', $ward->id) }}"
                        class="btn btn-export btn-export-excel btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </a>
                    <a href="{{ route('data-variation.pdf', $ward->id) }}" class="btn btn-export btn-export-pdf btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </a>
                    <button type="button" class="btn btn-export btn-export-csv btn-sm" id="exportFilteredBtn">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Filtered
                    </button>
                </div>
            </div>
        </div>

        <!-- STATISTICS CARDS -->
        @php
            $total = count($allData ?? $buildingVariations);
            $matches = 0;
            $variations = 0;
            $partialMatches = 0;
            $buildingOnly = 0;
            $assessmentOnly = 0;
            $noData = 0;
            $areaVariations = 0;

            foreach ($allData ?? $buildingVariations as $item) {
                $status = $item['usage_comparison']['usage_status'] ?? 'NO_DATA';
                switch ($status) {
                    case 'MATCH':
                        $matches++;
                        break;
                    case 'VARIATION':
                        $variations++;
                        break;
                    case 'PARTIAL_MATCH':
                        $partialMatches++;
                        break;
                    case 'BUILDING_ONLY':
                        $buildingOnly++;
                        break;
                    case 'ASSESSMENT_ONLY':
                        $assessmentOnly++;
                        break;
                    default:
                        $noData++;
                        break;
                }
                if (($item['area_comparison']['area_status'] ?? '') === 'VARIATION') {
                    $areaVariations++;
                }
            }
        @endphp

        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-blue"><i class="bi bi-building"></i></div>
                    <div>
                        <div class="stat-label">Total Buildings</div>
                        <div class="stat-value">{{ $total }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-green"><i class="bi bi-check2-circle"></i></div>
                    <div>
                        <div class="stat-label">Usage Match</div>
                        <div class="stat-value">{{ $matches }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-red"><i class="bi bi-x-circle"></i></div>
                    <div>
                        <div class="stat-label">Usage Variation</div>
                        <div class="stat-value">{{ $variations }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-amber"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <div class="stat-label">Partial Match</div>
                        <div class="stat-value">{{ $partialMatches }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-cyan"><i class="bi bi-building"></i></div>
                    <div>
                        <div class="stat-label">Building Only</div>
                        <div class="stat-value">{{ $buildingOnly }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-purple"><i class="bi bi-file-earmark-text"></i></div>
                    <div>
                        <div class="stat-label">Assessment Only</div>
                        <div class="stat-value">{{ $assessmentOnly }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-pink"><i class="bi bi-dash-circle"></i></div>
                    <div>
                        <div class="stat-label">No Data</div>
                        <div class="stat-value">{{ $noData }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-cyan"><i class="bi bi-arrows-expand"></i></div>
                    <div>
                        <div class="stat-label">Area Variation</div>
                        <div class="stat-value">{{ $areaVariations }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTER SECTION -->
        <div class="filter-card">
            <form id="filterForm" method="GET" action="{{ route('variation.show', $ward->id) }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <label class="form-label"><i class="bi bi-tags me-1"></i>Usage Status</label>
                        <select name="usage_status" id="filterUsageStatus" class="form-select form-select-sm">
                            <option value="all" {{ request('usage_status') == 'all' ? 'selected' : '' }}>All Status
                            </option>
                            <option value="MATCH" {{ request('usage_status') == 'MATCH' ? 'selected' : '' }}>✅ Match
                            </option>
                            <option value="VARIATION" {{ request('usage_status') == 'VARIATION' ? 'selected' : '' }}>❌
                                Variation</option>
                            <option value="PARTIAL_MATCH"
                                {{ request('usage_status') == 'PARTIAL_MATCH' ? 'selected' : '' }}>⚠️ Partial Match
                            </option>
                            <option value="BUILDING_ONLY"
                                {{ request('usage_status') == 'BUILDING_ONLY' ? 'selected' : '' }}>🏢 Building Only
                            </option>
                            <option value="ASSESSMENT_ONLY"
                                {{ request('usage_status') == 'ASSESSMENT_ONLY' ? 'selected' : '' }}>📄 Assessment Only
                            </option>
                            <option value="NO_DATA" {{ request('usage_status') == 'NO_DATA' ? 'selected' : '' }}>⬜ No Data
                            </option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <label class="form-label"><i class="bi bi-rulers me-1"></i>Area Status</label>
                        <select name="area_status" id="filterAreaStatus" class="form-select form-select-sm">
                            <option value="all" {{ request('area_status') == 'all' ? 'selected' : '' }}>All Status
                            </option>
                            <option value="MATCH" {{ request('area_status') == 'MATCH' ? 'selected' : '' }}>Match
                            </option>
                            <option value="VARIATION" {{ request('area_status') == 'VARIATION' ? 'selected' : '' }}>
                                Variation</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <label class="form-label"><i class="bi bi-hash me-1"></i>GIS ID</label>
                        <input type="text" name="gisid" id="filterGisid" class="form-control form-control-sm"
                            placeholder="Search GIS ID..." value="{{ request('gisid') }}">
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <label class="form-label"><i class="bi bi-sliders me-1"></i>Area Variation %</label>
                        <div class="d-flex gap-2">
                            <input type="number" name="var_min" id="filterVarMin" class="form-control form-control-sm"
                                placeholder="Min %" min="0" max="100" value="{{ request('var_min') }}">
                            <input type="number" name="var_max" id="filterVarMax" class="form-control form-control-sm"
                                placeholder="Max %" min="0" max="100" value="{{ request('var_max') }}">
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-filter">
                                <i class="bi bi-funnel me-1"></i> Apply
                            </button>
                            <a href="{{ route('variation.show', $ward->id) }}"
                                class="btn btn-outline-secondary btn-filter">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <label class="form-label"><i class="bi bi-eye me-1"></i>Per Page</label>
                        <select name="per_page" id="perPageSelect" class="form-select form-select-sm">
                            <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>All</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- TABLE -->
        <div class="table-container" id="tableContainer">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner"></div>
            </div>
            <div class="table-header">
                <h5><i class="bi bi-table me-2"></i>Data Variation Details</h5>
                <span class="text-muted small">
                    Showing {{ $pagination['from'] ?? 0 }} to {{ $pagination['to'] ?? 0 }} of
                    {{ $pagination['total'] ?? 0 }} records
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="variationTable">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>GIS ID</th>
                            <th>Building Usage</th>
                            <th>Assessment Usage</th>
                            <th>Usage Status</th>
                            <th>Building Area</th>
                            <th>Assessment Area</th>
                            <th>Area Variation</th>
                            <th>Area Status</th>
                            <th>Assessments</th>
                            <th style="width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @forelse($buildingVariations as $gisid => $variation)
                            @php
                                $buildingArea = $variation['building']['area'] ?? 0;
                                $buildingUsage = $variation['building']['usage'] ?? null;
                                $assessmentArea = $variation['assessment']['area'] ?? 0;
                                $assessmentUsage = $variation['assessment']['usage'] ?? null;
                                $assessmentCount = $variation['assessment']['count'] ?? 0;
                                $usageStatus = $variation['usage_comparison']['usage_status'] ?? 'NO_DATA';
                                $usageStatusLabel = $variation['usage_comparison']['usage_status_label'] ?? 'No Data';
                                $areaVariation = $variation['area_comparison']['area_variation'] ?? 0;
                                $areaStatus = $variation['area_comparison']['area_status'] ?? 'MATCH';
                                $variationPercentage = $variation['area_comparison']['variation_percentage'] ?? 0;
                                $allAssessmentUsages = $variation['assessment']['all_usages'] ?? [];
                                $hasMultiple = $variation['assessment']['has_multiple'] ?? false;
                                $floorCount = $variation['building']['details']['number_floor'] ?? 'N/A';
                                $basementCount = $variation['building']['details']['basement'] ?? 'N/A';
                                $assessmentTypeStatus =
                                    $variation['assessment']['details']['assessment_type_status'] ?? 'N/A';

                                $badgeClass = '';
                                $icon = '';
                                switch ($usageStatus) {
                                    case 'MATCH':
                                        $badgeClass = 'badge-match';
                                        $icon = 'bi-check-circle';
                                        break;
                                    case 'VARIATION':
                                        $badgeClass = 'badge-variation';
                                        $icon = 'bi-x-circle';
                                        break;
                                    case 'PARTIAL_MATCH':
                                        $badgeClass = 'badge-partial';
                                        $icon = 'bi-exclamation-triangle';
                                        break;
                                    case 'BUILDING_ONLY':
                                        $badgeClass = 'badge-building-only';
                                        $icon = 'bi-building';
                                        break;
                                    case 'ASSESSMENT_ONLY':
                                        $badgeClass = 'badge-assessment-only';
                                        $icon = 'bi-file-earmark-text';
                                        break;
                                    default:
                                        $badgeClass = 'badge-no-data';
                                        $icon = 'bi-dash-circle';
                                        break;
                                }

                                $isUsageVariation = $usageStatus === 'VARIATION';
                                $isPartialMatch = $usageStatus === 'PARTIAL_MATCH';
                                $isBuildingOnly = $usageStatus === 'BUILDING_ONLY';
                                $isAssessmentOnly = $usageStatus === 'ASSESSMENT_ONLY';
                                $isNoData = $usageStatus === 'NO_DATA';

                                $usageValueClass = $isUsageVariation
                                    ? 'mismatch'
                                    : ($isBuildingOnly || $isAssessmentOnly || $isNoData
                                        ? 'null-value'
                                        : 'match');

                                $assessmentDisplay = $assessmentUsage ?? 'N/A';
                                if ($isBuildingOnly) {
                                    $assessmentDisplay = '— (Not Assessed)';
                                }
                                if ($isNoData) {
                                    $assessmentDisplay = '— (No Data)';
                                }

                                $buildingDisplay = $buildingUsage ?? 'N/A';
                                if ($isAssessmentOnly) {
                                    $buildingDisplay = '— (Not Mapped)';
                                }
                                if ($isNoData) {
                                    $buildingDisplay = '— (No Data)';
                                }

                                $usageTooltip = !empty($allAssessmentUsages)
                                    ? implode(', ', $allAssessmentUsages)
                                    : 'No usages';
                            @endphp
                            <tr class="clickable-row" data-gisid="{{ $gisid }}" data-ward="{{ $ward->id }}">
                                <td>{{ $loop->iteration + ($pagination['current_page'] - 1) * $pagination['per_page'] }}
                                </td>
                                <td>
                                    <code>{{ $gisid }}</code>
                                    @if ($hasMultiple)
                                        <span class="badge bg-info ms-1" title="Multiple assessments">M</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="usage-detail-box" title="Building Usage">
                                        <span class="label">Usage:</span>
                                        <span class="value {{ $usageValueClass }}">{{ $buildingDisplay }}</span>
                                    </span>
                                    <div class="small text-muted mt-1">
                                        <span class="badge bg-light text-dark">F: {{ $floorCount }}</span>
                                        <span class="badge bg-light text-dark">B: {{ $basementCount }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="usage-detail-box" title="Assessment Usage">
                                        <span class="label">Usage:</span>
                                        <span class="value {{ $usageValueClass }}">{{ $assessmentDisplay }}</span>
                                    </span>
                                    @if ($isUsageVariation)
                                        <span class="badge badge-variation ms-1" title="Usage mismatch">Mismatch</span>
                                    @endif
                                    @if ($isPartialMatch)
                                        <span class="badge badge-partial ms-1" title="Partial match">Partial</span>
                                    @endif
                                    @if (!empty($allAssessmentUsages) && count($allAssessmentUsages) > 1)
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-list-ul"></i>
                                            <span
                                                title="All usages: {{ $usageTooltip }}">{{ count($allAssessmentUsages) }}
                                                usages</span>
                                        </div>
                                    @endif
                                    @if ($assessmentTypeStatus != 'N/A' && $assessmentTypeStatus != 'OTHER')
                                        <span
                                            class="badge badge-assessment-type {{ strtolower(str_replace(' ', '', $assessmentTypeStatus)) }}">
                                            {{ $assessmentTypeStatus }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $badgeClass }}" title="{{ $usageStatusLabel }}">
                                        <i class="{{ $icon }} me-1"></i>
                                        {{ $usageStatusLabel }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ number_format($buildingArea, 2) }}</span>
                                    <span class="text-muted small">sqft</span>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ number_format($assessmentArea, 2) }}</span>
                                    <span class="text-muted small">sqft</span>
                                </td>
                                <td
                                    class="{{ $areaVariation > 0 ? 'text-danger' : ($areaVariation < 0 ? 'text-success' : 'text-muted') }}">
                                    {{ $areaVariation > 0 ? '+' : '' }}{{ number_format($areaVariation, 2) }}
                                    <br>
                                    <small class="text-muted">{{ number_format($variationPercentage, 1) }}%</small>
                                </td>
                                <td>
                                    <span class="{{ $areaStatus == 'VARIATION' ? 'badge-variation' : 'badge-match' }}">
                                        {{ $areaStatus }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $assessmentCount == 0 ? 'bg-secondary' : 'bg-primary' }}">
                                        {{ $assessmentCount }}
                                    </span>
                                    @if ($assessmentCount > 0)
                                        <br>
                                        <small class="text-muted">assessments</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info"
                                            onclick="showDetails('{{ $gisid }}')" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a href="/data-variation/single-pdf/{{ $ward->id }}/{{ $gisid }}"
   class="btn btn-sm btn-outline-danger"
   title="Download PDF (FORM 2)"
   target="_blank">
    <i class="bi bi-file-earmark-pdf"></i>
</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h5>No Records Found</h5>
                                        <p>No variation data available for this ward with the applied filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if (($pagination['total'] ?? 0) > 0)
                <div class="pagination-container">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="text-muted small">
                            Showing {{ $pagination['from'] ?? 0 }} to {{ $pagination['to'] ?? 0 }} of
                            {{ $pagination['total'] ?? 0 }} entries
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm">
                                <li class="page-item {{ ($pagination['current_page'] ?? 1) <= 1 ? 'disabled' : '' }}">
                                    <a class="page-link"
                                        href="?page={{ ($pagination['current_page'] ?? 1) - 1 }}&per_page={{ $pagination['per_page'] ?? 20 }}&{{ http_build_query(request()->except(['page', 'per_page'])) }}">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                @php
                                    $currentPage = $pagination['current_page'] ?? 1;
                                    $lastPage = $pagination['last_page'] ?? 1;
                                    $start = max(1, $currentPage - 2);
                                    $end = min($lastPage, $currentPage + 2);
                                    if ($end - $start < 4) {
                                        if ($start == 1) {
                                            $end = min(5, $lastPage);
                                        } else {
                                            $start = max(1, $lastPage - 4);
                                        }
                                    }
                                @endphp
                                @if ($start > 1)
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?page=1&per_page={{ $pagination['per_page'] ?? 20 }}&{{ http_build_query(request()->except(['page', 'per_page'])) }}">1</a>
                                    </li>
                                    @if ($start > 2)
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    @endif
                                @endif
                                @for ($i = $start; $i <= $end; $i++)
                                    <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                        <a class="page-link"
                                            href="?page={{ $i }}&per_page={{ $pagination['per_page'] ?? 20 }}&{{ http_build_query(request()->except(['page', 'per_page'])) }}">
                                            {{ $i }}
                                        </a>
                                    </li>
                                @endfor
                                @if ($end < $lastPage)
                                    @if ($end < $lastPage - 1)
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    @endif
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?page={{ $lastPage }}&per_page={{ $pagination['per_page'] ?? 20 }}&{{ http_build_query(request()->except(['page', 'per_page'])) }}">
                                            {{ $lastPage }}
                                        </a>
                                    </li>
                                @endif
                                <li
                                    class="page-item {{ ($pagination['current_page'] ?? 1) >= ($pagination['last_page'] ?? 1) ? 'disabled' : '' }}">
                                    <a class="page-link"
                                        href="?page={{ ($pagination['current_page'] ?? 1) + 1 }}&per_page={{ $pagination['per_page'] ?? 20 }}&{{ http_build_query(request()->except(['page', 'per_page'])) }}">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- DETAIL MODAL -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Building Details - <span id="modalGisid"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-export btn-export-pdf" id="exportSinglePdfBtn">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Store data
        const wardId = {{ $ward->id }};
        let detailModal = null;

        $(document).ready(function() {
            detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

            // ─── PER PAGE CHANGE ───
            $('#perPageSelect').on('change', function() {
                $('#filterForm').submit();
            });

            // ─── SHOW DETAILS WITH LAZY LOADING ───
            // ─── SHOW DETAILS WITH LAZY LOADING ───
            window.showDetails = function(gisid) {
                $('#modalGisid').text(gisid);
                $('#modalBody').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading building details...</p>
        </div>
    `);
                detailModal.show();

                // FIXED: Use direct URL construction instead of route helper
                const url = `/data-variation/details/${wardId}/${gisid}`;

                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            renderDetails(response.data, gisid);
                        } else {
                            $('#modalBody').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Failed to load building details. Please try again.
                    </div>
                `);
                        }
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr);
                        $('#modalBody').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error loading building details. Status: ${xhr.status}
                </div>
            `);
                    }
                });
            };

            // ─── RENDER DETAILS ───
            function renderDetails(data, gisid) {
                // Update PDF button
                $('#exportSinglePdfBtn').attr('href',
                    "{{ route('data-variation.single-pdf', [$ward->id, '']) }}/" + gisid);

                let html = `
                    <div class="detail-modal-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6><i class="bi bi-building text-primary me-2"></i>Building Details</h6>
                                    <div class="detail-item">
                                        <span class="label">GIS ID</span>
                                        <span class="value"><code>${data.gisid}</code></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Area</span>
                                        <span class="value">${data.building.area.toFixed(2)} sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Usage</span>
                                        <span class="value">${data.building.usage || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Number of Floors</span>
                                        <span class="value">${data.building.details?.number_floor || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Basement</span>
                                        <span class="value">${data.building.details?.basement || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Percentage</span>
                                        <span class="value">${data.building.details?.percentage || 'N/A'}%</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Polygon Sqfeet</span>
                                        <span class="value">${data.building.details?.sqfeet?.toFixed(2) || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6><i class="bi bi-file-earmark-text text-success me-2"></i>Assessment Details</h6>
                                    <div class="detail-item">
                                        <span class="label">Area</span>
                                        <span class="value">${data.assessment.area.toFixed(2)} sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Usage</span>
                                        <span class="value">${data.assessment.usage || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Count</span>
                                        <span class="value">${data.assessment.count}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">All Usages</span>
                                        <span class="value">${data.assessment.all_usages.join(', ') || 'None'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Has Multiple</span>
                                        <span class="value">${data.assessment.has_multiple ? 'Yes' : 'No'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Assessment Type</span>
                                        <span class="value">${data.assessment.details?.assessment_type_status || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div class="detail-section ${data.area_comparison.area_status === 'VARIATION' ? 'warning' : 'success'}">
                                    <h6><i class="bi bi-arrows-expand text-cyan me-2"></i>Area Comparison</h6>
                                    <div class="detail-item">
                                        <span class="label">Status</span>
                                        <span class="value">
                                            <span class="${data.area_comparison.area_status === 'VARIATION' ? 'badge-variation' : 'badge-match'}">
                                                ${data.area_comparison.area_status}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Building Area</span>
                                        <span class="value">${data.area_comparison.building_area.toFixed(2)} sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Assessment Area</span>
                                        <span class="value">${data.area_comparison.assessment_area.toFixed(2)} sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Variation</span>
                                        <span class="value ${data.area_comparison.area_variation > 0 ? 'text-danger' : 'text-success'}">
                                            ${data.area_comparison.area_variation > 0 ? '+' : ''}${data.area_comparison.area_variation.toFixed(2)} sqft
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Variation %</span>
                                        <span class="value">${data.area_comparison.variation_percentage}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-section ${data.usage_comparison.usage_status === 'VARIATION' ? 'danger' : (data.usage_comparison.usage_status === 'PARTIAL_MATCH' ? 'warning' : 'success')}">
                                    <h6><i class="bi bi-tags text-amber me-2"></i>Usage Comparison</h6>
                                    <div class="detail-item">
                                        <span class="label">Status</span>
                                        <span class="value">
                                            <span class="${data.usage_comparison.usage_badge_class}">
                                                ${data.usage_comparison.usage_status_label}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Building Usage</span>
                                        <span class="value">${data.usage_comparison.building_usage || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Assessment Usage</span>
                                        <span class="value">${data.usage_comparison.assessment_usage || 'N/A'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">All Assessment Usages</span>
                                        <span class="value">${data.usage_comparison.all_assessment_usages.join(', ') || 'None'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Has Mismatch</span>
                                        <span class="value">${data.usage_comparison.has_mismatch ? 'Yes' : 'No'}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Has Partial Match</span>
                                        <span class="value">${data.usage_comparison.has_partial_match ? 'Yes' : 'No'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="detail-section">
                                    <h6><i class="bi bi-list-ul me-2"></i>Assessment Points</h6>
                                    ${data.assessment.details?.points && data.assessment.details.points.length > 0 ? `
                                            <div class="assessment-points-list">
                                                ${data.assessment.details.points.map(p => `
                                                <div class="point-item">
                                                    <span class="fw-bold">${p.assessment}</span>
                                                    <span class="text-muted">|</span>
                                                    ${p.point_area.toFixed(2)} sqft
                                                    <span class="text-muted">|</span>
                                                    Usage: ${p.qcusage || p.bill_usage || 'N/A'}
                                                    <span class="text-muted">|</span>
                                                    Type: ${p.assessment_type || 'N/A'}
                                                    ${p.mis_data ? `<span class="badge bg-info ms-1">MIS: ${p.mis_data.plot_area || 'N/A'}</span>` : ''}
                                                </div>
                                            `).join('')}
                                            </div>
                                        ` : `
                                            <div class="text-muted">No assessment points available</div>
                                        `}
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="detail-section">
                                    <h6><i class="bi bi-database text-secondary me-2"></i>Raw Data</h6>
                                    <pre style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e5e7eb;font-size:0.75rem;max-height:200px;overflow:auto;">${JSON.stringify(data.raw_data, null, 2)}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#modalBody').html(html);
            }

            // ─── EXPORT FILTERED ───
            $('#exportFilteredBtn').on('click', function() {
                const form = document.getElementById('filterForm');
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                Swal.fire({
                    title: 'Exporting...',
                    text: 'Please wait while we generate your file',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                window.location.href = "{{ route('data-variation.export', $ward->id) }}?" + params
                    .toString();

                setTimeout(() => {
                    Swal.close();
                }, 2000);
            });

            console.log('✅ Data Variation page ready with pagination');
            console.log(`📊 Total buildings: {{ $pagination['total'] ?? 0 }}`);
        });
    </script>
@endpush
