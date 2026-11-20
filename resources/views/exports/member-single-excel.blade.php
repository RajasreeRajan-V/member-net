<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        $sheetTitle = substr(preg_replace('/[\\\\\/?*\[\]]/', '', $member->membership_id ?: 'Member Profile'), 0, 31);
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
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 40px;
            border: 1pt solid #172554;
        }
        .report-subtitle {
            background-color: #F1F5F9;
            color: #475569;
            font-size: 9.5pt;
            text-align: center;
            vertical-align: middle;
            height: 24px;
            border: 0.5pt solid #CBD5E1;
        }
        .section-header {
            background-color: #334155;
            color: #FFFFFF;
            font-size: 10.5pt;
            font-weight: bold;
            vertical-align: middle;
            height: 30px;
            padding-left: 10px;
            border: 0.5pt solid #1E293B;
        }
        .field-label {
            background-color: #F8FAFC;
            color: #475569;
            font-weight: bold;
            font-size: 9.5pt;
            vertical-align: middle;
            border: 0.5pt solid #CBD5E1;
            padding: 8px 12px;
            width: 160px;
        }
        .field-value {
            background-color: #FFFFFF;
            color: #0F172A;
            font-size: 10pt;
            vertical-align: middle;
            border: 0.5pt solid #CBD5E1;
            padding: 8px 12px;
            width: 260px;
        }
        .text-format {
            mso-number-format: "\@";
        }
        th.scheme-head {
            background-color: #1E293B;
            color: #FFFFFF;
            font-size: 9.5pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 30px;
            border: 0.5pt solid #0F172A;
            padding: 6px 10px;
        }
        td.scheme-cell {
            vertical-align: middle;
            border: 0.5pt solid #CBD5E1;
            padding: 6px 10px;
            font-size: 9.5pt;
        }
        .scheme-even {
            background-color: #FFFFFF;
        }
        .scheme-odd {
            background-color: #F8FAFC;
        }
        .status-badge {
            font-weight: bold;
            padding: 4px 8px;
            text-align: center;
        }
        .status-approved {
            background-color: #DCFCE7;
            color: #166534;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .status-rejected {
            background-color: #FEE2E2;
            color: #991B1B;
        }
        .blank-row {
            height: 14px;
        }
    </style>
</head>
<body>
    @php
        $cleanAddress = preg_replace('/[\r\n]+/', ', ', trim($member->address ?? ''));
        $cleanAddress = preg_replace('/\s+/', ' ', $cleanAddress);
        $statusLower = strtolower($member->status ?? 'pending');
        $statusClass = match($statusLower) {
            'approved' => 'status-approved',
            'rejected' => 'status-rejected',
            default    => 'status-pending',
        };
    @endphp

    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <!-- Header -->
        <tr>
            <td colspan="4" class="report-title">MEMBER PROFILE REPORT</td>
        </tr>
        <tr>
            <td colspan="4" class="report-subtitle">
                Generated On: {{ now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; Membership ID: {{ $member->membership_id ?: 'N/A' }}
            </td>
        </tr>
        <tr class="blank-row"><td colspan="4"></td></tr>

        <!-- Personal & Contact Info -->
        <tr>
            <td colspan="4" class="section-header">1. PERSONAL & CONTACT INFORMATION</td>
        </tr>
        <tr>
            <td class="field-label">Full Name</td>
            <td class="field-value" style="font-weight: bold;">{{ $member->user->name ?? '—' }}</td>
            <td class="field-label">Membership ID</td>
            <td class="field-value text-format" style="font-weight: bold; color: #1E40AF;">{{ $member->membership_id ?: '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">Phone Number</td>
            <td class="field-value text-format">{{ $member->user->phone ?? '—' }}</td>
            <td class="field-label">Email Address</td>
            <td class="field-value">{{ $member->user->email ?? '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">Date of Birth</td>
            <td class="field-value text-format">{{ $member->dob ? \Carbon\Carbon::parse($member->dob)->format('d-m-Y') : '—' }}</td>
            <td class="field-label">Member Net Member</td>
            <td class="field-value" style="font-weight: bold; color: {{ $member->is_fumma_member ? '#059669' : '#64748B' }};">
                {{ $member->is_fumma_member ? 'Yes' : 'No' }}
            </td>
        </tr>

        <tr class="blank-row"><td colspan="4"></td></tr>

        <!-- Membership & Unit Info -->
        <tr>
            <td colspan="4" class="section-header">2. MEMBERSHIP & AFFILIATION DETAILS</td>
        </tr>
        <tr>
            <td class="field-label">Unit</td>
            <td class="field-value">{{ $member->unit->name ?? '—' }}</td>
            <td class="field-label">State</td>
            <td class="field-value">{{ $member->state->name ?? 'Kerala' }}</td>
        </tr>
        <tr>
            <td class="field-label">Membership Status</td>
            <td class="field-value">
                <span class="status-badge {{ $statusClass }}">{{ ucfirst($member->status ?? 'Pending') }}</span>
            </td>
            <td class="field-label">Registration Date</td>
            <td class="field-value text-format">{{ $member->created_at ? $member->created_at->format('d-m-Y') : '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">Joined Date</td>
            <td class="field-value text-format">{{ $member->joined_date ? \Carbon\Carbon::parse($member->joined_date)->format('d-m-Y') : '—' }}</td>
            <td class="field-label">Expiry Date</td>
            <td class="field-value text-format">{{ $member->expiry_date ? \Carbon\Carbon::parse($member->expiry_date)->format('d-m-Y') : '—' }}</td>
        </tr>

        <tr class="blank-row"><td colspan="4"></td></tr>

        <!-- Business & Address Info -->
        <tr>
            <td colspan="4" class="section-header">3. BUSINESS & LOCATION INFORMATION</td>
        </tr>
        <tr>
            <td class="field-label">Shop / Organization</td>
            <td colspan="3" class="field-value">{{ $member->organization ?: '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">Registered Address</td>
            <td colspan="3" class="field-value">{{ $cleanAddress ?: '—' }}</td>
        </tr>

        <tr class="blank-row"><td colspan="4"></td></tr>

        <!-- Schemes Section -->
        <tr>
            <td colspan="4" class="section-header">
                4. ENROLLED SCHEMES (Total: {{ $member->schemes ? $member->schemes->count() : 0 }})
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-top: 6px;">
        <thead>
            <tr>
                <th class="scheme-head" style="width: 40px;">#</th>
                <th class="scheme-head" style="width: 280px; text-align: left;">Scheme Title</th>
                <th class="scheme-head" style="width: 120px; text-align: right;">Amount (₹)</th>
                <th class="scheme-head" style="width: 130px;">Duration (Months)</th>
                <th class="scheme-head" style="width: 110px;">Start Date</th>
                <th class="scheme-head" style="width: 110px;">End Date</th>
                <th class="scheme-head" style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $totalSchemeAmount = 0; @endphp
            @forelse($member->schemes as $sIndex => $scheme)
                @php
                    $isOdd = ($sIndex % 2 === 1);
                    $rowCls = $isOdd ? 'scheme-odd' : 'scheme-even';
                    $totalSchemeAmount += (float)$scheme->amount;
                @endphp
                <tr class="{{ $rowCls }}">
                    <td class="scheme-cell" style="text-align: center; font-weight: 600;">{{ $sIndex + 1 }}</td>
                    <td class="scheme-cell" style="font-weight: 600;">{{ $scheme->title }}</td>
                    <td class="scheme-cell" style="text-align: right; font-weight: 600;">₹{{ number_format((float)$scheme->amount, 2) }}</td>
                    <td class="scheme-cell" style="text-align: center;">{{ $scheme->duration_months ?? '—' }}</td>
                    <td class="scheme-cell text-format" style="text-align: center;">{{ $scheme->start_date ? $scheme->start_date->format('d-m-Y') : '—' }}</td>
                    <td class="scheme-cell text-format" style="text-align: center;">{{ $scheme->end_date ? $scheme->end_date->format('d-m-Y') : '—' }}</td>
                    <td class="scheme-cell" style="text-align: center; font-weight: 600; color: {{ $scheme->is_active ? '#166534' : '#64748B' }};">
                        {{ $scheme->is_active ? 'Active' : 'Inactive' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="scheme-cell" style="text-align: center; padding: 16px; color: #64748B; font-style: italic;">
                        No schemes currently enrolled.
                    </td>
                </tr>
            @endforelse

            @if($member->schemes && $member->schemes->count() > 0)
                <tr style="background-color: #F1F5F9; font-weight: bold;">
                    <td colspan="2" class="scheme-cell" style="text-align: right; padding: 8px 12px;">Total Scheme Contribution:</td>
                    <td class="scheme-cell" style="text-align: right; color: #1E3A8A; font-size: 10.5pt;">₹{{ number_format($totalSchemeAmount, 2) }}</td>
                    <td colspan="4" class="scheme-cell"></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
