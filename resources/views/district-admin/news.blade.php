@extends('layouts.district-admin')
@section('title', 'News')
@section('page_title', 'News & Announcements')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-newspaper"></i> News & Announcements</div>
    <button class="btn bp bsm" onclick="resetAddNewsModal(); om('addNewsM')">
      <i class="fas fa-plus"></i> Create News
    </button>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Status</th>
          <th>Published At</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($news as $item)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td>
            <div style="display:flex; gap:10px; align-items:center;">
              <div style="width:50px;height:50px;border-radius:6px;overflow:hidden;flex-shrink:0;">
                @if($item->image)
                  <img src="{{ asset('storage/'.$item->image) }}" style="width:100%;height:100%;object-fit:cover;">
                @else
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:rgba(79,142,247,0.15);color:var(--accent);font-size:18px;">📰</div>
                @endif
              </div>
              <div>
                <div class="mn">{{ $item->title }}</div>
                <div class="me">{{ Str::limit(strip_tags($item->content), 60) }}</div>
              </div>
            </div>
          </td>
          <td>
            <span class="badge {{ $item->status === 'PUBLISHED' ? 'bap' : 'bpe' }}">{{ $item->status }}</span>
          </td>
          <td style="color:var(--text-dim)">
            {{ $item->published_at ? $item->published_at->format('d M Y h:i A') : '—' }}
          </td>
          <td style="color:var(--text-dim)">{{ $item->created_at->format('d M Y') }}</td>
          <td>
            <div class="abr">
              {{-- Js::from() safely escapes quotes AND line breaks inside the onclick --}}
              <div class="ab vw" title="View"
                   onclick="viewNews(
                     {{ \Illuminate\Support\Js::from($item->title) }},
                     {{ \Illuminate\Support\Js::from($item->content) }},
                     {{ \Illuminate\Support\Js::from($item->status) }},
                     {{ \Illuminate\Support\Js::from($item->published_at ? $item->published_at->format('d M Y h:i A') : '') }},
                     {{ \Illuminate\Support\Js::from($item->image ? asset('storage/'.$item->image) : '') }})">
                <i class="fas fa-eye"></i>
              </div>

              {{-- 4th argument tells the edit modal whether this news already has an image --}}
              <div class="ab ed" title="Edit"
                   onclick="editNews(
                     {{ $item->id }},
                     {{ \Illuminate\Support\Js::from($item->title) }},
                     {{ \Illuminate\Support\Js::from($item->content) }},
                     {{ $item->image ? 'true' : 'false' }})">
                <i class="fas fa-edit"></i>
              </div>

              <form method="POST" action="{{ route('district-admin.news.toggle-status') }}" style="display:inline">
                @csrf
                <input type="hidden" name="id" value="{{ $item->id }}">
                <button type="submit"
                  class="ab {{ $item->status === 'PUBLISHED' ? 'rej' : 'app' }}"
                  title="{{ $item->status === 'PUBLISHED' ? 'Unpublish' : 'Publish' }}"
                  onclick="return confirm('{{ $item->status === 'PUBLISHED' ? 'Unpublish' : 'Publish' }} this news?')">
                  <i class="fas fa-{{ $item->status === 'PUBLISHED' ? 'eye-slash' : 'paper-plane' }}"></i>
                </button>
              </form>

              <form method="POST" action="{{ route('district-admin.news.delete') }}" style="display:inline"
                    onsubmit="return confirm('Delete this news?')">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $item->id }}">
                <button type="submit" class="ab rej" title="Delete"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" style="text-align:center;color:var(--text-dim);padding:40px;">
            <div style="font-size:32px;margin-bottom:10px;">📰</div>
            <div style="font-size:13px;font-weight:600;margin-bottom:6px;">No news yet</div>
            <div style="font-size:11px;margin-bottom:14px;">Create your first announcement</div>
            <button class="btn bp bsm" onclick="resetAddNewsModal(); om('addNewsM')"><i class="fas fa-plus"></i> Create News</button>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($news->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);">
      {{ $news->links('pagination.custom') }}
    </div>
  @endif
</div>

{{-- ══ ADD NEWS MODAL ══ --}}
<div id="addNewsM" class="mo">
  <div class="md" style="width:560px">
    <div class="mh">
      <span class="mt2">Create News / Announcement</span>
      <div class="mc3" onclick="resetAddNewsModal(); cm('addNewsM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.news.store') }}" enctype="multipart/form-data" id="addNewsForm">
      @csrf
      <input type="hidden" name="form" value="create">

      @if ($errors->any() && old('form') === 'create')
        <div id="addNewsError" style="margin:14px 18px 0;padding:10px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid c1">
        <div class="fg">
          <label>Title</label>
          <input type="text" name="title" maxlength="200" placeholder="Enter news title"
                 value="{{ old('form') === 'create' ? old('title') : '' }}" required>
        </div>
        <div class="fg">
          <label>Content</label>
          <textarea name="content" placeholder="Write your announcement here..." style="min-height:120px" required>{{ old('form') === 'create' ? old('content') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Image <span style="color:#f87171">*</span></label>
          <input type="file" name="image" accept="image/jpg,image/jpeg,image/png" style="padding:6px 11px;" required>
        </div>
        <div class="fg">
          <label>Status</label>
          <select name="status" required>
            <option value="DRAFT" @selected(old('form') === 'create' && old('status') === 'DRAFT')>Draft (not visible to members)</option>
            <option value="PUBLISHED" @selected(old('form') === 'create' && old('status') === 'PUBLISHED')>Published (visible to members)</option>
          </select>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetAddNewsModal(); cm('addNewsM')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-paper-plane"></i> Create News</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT NEWS MODAL ══ --}}
<div id="editNewsM" class="mo">
  <div class="md" style="width:560px">
    <div class="mh">
      <span class="mt2">Edit News</span>
      <div class="mc3" onclick="resetEditNewsModal(); cm('editNewsM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.news.update') }}" enctype="multipart/form-data" id="editNewsForm">
      @csrf
      <input type="hidden" name="form" value="edit">
      <input type="hidden" name="id" id="editNewsId" value="{{ old('form') === 'edit' ? old('id') : '' }}">

      @if ($errors->any() && old('form') === 'edit')
        <div id="editNewsError" style="margin:14px 18px 0;padding:10px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid c1">
        <div class="fg">
          <label>Title</label>
          <input type="text" name="title" id="editNewsTitle" maxlength="200"
                 value="{{ old('form') === 'edit' ? old('title') : '' }}" required>
        </div>
        <div class="fg">
          <label>Content</label>
          <textarea name="content" id="editNewsContent" style="min-height:120px" required>{{ old('form') === 'edit' ? old('content') : '' }}</textarea>
        </div>
        <div class="fg">
          <label id="editNewsImageLabel">Replace Image (leave empty to keep current)</label>
          <input type="file" name="image" id="editNewsImage" accept="image/jpg,image/jpeg,image/png" style="padding:6px 11px;">
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetEditNewsModal(); cm('editNewsM')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-save"></i> Update News</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ VIEW NEWS MODAL ══ --}}
<div id="viewNewsM" class="mo">
  <div class="md" style="width:560px">
    <div class="mh">
      <span class="mt2" id="viewNewsTitle">News Detail</span>
      <div class="mc3" onclick="cm('viewNewsM')"><i class="fas fa-times"></i></div>
    </div>
    <div style="padding:20px;">
      <div id="viewNewsImage" style="margin-bottom:15px;"></div>
      <div style="display:flex;gap:8px;margin-bottom:14px;align-items:center;flex-wrap:wrap;">
        <span id="viewNewsStatus" class="badge"></span>
        <span id="viewNewsDate" style="font-size:11px;color:var(--text-dim);"></span>
      </div>
      <div id="viewNewsContent" style="font-size:13px;line-height:1.8;color:var(--text-dim);white-space:pre-wrap;"></div>
    </div>
    <div class="ff"><button class="btn bo" onclick="cm('viewNewsM')">Close</button></div>
  </div>
</div>

@endsection

@section('scripts')
<script>
function resetAddNewsModal() {
  const errBox = document.getElementById('addNewsError');
  if (errBox) errBox.remove();
  const form = document.getElementById('addNewsForm');
  if (form) form.reset();
}

function resetEditNewsModal() {
  const errBox = document.getElementById('editNewsError');
  if (errBox) errBox.remove();
  const fileInput = document.getElementById('editNewsImage');
  if (fileInput) fileInput.value = '';
}

// hasImage = true  -> image optional (keeps current one)
// hasImage = false -> older news with no image, so a new one is required
function editNews(id, title, content, hasImage) {
  resetEditNewsModal();
  document.getElementById('editNewsId').value      = id;
  document.getElementById('editNewsTitle').value   = title;
  document.getElementById('editNewsContent').value = content;

  const fileInput = document.getElementById('editNewsImage');
  const label     = document.getElementById('editNewsImageLabel');
  fileInput.required = !hasImage;
  label.innerHTML = hasImage
    ? 'Replace Image (leave empty to keep current)'
    : 'Image <span style="color:#f87171">*</span> (required, this news has no image)';

  om('editNewsM');
}

function viewNews(title, content, status, publishedAt, image) {
  document.getElementById('viewNewsTitle').textContent   = title;
  document.getElementById('viewNewsContent').textContent = content;

  const statusEl = document.getElementById('viewNewsStatus');
  statusEl.textContent = status;
  statusEl.className   = 'badge ' + (status === 'PUBLISHED' ? 'bap' : 'bpe');

  document.getElementById('viewNewsDate').textContent =
    publishedAt ? '📅 Published: ' + publishedAt : '📝 Not published yet';

  const imgEl = document.getElementById('viewNewsImage');
  imgEl.innerHTML = '';
  if (image) {
    const img = document.createElement('img');
    img.src = image;
    img.style.cssText = 'width:100%;max-height:250px;object-fit:cover;border-radius:8px;';
    imgEl.appendChild(img);
  }
  om('viewNewsM');
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      om('editNewsM');
    @elseif (old('form') === 'create')
      om('addNewsM');
    @endif
  });
@endif
</script>
@endsection