<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Revenue Department')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Bootstrap 5 CSS + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    {{-- Add to your main layout's styles section --}}

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <style>


    </style>
    <!-- In the head section -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v9.2.4/ol.css">

    <!-- Before closing body tag -->
    <script src="https://cdn.jsdelivr.net/npm/ol@v9.2.4/dist/ol.js"></script>
    @stack('styles')
</head>

<body>

    {{-- Mobile backdrop overlay --}}
    <div class="ol-overlay" id="olOverlay" onclick="toggleSidebar()"></div>

    {{-- ═══════════════════════════════
         SIDEBAR
    ═══════════════════════════════ --}}
    <aside class="ol-sidebar" id="olSidebar">

        {{-- Brand --}}
        <div class="ol-brand">
            <div class="ol-brand-icon">
                <i class="bi bi-building-check"></i>
            </div>
            <div class="ol-brand-text">
                <div class="ol-brand-sub">SIRS</div>
                <div class="ol-brand-sub-text">Spatial Revenue <br> Intelligent System</div>

            </div>
        </div>

        {{-- Navigation --}}
        <nav class="ol-nav">

            {{-- ================= OVERVIEW ================= --}}
            <div class="ol-nav-section">Main Menu</div>

            @auth

                {{-- Dashboard --}}
                <a href="{{ route(Auth::user()->role . '.dashboard') }}" class="ol-nav-item">
                    <i class="bi bi-house-door"></i>
                    <span class="ol-nav-label">Dashboard</span>
                </a>

                {{-- ================= SURVEYOR ================= --}}
                @if (Auth::user()->role == 'surveyor')
                    <a href="{{ route('surveyor.status') }}" class="ol-nav-item">
                        <i class="bi bi-clipboard-check"></i>
                        <span class="ol-nav-label">Survey Status</span>
                    </a>

                    <a href="{{ route('teamleader.map') }}" class="ol-nav-item">
                        <i class="bi bi-map"></i>
                        <span class="ol-nav-label">Map View</span>
                    </a>

                    {{-- ================= ADMIN ================= --}}
                @elseif(Auth::user()->role == 'admin')
                    <a href="{{ route('admin.corporations.index') }}" class="ol-nav-item">
                        <i class="bi bi-building"></i>
                        <span class="ol-nav-label">Corporation Info</span>
                    </a>

                    <a href="{{ route('admin.zones.index') }}" class="ol-nav-item">
                        <i class="bi bi-hexagon"></i>
                        <span class="ol-nav-label">Zones</span>
                    </a>

                    <a href="{{ route('admin.wards.index') }}" class="ol-nav-item">
                        <i class="bi bi-grid-3x3-gap"></i>
                        <span class="ol-nav-label">Wards</span>
                    </a>

                    <a href="{{ route('teamleader.map') }}" class="ol-nav-item">
                        <i class="bi bi-map"></i>
                        <span class="ol-nav-label">Map View</span>
                    </a>

                    {{-- ================= REVENUE ================= --}}
                    <div class="ol-nav-section">Revenue Management</div>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-clipboard-check"></i>
                        <span class="ol-nav-label">Assessments</span>
                    </a>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-cash-stack"></i>
                        <span class="ol-nav-label">Revenue Collection</span>
                    </a>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-house"></i>
                        <span class="ol-nav-label">Properties</span>
                    </a>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-person-x"></i>
                        <span class="ol-nav-label">Defaulters</span>
                    </a>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-file-earmark-text"></i>
                        <span class="ol-nav-label">Reports</span>
                    </a>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-bar-chart-line"></i>
                        <span class="ol-nav-label">Analytics</span>
                    </a>

                    {{-- ================= SYSTEM ================= --}}
                    <div class="ol-nav-section">System</div>

                    <a href="{{ route('admin.users.index') }}" class="ol-nav-item">
                        <i class="bi bi-people"></i>
                        <span class="ol-nav-label">User Management</span>
                    </a>

                    <a href="{{ route('admin.teams.index') }}" class="ol-nav-item">
                        <i class="bi bi-diagram-3"></i>
                        <span class="ol-nav-label">Teams</span>
                    </a>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-journal-text"></i>
                        <span class="ol-nav-label">System Logs</span>
                    </a>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-gear"></i>
                        <span class="ol-nav-label">Settings</span>
                    </a>

                    {{-- ================= TEAM LEADER ================= --}}
                @elseif(Auth::user()->role == 'teamleader')
                    <a href="{{ route('teamleader.map') }}" class="ol-nav-item">
                        <i class="bi bi-map"></i>
                        <span class="ol-nav-label">Map View</span>
                    </a>

                    {{-- ================= COMMISSIONER ================= --}}
                @elseif(Auth::user()->role == 'commissioner')
                    <a href="{{ route('teamleader.map') }}" class="ol-nav-item">
                        <i class="bi bi-map"></i>
                        <span class="ol-nav-label">Map View</span>
                    </a>

                    <a href="{{ route('commissioner.corporations.index') }}" class="ol-nav-item">
                        <i class="bi bi-building"></i>
                        <span class="ol-nav-label">Corporation Info</span>
                    </a>

                    <a href="{{ route('commissioner.zones.index') }}" class="ol-nav-item">
                        <i class="bi bi-hexagon"></i>
                        <span class="ol-nav-label">Zones</span>
                    </a>

                    <a href="{{ route('commissioner.wards.index') }}" class="ol-nav-item">
                        <i class="bi bi-grid-3x3-gap"></i>
                        <span class="ol-nav-label">Wards</span>
                    </a>

                    <a href="#" class="ol-nav-item">
                        <i class="bi bi-bar-chart-line"></i>
                        <span class="ol-nav-label">Analytics</span>
                    </a>
                @endif

            @endauth

        </nav>

        {{-- User footer --}}
        <div class="ol-sidebar-footer">
            <div class="ol-user-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
            </div>
            <div class="ol-sidebar-footer-text">
                <div class="ol-user-name">{{ auth()->user()->name ?? 'Officer' }}</div>
                <div class="ol-user-role">{{ auth()->user()->role ?? 'Staff' }}</div>
            </div>
        </div>

    </aside>

    {{-- ═══════════════════════════════
         TOP HEADER
    ═══════════════════════════════ --}}
    <header class="ol-header" id="olHeader">

        <button class="ol-header-toggle" id="olToggleBtn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <nav class="ol-breadcrumb" aria-label="Breadcrumb">

            <i class="bi bi-chevron-right"></i>
            <span class="page-title">{{ ucfirst(auth()->user()->role) }} GIS Dashboard</span>
        </nav>

        <div class="ol-header-actions">

            {{-- Search --}}
            <button class="ol-header-btn" title="Search" data-bs-toggle="modal" data-bs-target="#searchModal">
                <i class="bi bi-search"></i>
            </button>

            {{-- Notifications --}}
            <button class="ol-header-btn" title="Notifications" data-bs-toggle="offcanvas"
                data-bs-target="#notifOffcanvas">
                <i class="bi bi-bell"></i>
                <span class="badge-dot"></span>
            </button>

            {{-- Help --}}
            <button class="ol-header-btn" title="Help">
                <i class="bi bi-question-circle"></i>
            </button>

            {{-- User avatar dropdown --}}
            <div class="dropdown">
                <div class="ol-header-avatar" data-bs-toggle="dropdown" aria-expanded="false" role="button"
                    tabindex="0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border"
                    style="min-width:200px; border-radius:12px; font-size:0.82rem; margin-top:8px;">
                    <li>
                        <div class="px-3 py-2 border-bottom">
                            <div style="font-weight:600; color:#0a2e1a;">{{ auth()->user()->name ?? 'Officer Name' }}
                            </div>
                            <div style="font-size:0.72rem; color:#9ca3af;">
                                {{ auth()->user()->email ?? 'officer@revenue.tn.gov.in' }}</div>
                        </div>
                    </li>
                    <li><a class="dropdown-item py-2" href="{{ route('profile') }}">
                            <i class="bi bi-person me-2 text-muted"></i>My Profile</a>
                    </li>
                    <li><a class="dropdown-item py-2" href="#">
                            <i class="bi bi-gear me-2 text-muted"></i>Settings</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    {{-- ═══════════════════════════════
         CUSTOM FLASH MESSAGES (Top-Right)
    ═══════════════════════════════ --}}
    <div id="flashMessageContainer"></div>

    {{-- ═══════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════ --}}
    <main class="ol-main" id="olMain">
        @yield('content')
    </main>

    {{-- ═══════════════════════════════
         SEARCH MODAL
    ═══════════════════════════════ --}}
    <div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
            <div class="modal-content"
                style="border-radius:16px; border:none; box-shadow:0 24px 48px rgba(0,0,0,0.12);">
                <div class="modal-body p-0">
                    <div class="d-flex align-items-center px-4 py-3 border-bottom gap-2">
                        <i class="bi bi-search" style="color:#9ca3af; font-size:16px;"></i>
                        <input type="text" class="form-control border-0 shadow-none p-0"
                            placeholder="Search taxpayers, properties, payments…" style="font-size:0.9rem;"
                            id="globalSearch" autocomplete="off">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            style="font-size:11px;"></button>
                    </div>
                    <div class="px-4 py-3" id="searchResults">
                        <p class="text-muted mb-0" style="font-size:0.8rem;">Start typing to search…</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════
         NOTIFICATIONS OFFCANVAS
    ═══════════════════════════════ --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="notifOffcanvas"
        style="width:340px; border-left:1px solid #e5e7eb;">
        <div class="offcanvas-header border-bottom" style="padding:1rem 1.2rem;">
            <h6 class="offcanvas-title fw-bold" style="color:#0a2e1a; font-size:0.9rem;">Notifications</h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            @stack('notifications')
            <div class="px-4 py-5 text-center">
                <i class="bi bi-bell-slash" style="font-size:36px; color:#d1d5db;"></i>
                <p class="mt-2 mb-0" style="font-size:0.8rem; color:#9ca3af;">No new notifications</p>
            </div>
        </div>
    </div>

    {{-- Custom JS --}}
    <script src="{{ asset('js/app.js') }}"></script>

    <script>
        // ── Sidebar toggle ──
        function toggleSidebar() {
            const sidebar = document.getElementById('olSidebar');
            const header = document.getElementById('olHeader');
            const main = document.getElementById('olMain');
            const overlay = document.getElementById('olOverlay');
            const isMobile = window.innerWidth <= 1100; // matches your CSS breakpoint

            if (isMobile) {
                // Mobile: slide in/out
                const isOpen = sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('visible', isOpen);
                // Prevent body scroll when sidebar open
                document.body.style.overflow = isOpen ? 'hidden' : '';
            } else {
                // Desktop: collapse to icon-only
                const isCollapsed = sidebar.classList.toggle('collapsed');
                header.classList.toggle('sidebar-collapsed', isCollapsed);
                main.classList.toggle('sidebar-collapsed', isCollapsed);
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            }
        }

        // Close sidebar when overlay is clicked
        document.getElementById('olOverlay').addEventListener('click', function() {
            document.getElementById('olSidebar').classList.remove('mobile-open');
            this.classList.remove('visible');
            document.body.style.overflow = '';
        });

        // Close sidebar on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('olSidebar');
                const overlay = document.getElementById('olOverlay');
                if (sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('visible');
                    document.body.style.overflow = '';
                }
            }
        });

        // Restore desktop collapse state on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth > 1100) {
                const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (wasCollapsed) {
                    document.getElementById('olSidebar').classList.add('collapsed');
                    document.getElementById('olHeader').classList.add('sidebar-collapsed');
                    document.getElementById('olMain').classList.add('sidebar-collapsed');
                }
            }
        });

        // Custom flash message function (top-right)
        function showFlashMessage(message, type = 'success') {
            const container = document.getElementById('flashMessageContainer');

            const config = {
                success: {
                    icon: 'bi-check-circle-fill',
                    bg: '#10b981',
                    border: '#059669'
                },
                error: {
                    icon: 'bi-exclamation-circle-fill',
                    bg: '#dc2626',
                    border: '#b91c1c'
                }
            };

            const currentConfig = config[type] || config.success;

            const flashDiv = document.createElement('div');
            flashDiv.className = 'custom-flash-message';
            flashDiv.innerHTML = `
                <div class="d-flex align-items-center gap-2 p-3 rounded shadow-lg text-white"
                     style="background: ${currentConfig.bg}; border-left: 4px solid ${currentConfig.border};">
                    <i class="${currentConfig.icon}" style="font-size: 18px;"></i>
                    <div style="flex: 1; font-size: 13px; line-height: 1.4;">${message}</div>
                    <button type="button" class="btn-close btn-close-white" style="font-size: 10px;" onclick="this.closest('.custom-flash-message').remove()"></button>
                </div>
            `;

            container.appendChild(flashDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (flashDiv && flashDiv.parentNode) {
                    flashDiv.remove();
                }
            }, 5000);
        }
    </script>

    @stack('scripts')
</body>

</html>
