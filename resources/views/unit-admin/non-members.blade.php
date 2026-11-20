@extends('unit-admin.layout')
@section('title', 'Non-Members')
@section('page_title', 'Non-Members (Third-Party People)')

@section('content')

@if(session('success'))
  <div style="margin-bottom:14px;padding:12px 16px;border-radius:8px;background:rgba(52,211,153,0.1);border:1px solid var(--green,#10b981);color:var(--green,#10b981);font-size:13px;display:flex;align-items:center;gap:8px;">
    <i class="fas fa-check-circle"></i>{{ session('success') }}
  </div>
@endif

@if($errors->any())
  <div style="margin-bottom:14px;padding:12px 16px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid var(--red,#ef4444);color:var(--red,#ef4444);font-size:13px;">
    <div style="font-weight:700;margin-bottom:4px;">Please fix the following:</div>
    <ul style="margin:0 0 0 16px;padding:0;">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-user-tag"></i>Non-Members ({{ $myUnit->name ?? 'Unit' }})
      <span style="font-size:11px;color:var(--text-dim);font-weight:400;margin-left:4px;">({{ $nonMembers->total() }})</span>
    </div>
    <button class="btn bp bsm" onclick="om('addNonMemberModal')">
      <i class="fas fa-plus"></i>Add Non-Member
    </button>
  </div>

  {{-- FILTERS BAR --}}
  <form method="GET" action="{{ route('unit-admin.non-members') }}"
        style="display:flex;gap:8px;padding:12px 16px;border-bottom:1px solid var(--border);flex-wrap:wrap;align-items:center;">
    <div style="position:relative;display:flex;align-items:center;">
      <i class="fas fa-search" style="position:absolute;left:10px;color:var(--text-dim);font-size:11px;pointer-events:none;"></i>
      <input type="text" name="search" value="{{ $search ?? '' }}"
             placeholder="Search by name, phone, shop..."
             style="padding:5px 12px 5px 28px;font-size:11px;border-radius:20px;border:1px solid var(--border);background:transparent;color:var(--text);width:230px;outline:none;">
    </div>

    <button type="submit" class="btn bp bsm">
      <i class="fas fa-search"></i>Search
    </button>
    <a href="{{ route('unit-admin.non-members') }}" class="btn bo bsm">
      <i class="fas fa-times"></i>Clear
    </a>
  </form>

  {{-- TABLE --}}
  <div class="tw">
    <table style="min-width: 820px;">
      <thead>
        <tr>
          <th>#</th>
          <th>Non-Member</th>
          <th>Phone</th>
          <th>Shop Name / Org</th>
          <th>Associated Schemes</th>
          <th>Added On</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($nonMembers as $index => $nm)
          <tr>
            <td style="color:var(--text-dim);">{{ $nonMembers->firstItem() + $index }}</td>
            <td>
              <div class="mc2">
                @if($nm->photo)
                  <img src="{{ asset('storage/' . $nm->photo) }}" style="width:32px;height:32px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                @else
                  <div class="av2" style="background:rgba(245,158,11,0.15);color:#F59E0B;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;">
                    {{ strtoupper(substr($nm->name, 0, 2)) }}
                  </div>
                @endif
                <div>
                  <div class="mn" style="font-weight:600;color:var(--text);">
                    {{ $nm->name }}
                    <span class="badge" style="background:rgba(245,158,11,0.15);color:#F59E0B;border:1px solid rgba(245,158,11,0.3);font-size:9px;margin-left:4px;">Third-Party</span>
                  </div>
                  <div class="me">{{ $nm->email ?: 'No email' }}</div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-weight:600;font-size:12px;">{{ $nm->phone }}</div>
            </td>
            <td>
              <span style="font-size:12px;color:var(--text);">{{ $nm->organization ?: '—' }}</span>
            </td>
            <td>
              @if($nm->schemes && $nm->schemes->count() > 0)
                <div style="display:flex;flex-direction:column;gap:3px;">
                  @foreach($nm->schemes->take(2) as $sc)
                    <span style="font-size:11px;font-weight:600;color:var(--accent);">
                      &bull; {{ $sc->title }} (₹{{ number_format($sc->amount, 0) }})
                    </span>
                  @endforeach
                  @if($nm->schemes->count() > 2)
                    <span style="font-size:10px;color:var(--text-dim);font-style:italic;">
                      +{{ $nm->schemes->count() - 2 }} more schemes
                    </span>
                  @endif
                </div>
              @else
                <span style="color:var(--text-dim);font-size:11px;font-style:italic;">No schemes assigned</span>
              @endif
            </td>
            <td style="color:var(--text-dim);font-size:11px;">{{ $nm->created_at ? $nm->created_at->format('d M Y') : '—' }}</td>
            <td>
              <div class="abr">
                <button class="ab vi" title="View Profile & Schemes" onclick="viewNonMember({{ $nm->id }})">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="ab ed" title="Edit" onclick="editNonMember({{ $nm->id }})">
                  <i class="fas fa-pen"></i>
                </button>
                <button class="ab rej" title="Delete" onclick="openDeleteNonMember({{ $nm->id }}, '{{ addslashes($nm->name) }}')">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center;color:var(--text-dim);padding:36px;">
              <i class="fas fa-user-tag" style="font-size:32px;opacity:0.3;margin-bottom:8px;display:block;"></i>
              @if(request()->filled('search'))
                No third-party non-members found matching "<strong>{{ request('search') }}</strong>".
                <div style="margin-top:8px;">
                  <a href="{{ route('unit-admin.non-members') }}" class="btn bo bsm">Clear Search</a>
                </div>
              @else
                No third-party non-members registered in your unit yet. Click "Add Non-Member" to create one.
              @endif
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($nonMembers->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
      <span style="font-size:11px;color:var(--text-dim);">
        Showing {{ $nonMembers->firstItem() }}–{{ $nonMembers->lastItem() }} of {{ $nonMembers->total() }} non-members
      </span>
      {{ $nonMembers->appends(request()->query())->links('pagination.custom') }}
    </div>
  @endif
</div>

{{-- ══ ADD NON-MEMBER MODAL ══ --}}
<div class="mo" id="addNonMemberModal">
  <div class="md" style="width:580px;max-width:96vw;">
    <div class="mh">
      <div>
        <div class="mt2"><i class="fas fa-user-plus" style="margin-right:6px;color:#F59E0B;"></i>Add Non-Member</div>
        <div style="font-size:11px;color:var(--text-dim);margin-top:2px;">External third-party person for scheme participation without login access.</div>
      </div>
      <div class="mc3" onclick="cm('addNonMemberModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('unit-admin.non-members.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="fgrid">
        <div class="fg s2">
          <label>Full Name <span style="color:var(--red)">*</span></label>
          <input type="text" name="name" placeholder="Rajesh Kumar" value="{{ old('name') }}" required>
        </div>
        <div class="fg">
          <label>Phone Number <span style="color:var(--red)">*</span></label>
          <input type="text" name="phone" placeholder="9845002001" maxlength="15" value="{{ old('phone') }}" required>
        </div>
        <div class="fg">
          <label>Email Address (optional)</label>
          <input type="email" name="email" placeholder="rajesh@gmail.com" value="{{ old('email') }}">
        </div>
        <div class="fg">
          <label>Date of Birth</label>
          <input type="date" name="dob" value="{{ old('dob') }}">
        </div>
        <div class="fg">
          <label>Shop Name / Organization</label>
          <input type="text" name="organization" placeholder="Fathima Stores" value="{{ old('organization') }}">
        </div>
        <div class="fg s2">
          <label>Unit</label>
          <input type="text" value="{{ $myUnit->name ?? 'My Unit' }}" disabled style="opacity:0.7;">
        </div>
        <div class="fg s2">
          <label>Address</label>
          <textarea name="address" rows="2" placeholder="House name, street, area, PIN">{{ old('address') }}</textarea>
        </div>
        <div class="fg s2">
          <label>Upload Photo</label>
          <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg" style="padding:6px 11px;">
        </div>

        {{-- ASSOCIATE SCHEMES --}}
        <div class="fg s2">
          <label style="margin-bottom:6px;">Associate with Schemes (Optional)</label>
          <div style="max-height:160px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px;">
            @foreach($schemes as $sch)
              <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;"
                     onmouseover="this.style.background='rgba(79,142,247,0.06)'"
                     onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="scheme_ids[]" value="{{ $sch->id }}" style="width:16px;height:16px;">
                <span style="font-size:12px;font-weight:600;">{{ $sch->title }}</span>
                <span style="font-size:11px;color:var(--text-dim);">(₹{{ number_format($sch->amount, 0) }}/mo &bull; {{ $sch->duration_months }}m)</span>
              </label>
            @endforeach
          </div>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('addNonMemberModal')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-check"></i> Create Non-Member</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT NON-MEMBER MODAL ══ --}}
<div class="mo" id="editNonMemberModal">
  <div class="md" style="width:580px;max-width:96vw;">
    <div class="mh">
      <div class="mt2"><i class="fas fa-edit" style="margin-right:6px;color:var(--accent);"></i>Edit Non-Member</div>
      <div class="mc3" onclick="cm('editNonMemberModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('unit-admin.non-members.update') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="id" id="edit_nm_id">
      <div class="fgrid">
        <div class="fg s2">
          <label>Full Name <span style="color:var(--red)">*</span></label>
          <input type="text" name="name" id="edit_nm_name" required>
        </div>
        <div class="fg">
          <label>Phone Number <span style="color:var(--red)">*</span></label>
          <input type="text" name="phone" id="edit_nm_phone" maxlength="15" required>
        </div>
        <div class="fg">
          <label>Email Address</label>
          <input type="email" name="email" id="edit_nm_email">
        </div>
        <div class="fg">
          <label>Date of Birth</label>
          <input type="date" name="dob" id="edit_nm_dob">
        </div>
        <div class="fg">
          <label>Shop Name / Organization</label>
          <input type="text" name="organization" id="edit_nm_org">
        </div>
        <div class="fg s2">
          <label>Unit</label>
          <input type="text" value="{{ $myUnit->name ?? 'My Unit' }}" disabled style="opacity:0.7;">
        </div>
        <div class="fg s2">
          <label>Address</label>
          <textarea name="address" id="edit_nm_address" rows="2"></textarea>
        </div>
        <div class="fg s2">
          <label>Replace Photo (optional)</label>
          <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg" style="padding:6px 11px;">
        </div>

        {{-- ASSOCIATE SCHEMES --}}
        <div class="fg s2">
          <label style="margin-bottom:6px;">Associated Schemes</label>
          <div id="edit_nm_schemes_list" style="max-height:160px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px;">
            @foreach($schemes as $sch)
              <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;"
                     onmouseover="this.style.background='rgba(79,142,247,0.06)'"
                     onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="scheme_ids[]" value="{{ $sch->id }}" class="edit-scheme-cb" style="width:16px;height:16px;">
                <span style="font-size:12px;font-weight:600;">{{ $sch->title }}</span>
                <span style="font-size:11px;color:var(--text-dim);">(₹{{ number_format($sch->amount, 0) }}/mo &bull; {{ $sch->duration_months }}m)</span>
              </label>
            @endforeach
          </div>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('editNonMemberModal')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ VIEW DETAILS MODAL ══ --}}
<div class="mo" id="viewNonMemberModal">
  <div class="md" style="width:620px;max-width:96vw;">
    <div class="mh">
      <div class="mt2" id="viewModalTitle"><i class="fas fa-id-card" style="margin-right:6px;color:#F59E0B;"></i>Non-Member Details</div>
      <div class="mc3" onclick="cm('viewNonMemberModal')"><i class="fas fa-times"></i></div>
    </div>
    <div id="viewModalContent" style="padding:20px;">
      Loading...
    </div>
    <div class="ff">
      <button type="button" class="btn bo" onclick="cm('viewNonMemberModal')">Close</button>
    </div>
  </div>
</div>

{{-- ══ DELETE CONFIRM MODAL ══ --}}
<div class="mo" id="deleteNonMemberModal">
  <div class="md" style="width:400px;max-width:96vw;">
    <div class="mh">
      <div class="mt2" style="color:var(--red);"><i class="fas fa-trash-alt" style="margin-right:6px;"></i>Delete Non-Member</div>
      <div class="mc3" onclick="cm('deleteNonMemberModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('unit-admin.non-members.delete') }}" method="POST">
      @csrf
      @method('DELETE')
      <input type="hidden" name="id" id="delete_nm_id">
      <div style="padding:16px 20px;">
        <p style="font-size:13px;line-height:1.5;">Are you sure you want to remove <strong id="delete_nm_name">—</strong>? This will detach them from any linked schemes and remove their dues records.</p>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('deleteNonMemberModal')">Cancel</button>
        <button type="submit" class="btn" style="background:var(--red,#ef4444);color:#fff;border-color:var(--red,#ef4444);">
          <i class="fas fa-trash"></i> Delete
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function editNonMember(id) {
  fetch(`{{ url('/unit-admin/non-members') }}/${id}/detail`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('edit_nm_id').value = data.id;
      document.getElementById('edit_nm_name').value = data.name;
      document.getElementById('edit_nm_phone').value = data.phone;
      document.getElementById('edit_nm_email').value = data.email === '—' ? '' : data.email;
      document.getElementById('edit_nm_dob').value = data.dob ?? '';
      document.getElementById('edit_nm_org').value = data.organization === '—' ? '' : data.organization;
      document.getElementById('edit_nm_address').value = data.address === '—' ? '' : data.address;

      const assignedSchemeIds = (data.scheme_ids || []).map(String);
      document.querySelectorAll('#edit_nm_schemes_list .edit-scheme-cb').forEach(cb => {
        cb.checked = assignedSchemeIds.includes(cb.value);
      });

      om('editNonMemberModal');
    })
    .catch(() => alert('Failed to load non-member data!'));
}

function viewNonMember(id) {
  const container = document.getElementById('viewModalContent');
  container.innerHTML = 'Loading...';
  om('viewNonMemberModal');

  fetch(`{{ url('/unit-admin/non-members') }}/${id}/detail`)
    .then(res => res.json())
    .then(data => {
      let schemesHtml = '';
      if (data.schemes && data.schemes.length > 0) {
        schemesHtml = `
          <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
            ${data.schemes.map(s => `
              <div style="background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:8px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                  <div style="font-weight:600;font-size:13px;color:var(--text);">${esc(s.title)}</div>
                  <div style="font-size:11px;color:var(--text-dim);margin-top:2px;">
                    ₹${s.amount}/mo &bull; ${s.duration} Months &bull; Paid: ${s.paid_count}/${s.dues_count} dues (₹${s.total_paid})
                  </div>
                </div>
                <div>
                  <span class="badge ${s.is_active ? 'bap' : 'bpe'}" style="font-size:10px;">
                    ${s.is_active ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
            `).join('')}
          </div>
        `;
      } else {
        schemesHtml = `
          <div style="background:rgba(255,255,255,0.02);border:1px dashed var(--border);border-radius:8px;padding:14px;text-align:center;color:var(--text-dim);font-size:12px;margin-top:6px;">
            No schemes currently enrolled.
          </div>
        `;
      }

      container.innerHTML = `
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:18px;padding-bottom:16px;border-bottom:1px solid var(--border);">
          ${data.photo_url ?
            `<img src="${data.photo_url}" style="width:56px;height:56px;border-radius:10px;object-fit:cover;">` :
            `<div style="width:56px;height:56px;border-radius:10px;background:rgba(245,158,11,0.2);color:#F59E0B;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;">
              ${data.name.slice(0, 2).toUpperCase()}
            </div>`
          }
          <div>
            <div style="font-size:16px;font-weight:700;color:var(--text);">${esc(data.name)}</div>
            <div style="font-size:12px;color:var(--text-dim);margin-top:2px;">
              <span class="badge" style="background:rgba(245,158,11,0.15);color:#F59E0B;border:1px solid rgba(245,158,11,0.3);font-size:10px;padding:2px 8px;">Third-Party Person</span>
              <span class="utag" style="margin-left:6px;">${esc(data.unit_name)}</span>
            </div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
          <div style="background:rgba(255,255,255,0.02);padding:10px 12px;border-radius:8px;border:1px solid var(--border);">
            <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;">Phone Number</div>
            <div style="font-size:13px;font-weight:600;margin-top:2px;">${esc(data.phone)}</div>
          </div>
          <div style="background:rgba(255,255,255,0.02);padding:10px 12px;border-radius:8px;border:1px solid var(--border);">
            <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;">Email Address</div>
            <div style="font-size:13px;font-weight:600;margin-top:2px;">${esc(data.email)}</div>
          </div>
          <div style="background:rgba(255,255,255,0.02);padding:10px 12px;border-radius:8px;border:1px solid var(--border);">
            <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;">Shop Name / Org</div>
            <div style="font-size:13px;font-weight:600;margin-top:2px;">${esc(data.organization)}</div>
          </div>
          <div style="background:rgba(255,255,255,0.02);padding:10px 12px;border-radius:8px;border:1px solid var(--border);">
            <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;">Date of Birth</div>
            <div style="font-size:13px;font-weight:600;margin-top:2px;">${esc(data.dob_formatted)}</div>
          </div>
          <div style="grid-column:1/-1;background:rgba(255,255,255,0.02);padding:10px 12px;border-radius:8px;border:1px solid var(--border);">
            <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;">Address</div>
            <div style="font-size:13px;margin-top:2px;">${esc(data.address)}</div>
          </div>
        </div>

        <div style="margin-top:16px;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:13px;color:var(--text);">
              <i class="fas fa-layer-group" style="margin-right:6px;color:#F59E0B;"></i>Associated Schemes
            </div>
            <span class="badge" style="background:rgba(245,158,11,0.2);color:#F59E0B;font-weight:700;font-size:11px;">Total: ${data.schemes.length}</span>
          </div>
          ${schemesHtml}
        </div>
      `;
    })
    .catch(() => {
      container.innerHTML = '<div style="color:var(--red);text-align:center;">Failed to load details.</div>';
    });
}

function openDeleteNonMember(id, name) {
  document.getElementById('delete_nm_id').value = id;
  document.getElementById('delete_nm_name').textContent = name;
  om('deleteNonMemberModal');
}

function esc(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[s]);
}
</script>
@endsection
