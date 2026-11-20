@extends('layouts.admin')
@section('title', 'Units')
@section('page_title', 'Units')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-building"></i>All Units</div>
    <button class="btn bp bsm" onclick="resetAddUnitModal(); om('addUnitM')">
      <i class="fas fa-plus"></i>Add Unit
    </button>
  </div>
  <div class="tw">
    <table style="min-width: 760px;">
      <thead>
        <tr>
          <th>#</th>
          <th>Unit Name</th>
          <th>District</th>
          @unless($fixedState)
            <th>State</th>
          @endunless
          <th>Certificate Logo</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($units as $unit)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td><b>{{ $unit->name }}</b></td>
          <td style="color:var(--text-dim)">
            {{ $unit->district->name }}
          </td>
          @unless($fixedState)
            <td style="color:var(--text-dim)">
              {{ $unit->district->state->name }}
            </td>
          @endunless
          <td>
            @if($unit->certificate_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->certificate_logo))
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:34px;height:34px;border-radius:6px;background:#fff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;padding:2px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                  <img src="{{ asset('storage/' . $unit->certificate_logo) }}" style="max-width:100%;max-height:100%;object-fit:contain;">
                </div>
                <span class="badge bac" style="font-size:9.5px;padding:2px 6px;">Custom Logo</span>
              </div>
            @else
              <span class="badge" style="background:rgba(255,255,255,0.06);color:var(--text-dim);font-size:9.5px;padding:2px 7px;">Default Central</span>
            @endif
          </td>
          <td>
            <div class="abr">
              {{-- CERTIFICATE SETTINGS --}}
              <div class="ab vw"
                onclick='openCertConfigModal(@json($unit))'
                title="Configure Certificate">
                <i class="fas fa-certificate"></i>
              </div>

              <div class="ab ed"
                onclick="editUnit(
                  {{ $unit->id }},
                  '{{ addslashes($unit->name) }}',
                  {{ $unit->district_id }},
                  {{ $unit->district->state_id }})">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST"
                action="{{ route('admin.units.delete') }}"
                onsubmit="return confirm('Delete {{ addslashes($unit->name) }}?')"
                style="display:inline">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $unit->id }}">
                <button type="submit" class="ab rej">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="{{ $fixedState ? 5 : 6 }}"
            style="text-align:center;color:var(--text-dim);padding:30px;">
            No units yet
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div id="addUnitM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Add Unit</span>
      <div class="mc3" onclick="resetAddUnitModal(); cm('addUnitM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.units.store') }}" id="addUnitForm">
      @csrf
      <input type="hidden" name="form" value="create">

      @if ($errors->any() && old('form') === 'create')
        <div id="addUnitError" style="margin:14px 18px 0;padding:10px 12px;
                    background:rgba(239,68,68,0.1);
                    border:1px solid rgba(239,68,68,0.3);
                    border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid c1">
        @if($fixedState)
          {{-- Kerala is fixed: no State dropdown, districts are already loaded --}}
          <div class="fg">
            <label>District</label>
            <select name="district_id" id="addUnitDistrict" required>
              <option value="">Select District</option>
              @foreach($districts as $d)
                <option value="{{ $d->id }}"
                  @selected(old('form') === 'create' && old('district_id') == $d->id)>
                  {{ $d->name }}
                </option>
              @endforeach
            </select>
          </div>
        @else
          <div class="fg">
            <label>State</label>
            <select id="addUnitState"
                    onchange="loadDistricts(this.value)">
              <option value="">Select State</option>
              @foreach($states as $state)
                <option value="{{ $state->id }}">
                  {{ $state->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="fg">
            <label>District</label>
            <select name="district_id" id="addUnitDistrict" required>
              <option value="">Select State First</option>
            </select>
          </div>
        @endif
        <div class="fg">
          <label>Unit Name</label>
          <input type="text" name="name" id="addUnitName"
                 maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 value="{{ old('form') === 'create' ? old('name') : '' }}"
                 placeholder="Enter Unit name" required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetAddUnitModal(); cm('addUnitM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-plus"></i>Add Unit
        </button>
      </div>
    </form>
  </div>
</div>

<div id="editUnitM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit Unit</span>
      <div class="mc3" onclick="resetEditUnitModal(); cm('editUnitM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.units.update') }}" id="editUnitForm">
      @csrf @method('PUT')
      <input type="hidden" name="form" value="edit">
      <input type="hidden" name="id" id="editUnitId">

      @if ($errors->any() && old('form') === 'edit')
        <div id="editUnitError" style="margin:14px 18px 0;padding:10px 12px;
                    background:rgba(239,68,68,0.1);
                    border:1px solid rgba(239,68,68,0.3);
                    border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid c1">
        <div class="fg">
          <label>Unit Name</label>
          <input type="text" name="name"
                 id="editUnitName"
                 maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetEditUnitModal(); cm('editUnitM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Update
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ CONFIGURE CERTIFICATE MODAL ══ --}}
<div id="configureCertM" class="mo">
  <div class="md" style="max-width:540px;width:95%;">
    <div class="mh">
      <span class="mt2"><i class="fas fa-certificate" style="color:var(--accent);margin-right:6px;"></i>Certificate Config — <span id="certModalUnitName">Unit</span></span>
      <div class="mc3" onclick="cm('configureCertM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" id="certConfigForm" enctype="multipart/form-data">
      @csrf

      <div style="padding:16px 20px;">
        <div style="font-size:11.5px;color:var(--text-dim);margin-bottom:14px;">
          Upload a unique certificate logo and customize the certificate details for this unit.
        </div>

        {{-- CURRENT LOGO & UPLOAD --}}
        <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:14px;">
          <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-dim);display:block;margin-bottom:8px;">
            Certificate Logo
          </label>
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:90px;height:70px;border-radius:8px;background:#ffffff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;padding:4px;overflow:hidden;flex-shrink:0;">
              <img id="certModalLogoPreview" src="{{ asset('fumma-logo.png') }}" style="max-width:100%;max-height:100%;object-fit:contain;">
            </div>
            <div style="flex:1;min-width:160px;">
              <input type="file" name="certificate_logo" id="certModalFileInput" accept="image/png,image/jpeg,image/webp"
                     onchange="previewAdminCertLogo(this)" style="font-size:11px;color:var(--text);">
              <div style="font-size:10px;color:var(--text-dim);margin-top:4px;">
                PNG with transparent background recommended (Max 3MB).
              </div>
            </div>
          </div>
          <div id="certModalRemoveLogoBtnWrap" style="margin-top:10px;padding-top:8px;border-top:1px solid var(--border);text-align:right;display:none;">
            <button type="button" class="btn bo bsm" style="color:var(--red);border-color:rgba(239,68,68,0.3);"
                    onclick="removeAdminCertLogo()">
              <i class="fas fa-trash-alt"></i>Remove Logo (Revert to Default)
            </button>
          </div>
        </div>

        {{-- SEAL TEXT --}}
        <div class="fg" style="margin-bottom:12px;">
          <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-dim);display:block;margin-bottom:4px;">
            Seal Text (Optional)
          </label>
          <input type="text" name="certificate_seal_text" id="certModalSealText"
                 placeholder="e.g. Unit Name (Default: Member Net)"
                 maxlength="100"
                 style="width:100%;padding:7px 10px;font-size:12px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--text);">
        </div>

        {{-- SIGNATORIES --}}
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-dim);margin:14px 0 8px;">
          Certificate Signatories
        </div>

        {{-- Signatory 1 --}}
        <div style="background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:6px;padding:10px;margin-bottom:8px;">
          <div style="font-size:11px;font-weight:700;color:var(--text);margin-bottom:6px;">Signatory 1 (President)</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:6px;">
            <input type="text" name="signatory_1_name" id="certModalSig1Name" placeholder="Name (e.g. Tony Pulickal)"
                   style="width:100%;padding:6px 10px;font-size:11.5px;border-radius:5px;border:1px solid var(--border);background:var(--card);color:var(--text);">
            <input type="text" name="signatory_1_title" id="certModalSig1Title" placeholder="Title (e.g. Unit President)"
                   style="width:100%;padding:6px 10px;font-size:11.5px;border-radius:5px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:90px;height:32px;background:#fff;border:1px solid var(--border);border-radius:4px;display:flex;align-items:center;justify-content:center;padding:2px;flex-shrink:0;">
              <img id="certModalSig1Preview" src="{{ asset('sign-president.png') }}" style="max-height:100%;max-width:100%;object-fit:contain;">
            </div>
            <input type="file" name="signatory_1_signature" accept="image/png,image/jpeg,image/webp"
                   onchange="previewAdminCertSig(this, 'certModalSig1Preview')" style="font-size:10.5px;color:var(--text);">
          </div>
        </div>

        {{-- Signatory 2 --}}
        <div style="background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:6px;padding:10px;margin-bottom:8px;">
          <div style="font-size:11px;font-weight:700;color:var(--text);margin-bottom:6px;">Signatory 2 (General Secretary)</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:6px;">
            <input type="text" name="signatory_2_name" id="certModalSig2Name" placeholder="Name (e.g. Shaji Manhar)"
                   style="width:100%;padding:6px 10px;font-size:11.5px;border-radius:5px;border:1px solid var(--border);background:var(--card);color:var(--text);">
            <input type="text" name="signatory_2_title" id="certModalSig2Title" placeholder="Title (e.g. Unit Gen. Secretary)"
                   style="width:100%;padding:6px 10px;font-size:11.5px;border-radius:5px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:90px;height:32px;background:#fff;border:1px solid var(--border);border-radius:4px;display:flex;align-items:center;justify-content:center;padding:2px;flex-shrink:0;">
              <img id="certModalSig2Preview" src="{{ asset('sign-secretary.png') }}" style="max-height:100%;max-width:100%;object-fit:contain;">
            </div>
            <input type="file" name="signatory_2_signature" accept="image/png,image/jpeg,image/webp"
                   onchange="previewAdminCertSig(this, 'certModalSig2Preview')" style="font-size:10.5px;color:var(--text);">
          </div>
        </div>

        {{-- Signatory 3 --}}
        <div style="background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:6px;padding:10px;margin-bottom:8px;">
          <div style="font-size:11px;font-weight:700;color:var(--text);margin-bottom:6px;">Signatory 3 (Treasurer)</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:6px;">
            <input type="text" name="signatory_3_name" id="certModalSig3Name" placeholder="Name (e.g. Rafi Puthoor)"
                   style="width:100%;padding:6px 10px;font-size:11.5px;border-radius:5px;border:1px solid var(--border);background:var(--card);color:var(--text);">
            <input type="text" name="signatory_3_title" id="certModalSig3Title" placeholder="Title (e.g. Unit Treasurer)"
                   style="width:100%;padding:6px 10px;font-size:11.5px;border-radius:5px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:90px;height:32px;background:#fff;border:1px solid var(--border);border-radius:4px;display:flex;align-items:center;justify-content:center;padding:2px;flex-shrink:0;">
              <img id="certModalSig3Preview" src="{{ asset('sign-treasurer.png') }}" style="max-height:100%;max-width:100%;object-fit:contain;">
            </div>
            <input type="file" name="signatory_3_signature" accept="image/png,image/jpeg,image/webp"
                   onchange="previewAdminCertSig(this, 'certModalSig3Preview')" style="font-size:10.5px;color:var(--text);">
          </div>
        </div>
      </div>

      <div class="ff" style="display:flex;justify-content:space-between;align-items:center;">
        <a id="certModalPreviewLink" href="#" target="_blank" class="btn bo bsm">
          <i class="fas fa-file-pdf"></i>Preview PDF
        </a>
        <div style="display:flex;gap:8px;">
          <button type="button" class="btn bo" onclick="cm('configureCertM')">Cancel</button>
          <button type="submit" class="btn bp">
            <i class="fas fa-save"></i>Save Certificate
          </button>
        </div>
      </div>
    </form>

    {{-- Form for removing logo --}}
    <form id="adminRemoveLogoForm" method="POST" style="display:none;">
      @csrf
      @method('DELETE')
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
const KERALA_FIXED = {{ $fixedState ? 'true' : 'false' }};
let currentCertUnitId = null;

function openCertConfigModal(unit) {
  if (typeof unit === 'number') {
    return;
  }
  const id = unit.id;
  currentCertUnitId = id;
  document.getElementById('certModalUnitName').textContent = unit.name || 'Unit';
  document.getElementById('certConfigForm').action = `{{ url('/admin/units') }}/${id}/certificate`;
  document.getElementById('certModalPreviewLink').href = `{{ url('/admin/units') }}/${id}/certificate/preview`;
  document.getElementById('certModalSealText').value = unit.certificate_seal_text || '';

  // Signatory 1
  document.getElementById('certModalSig1Name').value = unit.signatory_1_name || unit.certificate_president_name || '';
  document.getElementById('certModalSig1Title').value = unit.signatory_1_title || '';
  document.getElementById('certModalSig1Preview').src = unit.signatory_1_signature ? `{{ asset('storage') }}/${unit.signatory_1_signature}` : `{{ asset('sign-president.png') }}`;

  // Signatory 2
  document.getElementById('certModalSig2Name').value = unit.signatory_2_name || unit.certificate_secretary_name || '';
  document.getElementById('certModalSig2Title').value = unit.signatory_2_title || '';
  document.getElementById('certModalSig2Preview').src = unit.signatory_2_signature ? `{{ asset('storage') }}/${unit.signatory_2_signature}` : `{{ asset('sign-secretary.png') }}`;

  // Signatory 3
  document.getElementById('certModalSig3Name').value = unit.signatory_3_name || '';
  document.getElementById('certModalSig3Title').value = unit.signatory_3_title || '';
  document.getElementById('certModalSig3Preview').src = unit.signatory_3_signature ? `{{ asset('storage') }}/${unit.signatory_3_signature}` : `{{ asset('sign-treasurer.png') }}`;

  document.getElementById('certModalFileInput').value = '';

  const preview = document.getElementById('certModalLogoPreview');
  const removeBtnWrap = document.getElementById('certModalRemoveLogoBtnWrap');

  if (unit.certificate_logo) {
    preview.src = `{{ asset('storage') }}/${unit.certificate_logo}`;
    removeBtnWrap.style.display = 'block';
  } else {
    preview.src = '{{ asset("fumma-logo.png") }}';
    removeBtnWrap.style.display = 'none';
  }

  om('configureCertM');
}

function previewAdminCertSig(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById(previewId).src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function previewAdminCertLogo(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('certModalLogoPreview').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function removeAdminCertLogo() {
  if (!currentCertUnitId) return;
  if (confirm('Remove custom logo and revert this unit to the default central logo?')) {
    const form = document.getElementById('adminRemoveLogoForm');
    form.action = `{{ url('/admin/units') }}/${currentCertUnitId}/certificate/logo`;
    form.submit();
  }
}

function loadDistricts(stateId) {
  if (!stateId) return;

  const select = document.getElementById('addUnitDistrict');
  select.innerHTML = '<option value="">Loading...</option>';

  fetch(`{{ route('admin.districts.by-state') }}`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({ state_id: stateId })
  })
  .then(r => r.json())
  .then(data => {
    select.innerHTML = '<option value="">Select District</option>';
    if (data.data && data.data.length > 0) {
      data.data.forEach(d => {
        select.innerHTML += `<option value="${d.id}">${d.name}</option>`;
      });
    } else {
      select.innerHTML = '<option value="">No districts found</option>';
    }
  })
  .catch(err => {
    select.innerHTML = '<option value="">Error loading districts</option>';
    console.error('District load failed:', err);
  });
}

// ── Reset "Add Unit" modal: clears stale error banner + inputs ──
function resetAddUnitModal() {
  const errBox = document.getElementById('addUnitError');
  if (errBox) errBox.remove();

  const name = document.getElementById('addUnitName');
  if (name) name.value = '';

  const distSelect = document.getElementById('addUnitDistrict');

  if (KERALA_FIXED) {
    // District list is already filled with Kerala districts: just deselect
    if (distSelect) distSelect.value = '';
  } else {
    const stateSelect = document.getElementById('addUnitState');
    if (stateSelect) stateSelect.value = '';
    if (distSelect) distSelect.innerHTML = '<option value="">Select State First</option>';
  }
}

// ── Reset "Edit Unit" modal: clears stale error banner ──
function resetEditUnitModal() {
  const errBox = document.getElementById('editUnitError');
  if (errBox) errBox.remove();
}

function editUnit(id, name) {
  resetEditUnitModal();
  document.getElementById('editUnitId').value   = id;
  document.getElementById('editUnitName').value = name;
  om('editUnitM');
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      document.getElementById('editUnitId').value   = '{{ old('id') }}';
      om('editUnitM');
    @else
      om('addUnitM');
    @endif
  });
@endif
</script>
@endsection