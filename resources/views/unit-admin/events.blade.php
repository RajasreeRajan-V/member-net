@extends('unit-admin.layout')
@section('title', 'Events')
@section('page_title', 'Events')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-calendar-alt"></i> Events
    </div>
    <button class="btn bp bsm" onclick="resetAddEventModal(); om('addEventM')">
      <i class="fas fa-plus"></i> Create Event
    </button>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Event</th>
          <th>Date & Time</th>
          <th>Location</th>
          <th>Seats</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($events as $event)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>

          {{-- IMAGE --}}
          <td>
            @if($event->image)
              <img src="{{ asset('storage/' . $event->image) }}"
                   alt="Event Image"
                   style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
            @else
              <span style="font-size:10px;color:var(--text-dim)">No Image</span>
            @endif
          </td>

          {{-- EVENT --}}
          <td>
            <div class="mn">{{ $event->title }}</div>
            <div class="me">📍 {{ $event->location ?? '—' }}</div>
          </td>

          {{-- DATE & TIME --}}
          <td style="color:var(--text-dim)">
            <div>{{ $event->event_date->format('d M Y') }}</div>
            <div style="font-size:10px;">{{ $event->event_time }}</div>
          </td>

          {{-- LOCATION --}}
          <td style="color:var(--text-dim)">{{ $event->location ?? '—' }}</td>

          {{-- SEATS --}}
          <td style="color:var(--text-dim)">
            <div>{{ $event->registrations_count }} registered</div>
            <div style="font-size:10px;">
              {{ max(0, $event->total_seats - $event->registrations_count) }} left
            </div>
            <span class="badge bap" style="margin-top:3px;">FREE</span>
          </td>

          {{-- STATUS --}}
          <td>
            <select onchange="updateStatus({{ $event->id }}, this.value)"
                    style="padding:3px 7px;font-size:10px;border-radius:20px;width:auto;
                           background:transparent;border:1px solid var(--border);
                           color:
                           @if($event->status == 'UPCOMING') var(--accent)
                           @elseif($event->status == 'ONGOING') var(--green)
                           @elseif($event->status == 'COMPLETED') var(--text-dim)
                           @else var(--red) @endif;
                           cursor:pointer;">
              <option value="UPCOMING"  {{ $event->status == 'UPCOMING'  ? 'selected' : '' }}>UPCOMING</option>
              <option value="ONGOING"   {{ $event->status == 'ONGOING'   ? 'selected' : '' }}>ONGOING</option>
              <option value="COMPLETED" {{ $event->status == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
              <option value="CANCELLED" {{ $event->status == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
            </select>
            <form id="statusForm{{ $event->id }}" method="POST"
                  action="{{ route('unit-admin.events.status') }}" style="display:none">
              @csrf
              <input type="hidden" name="id"     value="{{ $event->id }}">
              <input type="hidden" name="status" id="statusVal{{ $event->id }}">
            </form>
          </td>

          {{-- ACTIONS --}}
          <td>
            <div class="abr">
              <a href="{{ route('unit-admin.events.registrations', $event->id) }}"
                 class="ab vw" title="Registrations">
                <i class="fas fa-users"></i>
              </a>
              <div class="ab ed" onclick="editEvent({{ $event->id }})" title="Edit">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST" action="{{ route('unit-admin.events.delete') }}"
                    style="display:inline"
                    onsubmit="return confirm('Delete {{ addslashes($event->title) }}?')">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $event->id }}">
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
            <div style="font-size:32px;margin-bottom:10px;">📅</div>
            <div style="font-size:13px;font-weight:600;margin-bottom:6px;">No events yet</div>
            <div style="font-size:11px;color:var(--text-dim);margin-bottom:14px;">Create your first event</div>
            <button class="btn bp bsm" onclick="resetAddEventModal(); om('addEventM')">
              <i class="fas fa-plus"></i> Create Event
            </button>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ══ ADD EVENT MODAL ══ --}}
<div id="addEventM" class="mo">
  <div class="md" style="width:600px">
    <div class="mh">
      <span class="mt2">Create New Event</span>
      <div class="mc3" onclick="resetAddEventModal(); cm('addEventM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('unit-admin.events.store') }}" enctype="multipart/form-data" id="addEventForm">
      @csrf

      @if ($errors->any())
        <div id="addEventError" style="margin:14px 18px 0;padding:10px 12px;
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

      <div style="padding:14px 18px 0;font-size:10px;font-weight:700;
                  text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Event Info
      </div>
      <div class="fgrid">
        <div class="fg s2">
          <label>Event Title</label>
          <input type="text" name="title" maxlength="200" placeholder="Enter event title" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" placeholder="Describe the event..."
                    style="min-height:80px" required></textarea>
        </div>
        <div class="fg">
          <label>Event Date</label>
          <input type="date" name="event_date" id="addEventDate" min="{{ now()->format('Y-m-d') }}" required>
        </div>
        <div class="fg">
          <label>Event Time</label>
          <input type="time" name="event_time" required>
        </div>
        <div class="fg s2">
          <label>Image (optional)</label>
          <input type="file" name="image" accept="image/jpg,image/jpeg,image/png"
                 style="padding:6px 11px;">
        </div>
      </div>

      {{-- LOCATION — physical only, no online option --}}
      <div style="padding:0 18px;font-size:10px;font-weight:700;
                  text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Location
      </div>
      <div class="fgrid">
        <div class="fg s2">
          <label>Venue / Location</label>
          <input type="text" name="location" placeholder="Kozhikode Convention Centre">
        </div>
      </div>

      {{-- SEATS — no ticket type/price, always FREE --}}
      <div style="padding:0 18px;font-size:10px;font-weight:700;
                  text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Seats
      </div>
      <div class="fgrid">
        <div class="fg">
          <label>Total Seats</label>
          <input type="number" name="total_seats" placeholder="e.g. 100" min="1" max="1000" required>
        </div>
        <div class="fg">
          <label>Registration Deadline</label>
          <input type="date" name="registration_deadline" id="addEventDeadline">
        </div>
      </div>

      <div style="padding:8px 18px;">
        <span class="badge bap">All events are FREE & Physical</span>
      </div>

      <div class="ff">
        <button type="button" class="btn bo" onclick="resetAddEventModal(); cm('addEventM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-calendar-plus"></i> Create Event
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT EVENT MODAL ══ --}}
<div id="editEventM" class="mo">
  <div class="md" style="width:600px">
    <div class="mh">
      <span class="mt2">Edit Event</span>
      <div class="mc3" onclick="resetEditEventModal(); cm('editEventM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('unit-admin.events.update') }}" enctype="multipart/form-data" id="editEventForm">
      @csrf
      <input type="hidden" name="id" id="editEventId">

      <div style="padding:14px 18px 0;font-size:10px;font-weight:700;
                  text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Event Info
      </div>
      <div class="fgrid">
        <div class="fg s2">
          <label>Event Title</label>
          <input type="text" name="title" id="editEventTitle" maxlength="200" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" id="editEventDesc"
                    style="min-height:80px" required></textarea>
        </div>
        <div class="fg">
          <label>Event Date</label>
          <input type="date" name="event_date" id="editEventDate" required>
        </div>
        <div class="fg">
          <label>Event Time</label>
          <input type="time" name="event_time" id="editEventTime" required>
        </div>
        <div class="fg s2">
          <label>Venue / Location</label>
          <input type="text" name="location" id="editEventLocation"
                 placeholder="Kozhikode Convention Centre">
        </div>
        <div class="fg">
          <label>Total Seats</label>
          <input type="number" name="total_seats" id="editEventSeats" placeholder="e.g. 100" min="1" max="1000" required>
        </div>
        <div class="fg">
          <label>Registration Deadline</label>
          <input type="date" name="registration_deadline" id="editEventDeadline">
        </div>
        <div class="fg s2">
          <label>New Image (optional)</label>
          <input type="file" name="image" accept="image/jpg,image/jpeg,image/png"
                 style="padding:6px 11px;">
        </div>
      </div>

      <div class="ff">
        <button type="button" class="btn bo" onclick="resetEditEventModal(); cm('editEventM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i> Update Event
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    om('addEventM');
  });
@endif

// ── Reset "Create Event" modal: clears stale error banner + all inputs ──
function resetAddEventModal() {
  const errBox = document.getElementById('addEventError');
  if (errBox) errBox.remove();

  const form = document.getElementById('addEventForm');
  if (form) form.reset();

  // the deadline's max is set dynamically from the date field; clear it back
  document.getElementById('addEventDeadline').removeAttribute('max');
}

// ── Reset "Edit Event" modal: clears all inputs + hidden id + dynamic max ──
function resetEditEventModal() {
  const form = document.getElementById('editEventForm');
  if (form) form.reset();

  document.getElementById('editEventId').value = '';
  document.getElementById('editEventDeadline').removeAttribute('max');
}

document.querySelector('#addEventM [name="event_date"]')
    .addEventListener('change', function () {
        document.querySelector('#addEventM [name="registration_deadline"]').max = this.value;
    });

document.getElementById('editEventDate')
    .addEventListener('change', function () {
        document.getElementById('editEventDeadline').max = this.value;
    });

function editEvent(id) {
    resetEditEventModal(); // clear stale data before the fetch resolves
    fetch(`{{ url('/unit-admin/events') }}/${id}/detail`)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(e => {
            document.getElementById('editEventId').value       = id;
            document.getElementById('editEventTitle').value    = e.title;
            document.getElementById('editEventDesc').value     = e.description;
            document.getElementById('editEventDate').value     = e.event_date_raw;
            document.getElementById('editEventTime').value     = e.event_time;
            document.getElementById('editEventLocation').value = e.location ?? '';
            // old events saved with 0 seats: leave empty so the admin must enter a real number
            document.getElementById('editEventSeats').value    = e.total_seats > 0 ? e.total_seats : '';
            document.getElementById('editEventDeadline').value = e.deadline_raw ?? '';
            document.getElementById('editEventDeadline').max   = e.event_date_raw;
            om('editEventM');
        })
        .catch(err => alert('Failed to load event data! ' + err.message));
}

function updateStatus(id, status) {
    if (!confirm('Change status to ' + status + '?')) { location.reload(); return; }
    document.getElementById('statusVal' + id).value = status;
    document.getElementById('statusForm' + id).submit();
}
</script>
@endsection