<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fumma Unit Admin - @yield('title')</title>
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

    * { margin: 0; padding: 0; box-sizing: border-box; }

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
      left: 0; top: 0;
      display: flex;
      flex-direction: column;
      z-index: 100;
      overflow-y: auto;
      overflow-x: hidden;
      transition: transform 0.3s ease;
      height: 100vh;
    }

    .sl { padding: 20px 18px 14px; border-bottom: 1px solid var(--border); }
    .sl-row { display: flex; align-items: center; gap: 9px; margin-bottom: 3px; }
    .sl-icon {
      width: 34px; height: 34px;
      background: linear-gradient(135deg, var(--accent), #7c3aed);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 15px; flex-shrink: 0;
    }
    .sl-text { font-family: 'Instrument Serif', serif; font-size: 19px; }
    .sl-sub { font-size: 10px; color: var(--text-dim); }

    /* Unit badge in sidebar */
    .unit-info {
      margin: 10px 16px 0;
      padding: 8px 10px;
      background: rgba(79,142,247,0.07);
      border: 1px solid rgba(79,142,247,0.18);
      border-radius: 7px;
    }
    .unit-info .ui-label { font-size: 9px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
    .unit-info .ui-name { font-size: 12px; font-weight: 600; color: var(--accent-2); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .snav { flex: 1; padding: 8px 0; }

    .ns {
      padding: 12px 16px 3px;
      font-size: 9px; font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--text-dim);
    }

    .ni {
      display: flex; align-items: center; gap: 9px;
      padding: 8px 16px;
      color: var(--text-dim);
      font-size: 12.5px; font-weight: 500;
      text-decoration: none;
      transition: all 0.2s;
      border-left: 2px solid transparent;
    }
    .ni:hover { color: var(--text); background: var(--card); border-left-color: var(--accent); }
    .ni.active { color: var(--accent-2); background: rgba(79,142,247,0.1); border-left-color: var(--accent); }
    .ni i { width: 15px; font-size: 12px; text-align: center; }

    .sf {
      padding: 12px 16px;
      border-top: 1px solid var(--border);
      flex-shrink: 0;
      background: var(--navy-2);
      position: sticky;
      bottom: 0;
    }
    .sa { display: flex; align-items: center; gap: 9px; }
    .sav {
      width: 32px; height: 32px;
      background: linear-gradient(135deg, var(--accent), #7c3aed);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 11px; flex-shrink: 0;
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

    /* ── SIDEBAR OVERLAY ── */
    .sidebar-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.5); z-index: 99;
    }
    .sidebar-overlay.open { display: block; }

    /* ── MAIN ── */
    .main { margin-left: var(--sw); flex: 1; display: flex; flex-direction: column; min-width: 0; }

    .tb {
      padding: 13px 26px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      background: var(--navy-2);
      position: sticky; top: 0; z-index: 98;
      gap: 12px;
    }
    .tb-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .tb-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    .tb-menu {
      display: none; width: 32px; height: 32px;
      background: var(--card); border: 1px solid var(--border); border-radius: 7px;
      align-items: center; justify-content: center;
      cursor: pointer; color: var(--text-dim); font-size: 14px; flex-shrink: 0;
    }

    .tb h1 { font-family: 'Instrument Serif', serif; font-size: 19px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tb p { font-size: 10.5px; color: var(--text-dim); margin-top: 1px; white-space: nowrap; }

    .tbb {
      width: 32px; height: 32px;
      background: var(--card); border: 1px solid var(--border); border-radius: 7px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--text-dim); font-size: 12px;
      transition: all 0.2s; text-decoration: none; flex-shrink: 0;
    }
    .tbb:hover { background: var(--card-hover); color: var(--text); }
    .tbb-logout { color: var(--red); border-color: rgba(248,113,113,0.3); background: rgba(248,113,113,0.08); }
    .tbb-logout:hover { background: rgba(248,113,113,0.2); color: var(--red); }

    .content { padding: 22px 26px; flex: 1; }

    /* ── CARD ── */
    .card { background: var(--card); border: 1px solid var(--border); border-radius: 11px; overflow: hidden; margin-bottom: 18px; }
    .ch { padding: 13px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    .ct { font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
    .ct i { color: var(--accent); font-size: 11px; }
    .ca { font-size: 11px; color: var(--accent); cursor: pointer; font-weight: 600; text-decoration: none; }
    .ca:hover { color: var(--accent-2); }

    /* ── BUTTONS ── */
    .btn {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 6px 13px; border-radius: 7px;
      font-size: 11.5px; font-weight: 600;
      cursor: pointer; border: none;
      transition: all 0.2s; font-family: 'DM Sans', sans-serif; text-decoration: none;
    }
    .bp { background: var(--accent); color: #fff; }
    .bp:hover { background: var(--accent-2); color: var(--navy); }
    .bo { background: transparent; color: var(--text-dim); border: 1px solid var(--border); }
    .bo:hover { background: var(--card-hover); color: var(--text); }
    .bda { background: rgba(248,113,113,0.15); color: var(--red); border: 1px solid rgba(248,113,113,0.3); }
    .bda:hover { background: rgba(248,113,113,0.25); }
    .bsm { padding: 5px 10px; font-size: 10.5px; }
    .bsuc { background: rgba(52,211,153,0.15); color: var(--green); border: 1px solid rgba(52,211,153,0.3); }
    .bsuc:hover { background: rgba(52,211,153,0.25); }

    /* ── TABLE ── */
    .tw { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table { width: 100%; border-collapse: collapse; }
    thead th { padding: 9px 13px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-dim); background: rgba(255,255,255,0.02); text-align: left; white-space: nowrap; }
    tbody td { padding: 11px 13px; font-size: 12px; color: var(--text); border-top: 1px solid var(--border); vertical-align: middle; white-space: nowrap; }
    tbody tr:hover td { background: var(--card-hover); }

    /* ── BADGE ── */
    .badge { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
    .badge::before { content: ''; width: 4px; height: 4px; border-radius: 50%; background: currentColor; }
    .bac { background: rgba(79,142,247,0.15); color: var(--accent-2); }
    .brj { background: rgba(248,113,113,0.15); color: var(--red); }
    .bpe { background: rgba(251,146,60,0.15); color: var(--orange); }
    .bap { background: rgba(52,211,153,0.15); color: var(--green); }
    .bli { background: rgba(245,200,66,0.15); color: var(--gold); }
    .ban { background: rgba(79,142,247,0.15); color: var(--accent-2); }
    .bex { background: rgba(167,139,250,0.15); color: var(--purple); }
    .bup { background: rgba(52,211,153,0.15); color: var(--green); }
    .bcm { background: rgba(167,139,250,0.15); color: var(--purple); }
    .bcl { background: rgba(248,113,113,0.15); color: var(--red); }
    .bog { background: rgba(79,142,247,0.15); color: var(--accent-2); }

    /* ── ACTION BUTTONS ── */
    .ab { width: 24px; height: 24px; border-radius: 5px; border: 1px solid var(--border); background: var(--card); color: var(--text-dim); display: inline-flex; align-items: center; justify-content: center; font-size: 9px; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .ab:hover { background: var(--card-hover); color: var(--text); }
    .ab.app:hover { background: rgba(52,211,153,0.15); color: var(--green); border-color: var(--green); }
    .ab.rej:hover { background: rgba(248,113,113,0.15); color: var(--red); border-color: var(--red); }
    .ab.vw:hover { background: rgba(79,142,247,0.15); color: var(--accent-2); border-color: var(--accent); }
    .abr { display: flex; gap: 4px; }

    /* ── FORM ── */
    .fgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 18px; }
    .fgrid.c1 { grid-template-columns: 1fr; }
    .fg { display: flex; flex-direction: column; gap: 4px; }
    .fg.s2 { grid-column: span 2; }
    label { font-size: 10.5px; font-weight: 600; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px; }
    input, select, textarea { background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 7px; padding: 8px 11px; color: var(--text); font-size: 12px; font-family: 'DM Sans', sans-serif; transition: border 0.2s; outline: none; width: 100%; }
    input:focus, select:focus, textarea:focus { border-color: var(--accent); }
    select option { background: var(--navy-2); }
    .ff { padding: 12px 18px; border-top: 1px solid var(--border); display: flex; gap: 7px; justify-content: flex-end; flex-wrap: wrap; }

    /* ── MODAL ── */
    .mo { position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 200; display: none; align-items: center; justify-content: center; padding: 16px; }
    .mo.open { display: flex; }
    .md { background: var(--navy-2); border: 1px solid var(--border); border-radius: 13px; width: 500px; max-width: 100%; max-height: 90vh; overflow-y: auto; }
    .md.md-lg { width: 680px; }
    .mh { padding: 16px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .mt2 { font-size: 14px; font-weight: 600; }
    .mc3 { width: 28px; height: 28px; background: var(--card); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; color: var(--text-dim); display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
    .mc3:hover { color: var(--text); }

    /* ── MISC ── */
    .alert { padding: 12px 16px; border-radius: 8px; font-size: 12px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .alert-success { background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.3); color: var(--green); }
    .alert-error { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.3); color: var(--red); }

    /* ── STAT CARDS ── */
    .sg { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
    .sc { background: var(--card); border: 1px solid var(--border); border-radius: 11px; padding: 16px; transition: all 0.25s; position: relative; overflow: hidden; }
    .sc::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--ca, var(--accent)); opacity: 0; transition: opacity 0.25s; }
    .sc:hover { background: var(--card-hover); transform: translateY(-2px); }
    .sc:hover::before { opacity: 1; }
    .st { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .si { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
    .sv { font-family: 'Instrument Serif', serif; font-size: 28px; color: var(--text); line-height: 1; margin-bottom: 3px; }
    .sl2 { font-size: 11px; color: var(--text-dim); font-weight: 500; }

    /* ── MEMBER CELL ── */
    .mc2 { display: flex; align-items: center; gap: 8px; }
    .av2 { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; flex-shrink: 0; }
    .mn { font-weight: 600; font-size: 12px; }
    .me { font-size: 10px; color: var(--text-dim); }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

    /* ── EMPTY STATE ── */
    .empty-state { text-align: center; padding: 48px 20px; color: var(--text-dim); }
    .empty-state i { font-size: 32px; margin-bottom: 12px; opacity: 0.3; display: block; }
    .empty-state p { font-size: 13px; }

    /* ── DETAIL GRID (for modals) ── */
    .dg { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 18px; }
    .dg.c1 { grid-template-columns: 1fr; }
    .df { display: flex; flex-direction: column; gap: 3px; }
    .df.s2 { grid-column: span 2; }
    .dl { font-size: 9.5px; font-weight: 700; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.8px; }
    .dv { font-size: 12.5px; font-weight: 500; color: var(--text); }

    /* ── RESPONSIVE ── */
    @media(max-width:1024px) { .sg { grid-template-columns: repeat(3,1fr); } }
    @media(max-width:768px) {
      .tb-menu { display: flex; }
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; }
      .tb { padding: 12px 16px; }
      .tb h1 { font-size: 16px; }
      .tb p { display: none; }
      .content { padding: 16px; }
      .sg { grid-template-columns: repeat(2,1fr); gap: 10px; }
      .sv { font-size: 22px; }
      .fgrid { grid-template-columns: 1fr; gap: 10px; padding: 14px; }
      .fg.s2 { grid-column: span 1; }
      .dg { grid-template-columns: 1fr; }
      .df.s2 { grid-column: span 1; }
    }
    @media(max-width:480px) {
      .sg { grid-template-columns: 1fr 1fr; gap: 8px; }
      .sc { padding: 12px; }
      .sv { font-size: 20px; }
      .sl2 { font-size: 10px; }
      .si { width: 30px; height: 30px; font-size: 12px; }
      .content { padding: 12px; }
      .tb { padding: 10px 14px; }
      .tb h1 { font-size: 15px; }
      .md { border-radius: 10px; }
      .fgrid { padding: 12px; }
      .bsm { padding: 4px 9px; font-size: 10px; }
      .hide-mobile { display: none; }
      .alert { font-size: 11px; padding: 10px 12px; }
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
        <span class="sl-text">Fumma</span>
      </div>
      <div class="sl-sub">Unit Admin Panel</div>
    </div>

    {{-- Unit info badge --}}
    <div class="unit-info">
      <div class="ui-label">Your Unit</div>
      <div class="ui-name">{{ auth()->user()->unitAdmin->unit->name ?? '—' }}</div>
    </div>

    <nav class="snav">
      <div class="ns">Overview</div>
      <a class="ni {{ request()->routeIs('unit-admin.dashboard') ? 'active' : '' }}"
         href="{{ route('unit-admin.dashboard') }}" onclick="closeSidebar()">
        <i class="fas fa-th-large"></i>Dashboard
      </a>

      <div class="ns">Members</div>
      <a class="ni {{ request()->routeIs('unit-admin.members*') ? 'active' : '' }}"
         href="{{ route('unit-admin.members') }}" onclick="closeSidebar()">
        <i class="fas fa-users"></i>Members
      </a>
      <a class="ni {{ request()->routeIs('unit-admin.bulk-import*') ? 'active' : '' }}"
         href="{{ route('unit-admin.bulk-import') }}" onclick="closeSidebar()">
        <i class="fas fa-file-csv"></i>Bulk Import
      </a>

      <div class="ns">Content</div>
      <a class="ni {{ request()->routeIs('unit-admin.news*') ? 'active' : '' }}"
         href="{{ route('unit-admin.news') }}" onclick="closeSidebar()">
        <i class="fas fa-newspaper"></i>News
      </a>
      <a class="ni {{ request()->routeIs('unit-admin.events*') ? 'active' : '' }}"
         href="{{ route('unit-admin.events') }}" onclick="closeSidebar()">
        <i class="fas fa-calendar-alt"></i>Events
      </a>
      
      <a class="ni {{ request()->routeIs('unit-admin.fundraising*') ? 'active' : '' }}"
   href="{{ route('unit-admin.fundraising') }}" onclick="closeSidebar()">
  <i class="fas fa-hand-holding-heart"></i>Fundraising
</a>

      <a class="ni {{ request()->routeIs('unit-admin.committees*') ? 'active' : '' }}"
         href="{{ route('unit-admin.committees') }}" onclick="closeSidebar()">
        <i class="fas fa-users-cog"></i>Committee
      </a>
      
            <a class="ni {{ request()->routeIs('unit-admin.gallery*') ? 'active' : '' }}"
         href="{{ route('unit-admin.gallery') }}" onclick="closeSidebar()">
        <i class="fas fa-images"></i>Gallery
      </a>


      <a class="ni {{ request()->routeIs('unit-admin.other-fees*') ? 'active' : '' }}"
   href="{{ route('unit-admin.other-fees') }}" onclick="closeSidebar()">
  <i class="fas fa-receipt"></i>Other Fees
</a>

<a class="ni {{ request()->routeIs('unit-admin.expenses*') ? 'active' : '' }}"
   href="{{ route('unit-admin.expenses') }}" onclick="closeSidebar()">
  <i class="fas fa-wallet"></i>Expenses
</a>

 <a class="ni {{ request()->routeIs('unit-admin.schemes*') ? 'active' : '' }}"
   href="{{ route('unit-admin.schemes') }}" onclick="closeSidebar()">
  <i class="fas fa-piggy-bank"></i>Schemes
</a>

      <div class="ns">Analytics</div>
      <a class="ni {{ request()->routeIs('unit-admin.reports*') ? 'active' : '' }}"
         href="{{ route('unit-admin.reports') }}" onclick="closeSidebar()">
        <i class="fas fa-chart-bar"></i>Reports
      </a>
      
      <a class="ni {{ request()->routeIs('unit-admin.renewals-manage*') ? 'active' : '' }}"
         href="{{ route('unit-admin.renewals-manage') }}" onclick="closeSidebar()">
        <i class="fas fa-sync-alt"></i>Renewals
      </a>
      {{-- Payments (commented out) --}}
      {{-- <a class="ni {{ request()->routeIs('unit-admin.payments*') ? 'active' : '' }}"
         href="{{ route('unit-admin.payments') }}" onclick="closeSidebar()">
        <i class="fas fa-credit-card"></i>Payments
      </a> --}}

      <div class="ns">Account</div>
      <a class="ni {{ request()->routeIs('unit-admin.change-password*') ? 'active' : '' }}"
         href="{{ route('unit-admin.change-password') }}" onclick="closeSidebar()">
        <i class="fas fa-key"></i>Change Password
      </a>
      <a class="ni" href="{{ route('unit-admin.logout') }}"
         onclick="return confirm('Are you sure you want to logout?')"
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
        <div class="sav">
          {{ strtoupper(substr(explode(' ', trim(auth()->user()->name))[0], 0, 1)) }}{{ strtoupper(substr(explode(' ', trim(auth()->user()->name))[1] ?? '', 0, 1)) }}
        </div>
        <div style="min-width:0;flex:1;">
          <div style="font-size:11.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            {{ auth()->user()->name }}
          </div>
          <div style="font-size:10px;color:var(--text-dim);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            {{ auth()->user()->email }}
          </div>
        </div>
        <a href="{{ route('unit-admin.logout') }}"
           onclick="return confirm('Are you sure you want to logout?')"
           title="Logout"
           style="width:28px;height:28px;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.3);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--red);text-decoration:none;flex-shrink:0;transition:all 0.2s;">
          <i class="fas fa-sign-out-alt" style="font-size:11px;"></i>
        </a>
      </div>
    </div>
  </aside>

  <main class="main">
    <div class="tb">
      <div class="tb-left">
        <div class="tb-menu" onclick="openSidebar()">
          <i class="fas fa-bars"></i>
        </div>
        <div>
          <h1>@yield('page_title')</h1>
          <p>{{ now()->format('l, d F Y') }}</p>
        </div>
      </div>
      <div class="tb-right">
        <a href="{{ route('unit-admin.logout') }}" class="tbb tbb-logout" title="Logout"
           onclick="return confirm('Are you sure you want to logout?')">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>

    <div class="content">
      @if(session('success'))
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert-error">
          <i class="fas fa-times-circle"></i> {{ session('error') }}
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
      m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
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
      if (window.innerWidth > 768) { closeSidebar(); document.body.style.overflow = ''; }
    });

        /* ── SIDEBAR SCROLL BUTTONS + REMEMBER POSITION ── */
    (function () {
      var sb    = document.getElementById('sidebar');
      var inner = document.getElementById('ssbInner');
      var step  = 120;
      var KEY   = 'unitAdminSidebarScroll';

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