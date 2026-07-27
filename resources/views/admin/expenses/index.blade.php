{{-- resources/views/admin/expenses/index.blade.php --}}

@extends('layouts.office')

@section('title', 'Expenses')
@section('page_title', 'Expense Management')

@section('content')
    <!-- Flash Message Container -->
    <div id="flashMessageContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"></div>

    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">Expenses</h1>
            <p class="ol-page-sub">Manage all expenses</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4" id="statisticsCards">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Expenses</h6>
                    <h2 class="mb-0" id="totalExpenses">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Amount</h6>
                    <h2 class="mb-0" id="totalAmount">₹0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted">Average Amount</h6>
                    <h2 class="mb-0" id="averageAmount">₹0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted">Pending</h6>
                    <h2 class="mb-0" id="pendingCount">0</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="data-toolbar d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div class="data-search">
            <input type="text" id="expenseSearch" class="form-control" placeholder="Search expenses...">
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Date Range Filter -->
            <div class="d-flex gap-2">
                <input type="date" id="dateFrom" class="form-control" placeholder="From">
                <input type="date" id="dateTo" class="form-control" placeholder="To">
            </div>

            <select id="categoryFilter" class="form-select app-select">
                <option value="">All Categories</option>
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <select id="statusFilter" class="form-select app-select">
                <option value="">All Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <select id="paymentMethodFilter" class="form-select app-select">
                <option value="">All Payment Methods</option>
                @foreach ($paymentMethods as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <select id="userFilter" class="form-select app-select">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>

            <div class="btn-group">
                <button class="btn btn-success app-btn-sm" data-bs-toggle="modal" data-bs-target="#expenseModal" id="addExpenseBtn">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Expense</span>
                </button>
                <button class="btn btn-info app-btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i>
                    Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportExpenses('excel')">Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportExpenses('pdf')">PDF</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Loading Spinner --}}
    <div id="loadingSpinner" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Expenses Grid -->
    <div class="card-grid" id="expensesGrid">
        <!-- Expenses will be loaded here -->
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4" style="display: none;">
        <nav>
            <ul class="pagination" id="paginationList">
                <!-- Pagination will be loaded here -->
            </ul>
        </nav>
    </div>

    <!-- Expense Modal (Add/Edit) -->
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="expenseModalTitle">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Expense
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="expenseForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="expenseId">

                    <div class="modal-body" style="max-height:70vh;overflow-y:auto;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="f_title" class="form-control" placeholder="Enter expense title">
                                <div class="invalid-feedback" id="error-title"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="f_amount" class="form-control" placeholder="Enter amount" step="0.01">
                                <div class="invalid-feedback" id="error-amount"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" id="f_category" class="form-select">
                                    <option value="">Select Category</option>
                                    @foreach ($categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-category"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" id="f_expense_date" class="form-control">
                                <div class="invalid-feedback" id="error-expense_date"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" id="f_payment_method" class="form-select">
                                    <option value="">Select Payment Method</option>
                                    @foreach ($paymentMethods as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-payment_method"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="f_status" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <div class="invalid-feedback" id="error-status"></div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="f_description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                                <div class="invalid-feedback" id="error-description"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Receipt</label>
                                <input type="file" name="receipt" id="f_receipt" class="form-control" accept="image/*,.pdf">
                                <small class="text-muted">Accepted: JPG, PNG, PDF (Max 2MB)</small>
                                <div id="currentReceipt" style="display: none;">
                                    <div class="mt-2">
                                        <span class="badge bg-info">Current Receipt</span>
                                        <a href="#" id="receiptLink" target="_blank" class="d-block mt-1">View Receipt</a>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="error-receipt"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" id="f_notes" class="form-control" rows="3" placeholder="Additional notes"></textarea>
                                <div class="invalid-feedback" id="error-notes"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" id="expenseSaveBtn">Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="bi bi-trash3" style="font-size:22px;color:#ef4444;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Delete Expense?</h6>
                    <p class="text-muted" style="font-size:0.8rem;" id="deleteExpenseTitle"></p>
                    <input type="hidden" id="deleteExpenseId">
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Expense Modal -->
    <div class="modal fade" id="viewExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Expense Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="expenseDetails">
                    <!-- Expense details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Flash Message Function
    function showFlashMessage(message, type = 'success') {
        const container = $('#flashMessageContainer');
        const icon = type === 'success' ? '✓' : '✗';
        const bgColor = type === 'success' ? '#10b981' : '#ef4444';

        const toast = $(`
            <div class="flash-toast" style="background: white; border-left: 4px solid ${bgColor};
                        border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                        margin-bottom: 12px; padding: 16px 20px; min-width: 320px;
                        animation: slideInRight 0.3s ease-out;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 24px; height: 24px; border-radius: 50%; background: ${bgColor};
                                display: flex; align-items: center; justify-content: center; color: white;
                                font-weight: bold; font-size: 14px;">
                        ${icon}
                    </div>
                    <div style="flex: 1; color: #1f2937; font-size: 14px; font-weight: 500;">
                        ${message}
                    </div>
                    <button type="button" class="close-toast" style="background: none; border: none;
                                font-size: 20px; cursor: pointer; color: #9ca3af;">&times;</button>
                </div>
            </div>
        `);

        container.append(toast);

        if (!$('#flashStyles').length) {
            $('head').append(`
                <style id="flashStyles">
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOutRight {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                </style>
            `);
        }

        setTimeout(() => {
            toast.css('animation', 'slideOutRight 0.3s ease-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);

        toast.find('.close-toast').on('click', function() {
            toast.css('animation', 'slideOutRight 0.3s ease-out');
            setTimeout(() => toast.remove(), 300);
        });
    }

    let currentPage = 1;
    let totalPages = 1;
    let isLoading = false;
    let expenseModal = null;

    if (document.getElementById('expenseModal')) {
        expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    }

    // Load expenses with filters
    function loadExpenses(page = 1) {
        if (isLoading) return;

        isLoading = true;
        $('#loadingSpinner').show();

        let search = $("#expenseSearch").val();
        let category = $("#categoryFilter").val();
        let status = $("#statusFilter").val();
        let paymentMethod = $("#paymentMethodFilter").val();
        let userId = $("#userFilter").val();
        let dateFrom = $("#dateFrom").val();
        let dateTo = $("#dateTo").val();

        $.ajax({
            url: "{{ route('admin.expenses.list') }}",
            type: "GET",
            data: {
                title: search,
                category: category,
                status: status,
                payment_method: paymentMethod,
                user_id: userId,
                date_from: dateFrom,
                date_to: dateTo,
                page: page
            },
            success: function(response) {
                if (response.status && response.data) {
                    const paginator = response.data;
                    renderCards(paginator.data);
                    renderPagination({
                        current_page: paginator.current_page,
                        last_page: paginator.last_page,
                        per_page: paginator.per_page,
                        total: paginator.total,
                        from: paginator.from,
                        to: paginator.to
                    });
                    currentPage = paginator.current_page;
                    totalPages = paginator.last_page;
                    loadStatistics();
                }
                isLoading = false;
                $('#loadingSpinner').hide();
            },
            error: function(xhr) {
                showFlashMessage('Failed to load expenses', 'error');
                isLoading = false;
                $('#loadingSpinner').hide();
            }
        });
    }

    // Load statistics
    function loadStatistics() {
        let dateFrom = $("#dateFrom").val();
        let dateTo = $("#dateTo").val();

        $.ajax({
            url: "{{ route('admin.expenses.statistics') }}",
            type: "GET",
            data: {
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                if (response.status && response.data) {
                    const stats = response.data;
                    $('#totalExpenses').text(stats.total_expenses);
                    $('#totalAmount').text('₹' + parseFloat(stats.total_amount).toFixed(2));
                    $('#averageAmount').text('₹' + parseFloat(stats.average_amount).toFixed(2));

                    const pending = stats.status_breakdown.find(s => s.status === 'pending');
                    $('#pendingCount').text(pending ? pending.count : 0);
                }
            }
        });
    }

    // Render expense cards
    function renderCards(expenses) {
        if (!expenses || expenses.length === 0) {
            $('#expensesGrid').html(`
                <div class="text-center py-5 w-100">
                    <i class="bi bi-receipt fs-1 text-muted"></i>
                    <h5 class="mt-2">No Expenses Found</h5>
                    <p class="text-muted">Try adjusting your filters</p>
                </div>
            `);
            return;
        }

        const assetBase = "{{ asset('') }}";
        let html = '';

        $.each(expenses, function(index, expense) {
            let statusBadgeClass = {
                'pending': 'bg-warning',
                'approved': 'bg-success',
                'rejected': 'bg-danger'
            }[expense.status] || 'bg-secondary';

            let date = new Date(expense.expense_date);
            let formattedDate = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            html += `
                <div class="acard">
                    <div class="acard-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge ${statusBadgeClass}">${expense.status}</span>
                            <span class="text-muted small">${formattedDate}</span>
                        </div>
                        <h5 class="acard-title mb-1">${escapeHtml(expense.title)}</h5>
                        <div class="acard-meta mb-2">
                            <i class="bi bi-tag"></i>
                            ${escapeHtml(expense.category)}
                            ${expense.payment_method ? `• <i class="bi bi-credit-card"></i> ${escapeHtml(expense.payment_method)}` : ''}
                        </div>
                        <p class="acard-desc mb-2">
                            ${expense.description ? escapeHtml(expense.description.substring(0, 60)) + (expense.description.length > 60 ? '...' : '') : 'No description'}
                        </p>
                        <div class="acard-footer">
                            <span class="acard-author">
                                <i class="bi bi-person"></i>
                                ${escapeHtml(expense.user ? expense.user.name : 'N/A')}
                            </span>
                            <h5 class="mb-0 text-success">₹${parseFloat(expense.amount).toFixed(2)}</h5>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-info btn-sm flex-fill view-btn"
                                    data-id="${expense.id}">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm flex-fill edit-btn"
                                    data-id="${expense.id}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm flex-fill delete-btn"
                                    data-id="${expense.id}"
                                    data-title="${escapeHtml(expense.title)}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#expensesGrid').html(html);
    }

    // Render pagination
    function renderPagination(pagination) {
        if (!pagination || pagination.last_page <= 1) {
            $('#paginationContainer').hide();
            $('#paginationInfo').remove();
            return;
        }

        $('#paginationContainer').show();
        let html = '';

        if (pagination.current_page > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">&laquo; Previous</a></li>`;
        } else {
            html += `<li class="page-item disabled"><a class="page-link" href="#">&laquo; Previous</a></li>`;
        }

        if (pagination.current_page > 3) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (pagination.current_page > 4) {
                html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
            }
        }

        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.last_page, pagination.current_page + 2); i++) {
            if (i === pagination.current_page) {
                html += `<li class="page-item active"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }

        if (pagination.current_page < pagination.last_page - 2) {
            if (pagination.current_page < pagination.last_page - 3) {
                html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`;
        }

        if (pagination.current_page < pagination.last_page) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next &raquo;</a></li>`;
        } else {
            html += `<li class="page-item disabled"><a class="page-link" href="#">Next &raquo;</a></li>`;
        }

        $('#paginationList').html(html);

        let start = pagination.from || ((pagination.current_page - 1) * pagination.per_page + 1);
        let end = pagination.to || Math.min(pagination.current_page * pagination.per_page, pagination.total);
        let infoHtml = `<div class="text-center text-muted mt-3">
                    Showing ${start} to ${end} of ${pagination.total} expenses
                </div>`;

        if ($('#paginationInfo').length === 0) {
            $('#paginationContainer').after(`<div id="paginationInfo">${infoHtml}</div>`);
        } else {
            $('#paginationInfo').html(infoHtml);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function resetForm() {
        $('#expenseForm')[0].reset();
        $('#expenseId').val('');
        $('#expenseModalTitle').html('<i class="bi bi-plus-circle me-2"></i> Add Expense');
        $('.invalid-feedback').empty();
        $('.form-control, .form-select').removeClass('is-invalid');
        $('#currentReceipt').hide();
    }

    // Filter change events
    $('#expenseSearch').on('input', function() { loadExpenses(1); });
    $('#categoryFilter').on('change', function() { loadExpenses(1); });
    $('#statusFilter').on('change', function() { loadExpenses(1); });
    $('#paymentMethodFilter').on('change', function() { loadExpenses(1); });
    $('#userFilter').on('change', function() { loadExpenses(1); });
    $('#dateFrom, #dateTo').on('change', function() { loadExpenses(1); });

    // Pagination click
    $(document).on('click', '#paginationList .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) loadExpenses(page);
    });

    // Add Expense button
    $('#addExpenseBtn').on('click', function() {
        resetForm();
        $('#f_expense_date').val(new Date().toISOString().split('T')[0]);
    });

    // View Expense
    $(document).on('click', '.view-btn', function() {
        const id = $(this).data('id');

        $.ajax({
            url: '/admin/expenses/' + id,
            type: 'GET',
            success: function(res) {
                if (res.status && res.data) {
                    const expense = res.data;
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Title:</strong> ${escapeHtml(expense.title)}</p>
                                <p><strong>Amount:</strong> ₹${parseFloat(expense.amount).toFixed(2)}</p>
                                <p><strong>Category:</strong> ${escapeHtml(expense.category)}</p>
                                <p><strong>Date:</strong> ${new Date(expense.expense_date).toLocaleDateString()}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Status:</strong> <span class="badge bg-${expense.status === 'pending' ? 'warning' : expense.status === 'approved' ? 'success' : 'danger'}">${expense.status}</span></p>
                                <p><strong>Payment Method:</strong> ${expense.payment_method || 'N/A'}</p>
                                <p><strong>Created By:</strong> ${escapeHtml(expense.user ? expense.user.name : 'N/A')}</p>
                                <p><strong>Created At:</strong> ${new Date(expense.created_at).toLocaleString()}</p>
                            </div>
                            ${expense.description ? `<div class="col-12"><p><strong>Description:</strong><br>${escapeHtml(expense.description)}</p></div>` : ''}
                            ${expense.notes ? `<div class="col-12"><p><strong>Notes:</strong><br>${escapeHtml(expense.notes)}</p></div>` : ''}
                            ${expense.receipt ? `
                                <div class="col-12">
                                    <p><strong>Receipt:</strong></p>
                                    <a href="/storage/${expense.receipt}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark-pdf"></i> View Receipt
                                    </a>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    $('#expenseDetails').html(html);
                    $('#viewExpenseModal').modal('show');
                }
            }
        });
    });

    // Edit Expense
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');

        $.ajax({
            url: '/admin/expenses/' + id,
            type: 'GET',
            success: function(res) {
                if (res.status && res.data) {
                    const e = res.data;
                    resetForm();

                    $('#expenseModalTitle').html('<i class="bi bi-pencil-square me-2"></i> Edit Expense');
                    $('#expenseId').val(e.id);
                    $('#f_title').val(e.title);
                    $('#f_amount').val(e.amount);
                    $('#f_category').val(e.category);
                    $('#f_expense_date').val(e.expense_date);
                    $('#f_payment_method').val(e.payment_method);
                    $('#f_status').val(e.status);
                    $('#f_description').val(e.description);
                    $('#f_notes').val(e.notes);

                    if (e.receipt) {
                        $('#currentReceipt').show();
                        $('#receiptLink').attr('href', '/storage/' + e.receipt);
                    }

                    expenseModal.show();
                }
            },
            error: function() {
                showFlashMessage('Failed to load expense data', 'error');
            }
        });
    });

    // Delete Expense
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let title = $(this).data('title');

        $('#deleteExpenseId').val(id);
        $('#deleteExpenseTitle').text(title);
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        let id = $('#deleteExpenseId').val();

        $.ajax({
            url: "/admin/expenses/" + id,
            type: "DELETE",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.status) {
                    showFlashMessage('Expense deleted successfully', 'success');
                    $('#deleteModal').modal('hide');
                    loadExpenses(currentPage);
                } else {
                    showFlashMessage(response.message || 'Failed to delete expense', 'error');
                }
            },
            error: function(xhr) {
                showFlashMessage('Failed to delete expense', 'error');
            }
        });
    });

    // Form submission
    $('#expenseForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        let expenseId = $('#expenseId').val();
        let url = expenseId ? "/admin/expenses/" + expenseId : "{{ route('admin.expenses.store') }}";

        if (expenseId) {
            formData.append('_method', 'PUT');
        }

        $('#expenseSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    showFlashMessage(response.message || 'Expense saved successfully', 'success');
                    expenseModal.hide();
                    loadExpenses(currentPage);
                    resetForm();
                } else {
                    showFlashMessage(response.message || 'Failed to save expense', 'error');
                }
                $('#expenseSaveBtn').prop('disabled', false).html('Save Expense');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $('.invalid-feedback').empty();
                    $('.form-control, .form-select').removeClass('is-invalid');

                    $.each(errors, function(field, messages) {
                        let fieldId = '#error-' + field;
                        let inputField = '[name="' + field + '"]';

                        $(inputField).addClass('is-invalid');
                        $(fieldId).html(messages[0]);
                    });
                    showFlashMessage('Please fix the validation errors', 'error');
                } else {
                    showFlashMessage('An error occurred. Please try again.', 'error');
                }
                $('#expenseSaveBtn').prop('disabled', false).html('Save Expense');
            }
        });
    });

    // Export function
    window.exportExpenses = function(type) {
        let dateFrom = $("#dateFrom").val();
        let dateTo = $("#dateTo").val();
        let category = $("#categoryFilter").val();
        let status = $("#statusFilter").val();

        let url = "{{ route('admin.expenses.export') }}" +
            '?type=' + type +
            (dateFrom ? '&date_from=' + dateFrom : '') +
            (dateTo ? '&date_to=' + dateTo : '') +
            (category ? '&category=' + category : '') +
            (status ? '&status=' + status : '');

        window.location.href = url;
    };

    // Initial load
    loadExpenses(1);
});
</script>
@endpush
