@extends('layouts.office')

@section('title', 'Teams')
@section('page_title', 'Teams')

@section('content')

    <!-- Flash Message Container -->
    <div id="flashMessageContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"></div>

    <div class="ol-page-header">
        <div>
            <h1 class="ol-page-title">Teams</h1>
            <p class="ol-page-sub">Manage Team Leaders and their Surveyors</p>
        </div>
    </div>

    <div class="data-toolbar d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="data-search">
            <input type="text" id="teamSearch" class="form-control" placeholder="Search team leaders...">
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
            </select>

            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary app-btn-sm">
                <i class="bi bi-people"></i>
                <span>Manage Users</span>
            </a>
        </div>
    </div>

    {{-- loading spinner --}}
    <div id="loadingSpinner" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="card-grid" id="teamsGrid">
        <!-- Teams will be loaded here -->
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4" style="display: none;">
        <nav>
            <ul class="pagination" id="paginationList">
                <!-- Pagination will be loaded here -->
            </ul>
        </nav>
    </div>

    <!-- Team Detail Modal -->
    <div class="modal fade" id="teamDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-people-fill me-2"></i>
                        Team Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="teamDetailBody">
                    <!-- Team details will be loaded here -->
                </div>
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
                    <h6 class="fw-bold mb-1">Delete Team?</h6>
                    <p class="text-muted" style="font-size:0.8rem;" id="deleteTeamName"></p>
                    <p class="text-muted" style="font-size:0.8rem;">This will unassign all surveyors from this team leader.</p>
                    <input type="hidden" id="deleteTeamId">
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
    let currentPage = 1;
    let totalPages = 1;
    let isLoading = false;

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

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function loadTeams(page = 1) {
        if (isLoading) return;

        isLoading = true;
        $('#loadingSpinner').show();

        let search = $("#teamSearch").val();
        let status = $("#statusFilter").val();
        let corporation = $("#corporationFilter").val();
        let zone = $("#zoneFilter").val();
        let ward = $("#wardFilter").val();

        $.ajax({
            url: "{{ route('admin.teams.list') }}",
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
                showFlashMessage('Failed to load teams', 'error');
                isLoading = false;
                $('#loadingSpinner').hide();
            }
        });
    }

    function renderCards(teams) {
        if (!teams || teams.length === 0) {
            $('#teamsGrid').html(`
                <div class="text-center py-5 w-100">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <h5 class="mt-2">No Teams Found</h5>
                    <p class="text-muted">Create team leaders by assigning users the "Team Leader" role.</p>
                </div>
            `);
            return;
        }

        let html = '';

        $.each(teams, function(index, team) {
            let badgeClass = team.is_active ? 'bg-success' : 'bg-danger';
            let statusText = team.is_active ? 'Active' : 'Inactive';
            let surveyorCount = team.surveyors ? team.surveyors.length : 0;

            // Get zone and ward names
            let zoneName = team.zone ? team.zone.zone_name : 'N/A';
            let wardName = team.ward ? 'Ward ' + team.ward.ward_no : 'N/A';
            let corporationName = team.corporation ? team.corporation.name : 'N/A';

            html += `
                <div class="acard">
                    <div class="acard-img-wrap" style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9);">
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:48px;color:#2e7d32;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="acard-overlay"></div>
                        <span class="acard-tag" style="background: #2e7d32;">
                            <i class="bi bi-person-badge me-1"></i>
                            ${surveyorCount} Surveyors
                        </span>
                    </div>
                    <div class="acard-body">
                        <div class="acard-meta">
                            <i class="bi bi-person"></i>
                            Team Leader: ${escapeHtml(team.name)}
                        </div>
                        <h3 class="acard-title" style="font-size: 1rem;">
                            <i class="bi bi-building me-1"></i>
                            ${escapeHtml(corporationName)}
                        </h3>
                        <p class="acard-desc" style="font-size: 0.85rem;">
                            <i class="bi bi-geo-alt"></i>
                            Zone: ${escapeHtml(zoneName)} | Ward: ${escapeHtml(wardName)}
                        </p>
                        <div class="acard-footer">
                            <span class="acard-author">
                                <i class="bi bi-envelope"></i>
                                ${escapeHtml(team.email)}
                            </span>
                            <span class="badge ${badgeClass}">
                                ${statusText}
                            </span>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-info btn-sm flex-fill view-team-btn"
                                    data-id="${team.id}">
                                <i class="bi bi-eye"></i> View Team
                            </button>
                            <button class="btn btn-danger btn-sm flex-fill delete-btn"
                                    data-id="${team.id}"
                                    data-name="${escapeHtml(team.name)}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#teamsGrid').html(html);
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
                    Showing ${start} to ${end} of ${pagination.total} teams
                </div>`;

        if ($('#paginationInfo').length === 0) {
            $('#paginationContainer').after(`<div id="paginationInfo">${infoHtml}</div>`);
        } else {
            $('#paginationInfo').html(infoHtml);
        }
    }

    // Filter change handlers
    $('#teamSearch').on('input', function() {
        loadTeams(1);
    });

    $('#statusFilter').on('change', function() {
        loadTeams(1);
    });

    $('#corporationFilter').on('change', function() {
        let corporationId = $(this).val();
        $('#zoneFilter').val('');
        $('#wardFilter').val('');
        loadTeams(1);
    });

    $('#zoneFilter').on('change', function() {
        let zoneId = $(this).val();
        $('#wardFilter').val('');
        loadTeams(1);
    });

    $('#wardFilter').on('change', function() {
        loadTeams(1);
    });

    // Pagination click
    $(document).on('click', '#paginationList .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) loadTeams(page);
    });

    // View Team Details
    $(document).on('click', '.view-team-btn', function() {
        const id = $(this).data('id');

        $.ajax({
            url: '/admin/teams/' + id,
            type: 'GET',
            success: function(response) {
                if (response.status && response.data) {
                    const team = response.data;
                    let surveyorsHtml = '';

                    if (team.surveyors && team.surveyors.length > 0) {
                        surveyorsHtml = `
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        $.each(team.surveyors, function(index, surveyor) {
                            let statusBadge = surveyor.is_active ? 'bg-success' : 'bg-danger';
                            let statusText = surveyor.is_active ? 'Active' : 'Inactive';

                            surveyorsHtml += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${escapeHtml(surveyor.name)}</td>
                                    <td>${escapeHtml(surveyor.email)}</td>
                                    <td>${escapeHtml(surveyor.phone || '-')}</td>
                                    <td><span class="badge ${statusBadge}">${statusText}</span></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm remove-surveyor-btn"
                                                data-team-id="${team.id}"
                                                data-surveyor-id="${surveyor.id}"
                                                data-surveyor-name="${escapeHtml(surveyor.name)}">
                                            <i class="bi bi-person-dash"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });

                        surveyorsHtml += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        surveyorsHtml = `
                            <div class="text-center py-4">
                                <i class="bi bi-person fs-1 text-muted"></i>
                                <p class="text-muted">No surveyors assigned to this team yet.</p>
                            </div>
                        `;
                    }

                    // Get available surveyors for assignment
                    $.ajax({
                        url: '/admin/teams/' + team.id + '/available-surveyors',
                        type: 'GET',
                        success: function(availResponse) {
                            let assignHtml = '';
                            if (availResponse.status && availResponse.data && availResponse.data.length > 0) {
                                assignHtml = `
                                    <div class="mt-4 pt-3 border-top">
                                        <h6 class="fw-bold mb-2">Assign Surveyors</h6>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <select id="availableSurveyorSelect" class="form-select" style="flex: 1;">
                                                <option value="">Select a surveyor...</option>
                        `;

                                $.each(availResponse.data, function(index, surveyor) {
                                    assignHtml += `<option value="${surveyor.id}">${escapeHtml(surveyor.name)} (${escapeHtml(surveyor.email)})</option>`;
                                });

                                assignHtml += `
                                            </select>
                                            <button class="btn btn-success assign-surveyor-btn" data-team-id="${team.id}">
                                                <i class="bi bi-person-plus"></i> Assign
                                            </button>
                                        </div>
                                    </div>
                                `;
                            } else {
                                assignHtml = `
                                    <div class="mt-4 pt-3 border-top">
                                        <p class="text-muted mb-0"><i class="bi bi-info-circle"></i> No available surveyors to assign.</p>
                                    </div>
                                `;
                            }

                            let detailHtml = `
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">Team Information</h6>
                                                <p class="mb-1"><strong>Team Leader:</strong> ${escapeHtml(team.name)}</p>
                                                <p class="mb-1"><strong>Email:</strong> ${escapeHtml(team.email)}</p>
                                                <p class="mb-1"><strong>Corporation:</strong> ${escapeHtml(team.corporation ? team.corporation.name : 'N/A')}</p>
                                                <p class="mb-1"><strong>Zone:</strong> ${escapeHtml(team.zone ? team.zone.zone_name : 'N/A')}</p>
                                                <p class="mb-0"><strong>Ward:</strong> ${escapeHtml(team.ward ? 'Ward ' + team.ward.ward_no : 'N/A')}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">Team Statistics</h6>
                                                <p class="mb-1"><strong>Total Surveyors:</strong> ${team.surveyors ? team.surveyors.length : 0}</p>
                                                <p class="mb-0"><strong>Status:</strong> <span class="badge ${team.is_active ? 'bg-success' : 'bg-danger'}">${team.is_active ? 'Active' : 'Inactive'}</span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Team Members</h6>
                                            </div>
                                            <div class="card-body">
                                                ${surveyorsHtml}
                                                ${assignHtml}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;

                            $('#teamDetailBody').html(detailHtml);
                            $('#teamDetailModal').modal('show');
                        }
                    });
                }
            },
            error: function() {
                showFlashMessage('Failed to load team details', 'error');
            }
        });
    });

    // Assign Surveyor
    $(document).on('click', '.assign-surveyor-btn', function() {
        const teamId = $(this).data('team-id');
        const surveyorId = $('#availableSurveyorSelect').val();

        if (!surveyorId) {
            showFlashMessage('Please select a surveyor to assign', 'error');
            return;
        }

        $.ajax({
            url: '/admin/teams/' + teamId + '/assign-surveyor',
            type: 'POST',
            data: {
                surveyor_id: surveyorId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showFlashMessage(response.message || 'Surveyor assigned successfully', 'success');
                    $('#teamDetailModal').modal('hide');
                    loadTeams(currentPage);
                } else {
                    showFlashMessage(response.message || 'Failed to assign surveyor', 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let message = Object.values(errors).flat().join(', ');
                    showFlashMessage(message, 'error');
                } else {
                    showFlashMessage('Failed to assign surveyor', 'error');
                }
            }
        });
    });

    // Remove Surveyor
    $(document).on('click', '.remove-surveyor-btn', function() {
        const teamId = $(this).data('team-id');
        const surveyorId = $(this).data('surveyor-id');
        const surveyorName = $(this).data('surveyor-name');

        if (confirm('Remove ' + surveyorName + ' from this team?')) {
            $.ajax({
                url: '/admin/teams/' + teamId + '/remove-surveyor',
                type: 'POST',
                data: {
                    surveyor_id: surveyorId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showFlashMessage(response.message || 'Surveyor removed successfully', 'success');
                        $('#teamDetailModal').modal('hide');
                        loadTeams(currentPage);
                    } else {
                        showFlashMessage(response.message || 'Failed to remove surveyor', 'error');
                    }
                },
                error: function(xhr) {
                    showFlashMessage('Failed to remove surveyor', 'error');
                }
            });
        }
    });

    // Delete Team
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');

        $('#deleteTeamId').val(id);
        $('#deleteTeamName').text('Team Leader: ' + name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        let id = $('#deleteTeamId').val();

        $.ajax({
            url: "/admin/teams/" + id,
            type: "DELETE",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success || response.status) {
                    showFlashMessage('Team deleted successfully', 'success');
                    $('#deleteModal').modal('hide');
                    loadTeams(currentPage);
                } else {
                    showFlashMessage(response.message || 'Failed to delete team', 'error');
                }
            },
            error: function(xhr) {
                showFlashMessage('Failed to delete team', 'error');
            }
        });
    });

    // Initial load
    loadTeams(1);
});
</script>
@endpush
