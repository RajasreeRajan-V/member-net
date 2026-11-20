@extends('layouts.admin')
@section('title', 'News')
@section('page_title', 'News & Announcements')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-newspaper"></i>News & Announcements
    </div>
    <button class="btn bp bsm" onclick="resetAddNewsModal(); om('addNewsM')">
      <i class="fas fa-plus"></i>Create News
    </button>
  </div>

  <div class="tw">
    <table style="min-width: 820px;">
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Title</th>
          <th>Created By</th>
          <th>Status</th>
          <th>Published At</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($news as $item)
        @php
          // ── Who created this news? ──
          $creator = $item->createdBy;
          $role    = $creator->role ?? null;

          if ($role === 'unit_admin') {
              $label = 'Unit Admin';
              $color = 'var(--accent)';
              $scope = $unitAdmins[$item->created_by]->unit->name ?? null;
          } elseif ($role === 'district_admin') {
              $label = 'District Admin';
              $color = 'var(--green)';
              $scope = $districtAdmins[$item->created_by]->district->name ?? null;
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
            {{ ($news->currentPage() - 1) * $news->perPage() + $loop->iteration }}
          </td>

          {{-- IMAGE --}}
          <td>
            @if($item->image)
              <img src="{{ asset('storage/' . $item->image) }}"
                   alt="News Image"
                   style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
            @else
              <span style="font-size:10px;color:var(--text-dim)">No Image</span>
            @endif
          </td>

          <td>
            <div>
              <div class="mn">{{ $item->title }}</div>
              <div class="me">{{ Str::limit($item->content, 60) }}</div>
            </div>
          </td>

          {{-- ══ CREATED BY ══ --}}
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
            <span class="badge {{ $item->status == 'PUBLISHED' ? 'bap' : 'bpe' }}">
              {{ $item->status }}
            </span>
          </td>
          <td style="color:var(--text-dim)">
            {{ $item->published_at ? $item->published_at->format('d M Y h:i A') : '—' }}
          </td>
          <td style="color:var(--text-dim)">
            {{ $item->created_at->format('d M Y') }}
          </td>
          <td>
            <div class="abr">
              {{-- VIEW --}}
              <div class="ab vw"
                   onclick="viewNews(
                     '{{ addslashes($item->title) }}',
                     '{{ addslashes($item->content) }}',
                     '{{ $item->status }}',
                     '{{ $item->published_at ? $item->published_at->format('d M Y h:i A') : '' }}')"
                   title="View">
                <i class="fas fa-eye"></i>
              </div>

              {{-- EDIT --}}
              <div class="ab ed"
                   onclick="editNews(
                     {{ $item->id }},
                     '{{ addslashes($item->title) }}',
                     '{{ addslashes($item->content) }}')"
                   title="Edit">
                <i class="fas fa-edit"></i>
              </div>

              {{-- TOGGLE STATUS --}}
              <form method="POST" action="{{ route('admin.news.toggle-status') }}" style="display:inline">
                @csrf
                <input type="hidden" name="id" value="{{ $item->id }}">
                <button type="submit"
                  class="ab {{ $item->status == 'PUBLISHED' ? 'rej' : 'app' }}"
                  title="{{ $item->status == 'PUBLISHED' ? 'Unpublish' : 'Publish' }}"
                  onclick="return confirm('{{ $item->status == 'PUBLISHED' ? 'Unpublish' : 'Publish' }} this news?')">
                  <i class="fas fa-{{ $item->status == 'PUBLISHED' ? 'eye-slash' : 'paper-plane' }}"></i>
                </button>
              </form>

              {{-- DELETE --}}
              <form method="POST" action="{{ route('admin.news.delete') }}" style="display:inline"
                    onsubmit="return confirm('Delete {{ addslashes($item->title) }}?')">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $item->id }}">
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
            <div style="font-size:32px;margin-bottom:10px;">📰</div>
            <div style="font-size:13px;font-weight:600;margin-bottom:6px;">No news yet</div>
            <div style="font-size:11px;color:var(--text-dim);margin-bottom:14px;">
              Create your first announcement
            </div>
            <button class="btn bp bsm" onclick="resetAddNewsModal(); om('addNewsM')">
              <i class="fas fa-plus"></i>Create News
            </button>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ── PAGINATION ── --}}
  @if($news->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between;
                flex-wrap:wrap;gap:8px;">
      <span style="font-size:11px;color:var(--text-dim);">
        Showing {{ $news->firstItem() }}–{{ $news->lastItem() }}
        of {{ $news->total() }} items
      </span>
      {{ $news->appends(request()->query())->links('pagination.custom') }}
    </div>
  @endif

</div>{{-- end .card --}}


{{-- ══ ADD NEWS MODAL ══ --}}
<div id="addNewsM" class="mo">
  <div class="md" style="width:560px">
    <div class="mh">
      <span class="mt2">Create News / Announcement</span>
      <div class="mc3" onclick="resetAddNewsModal(); cm('addNewsM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data" id="addNewsForm">
      @csrf

      @if ($errors->any())
        <div id="addNewsError" style="margin:14px 18px 0;padding:10px 12px;
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
          <label>Title</label>
          <input type="text" name="title" id="addNewsTitle" maxlength="200"
                 value="{{ old('title') }}"
                 placeholder="Enter news title" required>
        </div>
        <div class="fg">
          <label>Content</label>
          <textarea name="content" id="addNewsContent" placeholder="Write your announcement here..."
                    style="min-height:120px" required>{{ old('content') }}</textarea>
        </div>
        <div class="fg">
          <label>Image <span style="color:#f87171">*</span></label>
          <input type="file" name="image" id="addNewsImage" accept="image/jpg,image/jpeg,image/png"
                 style="padding:6px 11px;" required>
        </div>
        <div class="fg">
          <label>Status</label>
          <select name="status" id="addNewsStatus" required>
            <option value="DRAFT" {{ old('status') == 'DRAFT' ? 'selected' : '' }}>Draft (not visible to members)</option>
            <option value="PUBLISHED" {{ old('status') == 'PUBLISHED' ? 'selected' : '' }}>Published (visible to members)</option>
          </select>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetAddNewsModal(); cm('addNewsM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-paper-plane"></i>Create News
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT NEWS MODAL ══ --}}
<div id="editNewsM" class="mo">
  <div class="md" style="width:560px">
    <div class="mh">
      <span class="mt2">Edit News</span>
      <div class="mc3" onclick="resetEditNewsModal(); cm('editNewsM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.news.update') }}" enctype="multipart/form-data" id="editNewsForm">
      @csrf
      <input type="hidden" name="id" id="editNewsId">
      <div class="fgrid c1">
        <div class="fg">
          <label>Title</label>
          <input type="text" name="title" id="editNewsTitle" maxlength="200" required>
        </div>
        <div class="fg">
          <label>Content</label>
          <textarea name="content" id="editNewsContent" style="min-height:120px" required></textarea>
        </div>
        <div class="fg">
          <label>New Image (optional — leave blank to keep current)</label>
          <input type="file" name="image" id="editNewsImage" accept="image/jpg,image/jpeg,image/png" style="padding:6px 11px;">
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetEditNewsModal(); cm('editNewsM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Update News
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ VIEW NEWS MODAL ══ --}}
<div id="viewNewsM" class="mo">
  <div class="md" style="width:560px">
    <div class="mh">
      <span class="mt2" id="viewNewsTitle">News Detail</span>
      <div class="mc3" onclick="cm('viewNewsM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <div style="padding:20px;">
      <div style="display:flex;gap:8px;margin-bottom:14px;align-items:center;flex-wrap:wrap;">
        <span id="viewNewsStatus" class="badge"></span>
        <span id="viewNewsDate" style="font-size:11px;color:var(--text-dim);"></span>
      </div>
      <div id="viewNewsContent"
           style="font-size:13px;line-height:1.8;color:var(--text-dim);white-space:pre-wrap;">
      </div>
    </div>
    <div class="ff">
      <button class="btn bo" onclick="cm('viewNewsM')">Close</button>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
// ── Reset "Create News" modal: clears stale error banner + every field
//    (title, content, image, status back to default) ──
function resetAddNewsModal() {
  const errBox = document.getElementById('addNewsError');
  if (errBox) errBox.remove();

  const form = document.getElementById('addNewsForm');
  if (form) form.reset();
}

// ── Reset "Edit News" modal: clears fields immediately so no stale
//    data from a previously-edited article briefly shows ──
function resetEditNewsModal() {
  const form = document.getElementById('editNewsForm');
  if (form) form.reset();

  document.getElementById('editNewsId').value = '';
}

function editNews(id, title, content) {
  resetEditNewsModal();
  document.getElementById('editNewsId').value      = id;
  document.getElementById('editNewsTitle').value   = title;
  document.getElementById('editNewsContent').value = content;
  om('editNewsM');
}

function viewNews(title, content, status, publishedAt) {
  document.getElementById('viewNewsTitle').textContent   = title;
  document.getElementById('viewNewsContent').textContent = content;

  const statusEl = document.getElementById('viewNewsStatus');
  statusEl.textContent = status;
  statusEl.className   = 'badge ' + (status === 'PUBLISHED' ? 'bap' : 'bpe');

  document.getElementById('viewNewsDate').textContent =
    publishedAt ? '📅 Published: ' + publishedAt : '📝 Not published yet';

  om('viewNewsM');
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    om('addNewsM');
  });
@endif
</script>
@endsection