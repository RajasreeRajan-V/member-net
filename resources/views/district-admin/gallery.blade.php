@extends('layouts.district-admin')
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
      <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;background:var(--card);">
        <div style="aspect-ratio:1/1;overflow:hidden;">
          <img src="{{ asset('storage/' . $image->image) }}" alt="Gallery image"
            style="width:100%;height:100%;object-fit:cover;">
        </div>
        <div style="padding:8px;display:flex;justify-content:space-between;align-items:center;">
          <span class="badge {{ $image->is_active ? 'bap' : 'brj' }}">
            {{ $image->is_active ? 'VISIBLE' : 'HIDDEN' }}
          </span>
          <div class="abr">
            <form action="{{ route('district-admin.gallery.toggle') }}" method="POST" style="display:inline;">
              @csrf
              @method('PUT')
              <input type="hidden" name="id" value="{{ $image->id }}">
              <button type="submit" class="ab" title="{{ $image->is_active ? 'Hide' : 'Show' }}">
                <i class="fas fa-{{ $image->is_active ? 'eye-slash' : 'eye' }}"></i>
              </button>
            </form>
            <form action="{{ route('district-admin.gallery.delete') }}" method="POST" style="display:inline;"
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
    <form action="{{ route('district-admin.gallery.store') }}" method="POST" enctype="multipart/form-data">
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