@extends('layouts.admin')
@section('title', 'Gallery')
@section('page_title', 'Gallery')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-images"></i> Gallery</div>
    <button class="btn bp bsm" onclick="om('uploadGalleryModal')">
      <i class="fas fa-upload"></i> Upload Images
    </button>
  </div>

  <div style="padding:18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;">
    @forelse ($images as $image)
      @php
        // ── Who uploaded this image? ──
        $creator = $image->createdBy;
        $role    = $creator->role ?? null;

        if ($role === 'unit_admin') {
            $label = 'Unit Admin';
            $color = 'var(--accent)';
            $scope = $unitAdmins[$image->created_by]->unit->name ?? null;
        } elseif ($role === 'district_admin') {
            $label = 'District Admin';
            $color = 'var(--green)';
            $scope = $districtAdmins[$image->created_by]->district->name ?? null;
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

      <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;background:var(--card);">
        <div style="aspect-ratio:1/1;overflow:hidden;">
          <img src="{{ asset('storage/' . $image->image) }}" alt="Gallery image"
            style="width:100%;height:100%;object-fit:cover;">
        </div>

        {{-- ══ UPLOADED BY ══ --}}
        <div style="padding:8px 8px 0;">
          @if($creator)
            <div style="font-size:11px;font-weight:600;white-space:nowrap;
                        overflow:hidden;text-overflow:ellipsis;"
                 title="{{ $creator->name }}">
              {{ $creator->name }}
            </div>
          @endif
          <span style="font-size:9px;padding:1px 7px;border-radius:20px;
                       border:1px solid var(--border);color:{{ $color }};">
            {{ $label }}
          </span>
          @if($scope)
            <div style="font-size:10px;color:var(--text-dim);margin-top:2px;
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                 title="{{ $scope }}">
              {{ $scope }}
            </div>
          @endif
        </div>

        <div style="padding:8px;display:flex;justify-content:space-between;align-items:center;">
          <span class="badge {{ $image->is_active ? 'bap' : 'brj' }}">
            {{ $image->is_active ? 'VISIBLE' : 'HIDDEN' }}
          </span>
          <div class="abr">
            <form action="{{ route('admin.gallery.toggle') }}" method="POST" style="display:inline;">
              @csrf
              @method('PUT')
              <input type="hidden" name="id" value="{{ $image->id }}">
              <button type="submit" class="ab" title="{{ $image->is_active ? 'Hide' : 'Show' }}">
                <i class="fas fa-{{ $image->is_active ? 'eye-slash' : 'eye' }}"></i>
              </button>
            </form>
            <form action="{{ route('admin.gallery.delete') }}" method="POST" style="display:inline;"
              onsubmit="return confirm('Delete this image?')">
              @csrf
              @method('DELETE')
              <input type="hidden" name="id" value="{{ $image->id }}">
              <button type="submit" class="ab rej" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div style="grid-column:1/-1;text-align:center;color:var(--text-dim);padding:30px 0;">
        No images uploaded yet.
      </div>
    @endforelse
  </div>

  <div style="padding:0 18px 18px;">
    {{ $images->links() }}
  </div>
</div>

{{-- ══ UPLOAD MODAL ══ --}}
<div class="mo" id="uploadGalleryModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">Upload Images</div>
      <div class="mc3" onclick="cm('uploadGalleryModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('admin.gallery.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="fgrid c1">
        <div class="fg">
          <label>Select Images (multiple allowed)</label>
          <input type="file" name="images[]" accept="image/*" multiple required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('uploadGalleryModal')">Cancel</button>
        <button type="submit" class="btn bp">Upload</button>
      </div>
    </form>
  </div>
</div>

@endsection