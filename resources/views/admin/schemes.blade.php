@extends('layouts.admin')
@section('title', 'Schemes')
@section('page_title', 'Schemes')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-piggy-bank"></i> Schemes</div>
    <button class="btn bp bsm" onclick="om('createSchemeModal')">
      <i class="fas fa-plus"></i> New Scheme
    </button>
  </div>

  <div class="tw">
    <table style="min-width: 920px;">
      <thead>
        <tr>
          <th>Title</th>
          <th>Amount / Month</th>
          <th>Duration</th>
          <th>Start – End</th>
          <th>Created By</th>
          <th>Assigned Members</th>
          <th>Dues Generated</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($schemes as $scheme)
          @php
            $creator = $scheme->createdBy;
            $role    = $creator->role ?? null;

            if ($role === 'unit_admin') {
                $label = 'Unit Admin';
                $color = 'var(--accent)';
                $scope = $unitAdmins[$scheme->created_by]->unit->name ?? null;
            } elseif ($role === 'district_admin') {
                $label = 'District Admin';
                $color = 'var(--green)';
                $scope = $districtAdmins[$scheme->created_by]->district->name ?? null;
            } elseif ($creator) {
                $label = 'Super Admin';
                $color = 'var(--red)';
                $scope = null;
            } else {
                $label = 'Unknown';
                $color = 'var(--text-dim)';
                $scope = null;
            }

            $totalAssigned = ($scheme->members_count ?? 0) + ($scheme->third_party_members_count ?? 0);
          @endphp
          <tr>
            <td style="white-space:normal;font-weight:600;">{{ $scheme->title }}</td>
            <td>₹{{ number_format($scheme->amount, 2) }}</td>
            <td>{{ $scheme->duration_months }} months</td>
            <td>
              {{ $scheme->start_date->format('d M Y') }}
              @if($scheme->end_date) – {{ $scheme->end_date->format('d M Y') }} @endif
            </td>

            {{-- CREATED BY --}}
            <td>
              @if($creator)
                <div class="mn">{{ $creator->name }}</div>
              @endif
              <div style="margin-top:3px;">
                <span style="font-size:10px;padding:2px 8px;border-radius:20px;
                             border:1px solid var(--border);color:{{ $color }};">
                  {{ $label }}
                </span>
              </div>
              @if($scope)
                <div class="me">{{ $scope }}</div>
              @endif
            </td>

            <td>
              <button type="button" class="ca" style="background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;"
                      onclick="viewSchemeMembers({{ $scheme->id }})">
                {{ $totalAssigned }} member(s) — View
              </button>
              @if(($scheme->third_party_members_count ?? 0) > 0)
                <div style="font-size:10px;color:#F59E0B;margin-top:2px;">
                  ({{ $scheme->members_count ?? 0 }} reg + {{ $scheme->third_party_members_count }} external)
                </div>
              @endif
            </td>
            <td>
              <a href="{{ route('admin.schemes.payers', $scheme->id) }}" class="ca">
                {{ $scheme->dues_count }} dues
              </a>
            </td>
            <td>
              <span class="badge {{ $scheme->is_active ? 'bap' : 'brj' }}">
                {{ $scheme->is_active ? 'ACTIVE' : 'INACTIVE' }}
              </span>
            </td>
            <td>
              <div class="abr">
                <button class="ab ed" title="Edit"
                  onclick="editScheme({{ $scheme->id }})">
                  <i class="fas fa-pen"></i>
                </button>

                <form action="{{ route('admin.schemes.toggle') }}" method="POST" style="display:inline;">
                  @csrf
                  <input type="hidden" name="id" value="{{ $scheme->id }}">
                  <button type="submit" class="ab" title="{{ $scheme->is_active ? 'Deactivate' : 'Activate' }}">
                    <i class="fas fa-power-off"></i>
                  </button>
                </form>

                <form action="{{ route('admin.schemes.delete') }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('Delete this scheme?')">
                  @csrf
                  <input type="hidden" name="id" value="{{ $scheme->id }}">
                  <button type="submit" class="ab rej" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" style="text-align:center;color:var(--text-dim);">No schemes found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($schemes->hasPages())
    <div style="padding:12px 18px;border-top:1px solid var(--border);">
      {{ $schemes->links('pagination.custom') }}
    </div>
  @endif
</div>

{{-- ══ CREATE MODAL ══ --}}
<div class="mo" id="createSchemeModal">
  <div class="md" style="width:680px;max-width:96vw;">
    <div class="mh">
      <div class="mt2">New Scheme</div>
      <div class="mc3" onclick="cm('createSchemeModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('admin.schemes.store') }}" method="POST">
      @csrf
      <input type="hidden" name="form_type" value="create_scheme">

      @if ($errors->any() && old('form_type') === 'create_scheme')
        <div style="margin:0 18px 12px;padding:10px 12px;border-radius:8px;background:rgba(220,53,69,0.08);border:1px solid var(--red);color:var(--red);font-size:12px;">
          <strong>Please fix the following:</strong>
          <ul style="margin:6px 0 0 18px;padding:0;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid">
        <div class="fg s2">
          <label>Title</label>
          <input type="text" name="title" value="{{ old('form_type') === 'create_scheme' ? old('title') : '' }}" required>
          @error('title') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" rows="3">{{ old('form_type') === 'create_scheme' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Amount per Month</label>
          <input type="number" step="0.01" name="amount" value="{{ old('form_type') === 'create_scheme' ? old('amount') : '' }}" required>
          @error('amount') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>Duration (Months)</label>
          <input type="number" name="duration_months" value="{{ old('form_type') === 'create_scheme' ? old('duration_months') : '' }}" required>
          @error('duration_months') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>Start Date</label>
          <input type="date" name="start_date" id="create_start_date"
                 value="{{ old('form_type') === 'create_scheme' ? old('start_date') : '' }}"
                 onchange="document.getElementById('create_end_date').min = this.value;"
                 required>
          @error('start_date') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>End Date (optional)</label>
          <input type="date" name="end_date" id="create_end_date"
                 value="{{ old('form_type') === 'create_scheme' ? old('end_date') : '' }}">
          @error('end_date') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>

        {{-- MEMBERS SELECTION WITH THIRD-PARTY OPTION --}}
        <div class="fg s2">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
            <div>
              <span style="font-weight:600;font-size:13px;color:var(--text);">Select Members</span>
              <span style="font-weight:400;font-size:11px;color:var(--text-dim);display:block;">
                (Choose registered members and/or external third-party members)
              </span>
            </div>
            <button type="button" class="btn bo bsm" onclick="openAddThirdPartyModal('create')"
                    style="font-size:11px;padding:5px 12px;color:var(--accent);border-color:var(--accent);display:flex;align-items:center;gap:6px;">
              <i class="fas fa-user-plus"></i> Add Third-Party Member
            </button>
          </div>
          @error('member_ids') <span style="color:var(--red);font-size:11px;display:block;margin-bottom:6px;">{{ $message }}</span> @enderror

          {{-- Filter Controls --}}
          <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center;">
            <input type="text" placeholder="🔍 Search by name, phone, email, unit..."
                   onkeyup="filterMemberList('create_member_list', this.value)"
                   style="flex:1;min-width:180px;padding:6px 12px;font-size:12px;border-radius:6px;border:1px solid var(--border);background:transparent;color:var(--text);">
            <div style="display:flex;gap:4px;">
              <button type="button" class="btn bo bsm filter-btn active" onclick="filterMemberType('create_member_list', 'all', this)">All</button>
              <button type="button" class="btn bo bsm filter-btn" onclick="filterMemberType('create_member_list', 'reg', this)">Registered</button>
              <button type="button" class="btn bo bsm filter-btn" onclick="filterMemberType('create_member_list', 'tp', this)">Third-Party</button>
            </div>
          </div>

          <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center;font-size:11px;">
            <button type="button" class="btn bo bsm" onclick="toggleAllCheckboxes('create_member_list', true, 'create_selected_count')">Select All</button>
            <button type="button" class="btn bo bsm" onclick="toggleAllCheckboxes('create_member_list', false, 'create_selected_count')">Clear All</button>
            <span id="create_selected_count" style="color:var(--text-dim);margin-left:auto;">0 selected</span>
          </div>

          <div id="create_member_list"
               style="max-height:240px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:6px;">
            {{-- Registered Members --}}
            @foreach ($allMembers as $m)
              <label class="member-check-row" data-type="reg"
                     style="display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:6px;cursor:pointer;"
                     onmouseover="this.style.background='rgba(79,142,247,0.06)'"
                     onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="member_ids[]" value="{{ $m->id }}"
                       {{ (old('form_type') === 'create_scheme' && in_array($m->id, old('member_ids', []))) ? 'checked' : '' }}
                       onchange="updateSelectedCount('create_member_list', 'create_selected_count')"
                       style="width:16px;height:16px;flex-shrink:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:10px;">
                  <div>
                    <span style="font-size:13px;font-weight:600;">{{ $m->user->name ?? '—' }}</span>
                    <span style="color:var(--text-dim);font-size:11px;margin-left:6px;">{{ $m->user->phone ?? '' }} &bull; {{ $m->user->email ?? '' }}</span>
                    @if($m->unit)<span class="utag" style="margin-left:6px;font-size:10px;">{{ $m->unit->name }}</span>@endif
                  </div>
                  <span class="badge" style="background:rgba(79,142,247,0.12);color:var(--accent);font-size:10px;padding:2px 8px;">Registered</span>
                </div>
              </label>
            @endforeach

            {{-- Third-Party Members --}}
            @foreach ($thirdPartyMembers as $tp)
              <label class="member-check-row" data-type="tp"
                     style="display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:6px;cursor:pointer;"
                     onmouseover="this.style.background='rgba(245,158,11,0.06)'"
                     onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="third_party_member_ids[]" value="{{ $tp->id }}"
                       {{ (old('form_type') === 'create_scheme' && in_array($tp->id, old('third_party_member_ids', []))) ? 'checked' : '' }}
                       onchange="updateSelectedCount('create_member_list', 'create_selected_count')"
                       style="width:16px;height:16px;flex-shrink:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:10px;">
                  <div>
                    <span style="font-size:13px;font-weight:600;color:#F59E0B;">{{ $tp->name }}</span>
                    <span style="color:var(--text-dim);font-size:11px;margin-left:6px;">{{ $tp->phone }} @if($tp->organization) &bull; {{ $tp->organization }} @endif</span>
                    @if($tp->unit)<span class="utag" style="margin-left:6px;font-size:10px;">{{ $tp->unit->name }}</span>@endif
                  </div>
                  <span class="badge" style="background:rgba(245,158,11,0.15);color:#F59E0B;border:1px solid rgba(245,158,11,0.3);font-size:10px;padding:2px 8px;">Third-Party</span>
                </div>
              </label>
            @endforeach
          </div>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('createSchemeModal')">Cancel</button>
        <button type="submit" class="btn bp">Create Scheme</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="mo" id="editSchemeModal">
  <div class="md" style="width:680px;max-width:96vw;">
    <div class="mh">
      <div class="mt2">Edit Scheme</div>
      <div class="mc3" onclick="cm('editSchemeModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('admin.schemes.update') }}" method="POST">
      @csrf
      <input type="hidden" name="form_type" value="edit_scheme">
      <input type="hidden" name="id" id="edit_scheme_id" value="{{ old('form_type') === 'edit_scheme' ? old('id') : '' }}">

      @if ($errors->any() && old('form_type') === 'edit_scheme')
        <div style="margin:0 18px 12px;padding:10px 12px;border-radius:8px;background:rgba(220,53,69,0.08);border:1px solid var(--red);color:var(--red);font-size:12px;">
          <strong>Please fix the following:</strong>
          <ul style="margin:6px 0 0 18px;padding:0;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid">
        <div class="fg s2">
          <label>Title</label>
          <input type="text" name="title" id="edit_title"
                 value="{{ old('form_type') === 'edit_scheme' ? old('title') : '' }}" required>
          @error('title') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" id="edit_description" rows="3">{{ old('form_type') === 'edit_scheme' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Amount per Month</label>
          <input type="number" step="0.01" name="amount" id="edit_amount"
                 value="{{ old('form_type') === 'edit_scheme' ? old('amount') : '' }}" required>
          @error('amount') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>Duration (Months)</label>
          <input type="number" name="duration_months" id="edit_duration_months"
                 value="{{ old('form_type') === 'edit_scheme' ? old('duration_months') : '' }}" required>
          @error('duration_months') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>Start Date</label>
          <input type="date" name="start_date" id="edit_start_date"
                 value="{{ old('form_type') === 'edit_scheme' ? old('start_date') : '' }}"
                 onchange="document.getElementById('edit_end_date').min = this.value;"
                 required>
          @error('start_date') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>End Date</label>
          <input type="date" name="end_date" id="edit_end_date"
                 value="{{ old('form_type') === 'edit_scheme' ? old('end_date') : '' }}">
          @error('end_date') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>

        {{-- MEMBERS SELECTION WITH THIRD-PARTY OPTION --}}
        <div class="fg s2">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
            <div>
              <span style="font-weight:600;font-size:13px;color:var(--text);">Select Members</span>
              <span style="font-weight:400;font-size:11px;color:var(--text-dim);display:block;">
                (Choose registered members and/or external third-party members)
              </span>
            </div>
            <button type="button" class="btn bo bsm" onclick="openAddThirdPartyModal('edit')"
                    style="font-size:11px;padding:5px 12px;color:var(--accent);border-color:var(--accent);display:flex;align-items:center;gap:6px;">
              <i class="fas fa-user-plus"></i> Add Third-Party Member
            </button>
          </div>
          @error('member_ids') <span style="color:var(--red);font-size:11px;display:block;margin-bottom:6px;">{{ $message }}</span> @enderror

          <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center;">
            <input type="text" placeholder="🔍 Search by name, phone, email, unit..."
                   onkeyup="filterMemberList('edit_member_list', this.value)"
                   style="flex:1;min-width:180px;padding:6px 12px;font-size:12px;border-radius:6px;border:1px solid var(--border);background:transparent;color:var(--text);">
            <div style="display:flex;gap:4px;">
              <button type="button" class="btn bo bsm filter-btn active" onclick="filterMemberType('edit_member_list', 'all', this)">All</button>
              <button type="button" class="btn bo bsm filter-btn" onclick="filterMemberType('edit_member_list', 'reg', this)">Registered</button>
              <button type="button" class="btn bo bsm filter-btn" onclick="filterMemberType('edit_member_list', 'tp', this)">Third-Party</button>
            </div>
          </div>

          <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center;font-size:11px;">
            <button type="button" class="btn bo bsm" onclick="toggleAllCheckboxes('edit_member_list', true, 'edit_selected_count')">Select All</button>
            <button type="button" class="btn bo bsm" onclick="toggleAllCheckboxes('edit_member_list', false, 'edit_selected_count')">Clear All</button>
            <span id="edit_selected_count" style="color:var(--text-dim);margin-left:auto;">0 selected</span>
          </div>

          <div id="edit_member_list"
               style="max-height:240px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:6px;">
            {{-- Registered Members --}}
            @foreach ($allMembers as $m)
              <label class="member-check-row" data-type="reg"
                     style="display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:6px;cursor:pointer;"
                     onmouseover="this.style.background='rgba(79,142,247,0.06)'"
                     onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="member_ids[]" value="{{ $m->id }}"
                       {{ (old('form_type') === 'edit_scheme' && in_array($m->id, old('member_ids', []))) ? 'checked' : '' }}
                       onchange="updateSelectedCount('edit_member_list', 'edit_selected_count')"
                       style="width:16px;height:16px;flex-shrink:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:10px;">
                  <div>
                    <span style="font-size:13px;font-weight:600;">{{ $m->user->name ?? '—' }}</span>
                    <span style="color:var(--text-dim);font-size:11px;margin-left:6px;">{{ $m->user->phone ?? '' }} &bull; {{ $m->user->email ?? '' }}</span>
                    @if($m->unit)<span class="utag" style="margin-left:6px;font-size:10px;">{{ $m->unit->name }}</span>@endif
                  </div>
                  <span class="badge" style="background:rgba(79,142,247,0.12);color:var(--accent);font-size:10px;padding:2px 8px;">Registered</span>
                </div>
              </label>
            @endforeach

            {{-- Third-Party Members --}}
            @foreach ($thirdPartyMembers as $tp)
              <label class="member-check-row" data-type="tp"
                     style="display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:6px;cursor:pointer;"
                     onmouseover="this.style.background='rgba(245,158,11,0.06)'"
                     onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="third_party_member_ids[]" value="{{ $tp->id }}"
                       {{ (old('form_type') === 'edit_scheme' && in_array($tp->id, old('third_party_member_ids', []))) ? 'checked' : '' }}
                       onchange="updateSelectedCount('edit_member_list', 'edit_selected_count')"
                       style="width:16px;height:16px;flex-shrink:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:10px;">
                  <div>
                    <span style="font-size:13px;font-weight:600;color:#F59E0B;">{{ $tp->name }}</span>
                    <span style="color:var(--text-dim);font-size:11px;margin-left:6px;">{{ $tp->phone }} @if($tp->organization) &bull; {{ $tp->organization }} @endif</span>
                    @if($tp->unit)<span class="utag" style="margin-left:6px;font-size:10px;">{{ $tp->unit->name }}</span>@endif
                  </div>
                  <span class="badge" style="background:rgba(245,158,11,0.15);color:#F59E0B;border:1px solid rgba(245,158,11,0.3);font-size:10px;padding:2px 8px;">Third-Party</span>
                </div>
              </label>
            @endforeach
          </div>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('editSchemeModal')">Cancel</button>
        <button type="submit" class="btn bp">Save Changes</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ ADD THIRD-PARTY MEMBER MODAL ══ --}}
<div class="mo" id="addThirdPartyModal">
  <div class="md" style="width:540px;max-width:96vw;">
    <div class="mh">
      <div>
        <div class="mt2"><i class="fas fa-user-plus" style="margin-right:6px;color:#F59E0B;"></i>Add Third-Party Member</div>
        <div style="font-size:11px;color:var(--text-dim);margin-top:2px;">External individual for scheme only. No account or login credentials will be created.</div>
      </div>
      <div class="mc3" onclick="cm('addThirdPartyModal')"><i class="fas fa-times"></i></div>
    </div>
    <form id="addThirdPartyForm" onsubmit="submitThirdPartyMember(event)" enctype="multipart/form-data">
      @csrf
      <div class="fgrid">
        <div class="fg s2">
          <label>Full Name <span style="color:var(--red)">*</span></label>
          <input type="text" id="tp_name" name="name" placeholder="Rajesh Kumar" required>
        </div>
        <div class="fg">
          <label>Phone Number <span style="color:var(--red)">*</span></label>
          <input type="text" id="tp_phone" name="phone" placeholder="9845002001" maxlength="15" required>
        </div>
        <div class="fg">
          <label>Email (optional)</label>
          <input type="email" id="tp_email" name="email" placeholder="rajesh@gmail.com">
        </div>
        <div class="fg">
          <label>Date of Birth</label>
          <input type="date" id="tp_dob" name="dob">
        </div>
        <div class="fg">
          <label>Shop Name</label>
          <input type="text" id="tp_org" name="organization" placeholder="Fathima Stores">
        </div>
        <div class="fg s2">
          <label>Unit <span style="color:var(--red)">*</span></label>
          <select id="tp_unit_id" name="unit_id" required>
            <option value="">Select Unit</option>
            @foreach($units as $u)
              <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="fg s2">
          <label>Address</label>
          <textarea id="tp_address" name="address" rows="2" placeholder="House name, street, area, PIN"></textarea>
        </div>
        <div class="fg s2">
          <label>Upload Member Photo</label>
          <input type="file" id="tp_photo" name="photo" accept="image/jpeg,image/png,image/jpg" style="padding:6px 11px;">
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('addThirdPartyModal')">Cancel</button>
        <button type="submit" id="tp_submit_btn" class="btn bp" style="background:#F59E0B;border-color:#F59E0B;color:#000;font-weight:600;">
          <i class="fas fa-check"></i> Save & Add to Scheme
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ VIEW MEMBERS MODAL ══ --}}
<div class="mo" id="viewMembersModal">
  <div class="md" style="width:520px;max-width:96vw;">
    <div class="mh">
      <div class="mt2" id="viewMembersTitle">Assigned Members</div>
      <div class="mc3" onclick="cm('viewMembersModal')"><i class="fas fa-times"></i></div>
    </div>
    <div id="viewMembersList" style="max-height:460px;overflow-y:auto;padding:0 18px 18px;"></div>
  </div>
</div>

@endsection

@section('scripts')
<script>
let currentTargetForm = 'create'; // 'create' or 'edit'

function openAddThirdPartyModal(target) {
  currentTargetForm = target;
  document.getElementById('addThirdPartyForm').reset();
  om('addThirdPartyModal');
}

function submitThirdPartyMember(e) {
  e.preventDefault();
  const form = document.getElementById('addThirdPartyForm');
  const submitBtn = document.getElementById('tp_submit_btn');
  const originalText = submitBtn.innerHTML;

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  const formData = new FormData(form);

  fetch(`{{ route('admin.schemes.third-party-member') }}`, {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
    }
  })
  .then(res => {
    if (!res.ok) {
      return res.json().then(err => { throw err; });
    }
    return res.json();
  })
  .then(data => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;

    if (data.success && data.member) {
      const m = data.member;
      const htmlRow = `
        <label class="member-check-row" data-type="tp"
               style="display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:6px;cursor:pointer;background:rgba(245,158,11,0.08);"
               onmouseover="this.style.background='rgba(245,158,11,0.06)'"
               onmouseout="this.style.background='transparent'">
          <input type="checkbox" name="third_party_member_ids[]" value="${m.id}" checked
                 onchange="updateSelectedCount('${currentTargetForm}_member_list', '${currentTargetForm}_selected_count')"
                 style="width:16px;height:16px;flex-shrink:0;">
          <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:10px;">
            <div>
              <span style="font-size:13px;font-weight:600;color:#F59E0B;">${esc(m.name)}</span>
              <span style="color:var(--text-dim);font-size:11px;margin-left:6px;">${esc(m.phone)} &bull; ${esc(m.organization || '—')}</span>
              <span class="utag" style="margin-left:6px;font-size:10px;">${esc(m.unit_name)}</span>
            </div>
            <span class="badge" style="background:rgba(245,158,11,0.15);color:#F59E0B;border:1px solid rgba(245,158,11,0.3);font-size:10px;padding:2px 8px;">Third-Party</span>
          </div>
        </label>
      `;

      // Prepend to current form member list
      const container = document.getElementById(`${currentTargetForm}_member_list`);
      if (container) {
        container.insertAdjacentHTML('afterbegin', htmlRow);
        updateSelectedCount(`${currentTargetForm}_member_list`, `${currentTargetForm}_selected_count`);
      }

      cm('addThirdPartyModal');
      alert(`Third-party member "${m.name}" created and selected for this scheme!`);
    }
  })
  .catch(err => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
    let msg = 'Failed to add third-party member.';
    if (err && err.errors) {
      msg = Object.values(err.errors).flat().join('\n');
    } else if (err && err.message) {
      msg = err.message;
    }
    alert(msg);
  });
}

function toggleAllCheckboxes(containerId, checked, countId) {
  document.querySelectorAll(`#${containerId} .member-check-row`).forEach(row => {
    if (row.style.display !== 'none') {
      row.querySelector('input[type="checkbox"]').checked = checked;
    }
  });
  updateSelectedCount(containerId, countId);
}

function updateSelectedCount(containerId, countId) {
  const regChecked = document.querySelectorAll(`#${containerId} input[name="member_ids[]"]:checked`).length;
  const tpChecked = document.querySelectorAll(`#${containerId} input[name="third_party_member_ids[]"]:checked`).length;
  const total = regChecked + tpChecked;
  document.getElementById(countId).textContent = `${total} selected (${regChecked} reg, ${tpChecked} 3rd-party)`;
}

function filterMemberList(containerId, query) {
  query = query.toLowerCase();
  document.querySelectorAll(`#${containerId} .member-check-row`).forEach(row => {
    const text = row.textContent.toLowerCase();
    const typeMatch = row.getAttribute('data-active-type-match') !== 'false';
    row.style.display = (text.includes(query) && typeMatch) ? 'flex' : 'none';
  });
}

function filterMemberType(containerId, type, btn) {
  const parent = btn.parentElement;
  parent.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  document.querySelectorAll(`#${containerId} .member-check-row`).forEach(row => {
    const rowType = row.getAttribute('data-type');
    let match = (type === 'all' || rowType === type);
    row.setAttribute('data-active-type-match', match ? 'true' : 'false');
    row.style.display = match ? 'flex' : 'none';
  });
}

function editScheme(id) {
  fetch(`{{ url('/admin/schemes') }}/${id}/detail`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('edit_scheme_id').value = id;
      document.getElementById('edit_title').value = data.title;
      document.getElementById('edit_description').value = data.description ?? '';
      document.getElementById('edit_amount').value = data.amount;
      document.getElementById('edit_duration_months').value = data.duration_months;
      document.getElementById('edit_start_date').value = data.start_date_raw;
      document.getElementById('edit_end_date').min = data.start_date_raw;
      document.getElementById('edit_end_date').value = data.end_date_raw ?? '';

      // Registered members
      const regAssigned = (data.member_ids || []).map(String);
      document.querySelectorAll('#edit_member_list input[name="member_ids[]"]').forEach(cb => {
        cb.checked = regAssigned.includes(cb.value);
      });

      // Third-party members
      const tpAssigned = (data.third_party_member_ids || []).map(String);
      document.querySelectorAll('#edit_member_list input[name="third_party_member_ids[]"]').forEach(cb => {
        cb.checked = tpAssigned.includes(cb.value);
      });

      updateSelectedCount('edit_member_list', 'edit_selected_count');
      om('editSchemeModal');
    })
    .catch(() => alert('Failed to load scheme data!'));
}

function viewSchemeMembers(id) {
  fetch(`{{ url('/admin/schemes') }}/${id}/detail`)
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('viewMembersList');
      list.innerHTML = '';

      const regMembers = data.members || [];
      const tpMembers = data.third_party_members || [];

      if (regMembers.length === 0 && tpMembers.length === 0) {
        list.innerHTML = '<div style="padding:24px 0;color:var(--text-dim);text-align:center;">No members assigned to this scheme.</div>';
      } else {
        let content = '';

        // Registered members section
        if (regMembers.length > 0) {
          content += `
            <div style="font-weight:700;font-size:12px;color:var(--accent);text-transform:uppercase;letter-spacing:0.5px;margin:12px 0 6px;">
              <i class="fas fa-users" style="margin-right:6px;"></i>Registered Members (${regMembers.length})
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px;">
              ${regMembers.map(m => `
                <div style="padding:8px 12px;background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:6px;display:flex;justify-content:space-between;align-items:center;">
                  <div>
                    <div style="font-weight:600;font-size:13px;color:var(--text);">${esc(m.name)}</div>
                    <div style="font-size:11px;color:var(--text-dim);">${esc(m.phone || '—')} &bull; ${esc(m.email || 'No email')}</div>
                  </div>
                  <div><span class="utag">${esc(m.unit)}</span></div>
                </div>
              `).join('')}
            </div>
          `;
        }

        // Third-party members section
        if (tpMembers.length > 0) {
          content += `
            <div style="font-weight:700;font-size:12px;color:#F59E0B;text-transform:uppercase;letter-spacing:0.5px;margin:12px 0 6px;">
              <i class="fas fa-user-tag" style="margin-right:6px;"></i>External Third-Party Members (${tpMembers.length})
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
              ${tpMembers.map(m => `
                <div style="padding:8px 12px;background:rgba(245,158,11,0.03);border:1px solid rgba(245,158,11,0.2);border-radius:6px;display:flex;justify-content:space-between;align-items:center;">
                  <div>
                    <div style="font-weight:600;font-size:13px;color:#F59E0B;">${esc(m.name)} <span class="badge" style="background:rgba(245,158,11,0.2);color:#F59E0B;font-size:9px;margin-left:4px;">External</span></div>
                    <div style="font-size:11px;color:var(--text-dim);">${esc(m.phone)} &bull; Shop: ${esc(m.organization || '—')} &bull; ${esc(m.email || 'No email')}</div>
                  </div>
                  <div><span class="utag">${esc(m.unit)}</span></div>
                </div>
              `).join('')}
            </div>
          `;
        }

        list.innerHTML = content;
      }

      document.getElementById('viewMembersTitle').textContent = `${data.title} — Assigned Members (${regMembers.length + tpMembers.length})`;
      om('viewMembersModal');
    })
    .catch(() => alert('Failed to load member list!'));
}

function esc(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[s]);
}

document.addEventListener('DOMContentLoaded', function () {
  updateSelectedCount('create_member_list', 'create_selected_count');
  @if ($errors->any())
    @if (old('form_type') === 'create_scheme')
      om('createSchemeModal');
      document.getElementById('create_end_date').min = document.getElementById('create_start_date').value || '';
    @elseif (old('form_type') === 'edit_scheme')
      om('editSchemeModal');
      document.getElementById('edit_end_date').min = document.getElementById('edit_start_date').value || '';
    @endif
  @endif
});
</script>
<style>
.filter-btn.active {
  background: var(--accent) !important;
  color: #fff !important;
  border-color: var(--accent) !important;
}
</style>
@endsection
