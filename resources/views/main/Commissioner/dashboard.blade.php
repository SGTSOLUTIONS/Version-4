@extends('layouts.office')

@section('title', 'Commissioner Dashboard')
@section('page_title', 'Commissioner Dashboard')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet">
    <style>
        /* ══════════════════════════════════════════════
                   DESIGN TOKENS — Government Revenue Portal
                   ══════════════════════════════════════════════ */
        :root {
            --font-display: 'Merriweather', Georgia, serif;
            --font-body: 'Inter', 'Segoe UI', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', 'Courier New', monospace;

            --gov-green: #0f6b47;
            --gov-green-dark: #0a4530;
            --gov-green-light: #1a8a5a;
            --gov-green-tint: #eaf4ef;
            --gov-gold: #a9741a;
            --gov-gold-tint: #faf3e6;

            --ink-900: #0e2019;
            --ink-700: #38473f;
            --ink-500: #6b7972;
            --ink-300: #c7d0cb;

            --bg-page: #f0f4f2;
            --surface: #ffffff;
            --border: #dfe4e1;
            --border-strong: #c7d0cb;

            --status-blue: #1d4ed8;
            --status-red: #b91c1c;
            --status-gold: #a9741a;
            --status-purple: #5b21b6;
            --status-teal: #0e7c72;

            --shadow-sm: 0 2px 8px rgba(10, 69, 48, 0.08);
            --shadow-md: 0 4px 16px rgba(10, 69, 48, 0.12);
            --shadow-lg: 0 8px 32px rgba(10, 69, 48, 0.16);
            --shadow-xl: 0 12px 48px rgba(10, 69, 48, 0.20);
            --shadow-glow: 0 0 40px rgba(15, 107, 71, 0.15);
        }

        body {
            background: var(--bg-page);
        }

        /* ══════════════════════════════════════════════
                   OFFICIAL LETTERHEAD BAR
                   ══════════════════════════════════════════════ */
        .gov-letterhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: linear-gradient(135deg, var(--gov-green-dark), var(--gov-green), #1a7a52, var(--gov-green-light));
            color: #fff;
            border-radius: 14px;
            padding: 1.2rem 1.8rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            min-height: 110px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg), var(--shadow-glow);
            transition: all 0.4s ease;
        }

        .gov-letterhead:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl), var(--shadow-glow);
        }

        /* Image on LEFT side with LOW OPACITY */
        .gov-letterhead::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 45%;
            height: 100%;
            background-image: url("{{ asset('city-banner.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.12;
            z-index: 0;
            border-radius: 0 14px 14px 0;
        }

        /* Decorative shine effect */
        .gov-letterhead::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 60%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.03), transparent);
            transform: rotate(25deg);
            z-index: 0;
        }

        /* All content on TOP of the image */
        .gov-letterhead .identity,
        .gov-letterhead .meta {
            position: relative;
            z-index: 1;
        }

        .gov-letterhead .identity {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .gov-letterhead .seal {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(4px);
            box-shadow: 0 0 20px rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .gov-letterhead .seal:hover {
            transform: scale(1.05) rotate(5deg);
            border-color: #fff;
        }

        .gov-letterhead .org-name {
            font-family: var(--font-display);
            font-weight: 900;
            font-size: 1.6rem;
            line-height: 1.2;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2);
            letter-spacing: 0.5px;
        }

        .gov-letterhead .org-sub {
            font-family: var(--font-body);
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.1rem;
            font-weight: 600;
            text-shadow: 0 1px 10px rgba(0,0,0,0.1);
        }

        .gov-letterhead .meta {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-family: var(--font-body);
            margin-left: auto;
        }

        .gov-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(4px);
            transition: all 0.3s ease;
        }

        .gov-status-chip:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .gov-status-chip .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #6ee7b7;
            box-shadow: 0 0 0 4px rgba(110, 231, 183, 0.25);
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.8); }
        }

        .gov-export-btn {
            background: #fff;
            color: var(--gov-green-dark);
            border: none;
            padding: 0.6rem 1.4rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.78rem;
            font-family: var(--font-body);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .gov-export-btn:hover {
            background: var(--gov-gold-tint);
            color: var(--gov-green-dark);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        /* ══════════════════════════════════════════════
                   BREADCRUMB + PAGE TITLE
                   ══════════════════════════════════════════════ */
        .gov-breadcrumb {
            font-family: var(--font-body);
            font-size: 0.75rem;
            color: var(--ink-500);
            margin-bottom: 0.5rem;
            letter-spacing: 0.03em;
            font-weight: 500;
        }

        .gov-breadcrumb a {
            color: var(--ink-500);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .gov-breadcrumb a:hover {
            color: var(--gov-green);
        }

        .gov-breadcrumb .sep {
            margin: 0 0.4rem;
            opacity: 0.4;
        }

        .gov-breadcrumb .current {
            color: var(--gov-green-dark);
            font-weight: 700;
        }

        .gov-page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.8rem;
            padding-bottom: 1.2rem;
            border-bottom: 3px solid var(--gov-green);
            position: relative;
        }

        .gov-page-head::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--gov-gold), transparent);
        }

        .gov-page-title {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 900;
            color: var(--ink-900);
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .gov-page-title span {
            color: var(--gov-green);
        }

        .gov-page-sub {
            font-family: var(--font-body);
            color: var(--gov-green-dark);
            margin: 0.15rem 0 0 0;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .gov-page-date {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--ink-500);
            text-align: right;
            font-weight: 600;
        }

        /* ══════════════════════════════════════════════
                   SECTION EYEBROWS
                   ══════════════════════════════════════════════ */
        .gov-section {
            margin-bottom: 2rem;
        }

        .gov-eyebrow {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.9rem;
        }

        .gov-eyebrow .label {
            font-family: var(--font-body);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--gov-green-dark);
            white-space: nowrap;
            background: var(--gov-green-tint);
            padding: 0.3rem 1rem;
            border-radius: 50px;
            border: 1px solid rgba(15, 107, 71, 0.15);
        }

        .gov-eyebrow .rule {
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, var(--border-strong), transparent);
        }

        .gov-eyebrow .rule.gold {
            background: linear-gradient(90deg, var(--gov-gold), transparent);
        }

        /* ══════════════════════════════════════════════
                   HIERARCHY LEDGER
                   ══════════════════════════════════════════════ */
        .ledger-flow {
            display: flex;
            align-items: stretch;
            background: linear-gradient(180deg, #ffffff 0%, #f8fffb 100%);
            border: 1px solid rgba(15, 107, 71, 0.15);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.4s ease;
            position: relative;
        }

        .ledger-flow:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg), var(--shadow-glow);
        }

        .ledger-flow::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gov-green-dark), var(--gov-green), #37b24d, #9be15d);
        }

        .ledger-step {
            flex: 1;
            min-width: 130px;
            padding: 1rem 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            position: relative;
            border-right: 1px solid var(--border);
            transition: all 0.3s ease;
            background: transparent;
        }

        .ledger-step:hover {
            background: rgba(15, 107, 71, 0.03);
        }

        .ledger-step:last-child {
            border-right: none;
        }

        .ledger-step .step-no {
            font-family: var(--font-mono);
            font-size: 0.6rem;
            font-weight: 700;
            color: var(--gov-gold);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .ledger-step .step-label {
            font-family: var(--font-body);
            font-size: 0.82rem;
            color: green;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .ledger-step .step-value {
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--gov-green-dark);
            line-height: 1.1;
        }

        .ledger-step .step-value .currency {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--gov-green);
        }

        /* ══════════════════════════════════════════════
                   KPI CARDS - ENHANCED
                   ══════════════════════════════════════════════ */
        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-left: 4px solid var(--gov-green);
            border-radius: 12px;
            padding: 1.1rem 1.3rem;
            height: 100%;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle at top right, rgba(15, 107, 71, 0.05), transparent);
            border-radius: 50%;
        }

        .kpi-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: var(--shadow-lg);
            border-color: var(--gov-green);
        }

        .kpi-card.accent-blue {
            border-left-color: var(--status-blue);
        }
        .kpi-card.accent-gold {
            border-left-color: var(--status-gold);
        }
        .kpi-card.accent-red {
            border-left-color: var(--status-red);
        }
        .kpi-card.accent-purple {
            border-left-color: var(--status-purple);
        }
        .kpi-card.accent-teal {
            border-left-color: var(--status-teal);
        }

        .kpi-card .kpi-label {
            font-family: var(--font-body);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ink-500);
            font-weight: 700;
        }

        .kpi-card .kpi-value {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--ink-900);
            margin-top: 0.15rem;
            letter-spacing: -0.5px;
        }

        .kpi-card .kpi-icon {
            float: right;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gov-green-tint);
            color: var(--gov-green);
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .kpi-card:hover .kpi-icon {
            transform: scale(1.1) rotate(5deg);
        }

        /* ══════════════════════════════════════════════
                   OFFICIAL CARD - ENHANCED
                   ══════════════════════════════════════════════ */
        .gov-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s ease;
            overflow: hidden;
        }

        .gov-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .gov-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.3rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 0.5rem;
            background: linear-gradient(180deg, #fafbfa, var(--surface));
            border-radius: 14px 14px 0 0;
        }

        .gov-card-title {
            font-family: var(--font-body);
            font-weight: 800;
            color: var(--ink-900);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .gov-card-title i {
            color: var(--gov-green);
            font-size: 1.2rem;
        }

        .gov-card-meta {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--ink-500);
            font-weight: 600;
            background: var(--bg-page);
            padding: 0.2rem 0.8rem;
            border-radius: 50px;
        }

        .gov-card-link {
            font-family: var(--font-body);
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--gov-green);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .gov-card-link:hover {
            color: var(--gov-gold);
            transform: translateX(3px);
        }

        .gov-card-body {
            padding: 1.3rem;
        }

        /* ══════════════════════════════════════════════
                   TABLES - ENHANCED
                   ══════════════════════════════════════════════ */
        .gov-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            font-family: var(--font-body);
        }

        .gov-table thead th {
            background: linear-gradient(180deg, var(--gov-green-tint), #dcebe3);
            color: var(--gov-green-dark);
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.08em;
            padding: 0.7rem 1rem;
            border-bottom: 2px solid var(--border-strong);
            text-align: left;
        }

        .gov-table tbody td {
            padding: 0.7rem 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--ink-700);
            font-weight: 500;
        }

        .gov-table tbody tr {
            transition: all 0.3s ease;
        }

        .gov-table tbody tr:hover {
            background: linear-gradient(90deg, var(--gov-green-tint), transparent);
            transform: scale(1.01);
        }

        .gov-table tbody tr:last-child td {
            border-bottom: none;
        }

        .gov-table .mono {
            font-family: var(--font-mono);
            font-weight: 600;
        }

        .gov-table tfoot td {
            padding: 0.8rem 1rem;
            border-top: 2px solid var(--border-strong);
            font-weight: 800;
            background: var(--gov-green-tint);
            font-size: 0.9rem;
        }

        /* ══════════════════════════════════════════════
                   BADGES - ENHANCED
                   ══════════════════════════════════════════════ */
        .gov-badge {
            padding: 0.25rem 0.8rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .gov-badge:hover {
            transform: scale(1.05);
        }

        .gov-badge.paid {
            background: #eaf6f0;
            color: #0a7a4a;
            border-color: #cbe9d8;
        }

        .gov-badge.pending {
            background: var(--gov-gold-tint);
            color: var(--gov-gold);
            border-color: #f0dfb8;
        }

        .gov-badge.overdue {
            background: #fdecec;
            color: var(--status-red);
            border-color: #f7cfcf;
        }

        .gov-badge.active {
            background: #eaf0fd;
            color: var(--status-blue);
            border-color: #cddbf9;
        }

        /* ══════════════════════════════════════════════
                   ZONE REGISTER CARDS - ENHANCED
                   ══════════════════════════════════════════════ */
        .zone-register {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.2rem 1.3rem;
            height: 100%;
            position: relative;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
        }

        .zone-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--gov-green), var(--gov-green-light));
            border-radius: 12px 12px 0 0;
        }

        .zone-register::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(15, 107, 71, 0.03), transparent);
            border-radius: 50%;
        }

        .zone-register:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: var(--shadow-lg), var(--shadow-glow);
            border-color: var(--gov-green);
        }

        .zone-register .zone-name {
            font-family: var(--font-display);
            font-weight: 900;
            font-size: 1.1rem;
            color: var(--gov-green-dark);
        }

        .zone-register .zone-officer {
            font-size: 0.78rem;
            color: var(--ink-500);
            margin-top: 0.1rem;
            font-weight: 600;
        }

        .zone-register .zone-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.4rem 0.8rem;
            margin: 0.8rem 0;
            padding: 0.7rem 0;
            border-top: 2px dashed var(--border-strong);
            border-bottom: 2px dashed var(--border-strong);
        }

        .zone-register .zone-stats span {
            font-size: 0.75rem;
            color: var(--ink-500);
            font-weight: 600;
        }

        .zone-register .zone-stats strong {
            color: var(--ink-900);
            font-family: var(--font-mono);
            font-weight: 800;
            font-size: 0.9rem;
        }

        .zone-register .tax-tags {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin-bottom: 0.7rem;
        }

        .zone-register .tax-tag {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.6rem;
            border-radius: 50px;
            border: 1px solid var(--border);
            color: var(--ink-500);
            background: var(--bg-page);
            transition: all 0.3s ease;
        }

        .zone-register .tax-tag:hover {
            background: var(--gov-green-tint);
            border-color: var(--gov-green);
            color: var(--gov-green-dark);
        }

        .zone-register .zone-footer {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .zone-register .zone-balance {
            font-family: var(--font-display);
            font-weight: 900;
            color: var(--status-red);
            font-size: 1.1rem;
        }

        /* ══════════════════════════════════════════════
                   ACTION REGISTRY
                   ══════════════════════════════════════════════ */
        .action-registry {
            display: flex;
            flex-direction: column;
        }

        .action-row {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 0.3rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
            color: var(--ink-700);
            font-family: var(--font-body);
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border-radius: 8px;
            padding-left: 0.8rem;
        }

        .action-row:last-child {
            border-bottom: none;
        }

        .action-row:hover {
            color: var(--gov-green-dark);
            background: linear-gradient(90deg, var(--gov-green-tint), transparent);
            transform: translateX(5px);
        }

        .action-row .num {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--gov-gold);
            width: 24px;
        }

        .action-row i {
            color: var(--gov-green);
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .action-row .arrow {
            margin-left: auto;
            color: var(--ink-300);
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .action-row:hover .arrow {
            color: var(--gov-green);
            transform: translateX(3px);
        }

        /* ══════════════════════════════════════════════
                   ACTIVITY LOG - ENHANCED
                   ══════════════════════════════════════════════ */
        .log-entry {
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
            transition: all 0.3s ease;
            border-radius: 8px;
            padding-left: 0.5rem;
        }

        .log-entry:hover {
            background: rgba(15, 107, 71, 0.03);
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .log-entry .log-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .log-entry:hover .log-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .log-entry .log-text {
            font-size: 0.82rem;
            color: var(--ink-700);
            flex: 1;
            font-family: var(--font-body);
            font-weight: 500;
        }

        .log-entry .log-text strong {
            color: var(--ink-900);
            font-weight: 700;
        }

        .log-entry .log-time {
            font-size: 0.68rem;
            color: var(--ink-500);
            font-family: var(--font-mono);
            white-space: nowrap;
            font-weight: 600;
        }

        /* ══════════════════════════════════════════════
                   ERROR STATE
                   ══════════════════════════════════════════════ */
        .gov-error {
            text-align: center;
            padding: 3rem 1rem;
            background: #fdecec;
            border: 1px solid #f7cfcf;
            border-radius: 14px;
            box-shadow: var(--shadow-md);
        }

        .gov-error i {
            font-size: 3rem;
            color: var(--status-red);
            margin-bottom: 1rem;
            display: block;
        }

        .gov-error h5 {
            color: #7a1414;
            font-weight: 800;
            font-family: var(--font-body);
            font-size: 1.2rem;
        }

        .gov-error p {
            color: #7a1414;
            opacity: 0.85;
            font-family: var(--font-body);
            font-weight: 500;
        }

        /* ══════════════════════════════════════════════
                   RESPONSIVE
                   ══════════════════════════════════════════════ */
        @media (max-width: 768px) {
            .ledger-flow {
                flex-direction: column;
            }

            .ledger-step {
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .gov-page-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .gov-page-date {
                text-align: left;
            }

            .gov-letterhead {
                flex-direction: column;
                text-align: center;
                padding: 1.2rem;
            }

            .gov-letterhead::before {
                width: 100%;
                border-radius: 14px;
                opacity: 0.08;
            }

            .gov-letterhead .identity {
                flex-direction: column;
            }

            .gov-letterhead .meta {
                margin-left: 0;
                flex-wrap: wrap;
                justify-content: center;
            }

            .gov-letterhead .org-name {
                font-size: 1.2rem;
            }

            .gov-page-title {
                font-size: 1.5rem;
            }
        }

        /* ══════════════════════════════════════════════
                   UTILITY ANIMATIONS
                   ══════════════════════════════════════════════ */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .gov-section {
            animation: fadeInUp 0.6s ease forwards;
        }

        .gov-section:nth-child(2) { animation-delay: 0.1s; }
        .gov-section:nth-child(3) { animation-delay: 0.2s; }
        .gov-section:nth-child(4) { animation-delay: 0.3s; }
        .gov-section:nth-child(5) { animation-delay: 0.4s; }
        .gov-section:nth-child(6) { animation-delay: 0.5s; }
        .gov-section:nth-child(7) { animation-delay: 0.6s; }
        .gov-section:nth-child(8) { animation-delay: 0.7s; }
        .gov-section:nth-child(9) { animation-delay: 0.8s; }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-page);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gov-green);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gov-green-dark);
        }
    </style>
@endpush

@section('content')

    {{-- ══════════════════════════ OFFICIAL LETTERHEAD ══════════════════════════ --}}
    <div class="gov-letterhead">
        <div class="identity">
            <div class="seal"><i class="bi bi-shield-check"></i></div>
            <div>
                <div class="org-name">
                    {{ isset($corporation) && $corporation ? $corporation->name : 'Municipal Corporation' }}
                </div>
                <div class="org-sub">Revenue &amp; Assessment Management Portal</div>
            </div>
        </div>
        <div class="meta">
            <span class="gov-status-chip"><span class="dot"></span> System Live</span>
            <a href="#" class="gov-export-btn"><i class="bi bi-file-earmark-arrow-down"></i> Export Report</a>
        </div>
    </div>

    @if (isset($error))
        <div class="gov-error">
            <i class="bi bi-exclamation-triangle"></i>
            <h5>{{ $error }}</h5>
            <p>Please contact your administrator to assign a corporation to your account.</p>
        </div>
    @else
        {{-- ══════════════════════════ BREADCRUMB + TITLE ══════════════════════════ --}}
        <div class="gov-breadcrumb">
            <a href="#">Home</a><span class="sep">/</span><a href="#">Office</a><span class="sep">/</span>
            <span class="current">Commissioner Dashboard</span>
        </div>
        <div class="gov-page-head">
            <div>
                <h1 class="gov-page-title">Executive Dashboard</h1>
                <p class="gov-page-sub">Consolidated view of zones, wards, assessments and revenue collection</p>
            </div>
            <div class="gov-page-date">
                Report generated<br>{{ now()->format('d M Y, h:i A') }}
            </div>
        </div>

        {{-- ══════════════════════════ ADMINISTRATIVE HIERARCHY LEDGER ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Administrative Hierarchy</span>
                <span class="rule"></span>
            </div>
            <div class="ledger-flow">
                <div class="ledger-step">
                    <span class="step-no">01 — JURISDICTION</span>
                    <span class="step-label">Zones</span>
                    <span class="step-value">{{ $hierarchyStats['zones'] ?? 0 }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">02 — JURISDICTION</span>
                    <span class="step-label">Wards</span>
                    <span class="step-value">{{ $hierarchyStats['wards'] ?? 0 }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">03 — INVENTORY</span>
                    <span class="step-label">Buildings</span>
                    <span
                        class="step-value">{{ isset($hierarchyStats['buildings']) ? number_format($hierarchyStats['buildings']) : '0' }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">04 — REGISTER</span>
                    <span class="step-label">Assessments</span>
                    <span
                        class="step-value">{{ isset($hierarchyStats['assessments']) ? number_format($hierarchyStats['assessments']) : '0' }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">05 — VERIFICATION</span>
                    <span class="step-label">Surveyed</span>
                    <span
                        class="step-value">{{ isset($hierarchyStats['surveyed']) ? number_format($hierarchyStats['surveyed']) : '0' }}</span>
                </div>
                <div class="ledger-step">
                    <span class="step-no">06 — VERIFICATION</span>
                    <span class="step-label">Connected</span>
                    <span
                        class="step-value">{{ isset($hierarchyStats['connected']) ? number_format($hierarchyStats['connected']) : '0' }}</span>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ WARD BOUNDARY MAP ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-card">
                <div class="gov-card-head">
                    <div class="gov-card-title"><i class="bi bi-map"></i> Ward Boundaries</div>
                    <span class="gov-card-meta">{{ $hierarchyStats['wards'] ?? 0 }} wards mapped</span>
                </div>
                <div class="gov-card-body">
                    <div id="wardMap"
                        style="width:100%; height:460px; border-radius:6px; border:1px solid var(--border);"></div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ REVENUE SNAPSHOT ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Revenue Snapshot</span>
                <span class="rule gold"></span>
            </div>
            <div class="row g-3">
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i class="bi bi-calendar-day"></i></div>
                        <div class="kpi-label">Half Year Tax</div>
                        <div class="kpi-value">
                            {{ isset($stats['total_half_year_tax']) && $stats['total_half_year_tax'] ? '₹' . number_format($stats['total_half_year_tax']) : '₹0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-gold">
                        <div class="kpi-icon" style="background:var(--gov-gold-tint); color:var(--gov-gold);"><i
                                class="bi bi-graph-up-arrow"></i></div>
                        <div class="kpi-label">Yearly Tax</div>
                        <div class="kpi-value">
                            {{ isset($stats['year_collection']) && $stats['year_collection'] ? '₹' . number_format($stats['year_collection']) : '₹0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-blue">
                        <div class="kpi-icon" style="background:#eaf0fd; color:var(--status-blue);"><i
                                class="bi bi-wallet2"></i></div>
                        <div class="kpi-label">Total Balance</div>
                        <div class="kpi-value">
                            {{ isset($stats['total_balance']) && $stats['total_balance'] ? '₹' . number_format($stats['total_balance']) : '₹0' }}
                        </div>
                    </div>
                </div>
                {{-- <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-teal">
                        <div class="kpi-icon" style="background:#e6f5f3; color:var(--status-teal);"><i
                                class="bi bi-check-circle"></i></div>
                        <div class="kpi-label">Total Paid</div>
                        <div class="kpi-value">
                            {{ isset($stats['total_collection']) && $stats['total_collection'] ? '₹' . number_format($stats['total_collection']) : '₹0' }}
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>

        {{-- ══════════════════════════ ASSESSMENT LEDGER ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Assessment Ledger</span>
                <span class="rule"></span>
            </div>
            <div class="row g-3">
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i class="bi bi-clipboard-data"></i></div>
                        <div class="kpi-label">Total Assessments (MIS)</div>
                        <div class="kpi-value">{{ isset($stats['mis_count']) ? number_format($stats['mis_count']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-blue">
                        <div class="kpi-icon" style="background:#eaf0fd; color:var(--status-blue);"><i
                                class="bi bi-check-circle"></i></div>
                        <div class="kpi-label">Active Assessments</div>
                        <div class="kpi-value">
                            {{ isset($stats['active_assessments']) ? number_format($stats['active_assessments']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-gold">
                        <div class="kpi-icon" style="background:var(--gov-gold-tint); color:var(--gov-gold);"><i
                                class="bi bi-hourglass-split"></i></div>
                        <div class="kpi-label">Not in MIS</div>
                        <div class="kpi-value">{{ isset($stats['notin_mis']) ? number_format($stats['notin_mis']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-red">
                        <div class="kpi-icon" style="background:#fdecec; color:var(--status-red);"><i
                                class="bi bi-exclamation-triangle"></i></div>
                        <div class="kpi-label">Overdue Assessments</div>
                        <div class="kpi-value">
                            {{ isset($stats['overdue_assessments']) ? number_format($stats['overdue_assessments']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i class="bi bi-check2-all"></i></div>
                        <div class="kpi-label">Paid Assessments</div>
                        <div class="kpi-value">
                            {{ isset($stats['paid_assessments']) ? number_format($stats['paid_assessments']) : '0' }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-purple">
                        <div class="kpi-icon" style="background:#f2ecfb; color:var(--status-purple);"><i
                                class="bi bi-eye"></i></div>
                        <div class="kpi-label">Surveyed</div>
                        <div class="kpi-value">{{ isset($stats['surveyed']) ? number_format($stats['surveyed']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card accent-teal">
                        <div class="kpi-icon" style="background:#e6f5f3; color:var(--status-teal);"><i
                                class="bi bi-link-45deg"></i></div>
                        <div class="kpi-label">Connected</div>
                        <div class="kpi-value">{{ isset($stats['connected']) ? number_format($stats['connected']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i class="bi bi-people"></i></div>
                        <div class="kpi-label">Property Owners</div>
                        <div class="kpi-value">{{ isset($stats['owners']) ? number_format($stats['owners']) : '0' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ TAX TYPE COUNTS ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Tax Type Register</span>
                <span class="rule"></span>
            </div>
            <div class="row g-3">
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card accent-blue">
                        <div class="kpi-icon" style="background:#eaf0fd; color:var(--status-blue);"><i
                                class="bi bi-droplet"></i></div>
                        <div class="kpi-label">Water Tax</div>
                        <div class="kpi-value">
                            {{ isset($stats['water_tax_count']) ? number_format($stats['water_tax_count']) : '0' }}</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card accent-gold">
                        <div class="kpi-icon" style="background:var(--gov-gold-tint); color:var(--gov-gold);"><i
                                class="bi bi-pipe"></i></div>
                        <div class="kpi-label">UGD Tax</div>
                        <div class="kpi-value">{{ isset($stats['ugd_count']) ? number_format($stats['ugd_count']) : '0' }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="kpi-card accent-purple">
                        <div class="kpi-icon" style="background:#f2ecfb; color:var(--status-purple);"><i
                                class="bi bi-briefcase"></i></div>
                        <div class="kpi-label">Professional Tax</div>
                        <div class="kpi-value">
                            {{ isset($stats['professional_tax_count']) ? number_format($stats['professional_tax_count']) : '0' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ QUICK ACTIONS + TAX BREAKDOWN ══════════════════════════ --}}
        <div class="gov-section">
            <div class="row g-3">
                <div class="col-xl-5">
                    <div class="gov-card h-100">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-lightning-charge"></i> Quick Actions</div>
                        </div>
                        <div class="gov-card-body">
                            <div class="action-registry">
                                <a href="{{ route('commissioner.map') ?? '#' }}" class="action-row">
                                    <span class="num">01</span><i class="bi bi-map"></i> View Ward Map <span
                                        class="arrow">→</span>
                                </a>
                                <a href="#" class="action-row">
                                    <span class="num">02</span><i class="bi bi-file-spreadsheet"></i> Collection Report
                                    <span class="arrow">→</span>
                                </a>
                                <a href="#" class="action-row">
                                    <span class="num">03</span><i class="bi bi-exclamation-triangle"></i> Pending
                                    Report <span class="arrow">→</span>
                                </a>
                                <a href="#" class="action-row">
                                    <span class="num">04</span><i class="bi bi-file-earmark-excel"></i> Export to Excel
                                    <span class="arrow">→</span>
                                </a>
                                <a href="#" class="action-row">
                                    <span class="num">05</span><i class="bi bi-printer"></i> Print Report <span
                                        class="arrow">→</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════ TAX BREAKDOWN ══════════════════════════ --}}
                <div class="col-xl-7">
                    <div class="gov-card h-100">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-pie-chart"></i> Tax Breakdown</div>
                            <span class="gov-card-meta">{{ now()->format('F Y') }}</span>
                        </div>
                        <div class="gov-card-body">
                            <table class="gov-table">
                                <thead>
                                    <tr>
                                        <th>Tax Category</th>
                                        <th>Count</th>
                                        <th>Half Year Tax (₹)</th>
                                        <th>Balance (₹)</th>
                                        <th>Paid (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($taxBreakdown ?? [] as $key => $tax)
                                        @php
                                            $labels = [
                                                'mis' => 'MIS Assessment',
                                                'water_tax' => 'Water Tax',
                                                'ugd' => 'UGD Tax',
                                                'professional_tax' => 'Professional Tax',
                                            ];
                                            $icons = [
                                                'mis' => 'clipboard-data',
                                                'water_tax' => 'droplet',
                                                'ugd' => 'pipe',
                                                'professional_tax' => 'briefcase',
                                            ];

                                            $halfYearTax = $tax['half_year_tax'] ?? 0;
                                            $balance = $tax['balance'] ?? 0;
                                            $paid = $halfYearTax - $balance;
                                        @endphp
                                        <tr>
                                            <td>
                                                <i class="bi bi-{{ $icons[$key] ?? 'file-text' }} me-2"
                                                    style="color:var(--gov-green);"></i>
                                                {{ $labels[$key] ?? ucfirst($key) }}
                                            </td>
                                            <td class="mono">{{ number_format($tax['count']) }}</td>
                                            <td class="mono" style="font-weight:700; color:var(--ink-900);">
                                                {{ $halfYearTax ? '₹' . number_format($halfYearTax) : '₹0' }}
                                            </td>
                                            <td class="mono" style="color:var(--status-red); font-weight:700;">
                                                {{ $balance > 0 ? '₹' . number_format($balance) : '₹0' }}
                                            </td>
                                            <td class="mono" style="color:var(--gov-green); font-weight:700;">
                                                {{ $paid > 0 ? '₹' . number_format($paid) : '₹0' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted">No tax data available
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @php
                                    $totalHalfYear = 0;
                                    $totalBalance = 0;
                                    foreach ($taxBreakdown ?? [] as $tax) {
                                        $totalHalfYear += $tax['half_year_tax'] ?? 0;
                                        $totalBalance += $tax['balance'] ?? 0;
                                    }
                                    $totalPaid = $totalHalfYear - $totalBalance;
                                @endphp
                                <tfoot>
                                    <tr style="background: var(--gov-green-tint); font-weight: 700;">
                                        <td><strong>TOTAL</strong></td>
                                        <td class="mono">
                                            {{ number_format(array_sum(array_column($taxBreakdown ?? [], 'count'))) }}</td>
                                        <td class="mono" style="color:var(--ink-900);">
                                            ₹{{ number_format($totalHalfYear) }}</td>
                                        <td class="mono" style="color:var(--status-red);">
                                            ₹{{ number_format($totalBalance) }}</td>
                                        <td class="mono" style="color:var(--gov-green);">
                                            ₹{{ number_format($totalPaid) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ ZONE-WISE TAX SUMMARY ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-card">
                <div class="gov-card-head">
                    <div class="gov-card-title"><i class="bi bi-graph-up"></i> Zone-wise Tax Summary</div>
                    <span class="gov-card-meta">{{ now()->format('F Y') }}</span>
                </div>
                <div class="gov-card-body" style="overflow-x:auto; padding:0;">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>Zone</th>
                                <th>Total Tax</th>
                                <th>Balance</th>
                                <th>Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($performanceZones ?? [] as $zone)
                                <tr>
                                    <td style="font-weight:700; color:var(--ink-900);">{{ $zone['name'] }}</td>
                                    <td class="mono">{{ $zone['total_tax'] }}</td>
                                    <td class="mono" style="color:var(--status-red);">{{ $zone['balance'] }}</td>
                                    <td class="mono" style="color:var(--gov-green); font-weight:700;">
                                        {{ $zone['paid'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No zones found for this
                                        corporation</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ ZONE REGISTER ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Zone Register</span>
                <span class="rule"></span>
                <a href="{{ route('admin.zones.index') }}" class="gov-card-link">View All Zones →</a>
            </div>
            <div class="row g-3">
                @forelse($zoneData ?? [] as $zone)
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="zone-register">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="zone-name">{{ $zone['name'] }}</div>
                                    <div class="zone-officer"><i
                                            class="bi bi-person-badge me-1"></i>{{ $zone['officer'] }}</div>
                                </div>
                                <i class="bi bi-building" style="color:var(--gov-green); font-size:1.1rem;"></i>
                            </div>
                            <div class="zone-stats">
                                <span>Wards: <strong>{{ $zone['wards'] }}</strong></span>
                                <span>Buildings: <strong>{{ number_format($zone['buildings']) }}</strong></span>
                                <span>Assessments: <strong>{{ number_format($zone['assessments']) }}</strong></span>
                                <span>Surveyed: <strong>{{ number_format($zone['surveyed']) }}</strong></span>
                            </div>
                            <div class="tax-tags">
                                <span class="tax-tag"><i
                                        class="bi bi-droplet me-1"></i>{{ $zone['water_tax'] ?? 0 }}</span>
                                <span class="tax-tag"><i class="bi bi-pipe me-1"></i>{{ $zone['ugd'] ?? 0 }}</span>
                                <span class="tax-tag"><i
                                        class="bi bi-briefcase me-1"></i>{{ $zone['professional_tax'] ?? 0 }}</span>
                            </div>
                            <div class="zone-footer">
                                <span class="zone-balance">Balance: {{ $zone['balance'] }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-diagram-3 fs-2 d-block mb-2"></i>
                            No zones found for this corporation
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
        {{-- ══════════════════════════ WARD VARIATION ANALYSIS ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-card">
                <div class="gov-card-head">
                    <div class="gov-card-title"><i class="bi bi-exclamation-diamond"></i> Ward Variation Analysis</div>
                    <span class="gov-card-meta">Area &amp; usage mismatch vs. building survey, ranked highest first</span>
                </div>
                <div class="gov-card-body" style="overflow-x:auto; padding:0;">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>Ward</th>
                                <th>Zone</th>
                                <th>Buildings</th>
                                <th>Surveyed</th>
                                <th>Survey %</th>
                                <th>Area Variation</th>
                                <th>Usage Variation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($wardVariationStats ?? [] as $w)
                                @php
                                    $areaBadge =
                                        $w['area_variation_percentage'] >= 20
                                            ? 'overdue'
                                            : ($w['area_variation_percentage'] >= 10
                                                ? 'pending'
                                                : 'paid');
                                    $usageBadge =
                                        $w['usage_variation_percentage'] >= 20
                                            ? 'overdue'
                                            : ($w['usage_variation_percentage'] >= 10
                                                ? 'pending'
                                                : 'paid');
                                @endphp
                                <tr>
                                    <td style="font-weight:700; color:var(--ink-900);">
                                        <a href="{{ route('commissioner.ward.showmap', $w['ward_id']) }}">
                                            Ward {{ $w['ward_no'] }}
                                        </a>
                                    </td>
                                    <td>{{ $w['zone_name'] }}</td>
                                    <td class="mono">{{ number_format($w['total_buildings']) }}</td>
                                    <td class="mono">{{ number_format($w['surveyed_buildings']) }}</td>
                                    <td class="mono">{{ $w['survey_percentage'] }}%</td>
                                    <td>
                                        <span class="gov-badge {{ $areaBadge }}">
                                            {{ $w['area_variation_count'] }} ({{ $w['area_variation_percentage'] }}%)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="gov-badge {{ $usageBadge }}">
                                            {{ $w['usage_variation_count'] }} ({{ $w['usage_variation_percentage'] }}%)
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">No ward variation data
                                        available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{-- ══════════════════════════ TAX REGISTERS ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-eyebrow">
                <span class="label">Tax Registers — Recent Entries</span>
                <span class="rule"></span>
            </div>
            <div class="row g-3">
                {{-- Water Tax --}}
                <div class="col-md-4">
                    <div class="gov-card">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-droplet"></i> Water Tax</div>
                            <a href="#" class="gov-card-link">View All</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="gov-table">
                                <thead>
                                    <tr>
                                        <th>Assessment No</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($waterTaxData ?? [] as $item)
                                        <tr>
                                            <td style="font-weight:600; font-size:0.75rem;">{{ $item['no'] }}</td>
                                            <td class="mono" style="font-size:0.78rem;">{{ $item['amount'] }}</td>
                                            <td><span
                                                    class="gov-badge {{ $item['status'] }}">{{ $item['status'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-2 text-muted"
                                                style="font-size:0.75rem;">No data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- UGD Tax --}}
                <div class="col-md-4">
                    <div class="gov-card">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-pipe"></i> UGD Tax</div>
                            <a href="#" class="gov-card-link">View All</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="gov-table">
                                <thead>
                                    <tr>
                                        <th>Assessment No</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ugdData ?? [] as $item)
                                        <tr>
                                            <td style="font-weight:600; font-size:0.75rem;">{{ $item['no'] }}</td>
                                            <td class="mono" style="font-size:0.78rem;">{{ $item['amount'] }}</td>
                                            <td><span
                                                    class="gov-badge {{ $item['status'] }}">{{ $item['status'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-2 text-muted"
                                                style="font-size:0.75rem;">No data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Professional Tax --}}
                <div class="col-md-4">
                    <div class="gov-card">
                        <div class="gov-card-head">
                            <div class="gov-card-title"><i class="bi bi-briefcase"></i> Professional Tax</div>
                            <a href="#" class="gov-card-link">View All</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="gov-table">
                                <thead>
                                    <tr>
                                        <th>Assessment No</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($professionalTaxData ?? [] as $item)
                                        <tr>
                                            <td style="font-weight:600; font-size:0.75rem;">{{ $item['no'] }}</td>
                                            <td class="mono" style="font-size:0.78rem;">{{ $item['amount'] }}</td>
                                            <td><span
                                                    class="gov-badge {{ $item['status'] }}">{{ $item['status'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-2 text-muted"
                                                style="font-size:0.75rem;">No data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════ ACTIVITY LOG ══════════════════════════ --}}
        <div class="gov-section">
            <div class="gov-card">
                <div class="gov-card-head">
                    <div class="gov-card-title"><i class="bi bi-journal-text"></i> Recent Activity Log</div>
                    <span class="gov-status-chip"
                        style="background:var(--gov-green-tint); border-color:#cbe9d8; color:var(--gov-green-dark);">
                        <span class="dot"
                            style="background:var(--gov-green); box-shadow:0 0 0 3px rgba(15,107,71,0.15);"></span>
                        Live
                    </span>
                </div>
                <div class="gov-card-body">
                    <div class="row g-0">
                        @forelse($activities ?? [] as $activity)
                            <div class="col-md-6">
                                <div class="log-entry" style="padding-right: 1rem;">
                                    <div class="log-icon"
                                        style="background:{{ $activity['color'] }}18; color:{{ $activity['color'] }};">
                                        <i class="bi bi-{{ $activity['icon'] }}"></i>
                                    </div>
                                    <div class="log-text">{!! $activity['text'] !!}</div>
                                    <div class="log-time">{{ $activity['time'] }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-3 text-muted">No recent activities</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    @endif

@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v9.2.4/ol.css">
    <script src="https://cdn.jsdelivr.net/npm/ol@v9.2.4/dist/ol.js"></script>
    <script>
        // This function must be GLOBAL for onclick to work
        window.openWard = function(wardId) {
            let url = "{{ route('commissioner.ward.showmap', ':id') }}";
            url = url.replace(':id', wardId);
            window.location.href = url;
        };

        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('.gov-perf-bar .fill');
            bars.forEach(bar => {
                const w = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = w;
                }, 200);
            });

            const allwardBoundary = @json($getAllwardBoundary ?? []);

            const mapEl = document.getElementById('wardMap');
            if (!mapEl || !allwardBoundary || allwardBoundary.length === 0) return;

            const features = [];
            const palette = ['#0f6b47', '#1d4ed8', '#a9741a', '#5b21b6', '#b91c1c', '#0e7c72'];

            allwardBoundary.forEach((ward, idx) => {
                if (!ward.boundary || !ward.boundary.coordinates) return;
                try {
                    const geometry = new ol.geom.MultiPolygon(ward.boundary.coordinates);
                    const feature = new ol.Feature({
                        geometry,
                        ward_id: ward.ward_id,
                        ward_no: ward.ward_no
                    });
                    const color = palette[idx % palette.length];

                    feature.setStyle(new ol.style.Style({
                        fill: new ol.style.Fill({
                            color: color + '26'
                        }),
                        stroke: new ol.style.Stroke({
                            color: color,
                            width: 2
                        }),
                        text: new ol.style.Text({
                            text: 'Ward ' + ward.ward_no,
                            font: '600 11px Inter, sans-serif',
                            fill: new ol.style.Fill({
                                color: '#0e2019'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#fff',
                                width: 3
                            })
                        })
                    }));
                    features.push(feature);
                } catch (e) {
                    console.error('Error building geometry for ward', ward.ward_id, e);
                }
            });

            const vectorSource = new ol.source.Vector({
                features: features
            });
            const map = new ol.Map({
                target: 'wardMap',
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.OSM()
                    }),
                    new ol.layer.Vector({
                        source: vectorSource
                    })
                ],
                view: new ol.View({
                    center: [0, 0],
                    zoom: 2
                })
            });

            if (vectorSource.getFeatures().length > 0) {
                map.getView().fit(vectorSource.getExtent(), {
                    padding: [30, 30, 30, 30],
                    maxZoom: 18
                });
            }

            const popupEl = document.createElement('div');
            popupEl.style.cssText =
                'background:#0a4530; color:#fff; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:600; font-family:Inter,sans-serif;';
            const popup = new ol.Overlay({
                element: popupEl,
                positioning: 'bottom-center',
                offset: [0, -10]
            });
            map.addOverlay(popup);

            map.on('click', function(evt) {
                const feature = map.forEachFeatureAtPixel(evt.pixel, f => f);

                if (feature) {
                    const wardNo = feature.get('ward_no');
                    const wardId = feature.get('ward_id');

                    popupEl.innerHTML = `
                        <div>
                            <p><strong>Ward No:</strong> ${wardNo}</p>
                            <button
                                class="btn btn-primary btn-sm"
                                onclick="openWard(${wardId})">
                                View Ward
                            </button>
                        </div>
                    `;

                    popup.setPosition(evt.coordinate);
                } else {
                    popup.setPosition(undefined);
                }
            });

            map.on('pointermove', function(evt) {
                map.getTargetElement().style.cursor = map.hasFeatureAtPixel(evt.pixel) ? 'pointer' : '';
            });
        });
    </script>
@endpush
