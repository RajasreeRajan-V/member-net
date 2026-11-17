@php
    $joined = \Carbon\Carbon::parse($profile->joined_date ?? now());

    // Signatories: change names/titles here if they have changed
    $sigs = [
        ['name' => 'Tony Pulickal', 'title' => 'State President',          'dist' => 'Dist.: President',         'img' => 'sign-president.png'],
        ['name' => 'Shaji Manhar',  'title' => 'State General Secretary',  'dist' => 'Dist.: General Secretary', 'img' => 'sign-secretary.png'],
        ['name' => 'Rafi Puthoor',  'title' => 'State Treasurer',          'dist' => 'Dist.: Treasurer',         'img' => 'sign-treasurer.png'],
    ];

    $img = function ($path) {
        $f = public_path($path);
        return file_exists($f) ? 'data:image/png;base64,' . base64_encode(file_get_contents($f)) : null;
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Serif', serif; color: #222; }

    /* A4 landscape = ~1122 x 793 px */
    .page { position: absolute; top: 0; left: 0; width: 1122px; height: 790px; background: #ffffff; overflow: hidden; }

    /* ── thin gold guide lines (white area) ── */
    .line-top { position: absolute; top: 30px; left: 250px; width: 622px; border-top: 2px solid #d4a62a; }
    .line-l   { position: absolute; top: 120px; left: 36px;  height: 380px; border-left: 2px solid #d4a62a; }
    .line-r   { position: absolute; top: 120px; right: 36px; height: 380px; border-right: 2px solid #d4a62a; }

    /* ── gold corner ornaments (CSS approximation) ── */
    .c  { position: absolute; width: 64px; height: 64px; border: 0 solid #d4a62a; }
    .ci { position: absolute; width: 30px; height: 30px; border: 0 solid #f0d36a; }
    .c-tl { top: 22px;  left: 22px;  border-top-width: 4px;    border-left-width: 4px; }
    .c-tr { top: 22px;  right: 22px; border-top-width: 4px;    border-right-width: 4px; }
    .c-bl { bottom: 22px; left: 22px;  border-bottom-width: 4px; border-left-width: 4px; }
    .c-br { bottom: 22px; right: 22px; border-bottom-width: 4px; border-right-width: 4px; }
    .c-tl .ci { top: 8px;    left: 8px;  border-top-width: 2px;    border-left-width: 2px; }
    .c-tr .ci { top: 8px;    right: 8px; border-top-width: 2px;    border-right-width: 2px; }
    .c-bl .ci { bottom: 8px; left: 8px;  border-bottom-width: 2px; border-left-width: 2px; }
    .c-br .ci { bottom: 8px; right: 8px; border-bottom-width: 2px; border-right-width: 2px; }

.memno { position: absolute; top: 100px; left: 84px; font-size: 13px; font-weight: bold; white-space: nowrap; }
.memno span { color: #c1121f; font-size: 15px; letter-spacing: 0.5px; }

/* logo */
.logo { position: absolute; top: 28px; left: 0; right: 0; text-align: center; }
.logo img { height: 80px; }

    /* ── navy ribbon ── */
    .tail   { position: absolute; top: 154px; width: 100px; height: 52px; background: #0b1428; }
    .tail-l { left: 221px; }
    .tail-r { left: 801px; }
    .notch-l { position: absolute; top: 154px; left: 221px; width: 0; height: 0;
               border-top: 26px solid #ffffff; border-bottom: 26px solid #ffffff; border-left: 18px solid #0b1428; }
    .notch-r { position: absolute; top: 154px; left: 883px; width: 0; height: 0;
               border-top: 26px solid #ffffff; border-bottom: 26px solid #ffffff; border-right: 18px solid #0b1428; }
    .ribbon { position: absolute; top: 140px; left: 281px; width: 560px; height: 48px; padding-top: 12px;
              background: #14213d; border-top: 2px solid #d4a62a; border-bottom: 2px solid #d4a62a;
              text-align: center; color: #ffffff; font-size: 30px; font-style: italic; font-weight: bold; letter-spacing: 1px; }

    /* body text: starts just under the ribbon and stays compact */
.body  { position: absolute; top: 208px; left: 150px; right: 150px; text-align: center; }
.lead  { font-size: 17px; font-style: italic; }
.name  { margin: 10px auto 0; width: 62%; border-bottom: 2px dotted #333; font-size: 26px;
         font-weight: bold; color: #14213d; padding-bottom: 4px; }
.grant { margin-top: 14px; font-size: 16px; font-style: italic; line-height: 1.8; }
.dot   { border-bottom: 2px dotted #333; padding: 0 10px; font-style: normal; font-weight: bold; }
.place { margin-top: 8px; font-size: 12px; color: #555; }

/* signatures: a little lower, clear of the text above */
.sigs { position: absolute; top: 450px; left: 120px; right: 120px; border-collapse: collapse; }
    .sigs td { width: 33%; text-align: center; vertical-align: bottom; font-size: 12px; padding: 0 12px; }
    .sig-img, .sig-img img { height: 48px; }
    .sig-line { border-top: 1px solid #333; margin: 2px 14px 5px; }
    .sig-name { font-weight: bold; font-size: 13px; }
    .sig-dist { margin-top: 10px; font-size: 11px; color: #444; }

    /* ── black bottom band with centre bulge ── */
    .band    { position: absolute; top: 610px; left: 0; width: 1122px; height: 184px; background: #120d05; border-top: 5px solid #d4a62a; }
    .bump-o  { position: absolute; top: 574px; left: 411px; width: 300px; height: 300px; border-radius: 150px; background: #d4a62a; }
    .bump-i  { position: absolute; top: 578px; left: 415px; width: 292px; height: 292px; border-radius: 146px; background: #120d05; }
    .cover   { position: absolute; top: 615px; left: 0; width: 1122px; height: 175px; background: #120d05; }

    .band-lines-l { position: absolute; top: 650px; left: 36px;  height: 90px; border-left: 2px solid #d4a62a; }
    .band-lines-r { position: absolute; top: 650px; right: 36px; height: 90px; border-right: 2px solid #d4a62a; }

    /* ── gold seal ── */
    .seal    { position: absolute; top: 596px; left: 491px; width: 140px; height: 140px; border-radius: 70px; background: #d4a62a; }
    .seal-in { position: absolute; top: 12px; left: 12px; width: 112px; height: 112px; border-radius: 56px;
               background: #120d05; border: 2px solid #f0d36a; text-align: center; }
    .seal-t1 { margin-top: 20px; color: #e8c547; font-size: 14px; font-weight: bold; letter-spacing: 2px; }
    .seal-yr { color: #f0d36a; font-size: 28px; font-weight: bold; margin-top: 2px; }
    .seal-t2 { color: #e8c547; font-size: 10px; letter-spacing: 3px; }
    .assoc   { position: absolute; top: 744px; left: 0; width: 1122px; text-align: center; color: #d4a62a;
               font-size: 10px; letter-spacing: 3px; line-height: 1.5; }
</style>
</head>
<body>
<div class="page">

    <div class="line-top"></div>
    <div class="line-l"></div>
    <div class="line-r"></div>

    <div class="c c-tl"><div class="ci"></div></div>
    <div class="c c-tr"><div class="ci"></div></div>

    <div class="memno">Membership No: <span>{{ $profile->membership_id }}</span></div>

    <div class="logo">
        @if($logo)
            <img src="{{ $logo }}">
        @else
            <div class="logo-text">FUMMA</div>
        @endif
    </div>

    <div class="tail tail-l"></div><div class="tail tail-r"></div>
    <div class="notch-l"></div><div class="notch-r"></div>
    <div class="ribbon">Certificate of Membership</div>

    <div class="body">
        <div class="lead">This is to certify that</div>
        <div class="name">{{ $profile->user->name }}</div>

        <div class="grant">
            Has been granted an honourable membership in this association<br>
            with effect from
            <span class="dot">{{ $joined->format('jS') }}</span> Day
            <span class="dot">{{ $joined->format('F') }}</span> Month
            <span class="dot">{{ $joined->format('Y') }}</span> Year
        </div>

        <div class="place">
            Unit: {{ $profile->unit->name ?? '' }} &nbsp;|&nbsp; District: {{ $profile->district->name ?? '' }}
        </div>
    </div>

    <table class="sigs">
        <tr>
            @foreach($sigs as $s)
                <td>
                    <div class="sig-img">
                        @if($i = $img($s['img']))
                            <img src="{{ $i }}">
                        @endif
                    </div>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $s['name'] }}</div>
                    <div>{{ $s['title'] }}</div>
                    <div class="sig-dist">{{ $s['dist'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- black band: order matters (band, bulge, cover, then seal) --}}
    <div class="band"></div>
    <div class="bump-o"></div>
    <div class="bump-i"></div>
    <div class="cover"></div>
    <div class="band-lines-l"></div>
    <div class="band-lines-r"></div>

    <div class="c c-bl"><div class="ci"></div></div>
    <div class="c c-br"><div class="ci"></div></div>

    <div class="seal">
        <div class="seal-in">
            <div class="seal-t1">FUMMA</div>
            <div class="seal-yr">{{ $joined->format('Y') }}</div>
            <div class="seal-t2">MEMBER</div>
        </div>
    </div>

    <div class="assoc">
        FURNITURE MANUFACTURERS &amp; MERCHANTS WELFARE ASSOCIATION
    </div>

</div>
</body>
</html>