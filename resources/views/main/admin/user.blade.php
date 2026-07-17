@extends('layouts.office')

@section('title', 'Users')
@section('page_title', 'Users')

@section('content')

    <!-- Flash Message Container -->
    <div id="flashMessageContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"></div>

    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">Users</h1>
            <p class="ol-page-sub">Manage all municipal Users</p>
        </div>
    </div>

    <div class="data-toolbar d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="data-search">
            <input type="text" id="userSearch" class="form-control" placeholder="Search by User name">
        </div>
        <div class="d-flex align-items-center gap-2">

            <select id="corporationFilter" class="form-select app-select">
                <option value="">All Corporations</option>
                @foreach ($corporations as $corporation)
                    <option value="{{ $corporation->id }}">
                        {{ $corporation->name }}
                    </option>
                @endforeach
            </select>

            <select id="zoneFilter" class="form-select app-select">
                <option value="">All Zones</option>
                @foreach ($corporations as $corporation)
                    @foreach ($corporation->zones as $zone)
                        <option value="{{ $zone->id }}" data-corporation="{{ $corporation->id }}">
                            {{ $corporation->name }} ({{ $zone->zone_name }})
                        </option>
                    @endforeach
                @endforeach
            </select>

            <select id="wardFilter" class="form-select app-select">
                <option value="">All Wards</option>
                @foreach ($corporations as $corporation)
                    @foreach ($corporation->zones as $zone)
                        @foreach ($zone->wards as $ward)
                            <option value="{{ $ward->id }}" data-zone="{{ $zone->id }}">
                                Ward {{ $ward->ward_no }}
                            </option>
                        @endforeach
                    @endforeach
                @endforeach
            </select>

            <select id="statusFilter" class="form-select app-select">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
            </select>
            <button class="btn btn-success app-btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" id="adduserBtn">
                <i class="bi bi-building-add"></i>
                <span>Add User</span>
            </button>
        </div>
    </div>

    {{-- loading spinner --}}
    <div id="loadingSpinner" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="card-grid" id="usersGrid">
        <!-- Users will be loaded here -->
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4" style="display: none;">
        <nav>
            <ul class="pagination" id="paginationList">
                <!-- Pagination will be loaded here -->
            </ul>
        </nav>
    </div>

    <!-- User Modal (Add/Edit) -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">
                        <i class="bi bi-person-plus me-2"></i>
                        Add User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="userForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="userId">

                    <div class="modal-body" style="max-height:70vh;overflow-y:auto;">

                        <!-- Basic Information -->
                        <div class="card mb-3">
                            <div class="card-header">Basic Information</div>
                            <div class="card-body">
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" id="f_name" class="form-control">
                                        <div class="invalid-feedback" id="error-name"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" id="f_email" class="form-control">
                                        <div class="invalid-feedback" id="error-email"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" id="f_phone" class="form-control">
                                        <div class="invalid-feedback" id="error-phone"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Password
                                            <small class="text-muted" id="passwordHint">(leave blank to keep current)</small>
                                        </label>
                                        <input type="password" name="password" id="f_password" class="form-control">
                                        <div class="invalid-feedback" id="error-password"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <select name="role" id="f_role" class="form-select">
                                            <option value="">Select Role</option>
                                            <option value="admin">Admin</option>
                                            <option value="commissioner">Commissioner</option>
                                            <option value="dc">DC</option>
                                            <option value="ac">AC</option>
                                            <option value="aro">ARO</option>
                                            <option value="bc">BC</option>
                                            <option value="teamleader">Team Leader</option>
                                            <option value="surveyor">Surveyor</option>
                                        </select>
                                        <div class="invalid-feedback" id="error-role"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Corporation</label>
                                        <select name="corporation_id" id="f_corporation_id" class="form-select">
                                            <option value="">Select Corporation</option>
                                            @foreach ($corporations as $corporation)
                                                <option value="{{ $corporation->id }}">
                                                    {{ $corporation->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="error-corporation_id"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Zone</label>
                                        <select name="zone_id" id="f_zone_id" class="form-select">
                                            <option value="">Select Zone</option>
                                        </select>
                                        <div class="invalid-feedback" id="error-zone_id"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Ward</label>
                                        <select name="ward_id" id="f_ward_id" class="form-select">
                                            <option value="">Select Ward</option>
                                        </select>
                                        <div class="invalid-feedback" id="error-ward_id"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Profile Image</label>
                                        <input type="file" name="profile" id="f_profile" class="form-control">
                                        <div class="invalid-feedback" id="error-profile"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <select name="is_active" id="f_is_active" class="form-select">
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                        <div class="invalid-feedback" id="error-is_active"></div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit" class="btn btn-success" id="userSaveBtn">
                            Save User
                        </button>
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
                    <div
                        style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="bi bi-trash3" style="font-size:22px;color:#ef4444;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Delete User?</h6>
                    <p class="text-muted" style="font-size:0.8rem;" id="deleteUserName"></p>
                    <input type="hidden" id="deleteUserId">
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">Delete</button>
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
                                from {
                                    transform: translateX(100%);
                                    opacity: 0;
                                }
                                to {
                                    transform: translateX(0);
                                    opacity: 1;
                                }
                            }
                            @keyframes slideOutRight {
                                from {
                                    transform: translateX(0);
                                    opacity: 1;
                                }
                                to {
                                    transform: translateX(100%);
                                    opacity: 0;
                                }
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
            let userModal = null;

            if (document.getElementById('userModal')) {
                userModal = new bootstrap.Modal(document.getElementById('userModal'));
            }

            // ─── FIXED: Filter dropdowns with proper data ───
            const allCorporations = @json($corporations);

            // Helper functions
            function fillSelect($select, options, placeholder) {
                $select.empty().append(`<option value="">${placeholder}</option>`);
                $.each(options, function(_, o) {
                    $select.append(`<option value="${o.value}">${escapeHtml(o.text)}</option>`);
                });
            }

            function zonesFor(corporationId) {
                const corp = allCorporations.find(c => c.id == corporationId);
                if (!corp || !corp.zones) return [];
                return corp.zones.map(z => ({ value: z.id, text: z.zone_name }));
            }

            function wardsFor(zoneId) {
                for (const corp of allCorporations) {
                    const zone = (corp.zones || []).find(z => z.id == zoneId);
                    if (zone) {
                        return (zone.wards || []).map(w => ({ value: w.id, text: 'Ward ' + w.ward_no }));
                    }
                }
                return [];
            }

            // ─── FIXED: Zone filter change handler ───
            $('#corporationFilter').on('change', function() {
                const corporationId = $(this).val();

                // Reset zone and ward filters
                $('#zoneFilter').val('');
                $('#wardFilter').val('');

                // Show/hide zone options based on corporation
                if (corporationId) {
                    $('#zoneFilter option').each(function() {
                        const corpId = $(this).data('corporation');
                        if (corpId) {
                            $(this).toggle(corpId == corporationId);
                        }
                    });
                } else {
                    $('#zoneFilter option').show();
                }

                // Show/hide ward options based on corporation (via zone)
                updateWardFilterByZone();

                loadUsers(1);
            });

            // ─── FIXED: Ward filter update based on zone ───
            function updateWardFilterByZone() {
                const zoneId = $('#zoneFilter').val();
                const corporationId = $('#corporationFilter').val();

                if (zoneId) {
                    $('#wardFilter option').each(function() {
                        const wardZoneId = $(this).data('zone');
                        if (wardZoneId) {
                            $(this).toggle(wardZoneId == zoneId);
                        }
                    });
                } else if (corporationId) {
                    // Show wards only for selected corporation
                    const corpZones = zonesFor(corporationId);
                    const zoneIds = corpZones.map(z => z.value);
                    $('#wardFilter option').each(function() {
                        const wardZoneId = $(this).data('zone');
                        if (wardZoneId) {
                            $(this).toggle(zoneIds.includes(wardZoneId));
                        }
                    });
                } else {
                    $('#wardFilter option').show();
                }
            }

            // ─── FIXED: Zone filter change updates ward filter ───
            $('#zoneFilter').on('change', function() {
                updateWardFilterByZone();
                loadUsers(1);
            });

            // ─── FIXED: Ward filter change ───
            $('#wardFilter').on('change', function() {
                loadUsers(1);
            });

            // ─── FIXED: Status filter ───
            $('#statusFilter').on('change', function() {
                loadUsers(1);
            });

            function loadUsers(page = 1) {
                if (isLoading) return;

                isLoading = true;
                $('#loadingSpinner').show();

                let search = $("#userSearch").val();
                let status = $("#statusFilter").val();
                let corporation = $("#corporationFilter").val();
                let zone = $("#zoneFilter").val();
                let ward = $("#wardFilter").val();

                $.ajax({
                    url: "{{ route('admin.users.list') }}",
                    type: "GET",
                    data: {
                        name: search,
                        status: status,
                        corporation_id: corporation,
                        zone_id: zone,
                        ward_id: ward,
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
                        }
                        isLoading = false;
                        $('#loadingSpinner').hide();
                        $('html, body').animate({
                            scrollTop: 0
                        }, 300);
                    },
                    error: function(xhr) {
                        showFlashMessage('Failed to load users', 'error');
                        isLoading = false;
                        $('#loadingSpinner').hide();
                    }
                });
            }

            function renderCards(users) {
                if (!users || users.length === 0) {
                    $('#usersGrid').html(`
                        <div class="text-center py-5 w-100">
                            <i class="bi bi-people fs-1 text-muted"></i>
                            <h5 class="mt-2">No Users Found</h5>
                        </div>
                    `);
                    return;
                }

                const assetBase = "{{ asset('') }}";
                let html = '';

                $.each(users, function(index, user) {
                    let imageUrl = user.profile ?
                        assetBase + user.profile :
                        assetBase + 'images/default-user.png';

                    let badgeClass = user.is_active == 1 || user.is_active === true ?
                        'bg-success' :
                        'bg-danger';

                    let statusText = (user.is_active == 1 || user.is_active === true) ?
                        'Active' :
                        'Inactive';

                    html += `
                    <div class="acard">
                        <div class="acard-img-wrap">
                            <img src="${imageUrl}"
                                onerror="this.src='${assetBase}images/default-user.png'">
                            <div class="acard-overlay"></div>
                            <span class="acard-tag">
                                ${escapeHtml(user.role ?? 'User')}
                            </span>
                        </div>
                        <div class="acard-body">
                            <div class="acard-meta">
                                <i class="bi bi-envelope"></i>
                                ${escapeHtml(user.email)}
                            </div>
                            <h3 class="acard-title">
                                ${escapeHtml(user.name)}
                            </h3>
                            <p class="acard-desc">
                                <i class="bi bi-telephone"></i>
                                ${escapeHtml(user.phone ?? '-')}
                            </p>
                            <div class="acard-footer">
                                <span class="acard-author">
                                    ID: ${user.id}
                                </span>
                                <span class="badge ${badgeClass}">
                                    ${statusText}
                                </span>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-warning btn-sm flex-fill edit-btn"
                                        data-id="${user.id}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm flex-fill delete-btn"
                                        data-id="${user.id}"
                                        data-name="${escapeHtml(user.name)}">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    `;
                });

                $('#usersGrid').html(html);
            }

            function renderPagination(pagination) {
                if (!pagination || pagination.last_page <= 1) {
                    $('#paginationContainer').hide();
                    $('#paginationInfo').remove();
                    return;
                }

                $('#paginationContainer').show();
                let html = '';

                if (pagination.current_page > 1) {
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">&laquo; Previous</a></li>`;
                } else {
                    html +=
                        `<li class="page-item disabled"><a class="page-link" href="#">&laquo; Previous</a></li>`;
                }

                if (pagination.current_page > 3) {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (pagination.current_page > 4) {
                        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
                    }
                }

                for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.last_page,
                        pagination.current_page + 2); i++) {
                    if (i === pagination.current_page) {
                        html +=
                            `<li class="page-item active"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    } else {
                        html +=
                            `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    }
                }

                if (pagination.current_page < pagination.last_page - 2) {
                    if (pagination.current_page < pagination.last_page - 3) {
                        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
                    }
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`;
                }

                if (pagination.current_page < pagination.last_page) {
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next &raquo;</a></li>`;
                } else {
                    html += `<li class="page-item disabled"><a class="page-link" href="#">Next &raquo;</a></li>`;
                }

                $('#paginationList').html(html);

                let start = pagination.from || ((pagination.current_page - 1) * pagination.per_page + 1);
                let end = pagination.to || Math.min(pagination.current_page * pagination.per_page, pagination.total);
                let infoHtml = `<div class="text-center text-muted mt-3">
                            Showing ${start} to ${end} of ${pagination.total} users
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
                $('#userForm')[0].reset();
                $('#userId').val('');
                $('#userModalTitle').html('<i class="bi bi-person-plus me-2"></i> Add User');
                $('#passwordHint').show();
                $('.invalid-feedback').empty();
                $('.form-control, .form-select').removeClass('is-invalid');
                $('#f_zone_id').empty().append('<option value="">Select Zone</option>');
                $('#f_ward_id').empty().append('<option value="">Select Ward</option>');
            }

            // ─── FIXED: Search input ───
            $('#userSearch').on('input', function() {
                loadUsers(1);
            });

            // Pagination click
            $(document).on('click', '#paginationList .page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page) loadUsers(page);
            });

            // Add User button
            $('#adduserBtn').on('click', function() {
                resetForm();
            });

            // Edit functionality
            $(document).on('click', '.edit-btn', function() {
                const id = $(this).data('id');

                $.ajax({
                    url: '/admin/users/' + id,
                    type: 'GET',
                    success: function(res) {
                        if (res.status && res.data) {
                            const u = res.data;

                            resetForm();

                            $('#userModalTitle').html('<i class="bi bi-pencil-square me-2"></i> Edit User');
                            $('#userId').val(u.id);

                            $('#f_name').val(u.name);
                            $('#f_email').val(u.email);
                            $('#f_phone').val(u.phone);
                            $('#f_role').val(u.role);
                            $('#f_is_active').val(u.is_active ? '1' : '0');

                            if (u.corporation_id) {
                                $('#f_corporation_id').val(u.corporation_id);

                                // Trigger change to load zones
                                $('#f_corporation_id').trigger('change');

                                // Wait for zones to load then set values
                                setTimeout(function() {
                                    if (u.zone_id) {
                                        $('#f_zone_id').val(u.zone_id).trigger('change');

                                        setTimeout(function() {
                                            if (u.ward_id) {
                                                $('#f_ward_id').val(u.ward_id);
                                            }
                                        }, 100);
                                    }
                                }, 100);
                            }

                            userModal.show();
                        }
                    },
                    error: function() {
                        showFlashMessage('Failed to load user data', 'error');
                    }
                });
            });

            // Delete functionality
            $(document).on('click', '.delete-btn', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');

                $('#deleteUserId').val(id);
                $('#deleteUserName').text(name);
                $('#deleteModal').modal('show');
            });

            $('#confirmDeleteBtn').on('click', function() {
                let id = $('#deleteUserId').val();

                $.ajax({
                    url: "/admin/users/" + id,
                    type: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.status) {
                            showFlashMessage('User deleted successfully', 'success');
                            $('#deleteModal').modal('hide');
                            loadUsers(currentPage);
                        } else {
                            showFlashMessage(response.message || 'Failed to delete user', 'error');
                        }
                    },
                    error: function(xhr) {
                        showFlashMessage('Failed to delete user', 'error');
                    }
                });
            });

            // ─── FIXED: Form submission ───
            $('#userForm').on('submit', function(e) {
                e.preventDefault();

                let formData = new FormData(this);
                let userId = $('#userId').val();
                let url = userId ? "/admin/users/" + userId : "{{ route('admin.users.store') }}";

                if (userId) {
                    formData.append('_method', 'PUT');
                }

                $('#userSaveBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Saving...');

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
                            showFlashMessage(response.message || 'User saved successfully', 'success');
                            userModal.hide();
                            loadUsers(currentPage);
                            resetForm();
                        } else {
                            showFlashMessage(response.message || 'Failed to save user', 'error');
                        }
                        $('#userSaveBtn').prop('disabled', false).html('Save User');
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
                        $('#userSaveBtn').prop('disabled', false).html('Save User');
                    }
                });
            });

            // ─── FIXED: Corporation change in form - load zones ───
            $('#f_corporation_id').on('change', function() {
                let corporationId = $(this).val();
                let zones = zonesFor(corporationId);

                fillSelect($('#f_zone_id'), zones, 'Select Zone');
                $('#f_ward_id').empty().append('<option value="">Select Ward</option>');
            });

            // ─── FIXED: Zone change in form - load wards ───
            $('#f_zone_id').on('change', function() {
                let zoneId = $(this).val();
                let wards = wardsFor(zoneId);

                fillSelect($('#f_ward_id'), wards, 'Select Ward');
            });

            // ─── FIXED: Initialize zone/ward filters on page load ───
            function initializeFilters() {
                // Show only zones for selected corporation
                const corpId = $('#corporationFilter').val();
                if (corpId) {
                    $('#zoneFilter option').each(function() {
                        const cId = $(this).data('corporation');
                        if (cId) {
                            $(this).toggle(cId == corpId);
                        }
                    });
                }
                updateWardFilterByZone();
            }
            initializeFilters();

            // Initial load
            loadUsers(1);
        });
    </script>
@endpush
