@if ($paginator->hasPages())
<nav style="display:flex;align-items:center;gap:6px;">

  {{-- Previous --}}
  @if ($paginator->onFirstPage())
    <span style="display:flex;align-items:center;justify-content:center;
                 width:30px;height:30px;border-radius:6px;font-size:12px;
                 border:1px solid var(--border);color:var(--text-dim);
                 opacity:0.4;cursor:not-allowed;">
      <i class="fas fa-chevron-left"></i>
    </span>
  @else
    <a href="{{ $paginator->previousPageUrl() }}"
       style="display:flex;align-items:center;justify-content:center;
              width:30px;height:30px;border-radius:6px;font-size:12px;
              border:1px solid var(--border);color:var(--text-dim);
              text-decoration:none;transition:all 0.15s;"
       onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
       onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-dim)'">
      <i class="fas fa-chevron-left"></i>
    </a>
  @endif

  {{-- Page Numbers --}}
  @foreach ($elements as $element)
    @if (is_string($element))
      <span style="display:flex;align-items:center;justify-content:center;
                   width:30px;height:30px;font-size:12px;color:var(--text-dim);">
        ...
      </span>
    @endif

    @if (is_array($element))
      @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
          <span style="display:flex;align-items:center;justify-content:center;
                       width:30px;height:30px;border-radius:6px;font-size:12px;
                       font-weight:700;background:var(--accent);color:#fff;
                       border:1px solid var(--accent);">
            {{ $page }}
          </span>
        @else
          <a href="{{ $url }}"
             style="display:flex;align-items:center;justify-content:center;
                    width:30px;height:30px;border-radius:6px;font-size:12px;
                    border:1px solid var(--border);color:var(--text-dim);
                    text-decoration:none;transition:all 0.15s;"
             onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
             onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-dim)'">
            {{ $page }}
          </a>
        @endif
      @endforeach
    @endif
  @endforeach

  {{-- Next --}}
  @if ($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}"
       style="display:flex;align-items:center;justify-content:center;
              width:30px;height:30px;border-radius:6px;font-size:12px;
              border:1px solid var(--border);color:var(--text-dim);
              text-decoration:none;transition:all 0.15s;"
       onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
       onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-dim)'">
      <i class="fas fa-chevron-right"></i>
    </a>
  @else
    <span style="display:flex;align-items:center;justify-content:center;
                 width:30px;height:30px;border-radius:6px;font-size:12px;
                 border:1px solid var(--border);color:var(--text-dim);
                 opacity:0.4;cursor:not-allowed;">
      <i class="fas fa-chevron-right"></i>
    </span>
  @endif

</nav>
@endif