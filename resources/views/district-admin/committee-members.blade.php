@extends('layouts.district-admin')
@section('title', 'Committee Members')
@section('page_title', $committee->name . ' — Members')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-users-cog"></i> {{ $committee->name }} — Members</div>
    <div style="display:flex;gap:8px;">
      <button class="btn bp bsm" onclick="om('addMemberModal')">
        <i class="fas fa-plus"></i> Add Member
      </button>
      <a href="{{ route('district-admin.committees') }}" class="btn bo bsm">
        <i class="fas fa-arrow-left"></i> Back to Committees
      </a>
    </div>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>Member</th>
          <th class="hide-mobile">Unit</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($committee->members as $member)
          <tr>
            <td>
              <div class="mc2">
                <div class="av2" style="background:rgba(79,142,247,0.2);color:var(--accent-2);">
                  {{ strtoupper(substr($member->user->name ?? '—', 0, 2)) }}
                </div>
                <div>
                  <div class="mn">{{ $member->user->name ?? '—' }}</div>
                  <div class="me">{{ $member->user->email ?? '' }}</div>
                </div>
              </div>
            </td>
            <td class="hide-mobile" style="color:var(--text-dim);">{{ $member->unit->name ?? '—' }}</td>
            <td>
              <span class="badge ban">{{ $member->pivot->role }}</span>
            </td>
            <td>
              <div class="abr">
                <button type="button" class="ab ed" title="Edit Role"
                  onclick="editRole({{ $member->id }}, {{ \Illuminate\Support\Js::from($member->pivot->role) }})">
                  <i class="fas fa-pen"></i>
                </button>

                <form action="{{ route('district-admin.committees.members.remove') }}" method="POST" style="display:inline;"
                      onsubmit="return confirm('Remove this member from the committee?')">
                  @csrf
                  <input type="hidden" name="committee_id" value="{{ $committee->id }}">
                  <input type="hidden" name="member_profile_id" value="{{ $member->id }}">
                  <button type="submit" class="ab rej" title="Remove"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" style="text-align:center;color:var(--text-dim);white-space:normal;padding:30px;">No members added yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ══ ADD MEMBER MODAL ══ --}}
<div class="mo" id="addMemberModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">Add Member</div>
      <div class="mc3" onclick="cm('addMemberModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('district-admin.committees.members.add') }}" method="POST">
      @csrf
      <input type="hidden" name="committee_id" value="{{ $committee->id }}">
      <div class="fgrid c1">
        <div class="fg">
          <label>Member (approved members of your district)</label>
          <select name="member_profile_id" required>
            <option value="">Select a member…</option>
            @foreach ($availableMembers as $m)
              <option value="{{ $m->id }}">
                {{ $m->user->name ?? '—' }} ({{ $m->user->email ?? '' }}){{ $m->unit ? ' · ' . $m->unit->name : '' }}
              </option>
            @endforeach
          </select>
          @if($availableMembers->isEmpty())
            <div style="font-size:11px;color:var(--text-dim);margin-top:4px;">
              No approved members left to add.
            </div>
          @endif
        </div>
        <div class="fg">
          <label>Role</label>
          <select name="role" required>
            <option value="Member">Member</option>
            <option value="Coordinator">Coordinator</option>
            <option value="Secretary">Secretary</option>
            <option value="Chairperson">Chairperson</option>
          </select>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('addMemberModal')">Cancel</button>
        <button type="submit" class="btn bp">Add to Committee</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT ROLE MODAL ══ --}}
<div class="mo" id="editRoleModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">Edit Role</div>
      <div class="mc3" onclick="cm('editRoleModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('district-admin.committees.members.update-role') }}" method="POST">
      @csrf
      <input type="hidden" name="committee_id" value="{{ $committee->id }}">
      <input type="hidden" name="member_profile_id" id="edit_role_member_id">
      <div class="fgrid c1">
        <div class="fg">
          <label>Role</label>
          <select name="role" id="edit_role_value" required>
            <option value="Member">Member</option>
            <option value="Coordinator">Coordinator</option>
            <option value="Secretary">Secretary</option>
            <option value="Chairperson">Chairperson</option>
          </select>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('editRoleModal')">Cancel</button>
        <button type="submit" class="btn bp">Save Role</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function editRole(memberId, currentRole) {
  const sel = document.getElementById('edit_role_value');

  // if the saved role isn't one of the standard options, add it so it isn't lost
  if (![...sel.options].some(o => o.value === currentRole)) {
    const opt = document.createElement('option');
    opt.value = currentRole;
    opt.textContent = currentRole;
    sel.appendChild(opt);
  }

  document.getElementById('edit_role_member_id').value = memberId;
  sel.value = currentRole;
  om('editRoleModal');
}
</script>
@endsection