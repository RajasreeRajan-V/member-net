<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        $sheetTitle = substr(preg_replace('/[\\\\\/?*\[\]]/', '', $title ?? 'Members Directory'), 0, 31);
        echo "<!--[if gte mso 9]>";
        echo "<"."xml>";
        echo "<"."x:ExcelWorkbook>";
        echo "<"."x:ExcelWorksheets>";
        echo "<"."x:ExcelWorksheet>";
        echo "<"."x:Name>" . htmlspecialchars($sheetTitle) . "</"."x:Name>";
        echo "<"."x:WorksheetOptions>";
        echo "<"."x:DisplayGridlines/>";
        echo "<"."x:Print>";
        echo "<"."x:ValidPrinterInfo/>";
        echo "</"."x:Print>";
        echo "</"."x:WorksheetOptions>";
        echo "</"."x:ExcelWorksheet>";
        echo "</"."x:ExcelWorksheets>";
        echo "</"."x:ExcelWorkbook>";
        echo "</"."xml>";
        echo "<![endif]-->";
    @endphp
    <style>
        body, table {
            font-family: 'Segoe UI', Calibri, Arial, sans-serif;
            font-size: 10pt;
            color: #1E293B;
        }
        .report-title {
            background-color: #1E3A8A;
            color: #FFFFFF;
            font-size: 15pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 42px;
            border: 1pt solid #172554;
        }
        .report-subtitle {
            background-color: #F1F5F9;
            color: #475569;
            font-size: 9.5pt;
            text-align: center;
            vertical-align: middle;
            height: 26px;
            border-left: 0.5pt solid #CBD5E1;
            border-right: 0.5pt solid #CBD5E1;
            border-bottom: 0.5pt solid #CBD5E1;
        }
        .blank-row {
            height: 14px;
        }
        th.col-head {
            background-color: #0F172A;
            color: #FFFFFF;
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 34px;
            border: 0.5pt solid #334155;
            padding: 6px 10px;
            white-space: nowrap;
        }
        td.cell {
            vertical-align: middle;
            border: 0.5pt solid #CBD5E1;
            padding: 6px 10px;
            font-size: 9.5pt;
        }
        td.cell-center {
            text-align: center;
        }
        td.cell-left {
            text-align: left;
        }
        td.cell-right {
            text-align: right;
        }
        td.text-format {
            mso-number-format: "\@";
            text-align: center;
        }
        .row-even {
            background-color: #FFFFFF;
        }
        .row-odd {
            background-color: #F8FAFC;
        }
        .status-approved {
            background-color: #DCFCE7;
            color: #166534;
            font-weight: bold;
            text-align: center;
            border: 0.5pt solid #86EFAC;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
            font-weight: bold;
            text-align: center;
            border: 0.5pt solid #FDE68A;
        }
        .status-rejected {
            background-color: #FEE2E2;
            color: #991B1B;
            font-weight: bold;
            text-align: center;
            border: 0.5pt solid #FCA5A5;
        }
        .tag-yes {
            color: #059669;
            font-weight: 600;
        }
        .tag-no {
            color: #64748B;
        }
        .schemes-cell {
            font-size: 9pt;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <!-- Title Banner -->
        <tr>
            <td colspan="17" class="report-title">
                {{ strtoupper($title ?? 'Member Net - Members Directory') }}
            </td>
        </tr>
        <tr>
            <td colspan="17" class="report-subtitle">
                Generated On: {{ $generatedAt ?? now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; Total Records: {{ count($members) }} &nbsp;|&nbsp; Filter Scope: {{ $scopeDescription ?? 'All Members' }}
            </td>
        </tr>
        <tr class="blank-row">
            <td colspan="17"></td>
        </tr>

        <!-- Table Headers -->
        <thead>
            <tr>
                <th class="col-head" style="width: 50px;">S.No</th>
                <th class="col-head" style="width: 140px;">Membership ID</th>
                <th class="col-head" style="width: 200px;">Full Name</th>
                <th class="col-head" style="width: 120px;">Phone Number</th>
                <th class="col-head" style="width: 210px;">Email Address</th>
                <th class="col-head" style="width: 140px;">Unit</th>
                <th class="col-head" style="width: 100px;">State</th>
                <th class="col-head" style="width: 200px;">Shop Name / Org</th>
                <th class="col-head" style="width: 260px;">Address</th>
                <th class="col-head" style="width: 110px;">Date of Birth</th>
                <th class="col-head" style="width: 110px;">Member Net</th>
                <th class="col-head" style="width: 110px;">Status</th>
                <th class="col-head" style="width: 110px;">Joined Date</th>
                <th class="col-head" style="width: 110px;">Expiry Date</th>
                <th class="col-head" style="width: 110px;">Registered On</th>
                <th class="col-head" style="width: 100px;">Total Schemes</th>
                <th class="col-head" style="width: 320px;">Enrolled Schemes Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $index => $m)
                @php
                    $isOdd = ($index % 2 === 1);
                    $rowClass = $isOdd ? 'row-odd' : 'row-even';
                    $cleanAddress = preg_replace('/[\r\n]+/', ', ', trim($m->address ?? ''));
                    $cleanAddress = preg_replace('/\s+/', ' ', $cleanAddress);

                    $statusLower = strtolower($m->status ?? 'pending');
                    $statusClass = match($statusLower) {
                        'approved' => 'status-approved',
                        'rejected' => 'status-rejected',
                        default    => 'status-pending',
                    };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="cell cell-center" style="font-weight: 600;">{{ $index + 1 }}</td>
                    <td class="cell text-format" style="font-weight: 600; color: #1E40AF;">{{ $m->membership_id ?: '—' }}</td>
                    <td class="cell cell-left" style="font-weight: 600;">{{ $m->user->name ?? '—' }}</td>
                    <td class="cell text-format">{{ $m->user->phone ?? '—' }}</td>
                    <td class="cell cell-left">{{ $m->user->email ?? '—' }}</td>
                    <td class="cell cell-left">{{ $m->unit->name ?? '—' }}</td>
                    <td class="cell cell-center">{{ $m->state->name ?? 'Kerala' }}</td>
                    <td class="cell cell-left">{{ $m->organization ?: '—' }}</td>
                    <td class="cell cell-left">{{ $cleanAddress ?: '—' }}</td>
                    <td class="cell text-format">{{ $m->dob ? \Carbon\Carbon::parse($m->dob)->format('d-m-Y') : '—' }}</td>
                    <td class="cell cell-center">
                        @if($m->is_fumma_member)
                            <span class="tag-yes">Yes</span>
                        @else
                            <span class="tag-no">No</span>
                        @endif
                    </td>
                    <td class="cell {{ $statusClass }}">{{ ucfirst($m->status ?? 'Pending') }}</td>
                    <td class="cell text-format">{{ $m->joined_date ? \Carbon\Carbon::parse($m->joined_date)->format('d-m-Y') : '—' }}</td>
                    <td class="cell text-format">{{ $m->expiry_date ? \Carbon\Carbon::parse($m->expiry_date)->format('d-m-Y') : '—' }}</td>
                    <td class="cell text-format">{{ $m->created_at ? $m->created_at->format('d-m-Y') : '—' }}</td>
                    <td class="cell cell-center" style="font-weight: bold;">
                        {{ $m->schemes ? $m->schemes->count() : 0 }}
                    </td>
                    <td class="cell cell-left schemes-cell">
                        @if($m->schemes && $m->schemes->count() > 0)
                            @foreach($m->schemes as $sIdx => $sch)
                                <div>{{ $sIdx + 1 }}. {{ $sch->title }} (₹{{ number_format((float)$sch->amount, 0) }})</div>
                            @endforeach
                        @else
                            <span style="color: #94A3B8; font-style: italic;">No schemes enrolled</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="cell cell-center" style="padding: 24px; color: #64748B; font-style: italic;">
                        No member records found matching the criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
