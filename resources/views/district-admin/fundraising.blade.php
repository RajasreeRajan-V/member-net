@extends('layouts.district-admin')
@section('title', 'Fundraising')
@section('page_title', 'Fundraising Campaigns')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-hand-holding-heart"></i> All Campaigns</div>
    <button class="btn bp bsm" onclick="resetAddCampaignModal(); om('addCampaignM')">
      <i class="fas fa-plus"></i> Create Campaign
    </button>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Campaign</th>
          <th>Progress</th>
          <th>End Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($campaigns as $c)
          @php
            $pct = $c->target_amount > 0
              ? min(100, round(($c->raised_amount / $c->target_amount) * 100))
              : 0;
          @endphp
          <tr>
            <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>

            <td>
              @if($c->image)
                <img src="{{ asset('storage/' . $c->image) }}" alt="Campaign Image"
                     style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
              @else
                <span style="font-size:10px;color:var(--text-dim)">No Image</span>
              @endif
            </td>

            <td>
              <div class="mn">{{ $c->title }}</div>
              <div class="me">{{ \Illuminate\Support\Str::limit($c->description, 40) }}</div>
            </td>

            <td style="color:var(--text-dim);min-width:150px;">
              <div>₹{{ number_format(min($c->raised_amount, $c->target_amount)) }} / ₹{{ number_format($c->target_amount) }}</div>
              <div style="height:5px;background:var(--border);border-radius:5px;margin:5px 0 3px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:var(--green);border-radius:5px;"></div>
              </div>
              <div style="font-size:10px;">{{ $pct }}% funded</div>
            </td>

            <td style="color:var(--text-dim)">
              {{ $c->end_date ? $c->end_date->format('d M Y') : '—' }}
            </td>

            <td>
              <select onchange="updateCampaignStatus({{ $c->id }}, this.value)"
                      style="padding:3px 7px;font-size:10px;border-radius:20px;width:auto;
                             background:transparent;border:1px solid var(--border);
                             color:
                             @if($c->status == 'ACTIVE') var(--green)
                             @elseif($c->status == 'COMPLETED') var(--accent)
                             @else var(--red) @endif;
                             cursor:pointer;">
                <option value="ACTIVE"    {{ $c->status == 'ACTIVE'    ? 'selected' : '' }}>ACTIVE</option>
                <option value="COMPLETED" {{ $c->status == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                <option value="CANCELLED" {{ $c->status == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
              </select>

              <form id="campaignStatusForm{{ $c->id }}" method="POST"
                    action="{{ route('district-admin.fundraising.status') }}" style="display:none">
                @csrf
                <input type="hidden" name="id" value="{{ $c->id }}">
                <input type="hidden" name="status" id="campaignStatusVal{{ $c->id }}">
              </form>
            </td>

            <td>
              <div class="abr">
                <div class="ab ed" onclick="editCampaign({{ $c->id }})" title="Edit">
                  <i class="fas fa-edit"></i>
                </div>
                <form method="POST" action="{{ route('district-admin.fundraising.delete') }}"
                      style="display:inline"
                      onsubmit="return confirm('Delete this campaign?')">
                  @csrf @method('DELETE')
                  <input type="hidden" name="id" value="{{ $c->id }}">
                  <button type="submit" class="ab rej" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center;color:var(--text-dim);padding:40px;">
              <div style="font-size:32px;margin-bottom:10px;">💝</div>
              <div style="font-size:13px;font-weight:600;margin-bottom:10px;">No campaigns yet</div>
              <button class="btn bp bsm" onclick="resetAddCampaignModal(); om('addCampaignM')">
                <i class="fas fa-plus"></i> Create Campaign
              </button>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($campaigns->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);">
      {{ $campaigns->links('pagination.custom') }}
    </div>
  @endif
</div>

{{-- ══ ADD CAMPAIGN MODAL ══ --}}
<div id="addCampaignM" class="mo">
  <div class="md" style="width:600px">
    <div class="mh">
      <span class="mt2">Create New Campaign</span>
      <div class="mc3" onclick="resetAddCampaignModal(); cm('addCampaignM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.fundraising.store') }}" enctype="multipart/form-data" id="addCampaignForm">
      @csrf
      <input type="hidden" name="form" value="create">

      @if ($errors->any() && old('form') === 'create')
        <div id="addCampaignError" style="margin:14px 18px 0;padding:10px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid">
        <div class="fg s2">
          <label>Campaign Title</label>
          <input type="text" name="title" maxlength="200" placeholder="Help Kerala Flood Victims"
                 value="{{ old('form') === 'create' ? old('title') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" style="min-height:80px" required>{{ old('form') === 'create' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Target Amount (₹, max 7 digits)</label>
          <input type="number" name="target_amount" min="1" max="9999999" step="0.01"
                 value="{{ old('form') === 'create' ? old('target_amount') : '' }}" required>
        </div>
        <div class="fg">
  <label>Already Raised (₹, max 7 digits)</label>
  <input type="number" name="raised_amount" id="addCampaignRaised"
         min="0" max="9999999" step="0.01"
         value="{{ old('form') === 'create' ? old('raised_amount', '0') : '0' }}"
         onfocus="if (this.value === '0') this.value = '';"
         onblur="if (this.value === '') this.value = '0';">
</div>
        <div class="fg">
          <label>End Date</label>
          <input type="date" name="end_date" min="{{ now()->format('Y-m-d') }}"
                 value="{{ old('form') === 'create' ? old('end_date') : '' }}">
        </div>
        <div class="fg">
          <label>Image (optional)</label>
          <input type="file" name="image" accept="image/jpg,image/jpeg,image/png" style="padding:6px 11px;">
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetAddCampaignModal(); cm('addCampaignM')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-plus"></i> Create Campaign</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT CAMPAIGN MODAL ══ --}}
<div id="editCampaignM" class="mo">
  <div class="md" style="width:600px">
    <div class="mh">
      <span class="mt2">Edit Campaign</span>
      <div class="mc3" onclick="resetEditCampaignModal(); cm('editCampaignM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.fundraising.update') }}" enctype="multipart/form-data" id="editCampaignForm">
      @csrf
      <input type="hidden" name="form" value="edit">
      <input type="hidden" name="id" id="editCampaignId" value="{{ old('form') === 'edit' ? old('id') : '' }}">

      @if ($errors->any() && old('form') === 'edit')
        <div id="editCampaignError" style="margin:14px 18px 0;padding:10px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid">
        <div class="fg s2">
          <label>Campaign Title</label>
          <input type="text" name="title" id="editCampaignTitle" maxlength="200"
                 value="{{ old('form') === 'edit' ? old('title') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" id="editCampaignDesc" style="min-height:80px" required>{{ old('form') === 'edit' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Target Amount (₹, max 7 digits)</label>
          <input type="number" name="target_amount" id="editCampaignTarget" min="1" max="9999999" step="0.01"
                 value="{{ old('form') === 'edit' ? old('target_amount') : '' }}" required>
        </div>
        <div class="fg">
          <label>Raised Amount (₹, max 7 digits)</label>
          <input type="number" name="raised_amount" id="editCampaignRaised" min="0" max="9999999" step="0.01"
                 value="{{ old('form') === 'edit' ? old('raised_amount') : '' }}">
        </div>
        <div class="fg">
          <label>End Date</label>
          <input type="date" name="end_date" id="editCampaignEndDate"
                 value="{{ old('form') === 'edit' ? old('end_date') : '' }}">
        </div>
        <div class="fg">
          <label>New Image (optional)</label>
          <input type="file" name="image" accept="image/jpg,image/jpeg,image/png" style="padding:6px 11px;">
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetEditCampaignModal(); cm('editCampaignM')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-save"></i> Update Campaign</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
// ── Reset "Create Campaign" modal ──
function resetAddCampaignModal() {
  const errBox = document.getElementById('addCampaignError');
  if (errBox) errBox.remove();

  const form = document.getElementById('addCampaignForm');
  if (form) {
    form.querySelectorAll('input:not([type=hidden]):not([type=file]), textarea').forEach(el => {
      el.value = (el.name === 'raised_amount') ? '0' : '';
    });
    const file = form.querySelector('input[type=file]');
    if (file) file.value = '';
  }
}

// ── Reset "Edit Campaign" modal ──
function resetEditCampaignModal() {
  const errBox = document.getElementById('editCampaignError');
  if (errBox) errBox.remove();

  const form = document.getElementById('editCampaignForm');
  if (form) {
    form.querySelectorAll('input:not([type=hidden]):not([type=file]), textarea').forEach(el => el.value = '');
    const file = form.querySelector('input[type=file]');
    if (file) file.value = '';
  }
  document.getElementById('editCampaignId').value = '';
}

function editCampaign(id) {
  resetEditCampaignModal();
  fetch(`{{ url('/district-admin/fundraising') }}/${id}/detail`, { headers: { 'Accept': 'application/json' } })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(c => {
      document.getElementById('editCampaignId').value      = id;
      document.getElementById('editCampaignTitle').value   = c.title;
      document.getElementById('editCampaignDesc').value    = c.description;
      document.getElementById('editCampaignTarget').value  = c.target_amount;
      document.getElementById('editCampaignRaised').value  = c.raised_amount;
      document.getElementById('editCampaignEndDate').value = c.end_date_raw ?? '';
      om('editCampaignM');
    })
    .catch(err => alert('Failed to load campaign data! ' + err.message));
}

function updateCampaignStatus(id, status) {
  if (!confirm('Change status to ' + status + '?')) {
    location.reload();
    return;
  }
  document.getElementById('campaignStatusVal' + id).value = status;
  document.getElementById('campaignStatusForm' + id).submit();
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      om('editCampaignM');
    @elseif (old('form') === 'create')
      om('addCampaignM');
    @endif
  });
@endif
</script>
@endsection