@extends('layouts.admin')
@section('title', 'Membership Plans')
@section('page_title', 'Membership Plans')

@section('content')

{{-- ── MEMBERSHIP PLANS HEADER ── --}}
<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-tags"></i>Membership Plans
    </div>
    <button class="btn bp bsm" onclick="om('addPlanM')">
      <i class="fas fa-plus"></i>Add Plan
    </button>
  </div>
</div>

{{-- ── PLAN CARDS ── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:20px;">

  @forelse($plans as $plan)
  <div class="card" style="margin:0">
    <div style="padding:20px;">

      {{-- HEADER --}}
      <div style="display:flex;align-items:flex-start;
                  justify-content:space-between;margin-bottom:14px;">
        <div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
            <div style="width:36px;height:36px;border-radius:9px;
                        display:flex;align-items:center;justify-content:center;
                        font-size:16px;
                        background:{{ $plan->type == 'LIFE'
                          ? 'rgba(245,200,66,0.15)'
                          : 'rgba(79,142,247,0.15)' }}">
              {{ $plan->type == 'LIFE' ? '♾️' : '📅' }}
            </div>
            <div>
              <div style="font-family:'Instrument Serif',serif;
                          font-size:18px;font-weight:600;">
                {{ $plan->name }}
              </div>
              <span class="badge {{ $plan->type == 'LIFE' ? 'bli' : 'bac' }}">
                {{ $plan->type }}
              </span>
            </div>
          </div>
        </div>
        <span class="badge {{ $plan->is_active ? 'bap' : 'brj' }}">
          {{ $plan->is_active ? 'Active' : 'Inactive' }}
        </span>
      </div>

      {{-- AMOUNT --}}
      <div style="margin-bottom:12px;">
        <div style="font-family:'Instrument Serif',serif;
                    font-size:36px;color:var(--accent);line-height:1;">
          ₹{{ number_format($plan->amount, 0) }}
        </div>
        <div style="font-size:11px;color:var(--text-dim);margin-top:3px;">
          @if($plan->duration_months == 0)
            One time payment · Never expires
          @else
            Per year · {{ $plan->duration_months }} months duration
          @endif
        </div>
      </div>

      {{-- DESCRIPTION --}}
      @if($plan->description)
      <div style="font-size:12px;color:var(--text-dim);
                  padding:10px 12px;
                  background:rgba(255,255,255,0.03);
                  border:1px solid var(--border);
                  border-radius:8px;margin-bottom:14px;line-height:1.5;">
        {{ $plan->description }}
      </div>
      @endif

      {{-- FEATURES --}}
      <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px;">
        @if($plan->type == 'LIFE')
          @foreach(['Lifetime membership', 'Never expires', 'Priority event access', 'News & announcements'] as $feature)
          <div style="font-size:12px;color:var(--text-dim);
                      display:flex;align-items:center;gap:6px;">
            <span style="color:var(--green);font-weight:700;">✓</span> {{ $feature }}
          </div>
          @endforeach
        @else
          @foreach(['Full membership access', 'Event registration', 'News & announcements', 'Renewal required yearly'] as $feature)
          <div style="font-size:12px;color:var(--text-dim);
                      display:flex;align-items:center;gap:6px;">
            <span style="color:var(--green);font-weight:700;">✓</span> {{ $feature }}
          </div>
          @endforeach
        @endif
      </div>

      {{-- ACTIONS --}}
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button class="btn bo bsm"
          onclick="editPlan({{ $plan->id }},'{{ addslashes($plan->name) }}','{{ $plan->amount }}','{{ addslashes($plan->description ?? '') }}')">
          <i class="fas fa-edit"></i>Edit
        </button>
        <form method="POST" action="{{ route('admin.plans.toggle-status') }}" style="display:inline">
          @csrf
          <input type="hidden" name="id" value="{{ $plan->id }}">
          <button type="submit"
            class="btn bsm {{ $plan->is_active ? 'bda' : 'bo' }}"
            onclick="return confirm('{{ $plan->is_active ? 'Deactivate' : 'Activate' }} {{ $plan->name }}?')">
            <i class="fas fa-{{ $plan->is_active ? 'toggle-off' : 'toggle-on' }}"></i>
            {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
          </button>
        </form>
        <form method="POST" action="{{ route('admin.plans.delete') }}" style="display:inline"
              onsubmit="return confirm('Delete {{ $plan->name }}? This cannot be undone!')">
          @csrf @method('DELETE')
          <input type="hidden" name="id" value="{{ $plan->id }}">
          <button type="submit" class="btn bda bsm">
            <i class="fas fa-trash"></i>Delete
          </button>
        </form>
      </div>

    </div>
  </div>
  @empty
  <div style="grid-column:1/-1;text-align:center;padding:50px 20px;">
    <div style="font-size:40px;margin-bottom:12px;">📋</div>
    <div style="font-size:14px;font-weight:600;margin-bottom:6px;">No plans created yet</div>
    <div style="font-size:12px;color:var(--text-dim);margin-bottom:16px;">
      Create LIFE and ANNUAL membership plans
    </div>
    <button class="btn bp" onclick="om('addPlanM')">
      <i class="fas fa-plus"></i>Create First Plan
    </button>
  </div>
  @endforelse

</div>

{{-- ── PLAN SUMMARY TABLE ── --}}
@if($plans->count() > 0)
<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-table"></i>Plans Summary</div>
  </div>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Plan Name</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Duration</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($plans as $plan)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td><b>{{ $plan->name }}</b></td>
          <td>
            <span class="badge {{ $plan->type == 'LIFE' ? 'bli' : 'bac' }}">
              {{ $plan->type }}
            </span>
          </td>
          <td style="color:var(--green);font-weight:600;">
            ₹{{ number_format($plan->amount, 0) }}
          </td>
          <td style="color:var(--text-dim)">
            {{ $plan->duration_months == 0 ? 'Lifetime' : $plan->duration_months . ' months' }}
          </td>
          <td>
            <span class="badge {{ $plan->is_active ? 'bap' : 'brj' }}">
              {{ $plan->is_active ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td style="color:var(--text-dim)">{{ $plan->created_at->format('d M Y') }}</td>
          <td>
            <div class="abr">
              <div class="ab ed"
                onclick="editPlan({{ $plan->id }},'{{ addslashes($plan->name) }}','{{ $plan->amount }}','{{ addslashes($plan->description ?? '') }}')">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST" action="{{ route('admin.plans.toggle-status') }}" style="display:inline">
                @csrf
                <input type="hidden" name="id" value="{{ $plan->id }}">
                <button type="submit" class="ab {{ $plan->is_active ? 'rej' : 'app' }}"
                  title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}"
                  onclick="return confirm('{{ $plan->is_active ? 'Deactivate' : 'Activate' }} {{ $plan->name }}?')">
                  <i class="fas fa-{{ $plan->is_active ? 'ban' : 'check' }}"></i>
                </button>
              </form>
              <form method="POST" action="{{ route('admin.plans.delete') }}" style="display:inline"
                    onsubmit="return confirm('Delete {{ $plan->name }}?')">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $plan->id }}">
                <button type="submit" class="ab rej"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif


{{-- ════════════════════════════════
     REGISTRATION FEE SECTION
════════════════════════════════ --}}

{{-- ── FEE HEADER ── --}}
<div class="card" style="margin-top:28px;">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-hand-holding-usd"></i>Registration / Joining Fee
    </div>
    <button class="btn bp bsm" onclick="om('addFeeM')">
      <i class="fas fa-plus"></i>Add Fee
    </button>
  </div>
</div>

{{-- ── FEE CARDS ── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:20px;">

  @forelse($fees as $fee)
  <div class="card" style="margin:0">
    <div style="padding:20px;">

      {{-- HEADER --}}
      <div style="display:flex;align-items:flex-start;
                  justify-content:space-between;margin-bottom:14px;">
        <div style="display:flex;align-items:center;gap:8px;">
          <div style="width:36px;height:36px;border-radius:9px;
                      display:flex;align-items:center;justify-content:center;
                      font-size:16px;background:rgba(52,211,153,0.15);">
            💰
          </div>
          <div>
            <div style="font-family:'Instrument Serif',serif;
                        font-size:18px;font-weight:600;">
              {{ $fee->name }}
            </div>
            <span class="badge bap">One Time</span>
          </div>
        </div>
      </div>

      {{-- AMOUNT --}}
      <div style="margin-bottom:12px;">
        <div style="font-family:'Instrument Serif',serif;
                    font-size:36px;color:var(--accent);line-height:1;">
          ₹{{ number_format($fee->amount, 0) }}
        </div>
        <div style="font-size:11px;color:var(--text-dim);margin-top:3px;">
          One time payment · Paid on registration
        </div>
      </div>

      {{-- FEATURES --}}
      <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px;">
        @foreach(['Paid once at joining', 'Non-refundable', 'Required for all new members'] as $feature)
        <div style="font-size:12px;color:var(--text-dim);
                    display:flex;align-items:center;gap:6px;">
          <span style="color:var(--green);font-weight:700;">✓</span> {{ $feature }}
        </div>
        @endforeach
      </div>

      {{-- ACTIONS --}}
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button class="btn bo bsm"
          onclick="editFee({{ $fee->id }},'{{ addslashes($fee->name) }}','{{ $fee->amount }}')">
          <i class="fas fa-edit"></i>Edit
        </button>
        <form method="POST"
              action="{{ route('admin.plans.registration-fee.delete', $fee->id) }}"
              style="display:inline"
              onsubmit="return confirm('Delete {{ $fee->name }}? This cannot be undone!')">
          @csrf @method('DELETE')
          <button type="submit" class="btn bda bsm">
            <i class="fas fa-trash"></i>Delete
          </button>
        </form>
      </div>

    </div>
  </div>
  @empty
  <div style="grid-column:1/-1;text-align:center;padding:50px 20px;">
    <div style="font-size:40px;margin-bottom:12px;">💰</div>
    <div style="font-size:14px;font-weight:600;margin-bottom:6px;">No registration fee set yet</div>
    <div style="font-size:12px;color:var(--text-dim);margin-bottom:16px;">
      Set a one-time joining fee for new members
    </div>
    <button class="btn bp" onclick="om('addFeeM')">
      <i class="fas fa-plus"></i>Add Registration Fee
    </button>
  </div>
  @endforelse

</div>

{{-- ── FEE SUMMARY TABLE ── --}}
@if($fees->count() > 0)
<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-table"></i>Fee Summary</div>
  </div>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Fee Name</th>
          <th>Amount</th>
          <th>Type</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($fees as $fee)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td><b>{{ $fee->name }}</b></td>
          <td style="color:var(--green);font-weight:600;">
            ₹{{ number_format($fee->amount, 0) }}
          </td>
          <td><span class="badge bap">One Time</span></td>
          <td style="color:var(--text-dim)">{{ $fee->created_at->format('d M Y') }}</td>
          <td>
            <div class="abr">
              <div class="ab ed"
                onclick="editFee({{ $fee->id }},'{{ addslashes($fee->name) }}','{{ $fee->amount }}')">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST"
                action="{{ route('admin.plans.registration-fee.delete', $fee->id) }}"
                style="display:inline"
                onsubmit="return confirm('Delete {{ $fee->name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="ab rej"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif


{{-- ════════════════════════════
     MODALS
════════════════════════════ --}}

{{-- ADD PLAN MODAL --}}
<div id="addPlanM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Create Membership Plan</span>
      <div class="mc3" onclick="cm('addPlanM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('admin.plans.store') }}">
      @csrf
      <div class="fgrid">
        <div class="fg">
          <label>Plan Name</label>
          <input type="text" name="name" placeholder="e.g. Life Plan" required>
        </div>
        <div class="fg">
          <label>Plan Type</label>
          <select name="type" onchange="handleTypeChange(this.value)" required>
            <option value="">Select Type</option>
            <option value="LIFE">LIFE</option>
            <option value="ANNUAL">ANNUAL</option>
          </select>
        </div>
        <div class="fg">
          <label>Amount (₹)</label>
          <input type="number" name="amount" id="planAmount"
                 placeholder="Enter amount" min="0" step="0.01" required>
        </div>
        <div class="fg">
          <label>Duration (months) — 0 = Lifetime</label>
          <input type="number" name="duration_months" id="planDuration"
                 placeholder="0 = Lifetime" min="0" required>
        </div>
        <div class="fg s2">
          <label>Description (optional)</label>
          <textarea name="description" placeholder="Describe this plan..."></textarea>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('addPlanM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-plus"></i>Create Plan
        </button>
      </div>
    </form>
  </div>
</div>

{{-- EDIT PLAN MODAL --}}
<div id="editPlanM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit Plan</span>
      <div class="mc3" onclick="cm('editPlanM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('admin.plans.update') }}">
      @csrf
      <input type="hidden" name="id" id="editPlanId">
      <div class="fgrid">
        <div class="fg">
          <label>Plan Name</label>
          <input type="text" name="name" id="editPlanName" required>
        </div>
        <div class="fg">
          <label>Amount (₹)</label>
          <input type="number" name="amount" id="editPlanAmount"
                 min="0" step="0.01" required>
        </div>
        <div class="fg s2">
          <label>Description (optional)</label>
          <textarea name="description" id="editPlanDesc"></textarea>
        </div>
      </div>
      <div style="margin:0 18px 14px;padding:10px 12px;
                  background:rgba(245,200,66,0.07);border-radius:8px;
                  font-size:11px;color:var(--text-dim);">
        <i class="fas fa-info-circle" style="color:var(--gold)"></i>
        Updating the amount applies to <b style="color:var(--gold)">new payments only</b>.
        Existing memberships are not affected.
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('editPlanM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Update Plan
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ADD FEE MODAL --}}
<div id="addFeeM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Add Registration Fee</span>
      <div class="mc3" onclick="cm('addFeeM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('admin.plans.registration-fee') }}">
      @csrf
      <div class="fgrid">
        <div class="fg">
          <label>Fee Name</label>
          <input type="text" name="name" placeholder="Registration Fee" required>
        </div>
        <div class="fg">
          <label>Amount (₹)</label>
          <input type="number" name="amount" placeholder="500" min="0" step="0.01" required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('addFeeM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Save Fee
        </button>
      </div>
    </form>
  </div>
</div>

{{-- EDIT FEE MODAL --}}
<div id="editFeeM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit Registration Fee</span>
      <div class="mc3" onclick="cm('editFeeM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('admin.plans.registration-fee.update') }}">
      @csrf
      <input type="hidden" name="id" id="editFeeId">
      <div class="fgrid">
        <div class="fg">
          <label>Fee Name</label>
          <input type="text" name="name" id="editFeeName" required>
        </div>
        <div class="fg">
          <label>Amount (₹)</label>
          <input type="number" name="amount" id="editFeeAmount"
                 min="0" step="0.01" required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('editFeeM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Update Fee
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function editPlan(id, name, amount, description) {
  document.getElementById('editPlanId').value     = id;
  document.getElementById('editPlanName').value   = name;
  document.getElementById('editPlanAmount').value = amount;
  document.getElementById('editPlanDesc').value   = description || '';
  om('editPlanM');
}

function editFee(id, name, amount) {
  document.getElementById('editFeeId').value     = id;
  document.getElementById('editFeeName').value   = name;
  document.getElementById('editFeeAmount').value = amount;
  om('editFeeM');
}

function handleTypeChange(type) {
  const amount   = document.getElementById('planAmount');
  const duration = document.getElementById('planDuration');
  if (type === 'LIFE') {
    amount.placeholder   = '5000';
    duration.value       = '0';
    duration.placeholder = '0 (Lifetime)';
  } else if (type === 'ANNUAL') {
    amount.placeholder   = '500';
    duration.value       = '12';
    duration.placeholder = '12 months';
  }
}
</script>
@endsection