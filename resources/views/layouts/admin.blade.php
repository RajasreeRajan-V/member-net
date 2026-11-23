<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Member Net Admin - @yield('title')</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
  --navy: #ffffff;
  --navy-2: #f0f4ff;
  --accent: #4f8ef7;
  --accent-2: #3a7ae0;
  --gold: #f5c842;
  --green: #34d399;
  --red: #f87171;
  --orange: #fb923c;
  --purple: #a78bfa;
  --text: #0f1729;
  --text-dim: #4a5a7a;
  --border: rgba(15, 23, 41, 0.1);
  --card: rgba(15, 23, 41, 0.04);
  --card-hover: rgba(15, 23, 41, 0.07);
  --sw: 255px;
}

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--navy);
      color: var(--text);
      min-height: 100vh;
      display: flex;
    }

    /* ── SIDEBAR ── */
    .sidebar {
      width: var(--sw);
      min-height: 100vh;
      background: var(--navy-2);
      border-right: 1px solid var(--border);
      position: fixed;
      left: 0;
      top: 0;
      display: flex;
      flex-direction: column;
      z-index: 100;
      overflow-y: auto;
      overflow-x: hidden;
      transition: transform 0.3s ease;
      height: 100vh;
    }

    .sl {
      padding: 20px 18px 14px;
      border-bottom: 1px solid var(--border);
    }

    .sl-row {
      display: flex;
      align-items: center;
      gap: 9px;
      margin-bottom: 3px;
    }

    .sl-icon {
      width: 34px;
      height: 34px;
      background: linear-gradient(135deg, var(--accent), #7c3aed);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      flex-shrink: 0;
    }

    .sl-text {
      font-family: 'Instrument Serif', serif;
      font-size: 19px;
    }

    .sl-sub {
      font-size: 10px;
      color: var(--text-dim);
    }

    .snav {
      flex: 1;
      padding: 8px 0;
    }

    .ns {
      padding: 12px 16px 3px;
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--text-dim);
    }

    .ni {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 8px 16px;
      color: var(--text-dim);
      font-size: 12.5px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s;
      border-left: 2px solid transparent;
    }

    .ni:hover {
      color: var(--text);
      background: var(--card);
      border-left-color: var(--accent);
    }

    .ni.active {
      color: var(--accent-2);
      background: rgba(79, 142, 247, 0.1);
      border-left-color: var(--accent);
    }

    .ni i {
      width: 15px;
      font-size: 12px;
      text-align: center;
    }

    .ni.soon {
      opacity: 0.4;
      cursor: not-allowed;
      pointer-events: none;
    }

    .nb {
      margin-left: auto;
      background: var(--orange);
      color: #fff;
      font-size: 9px;
      font-weight: 700;
      padding: 1px 6px;
      border-radius: 20px;
    }

    .soon-tag {
      margin-left: auto;
      font-size: 9px;
      color: var(--text-dim);
      background: rgba(255, 255, 255, 0.05);
      padding: 1px 6px;
      border-radius: 20px;
      border: 1px solid var(--border);
    }

    .sf {
      padding: 12px 16px;
      border-top: 1px solid var(--border);
      flex-shrink: 0;
      background: var(--navy-2);
      position: sticky;
      bottom: 0;
    }

    .sa {
      display: flex;
      align-items: center;
      gap: 9px;
    }

    .sav {
      width: 32px;
      height: 32px;
      background: linear-gradient(135deg, var(--accent), #7c3aed);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 11px;
      flex-shrink: 0;
    }

    /* ── SIDEBAR SCROLL BUTTON ── */
    .ssb {
      position: sticky;
      bottom: 56px;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      padding: 0 10px;
      pointer-events: none;
      z-index: 101;
    }

    .ssb-inner {
      display: flex;
      flex-direction: column;
      gap: 3px;
      pointer-events: all;
      opacity: 0;
      transition: opacity 0.2s;
    }

    .ssb-inner.visible {
      opacity: 1;
    }

    .ssb-btn {
      width: 26px;
      height: 26px;
      background: var(--navy-2);
      border: 1px solid var(--border);
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--text-dim);
      font-size: 11px;
      transition: all 0.15s;
      user-select: none;
    }

    .ssb-btn:hover {
      background: var(--card-hover);
      color: var(--accent-2);
      border-color: var(--accent);
    }

    .ssb-btn:active {
      transform: scale(0.92);
    }

    /* ── SIDEBAR OVERLAY (mobile) ── */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 99;
    }

    .sidebar-overlay.open {
      display: block;
    }

    /* ── MAIN ── */
    .main {
      margin-left: var(--sw);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .tb {
      padding: 13px 26px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--navy-2);
      position: sticky;
      top: 0;
      z-index: 98;
      gap: 12px;
    }

    .tb-left {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .tb-right {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
    }

    .tb-menu {
      display: none;
      width: 32px;
      height: 32px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 7px;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--text-dim);
      font-size: 14px;
      flex-shrink: 0;
    }

    .tb h1 {
      font-family: 'Instrument Serif', serif;
      font-size: 19px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .tb p {
      font-size: 10.5px;
      color: var(--text-dim);
      margin-top: 1px;
      white-space: nowrap;
    }

    .tbb {
      width: 32px;
      height: 32px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 7px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--text-dim);
      font-size: 12px;
      transition: all 0.2s;
      text-decoration: none;
      flex-shrink: 0;
    }

    .tbb:hover {
      background: var(--card-hover);
      color: var(--text);
    }

    .tbb-logout {
      color: var(--red);
      border-color: rgba(248, 113, 113, 0.3);
      background: rgba(248, 113, 113, 0.08);
    }

    .tbb-logout:hover {
      background: rgba(248, 113, 113, 0.2);
      color: var(--red);
    }

    .content {
      padding: 22px 26px;
      flex: 1;
      min-width: 0;
    }

    /* ── CARD ── */
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 11px;
      overflow: hidden;
      margin-bottom: 18px;
      width: 100%;
      max-width: 100%;
    }

    .ch {
      padding: 13px 18px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
    }

    .ct {
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .ct i {
      color: var(--accent);
      font-size: 11px;
    }

    .ca {
      font-size: 11px;
      color: var(--accent);
      cursor: pointer;
      font-weight: 600;
      text-decoration: none;
    }

    .ca:hover {
      color: var(--accent-2);
    }

    /* ── BUTTONS ── */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 13px;
      border-radius: 7px;
      font-size: 11.5px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
      font-family: 'DM Sans', sans-serif;
      text-decoration: none;
    }

    .bp {
      background: var(--accent);
      color: #fff;
    }

    .bp:hover {
      background: var(--accent-2);
      color: var(--navy);
    }

    .bo {
      background: transparent;
      color: var(--text-dim);
      border: 1px solid var(--border);
    }

    .bo:hover {
      background: var(--card-hover);
      color: var(--text);
    }

    .bda {
      background: rgba(248, 113, 113, 0.15);
      color: var(--red);
      border: 1px solid rgba(248, 113, 113, 0.3);
    }

    .bda:hover {
      background: rgba(248, 113, 113, 0.25);
    }

    .bsm {
      padding: 5px 10px;
      font-size: 10.5px;
    }

    /* ── TABLE WRAPPER & SMOOTH HORIZONTAL SCROLLBAR ── */
    .tw {
      width: 100%;
      max-width: 100%;
      overflow-x: auto !important;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior-x: contain;
      position: relative;
      scrollbar-width: thin;
      scrollbar-color: rgba(79, 142, 247, 0.45) rgba(15, 23, 41, 0.04);
    }

    .tw::-webkit-scrollbar {
      height: 7px;
    }

    .tw::-webkit-scrollbar-track {
      background: rgba(15, 23, 41, 0.04);
      border-radius: 4px;
      margin: 0 4px;
    }

    .tw::-webkit-scrollbar-thumb {
      background: rgba(79, 142, 247, 0.45);
      border-radius: 4px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .tw::-webkit-scrollbar-thumb:hover {
      background: var(--accent);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    .tw table {
      width: 100%;
      min-width: 720px;
      border-collapse: collapse;
    }

    thead th {
      padding: 9px 13px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--text-dim);
      background: rgba(255, 255, 255, 0.02);
      text-align: left;
      white-space: nowrap;
    }

    tbody td {
      padding: 11px 13px;
      font-size: 12px;
      color: var(--text);
      border-top: 1px solid var(--border);
      vertical-align: middle;
      white-space: nowrap;
    }

    tbody tr:hover td {
      background: var(--card-hover);
    }

    /* ── BADGE ── */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 600;
    }

    .badge::before {
      content: '';
      width: 4px;
      height: 4px;
      border-radius: 50%;
      background: currentColor;
    }

    .bac {
      background: rgba(79, 142, 247, 0.15);
      color: var(--accent-2);
    }

    .brj {
      background: rgba(248, 113, 113, 0.15);
      color: var(--red);
    }

    .bpe {
      background: rgba(251, 146, 60, 0.15);
      color: var(--orange);
    }

    .bap {
      background: rgba(52, 211, 153, 0.15);
      color: var(--green);
    }

    .bli {
      background: rgba(245, 200, 66, 0.15);
      color: var(--gold);
    }

    .ban {
      background: rgba(79, 142, 247, 0.15);
      color: var(--accent-2);
    }

    .bex {
      background: rgba(167, 139, 250, 0.15);
      color: var(--purple);
    }

    /* ── ACTION BUTTONS ── */
    .ab {
      width: 24px;
      height: 24px;
      border-radius: 5px;
      border: 1px solid var(--border);
      background: var(--card);
      color: var(--text-dim);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 9px;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .ab:hover {
      background: var(--card-hover);
      color: var(--text);
    }

    .ab.app:hover {
      background: rgba(52, 211, 153, 0.15);
      color: var(--green);
      border-color: var(--green);
    }

    .ab.rej:hover {
      background: rgba(248, 113, 113, 0.15);
      color: var(--red);
      border-color: var(--red);
    }

    .ab.vw:hover {
      background: rgba(79, 142, 247, 0.15);
      color: var(--accent-2);
      border-color: var(--accent);
    }

    .ab.ed:hover {
      background: rgba(245, 200, 66, 0.15);
      color: var(--gold);
      border-color: var(--gold);
    }

    .abr {
      display: flex;
      gap: 4px;
    }

    /* ── FORM ── */
    .fgrid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      padding: 18px;
    }

    .fgrid.c1 {
      grid-template-columns: 1fr;
    }

    .fg {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .fg.s2 {
      grid-column: span 2;
    }

    label {
      font-size: 10.5px;
      font-weight: 600;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    input,
    select,
    textarea {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border);
      border-radius: 7px;
      padding: 8px 11px;
      color: var(--text);
      font-size: 12px;
      font-family: 'DM Sans', sans-serif;
      transition: border 0.2s;
      outline: none;
      width: 100%;
    }

    input:focus,
    select:focus,
    textarea:focus {
      border-color: var(--accent);
    }

    select option {
      background: var(--navy-2);
    }

    .ff {
      padding: 12px 18px;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 7px;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    /* ── MODAL ── */
    .mo {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(4px);
      z-index: 200;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .mo.open {
      display: flex;
    }

    .md {
      background: var(--navy-2);
      border: 1px solid var(--border);
      border-radius: 13px;
      width: 500px;
      max-width: 100%;
      max-height: 90vh;
      overflow-y: auto;
    }

    .mh {
      padding: 16px 18px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .mt2 {
      font-size: 14px;
      font-weight: 600;
    }

    .mc3 {
      width: 28px;
      height: 28px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 6px;
      cursor: pointer;
      color: var(--text-dim);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      flex-shrink: 0;
    }

    .mc3:hover {
      color: var(--text);
    }

    /* ── MISC ── */
    .utag {
      font-size: 10px;
      padding: 2px 7px;
      background: rgba(79, 142, 247, 0.1);
      color: var(--accent-2);
      border-radius: 4px;
      border: 1px solid rgba(79, 142, 247, 0.2);
    }

    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 12px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .alert-success {
      background: rgba(52, 211, 153, 0.1);
      border: 1px solid rgba(52, 211, 153, 0.3);
      color: var(--green);
    }

    .alert-error {
      background: rgba(248, 113, 113, 0.1);
      border: 1px solid rgba(248, 113, 113, 0.3);
      color: var(--red);
    }
    input.is-invalid, select.is-invalid, textarea.is-invalid { border-color: #f87171 !important; box-shadow: 0 0 0 1px rgba(248,113,113,0.3) !important; }

    /* ── STAT CARDS ── */
    .sg {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }

    .sc {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 11px;
      padding: 16px;
      transition: all 0.25s;
      position: relative;
      overflow: hidden;
    }

    .sc::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: var(--ca, var(--accent));
      opacity: 0;
      transition: opacity 0.25s;
    }

    .sc:hover {
      background: var(--card-hover);
      transform: translateY(-2px);
    }

    .sc:hover::before {
      opacity: 1;
    }

    .st {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .si {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      flex-shrink: 0;
    }

    .str {
      font-size: 10px;
      font-weight: 600;
      padding: 2px 7px;
      border-radius: 20px;
    }

    .tu {
      background: rgba(52, 211, 153, 0.15);
      color: var(--green);
    }

    .tn {
      background: rgba(167, 139, 250, 0.15);
      color: var(--purple);
    }

    .sv {
      font-family: 'Instrument Serif', serif;
      font-size: 28px;
      color: var(--text);
      line-height: 1;
      margin-bottom: 3px;
    }

    .sl2 {
      font-size: 11px;
      color: var(--text-dim);
      font-weight: 500;
    }

    /* ── MEMBER CELL ── */
    .mc2 {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .av2 {
      width: 28px;
      height: 28px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .mn {
      font-weight: 600;
      font-size: 12px;
    }

    .me {
      font-size: 10px;
      color: var(--text-dim);
    }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar {
      width: 4px;
      height: 4px;
    }

    ::-webkit-scrollbar-track {
      background: transparent;
    }

    ::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 4px;
    }

    /* ══════════════════════════════
   RESPONSIVE
══════════════════════════════ */
    @media(max-width:1024px) {
      .sg {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media(max-width:768px) {
      .tb-menu {
        display: flex;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .main {
        margin-left: 0;
      }

      .tb {
        padding: 12px 16px;
      }

      .tb h1 {
        font-size: 16px;
      }

      .tb p {
        display: none;
      }

      .content {
        padding: 16px;
      }

      .sg {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
      }

      .sv {
        font-size: 22px;
      }

      .fgrid {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 14px;
      }

      .fg.s2 {
        grid-column: span 1;
      }
    }

    @media(max-width:480px) {
      .sg {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
      }

      .sc {
        padding: 12px;
      }

      .sv {
        font-size: 20px;
      }

      .sl2 {
        font-size: 10px;
      }

      .si {
        width: 30px;
        height: 30px;
        font-size: 12px;
      }

      .content {
        padding: 12px;
      }

      .tb {
        padding: 10px 14px;
      }

      .tb h1 {
        font-size: 15px;
      }

      .md {
        border-radius: 10px;
      }

      .fgrid {
        padding: 12px;
      }

      .bsm {
        padding: 4px 9px;
        font-size: 10px;
      }

      .hide-mobile {
        display: none;
      }

      .alert {
        font-size: 11px;
        padding: 10px 12px;
      }
    }
  </style>
</head>

<body>

  {{-- SIDEBAR OVERLAY --}}
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sl">
      <div class="sl-row">
        <div class="sl-icon">🎓</div>
        <span class="sl-text">Member Net</span>
      </div>
      <div class="sl-sub">Member Net Association</div>
    </div>

    <nav class="snav">
      <div class="ns">Overview</div>
      <a class="ni {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"
        onclick="closeSidebar()">
        <i class="fas fa-th-large"></i>Dashboard
      </a>

      <div class="ns">Location</div>
      <a class="ni {{ request()->routeIs('admin.units*') ? 'active' : '' }}" href="{{ route('admin.units') }}"
        onclick="closeSidebar()">
        <i class="fas fa-building"></i>Units
      </a>

      <div class="ns">Management</div>
      <a class="ni {{ request()->routeIs('admin.unit-admins*') ? 'active' : '' }}"
        href="{{ route('admin.unit-admins') }}" onclick="closeSidebar()">
        <i class="fas fa-user-shield"></i>Unit Admins
      </a>
      <a class="ni {{ request()->routeIs('admin.members*') ? 'active' : '' }}" href="{{ route('admin.members') }}"
        onclick="closeSidebar()">
        <i class="fas fa-users"></i>Members
      </a>
      <a class="ni {{ request()->routeIs('admin.non-members*') ? 'active' : '' }}" href="{{ route('admin.non-members') }}"
        onclick="closeSidebar()">
        <i class="fas fa-user-tag"></i>Non-Members
      </a>
      <a class="ni {{ request()->routeIs('admin.bulk-import*') ? 'active' : '' }}"
        href="{{ route('admin.bulk-import') }}" onclick="closeSidebar()">
        <i class="fas fa-file-csv"></i>Bulk Import
      </a>

      <div class="ns">Content</div>
      <a class="ni {{ request()->routeIs('admin.news*') ? 'active' : '' }}" href="{{ route('admin.news') }}"
        onclick="closeSidebar()">
        <i class="fas fa-newspaper"></i>News
      </a>
      <a class="ni {{ request()->routeIs('admin.events*') ? 'active' : '' }}" href="{{ route('admin.events') }}"
        onclick="closeSidebar()">
        <i class="fas fa-calendar-alt"></i>Events
      </a>
            <a class="ni {{ request()->routeIs('admin.fundraising*') ? 'active' : '' }}"
        href="{{ route('admin.fundraising') }}" onclick="closeSidebar()">
        <i class="fas fa-hand-holding-heart"></i>Fundraising
      </a>
      
      <a class="ni {{ request()->routeIs('admin.committees*') ? 'active' : '' }}"
   href="{{ route('admin.committees') }}" onclick="closeSidebar()">
  <i class="fas fa-users-cog"></i>Committee
</a>

<a class="ni {{ request()->routeIs('admin.gallery*') ? 'active' : '' }}"
   href="{{ route('admin.gallery') }}" onclick="closeSidebar()">
  <i class="fas fa-images"></i>Gallery
</a>

<a class="ni {{ request()->routeIs('admin.monthly-fee') ? 'active' : '' }}"
        href="{{ route('admin.monthly-fee') }}" onclick="closeSidebar()">
        <i class="fas fa-calendar-check"></i>Monthly Fee Settings
      </a>

      <a class="ni {{ request()->routeIs('admin.monthly-fee.paid*') ? 'active' : '' }}"
        href="{{ route('admin.monthly-fee.paid') }}" onclick="closeSidebar()">
        <i class="fas fa-user-check"></i>Paid Monthly Fees
      </a>


      <a class="ni {{ request()->routeIs('admin.expenses*') ? 'active' : '' }}"
        href="{{ route('admin.expenses') }}" onclick="closeSidebar()">
        <i class="fas fa-calendar-check"></i>Expenses
      </a>
      
       <a class="ni {{ request()->routeIs('admin.other-fees*') ? 'active' : '' }}"
        href="{{ route('admin.other-fees') }}" onclick="closeSidebar()">
        <i class="fas fa-receipt"></i>Other Fees
      </a>
      
         <a class="ni {{ request()->routeIs('admin.schemes*') ? 'active' : '' }}"
     href="{{ route('admin.schemes') }}" onclick="closeSidebar()">
     <i class="fas fa-piggy-bank"></i>Schemes
   </a>

      <div class="ns">Analytics</div>
      <a class="ni {{ request()->routeIs('admin.reports*') ? 'active' : '' }}"
        href="{{ route('admin.reports') }}" onclick="closeSidebar()">
        <i class="fas fa-chart-bar"></i>Reports
      </a>
      <a class="ni {{ request()->routeIs('admin.renewals-manage*') ? 'active' : '' }}"
  href="{{ route('admin.renewals-manage') }}" onclick="closeSidebar()">
  <i class="fas fa-sync-alt"></i>Renewals
</a>

      <div class="ns">Account</div>
      <a class="ni {{ request()->routeIs('admin.change-password*') ? 'active' : '' }}"
        href="{{ route('admin.change-password') }}" onclick="closeSidebar()">
        <i class="fas fa-key"></i>Change Password
      </a>
      <a class="ni" href="{{ route('admin.logout') }}" onclick="return confirm('Are you sure you want to logout?')"
        style="color:var(--red);">
        <i class="fas fa-sign-out-alt"></i>Logout
      </a>
    </nav>

    {{-- SIDEBAR SCROLL BUTTONS --}}
    <div class="ssb" id="ssbWrap">
      <div class="ssb-inner" id="ssbInner">
        <div class="ssb-btn" id="ssbUp" title="Scroll up (↑)">
          <i class="fas fa-chevron-up"></i>
        </div>
        <div class="ssb-btn" id="ssbDown" title="Scroll down (↓)">
          <i class="fas fa-chevron-down"></i>
        </div>
      </div>
    </div>

    {{-- SIDEBAR FOOTER --}}
    <div class="sf">
      <div class="sa">
        <div class="sav">SA</div>
        <div style="min-width:0;flex:1;">
          <div style="font-size:11.5px;font-weight:600;
                    white-space:nowrap;overflow:hidden;
                    text-overflow:ellipsis;">
            Super Admin
          </div>
          <div style="font-size:10px;color:var(--text-dim);
                    white-space:nowrap;overflow:hidden;
                    text-overflow:ellipsis;">
            {{ auth()->user()->email }}
          </div>
        </div>
        <a href="{{ route('admin.logout') }}" onclick="return confirm('Are you sure you want to logout?')"
          title="Logout" style="width:28px;height:28px;
                background:rgba(248,113,113,0.1);
                border:1px solid rgba(248,113,113,0.3);
                border-radius:7px;display:flex;
                align-items:center;justify-content:center;
                color:var(--red);text-decoration:none;
                flex-shrink:0;transition:all 0.2s;">
          <i class="fas fa-sign-out-alt" style="font-size:11px;"></i>
        </a>
      </div>
    </div>
  </aside>

  <main class="main">
    <div class="tb">
      <div class="tb-left">
        {{-- HAMBURGER (mobile only) --}}
        <div class="tb-menu" onclick="openSidebar()">
          <i class="fas fa-bars"></i>
        </div>
        <div>
          <h1>@yield('page_title')</h1>
          <p>{{ now()->format('l, d F Y') }}</p>
        </div>
      </div>
      <div class="tb-right">
        <a href="{{ route('admin.logout') }}" class="tbb tbb-logout" title="Logout"
          onclick="return confirm('Are you sure you want to logout?')">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>

    <div class="content">
      @if(session('success'))
        <div id="flashSuccessAlert" class="alert alert-success" style="transition: opacity 0.3s ease, margin-bottom 0.3s ease;">
          <i class="fas fa-check-circle"></i>
          {{ session('success') }}
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert-error">
          <i class="fas fa-times-circle"></i>
          {{ session('error') }}
        </div>
      @endif
      @yield('content')
    </div>
  </main>

  <script>
    /* ── MODAL HELPERS ── */
    function om(id) { document.getElementById(id).classList.add('open'); }
    function cm(id) { document.getElementById(id).classList.remove('open'); }
    document.querySelectorAll('.mo').forEach(m => {
      m.addEventListener('click', e => {
        if (e.target === m) m.classList.remove('open');
      });
    });

    /* ── AUTO-DISMISS SUCCESS ALERT AFTER 2 SECONDS ── */
    document.addEventListener('DOMContentLoaded', function () {
      const successAlert = document.getElementById('flashSuccessAlert') || document.querySelector('.alert-success');
      if (successAlert) {
        setTimeout(function () {
          successAlert.style.opacity = '0';
          setTimeout(function () {
            successAlert.style.display = 'none';
            if (successAlert.parentNode) successAlert.remove();
          }, 300);
        }, 2000);
      }
    });

    /* ── SIDEBAR OPEN / CLOSE (mobile) ── */
    function openSidebar() {
      document.getElementById('sidebar').classList.add('open');
      document.getElementById('sidebarOverlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('sidebarOverlay').classList.remove('open');
      document.body.style.overflow = '';
    }
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) {
        closeSidebar();
        document.body.style.overflow = '';
      }
    });

        /* ── SIDEBAR SCROLL BUTTONS + REMEMBER POSITION ── */
    (function () {
      var sb     = document.getElementById('sidebar');
      var inner  = document.getElementById('ssbInner');
      var step   = 120;
      var KEY    = 'adminSidebarScroll';

      /* Restore the saved position, or centre the active link on first visit */
      try {
        var saved = sessionStorage.getItem(KEY);
        if (saved !== null) {
          sb.scrollTop = parseInt(saved, 10);
        } else {
          var active = sb.querySelector('.ni.active');
          if (active) sb.scrollTop = active.offsetTop - sb.clientHeight / 2;
        }
      } catch (e) {}

      function savePos() {
        try { sessionStorage.setItem(KEY, sb.scrollTop); } catch (e) {}
      }

      /* Save right before navigating away */
      sb.querySelectorAll('a.ni').forEach(function (a) {
        a.addEventListener('click', savePos);
      });
      window.addEventListener('pagehide', savePos);

      function updateBtns() {
        var scrollable = sb.scrollHeight > sb.clientHeight + 8;
        var atTop      = sb.scrollTop <= 4;
        var atBottom   = sb.scrollTop + sb.clientHeight >= sb.scrollHeight - 4;
        inner.classList.toggle('visible', scrollable && !(atTop && atBottom));
      }

      document.getElementById('ssbUp').addEventListener('click', function () {
        sb.scrollBy({ top: -step, behavior: 'smooth' });
      });

      document.getElementById('ssbDown').addEventListener('click', function () {
        sb.scrollBy({ top: step, behavior: 'smooth' });
      });

      sb.addEventListener('scroll', function () { updateBtns(); savePos(); });
      window.addEventListener('resize', updateBtns);

      document.addEventListener('keydown', function (e) {
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA') return;
        if (e.key === 'ArrowUp') {
          sb.scrollBy({ top: -step, behavior: 'smooth' });
          e.preventDefault();
        } else if (e.key === 'ArrowDown') {
          sb.scrollBy({ top: step, behavior: 'smooth' });
          e.preventDefault();
        }
      });

      updateBtns();
    })();
  </script>
  @yield('scripts')
</body>

</html>