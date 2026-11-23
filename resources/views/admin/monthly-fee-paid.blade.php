@extends('layouts.admin')
@section('title', 'Paid Monthly Fees')
@section('page_title', 'Monthly Fee — Paid Members')

@section('content')

  {{-- ── SUMMARY KPI CARDS ── --}}
  <div class="sg">
    <div class="sc" style="--ca:var(--green);">
      <div class="st">
        <div class="si" style="background:rgba(52,211,153,0.15)">
          <i class="fas fa-wallet" style="color:var(--green)"></i>
        </div>
        <span class="str tu">● Total</span>
      </div>
      <div class="sv">₹{{ number_format($stats['total_amount'], 2) }}</div>
      <div class="sl2">Total Fee Collected</div>
    </div>

    <div class="sc" style="--ca:#22d3ee;">
      <div class="st">
        <div class="si" style="background:rgba(34,211,238,0.15)">
          <i class="fas fa-receipt" style="color:#22d3ee"></i>
        </div>
        <span class="str tn">● Dues</span>
      </div>
      <div class="sv">{{ number_format($stats['total_count']) }}</div>
      <div class="sl2">Total Dues Cleared</div>
    </div>

    <div class="sc" style="--ca:var(--accent);">
      <div class="st">
        <div class="si" style="background:rgba(79,142,247,0.15)">
          <i class="fas fa-users" style="color:var(--accent)"></i>
        </div>
        <span class="str" style="background:rgba(79,142,247,0.15);color:var(--accent);">● Members</span>
      </div>
      <div class="sv">{{ number_format($stats['distinct_members']) }}</div>
      <div class="sl2">Paid Members</div>
    </div>

    <div class="sc" style="--ca:var(--orange);">
      <div class="st">
        <div class="si" style="background:rgba(251,146,60,0.15)">
          <i class="fas fa-calendar-check" style="color:var(--orange)"></i>
        </div>
        <span class="str" style="background:rgba(251,146,60,0.15);color:var(--orange);">● This Month</span>
      </div>
      <div class="sv">₹{{ number_format($stats['this_month_amount'], 2) }}</div>
      <div class="sl2">{{ $stats['this_month_count'] }} Paid This Month</div>
    </div>
  </div>

  {{-- ── MAIN CARD ── --}}
  <div class="card">
    <div class="ch" style="flex-wrap:wrap;gap:10px;">
      <div class="ct">
        <i class="fas fa-user-check"></i>Members with Paid Monthly Fees
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('admin.monthly-fee.paid', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn bo bsm">
          <i class="fas fa-download"></i>Export CSV
        </a>
        <a href="{{ route('admin.monthly-fee') }}" class="btn bo bsm">
          <i class="fas fa-cog"></i>Fee Settings
        </a>
      </div>
    </div>

    {{-- ── FILTER BAR ── --}}
    <form method="GET" action="{{ route('admin.monthly-fee.paid') }}">
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        
        {{-- Search Member --}}
        <div class="fg" style="margin:0;min-width:180px;flex:2;">
          <label style="font-size:11px;font-weight:600;color:var(--text-dim);margin-bottom:4px;display:block;">Search</label>
          <input type="text" name="search" value="{{ request('search') }}"
                 placeholder="Search by name, ID, phone, email..." style="width:100%;height:36px;font-size:12px;">
        </div>

        {{-- Month Filter --}}
        <div class="fg" style="margin:0;min-width:140px;flex:1;">
          <label style="font-size:11px;font-weight:600;color:var(--text-dim);margin-bottom:4px;display:block;">Fee Month</label>
          <select name="month_year" style="width:100%;height:36px;font-size:12px;">
            <option value="">All Months</option>
            @foreach($availableMonths as $my)
              @php
                try {
                  $formattedM = \Carbon\Carbon::createFromFormat('Y-m', $my)->format('M Y');
                } catch (\Throwable $e) {
                  $formattedM = $my;
                }
              @endphp
              <option value="{{ $my }}" {{ request('month_year') === $my ? 'selected' : '' }}>
                {{ $formattedM }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Unit Filter --}}
        <div class="fg" style="margin:0;min-width:150px;flex:1;">
          <label style="font-size:11px;font-weight:600;color:var(--text-dim);margin-bottom:4px;display:block;">Unit</label>
          <select name="unit_id" style="width:100%;height:36px;font-size:12px;">
            <option value="">All Units</option>
            @foreach($units as $u)
              <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>
                {{ $u->name }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Date From --}}
        <div class="fg" style="margin:0;min-width:130px;flex:1;">
          <label style="font-size:11px;font-weight:600;color:var(--text-dim);margin-bottom:4px;display:block;">Paid From</label>
          <input type="date" name="date_from" value="{{ request('date_from') }}" style="width:100%;height:36px;font-size:12px;">
        </div>

        {{-- Date To --}}
        <div class="fg" style="margin:0;min-width:130px;flex:1;">
          <label style="font-size:11px;font-weight:600;color:var(--text-dim);margin-bottom:4px;display:block;">Paid To</label>
          <input type="date" name="date_to" value="{{ request('date_to') }}" style="width:100%;height:36px;font-size:12px;">
        </div>

        {{-- Action buttons --}}
        <div style="display:flex;gap:6px;">
          <button type="submit" class="btn bp bsm" style="height:36px;padding:0 14px;">
            <i class="fas fa-filter"></i> Filter
          </button>
          @if(request()->hasAny(['search', 'month_year', 'unit_id', 'date_from', 'date_to']))
            <a href="{{ route('admin.monthly-fee.paid') }}" class="btn bo bsm" style="height:36px;padding:0 12px;line-height:36px;" title="Reset filters">
              <i class="fas fa-rotate-left"></i>
            </a>
          @endif
        </div>

      </div>
    </form>

    {{-- ── TABLE ── --}}
    <div class="tw">
      <table style="min-width:860px;">
        <thead>
          <tr>
            <th style="width:50px;">#</th>
            <th>Member</th>
            <th>Unit</th>
            <th>Fee Month</th>
            <th>Amount</th>
            <th>Payment Details</th>
            <th>Status</th>
            <th>Paid Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse($dues as $index => $due)
            @php
              $profile = $due->memberProfile;
              $user = $profile?->user;
              $tx = $due->payment_transaction;

              $feeMonthName = '—';
              if ($due->month_year) {
                try {
                  $feeMonthName = \Carbon\Carbon::createFromFormat('Y-m', $due->month_year)->format('M Y');
                } catch (\Throwable $e) {
                  $feeMonthName = $due->month_year;
                }
              } elseif ($due->due_date) {
                $feeMonthName = \Carbon\Carbon::parse($due->due_date)->format('M Y');
              }
            @endphp
            <tr>
              <td style="color:var(--text-dim);">
                {{ $dues->firstItem() + $index }}
              </td>

              {{-- Member Details --}}
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  @if($profile?->photo)
                    <img src="{{ asset('storage/' . $profile->photo) }}"
                         alt="{{ $user?->name }}"
                         style="width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid var(--border);">
                  @else
                    <div class="av2" style="width:36px;height:36px;border-radius:8px;background:rgba(79,142,247,0.15);color:var(--accent);font-weight:600;display:flex;align-items:center;justify-content:center;font-size:12px;">
                      {{ strtoupper(substr($user?->name ?? 'M', 0, 2)) }}
                    </div>
                  @endif

                  <div>
                    <div style="font-weight:600;color:var(--text);font-size:13px;line-height:1.2;">
                      {{ $user?->name ?? '—' }}
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:2px;font-size:11px;color:var(--text-dim);">
                      @if($profile?->membership_id)
                        <span style="font-family:monospace;background:rgba(79,142,247,0.1);color:var(--accent);padding:1px 5px;border-radius:4px;font-size:10px;font-weight:600;">
                          {{ $profile->membership_id }}
                        </span>
                      @endif
                      @if($user?->phone)
                        <span><i class="fas fa-phone" style="font-size:9px;opacity:0.7;"></i> {{ $user->phone }}</span>
                      @endif
                    </div>
                    @if($user?->email)
                      <div style="font-size:11px;color:var(--text-dim);margin-top:1px;">
                        <i class="fas fa-envelope" style="font-size:9px;opacity:0.7;"></i> {{ $user->email }}
                      </div>
                    @endif
                  </div>
                </div>
              </td>

              {{-- Unit --}}
              <td style="color:var(--text);">
                {{ $profile?->unit?->name ?? '—' }}
              </td>

              {{-- Fee Month --}}
              <td>
                <span style="font-weight:600;color:var(--text);">
                  {{ $feeMonthName }}
                </span>
                @if($due->due_date)
                  <div style="font-size:11px;color:var(--text-dim);">
                    Due: {{ \Carbon\Carbon::parse($due->due_date)->format('d M Y') }}
                  </div>
                @endif
              </td>

              {{-- Amount --}}
              <td>
                <strong style="color:var(--green);font-size:13px;">
                  ₹{{ number_format($due->amount, 2) }}
                </strong>
              </td>

              {{-- Payment Details --}}
              <td>
                <div style="font-size:12px;color:var(--text);font-weight:500;">
                  {{ $tx?->method ?? 'Online / Gateway' }}
                </div>
                @if($tx?->gateway_transaction_id)
                  <div style="font-size:10px;font-family:monospace;color:var(--text-dim);margin-top:2px;">
                    Ref: {{ $tx->gateway_transaction_id }}
                  </div>
                @elseif($tx)
                  <div style="font-size:10px;font-family:monospace;color:var(--text-dim);margin-top:2px;">
                    Txn: #{{ $tx->id }}
                  </div>
                @endif
              </td>

              {{-- Status --}}
              <td>
                <span class="badge bac" style="display:inline-flex;align-items:center;gap:4px;">
                  <i class="fas fa-check-circle" style="font-size:10px;"></i> PAID
                </span>
              </td>

              {{-- Paid Date --}}
              <td style="color:var(--text-dim);font-size:12px;">
                @if($due->paid_date)
                  <span style="color:var(--text);font-weight:500;">
                    {{ \Carbon\Carbon::parse($due->paid_date)->format('d M Y') }}
                  </span>
                @else
                  —
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align:center;padding:48px 16px;">
                <div style="width:48px;height:48px;border-radius:50%;background:rgba(79,142,247,0.1);color:var(--accent);display:inline-flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:12px;">
                  <i class="fas fa-receipt"></i>
                </div>
                <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:4px;">No paid monthly fees found</div>
                <div style="font-size:12px;color:var(--text-dim);max-width:320px;margin:0 auto;">
                  @if(request()->hasAny(['search', 'month_year', 'unit_id', 'date_from', 'date_to']))
                    Try adjusting or clearing your filters to see more records.
                  @else
                    When members pay their monthly membership fees, they will appear here.
                  @endif
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ── PAGINATION ── --}}
    @if($dues->hasPages())
      <div style="padding:12px 16px;border-top:1px solid var(--border);
                  display:flex;align-items:center;justify-content:space-between;
                  flex-wrap:wrap;gap:8px;">
        <span style="font-size:11px;color:var(--text-dim);">
          Showing {{ $dues->firstItem() }}–{{ $dues->lastItem() }} of {{ $dues->total() }} records
        </span>
        {{ $dues->appends(request()->query())->links('pagination.custom') }}
      </div>
    @endif

  </div>

@endsection
