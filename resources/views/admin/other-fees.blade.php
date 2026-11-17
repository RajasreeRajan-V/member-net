@extends('layouts.admin')
@section('title', 'Other Fees')
@section('page_title', 'Other Fees')

@section('content')

  <div class="card">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-receipt"></i>All Other Fees
      </div>
      <button class="btn bp bsm" onclick="om('addFeeM')">
        <i class="fas fa-plus"></i>Create Fee
      </button>
    </div>

    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Amount</th>
            <th>Due Date</th>
            <th>Created By</th>
            <th>Members Owing</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($fees as $f)
            @php
              $creator = $f->createdBy;
              $role    = $creator->role ?? null;

              if ($role === 'unit_admin') {
                  $label = 'Unit Admin';
                  $color = 'var(--accent)';
                  $scope = $unitAdmins[$f->created_by]->unit->name ?? null;
              } elseif ($role === 'district_admin') {
                  $label = 'District Admin';
                  $color = 'var(--green)';
                  $scope = $districtAdmins[$f->created_by]->district->name ?? null;
              } elseif ($creator) {
                  $label = 'Super Admin';
                  $color = 'var(--red)';
                  $scope = null;
              } else {
                  $label = 'Unknown';
                  $color = 'var(--text-dim)';
                  $scope = null;
              }
            @endphp
            <tr>
              <td style="color:var(--text-dim)">
                {{ ($fees->currentPage() - 1) * $fees->perPage() + $loop->iteration }}
              </td>

              {{-- TITLE — wrapped so a long title can't stretch the table wider than the page --}}
              <td class="mn" style="white-space:normal;word-break:break-word;overflow-wrap:anywhere;max-width:260px;">
                {{ $f->title }}
              </td>

              <td>₹{{ number_format($f->amount, 2) }}</td>

              <td style="color:var(--text-dim)">
                {{ $f->due_date ? $f->due_date->format('d M Y') : '—' }}
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
                <a href="{{ route('admin.other-fees.payers', $f->id) }}" style="color:var(--accent)">
                  {{ $f->payments_count }} member(s)
                </a>
              </td>

              <td>
                <span style="padding:3px 10px;font-size:10px;border-radius:20px;
                             color: {{ $f->is_active ? 'var(--green)' : 'var(--red)' }};
                             border:1px solid var(--border);">
                  {{ $f->is_active ? 'ACTIVE' : 'INACTIVE' }}
                </span>
              </td>

              <td>
                <div class="abr">
                  <div class="ab ed" onclick="editFee({{ $f->id }})" title="Edit">
                    <i class="fas fa-edit"></i>
                  </div>

                  <form method="POST" action="{{ route('admin.other-fees.toggle') }}" style="display:inline">
                    @csrf @method('PUT')
                    <input type="hidden" name="id" value="{{ $f->id }}">
                    <button type="submit" class="ab" title="{{ $f->is_active ? 'Deactivate' : 'Activate' }}">
                      <i class="fas fa-power-off"></i>
                    </button>
                  </form>

                  <form method="POST" action="{{ route('admin.other-fees.delete') }}"
                        style="display:inline"
                        onsubmit="return confirm('Delete {{ addslashes($f->title) }}?')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="id" value="{{ $f->id }}">
                    <button type="submit" class="ab rej" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align:center;color:var(--text-dim);padding:40px;">
                <div style="font-size:32px;margin-bottom:10px;">🧾</div>
                <div style="font-size:13px;font-weight:600;margin-bottom:6px;">No other fees yet</div>
                <button class="btn bp bsm" onclick="om('addFeeM')">
                  <i class="fas fa-plus"></i>Create Fee
                </button>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ── PAGINATION (same styled block as Members / Expenses) ── --}}
    @if($fees->hasPages())
      <div style="padding:12px 16px;border-top:1px solid var(--border);
                  display:flex;align-items:center;justify-content:space-between;
                  flex-wrap:wrap;gap:8px;">
        <span style="font-size:11px;color:var(--text-dim);">
          Showing {{ $fees->firstItem() }}–{{ $fees->lastItem() }}
          of {{ $fees->total() }} items
        </span>
        {{ $fees->appends(request()->query())->links('pagination.custom') }}
      </div>
    @endif
  </div>

  {{-- ══ ADD FEE MODAL ══ --}}
  <div id="addFeeM" class="mo">
    <div class="md" style="width:500px">
      <div class="mh">
        <span class="mt2">Create Other Fee</span>
        <div class="mc3" onclick="closeAndReset('addFeeM','addFeeForm')"><i class="fas fa-times"></i></div>
      </div>
      <form method="POST" action="{{ route('admin.other-fees.store') }}" id="addFeeForm">
        @csrf

        @if ($errors->any() && old('form') === 'create')
          <div style="margin:14px 18px 0;padding:10px 12px;
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

        <input type="hidden" name="form" value="create">

        <div class="fgrid">
          <div class="fg s2">
            <label>Fee Title</label>
            <input type="text" name="title" maxlength="200"
                   value="{{ old('form') === 'create' ? old('title') : '' }}"
                   placeholder="Annual conference fee" required>
          </div>
          <div class="fg">
            <label>Amount (₹, max 7 digits)</label>
            <input type="number" name="amount" min="0.01" max="9999999" step="0.01"
                   value="{{ old('form') === 'create' ? old('amount') : '' }}" required>
          </div>
          <div class="fg">
            <label>Due Date (optional)</label>
            <input type="date" name="due_date"
                   min="{{ now()->format('Y-m-d') }}"
                   value="{{ old('form') === 'create' ? old('due_date') : '' }}">
          </div>
        </div>
        <p style="font-size:11px;color:var(--text-dim);padding:0 20px 10px;">
          This fee will apply to every active member automatically once they check their dues.
        </p>
        <div class="ff">
          <button type="button" class="btn bo" onclick="closeAndReset('addFeeM','addFeeForm')">Cancel</button>
          <button type="submit" class="btn bp">
            <i class="fas fa-plus"></i>Create Fee
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- ══ EDIT FEE MODAL ══ --}}
  <div id="editFeeM" class="mo">
    <div class="md" style="width:500px">
      <div class="mh">
        <span class="mt2">Edit Other Fee</span>
        <div class="mc3" onclick="closeAndReset('editFeeM','editFeeForm')"><i class="fas fa-times"></i></div>
      </div>
      <form method="POST" action="{{ route('admin.other-fees.update') }}" id="editFeeForm">
        @csrf @method('PUT')

        @if ($errors->any() && old('form') === 'edit')
          <div style="margin:14px 18px 0;padding:10px 12px;
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

        <input type="hidden" name="form" value="edit">
        <input type="hidden" name="id" id="editFeeId"
               value="{{ old('form') === 'edit' ? old('id') : '' }}">
        <div class="fgrid">
          <div class="fg s2">
            <label>Fee Title</label>
            <input type="text" name="title" id="editFeeTitle" maxlength="200"
                   value="{{ old('form') === 'edit' ? old('title') : '' }}" required>
          </div>
          <div class="fg">
            <label>Amount (₹, max 7 digits)</label>
            <input type="number" name="amount" id="editFeeAmount" min="0.01" max="9999999" step="0.01"
                   value="{{ old('form') === 'edit' ? old('amount') : '' }}" required>
          </div>
          <div class="fg">
            <label>Due Date</label>
            <input type="date" name="due_date" id="editFeeDueDate"
                   min="{{ now()->format('Y-m-d') }}"
                   value="{{ old('form') === 'edit' ? old('due_date') : '' }}">
          </div>
        </div>
        <div class="ff">
          <button type="button" class="btn bo" onclick="closeAndReset('editFeeM','editFeeForm')">Cancel</button>
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
// ── Close a modal AND reset its form, so stale values don't linger
//    for the next time it's opened (fixes fields not clearing on Cancel/×). ──
function closeAndReset(modalId, formId) {
    cm(modalId);
    const form = document.getElementById(formId);
    if (form) form.reset();
}

function editFee(id) {
    fetch(`{{ url('/admin/other-fees') }}/${id}/detail`)
        .then(r => r.json())
        .then(f => {
            document.getElementById('editFeeId').value       = id;
            document.getElementById('editFeeTitle').value    = f.title;
            document.getElementById('editFeeAmount').value   = f.amount;
            document.getElementById('editFeeDueDate').value  = f.due_date_raw ?? '';
            om('editFeeM');
        })
        .catch(() => alert('Failed to load fee data!'));
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      om('editFeeM');
    @else
      om('addFeeM');
    @endif
  });
@endif
</script>
@endsection