<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Scholarship Summary</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            color: #333;
            margin: 0;
            padding: 40px;
            background: #fff;
        }
        .norsu-letterhead {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        .norsu-logo {
            width: 80px;
            height: 80px;
            margin-right: 20px;
        }
        .norsu-details {
            text-align: center;
        }
        .norsu-details h2 {
            margin: 0;
            font-size: 18px;
            color: #002d54;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .norsu-details p {
            margin: 2px 0;
            font-size: 12px;
            color: #555;
            font-weight: 600;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #002d54;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #002d54;
            text-transform: uppercase;
            font-size: 22px;
            letter-spacing: 2px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
        }
        th {
            background-color: #002d54;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            padding: 12px;
            border: 1px solid #002d54;
            text-align: left;
        }
        td {
            padding: 12px;
            border: 1px solid #dee2e6;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .stats-summary {
            display: flex;
            justify-content: flex-start;
            gap: 80px;
            margin-top: 30px;
            width: 100%;
        }
        .stats-summary-col {
            width: auto;
            min-width: 250px;
        }
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 4px;
            font-size: 13px;
        }
        .info-label {
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
            width: 160px;
            flex-shrink: 0;
        }
        .info-value {
            font-weight: 600;
            color: #002d54;
            text-align: left;
        }
        .stats-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #002d54;
            margin-top: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        .stats-box h3 {
            margin: 0;
            font-size: 18px;
            color: #002d54;
        }
        .stats-box p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #777;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #002d54;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">Print Report</button>

    <div class="norsu-letterhead">
        <img src="{{ asset('assets/img/norsu_logo.png') }}" class="norsu-logo" alt="NORSU Logo">
        <div class="norsu-details">
            <p style="text-transform: uppercase; font-size: 10px; color: #777; margin-bottom: 0;">Republic of the Philippines</p>
            <h2>NEGROS ORIENTAL STATE UNIVERSITY</h2>
            <p>Main Campus, Dumaguete City, Negros Oriental</p>
            <p>Scholarship and Financial Assistance Office</p>
        </div>
    </div>

    <div class="header">
        <h1>Master Scholarship Audit Summary</h1>
        <p>Comprehensive Fund Utilization Report | Generated: {{ now()->format('M d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Scholarship Program</th>
                <th style="text-align: center;">Total Batches</th>
                <th style="text-align: center;">Total Scholars</th>
                <th style="text-align: right;">Total Funds</th>
                <th style="text-align: center;">Disbursement %</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandTotalScholars = 0;
                $grandTotalAmount = 0;
            @endphp
            @foreach($summary as $row)
            @php 
                $grandTotalScholars += $row->total_scholars;
                $grandTotalAmount += $row->total_amount;
                $percent = $row->total_scholars > 0 ? ($row->total_disbursed / $row->total_scholars) * 100 : 0;
            @endphp
            <tr>
                <td style="font-weight: bold; color: #002d54;">{{ $row->program }}</td>
                <td style="text-align: center;">{{ $row->total_batches }}</td>
                <td style="text-align: center;">{{ number_format($row->total_scholars) }}</td>
                <td style="text-align: right;">₱{{ number_format($row->total_amount, 2) }}</td>
                <td style="text-align: center;">
                    <span style="font-weight: bold; color: {{ $percent == 100 ? '#28a745' : '#002d54' }}">
                        {{ round($percent) }}%
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #e9ecef; font-weight: bold;">
                <td>GRAND TOTAL</td>
                <td style="text-align: center;">{{ $summary->sum('total_batches') }}</td>
                <td style="text-align: center;">{{ number_format($grandTotalScholars) }}</td>
                <td style="text-align: right;">₱{{ number_format($grandTotalAmount, 2) }}</td>
                <td style="text-align: center;">-</td>
            </tr>
        </tfoot>
    </table>

    <div class="stats-summary">
        <div class="info-row">
            <span class="info-label">Report Type:</span>
            <span class="info-value">Master Scholarship Audit</span>
        </div>
        <div class="info-row">
            <span class="info-label">Report Date:</span>
            <span class="info-value">{{ now()->format('M d, Y') }}</span>
        </div>
        <div class="info-row" style="border-bottom: 2px solid #002d54;">
            <span class="info-label">Status:</span>
            <span class="info-value">Official Document</span>
        </div>

        <div class="stats-box">
            <div class="info-row" style="border-bottom: none;">
                <span class="info-label">Overall System Capacity:</span>
                <span class="info-value" style="font-size: 18px;">{{ number_format($grandTotalScholars) }} Total Scholars</span>
            </div>
            <div class="info-row" style="border-bottom: none; margin-top: 10px;">
                <span class="info-label">Total Budget Allocation:</span>
                <span class="info-value" style="font-size: 18px;">₱{{ number_format($grandTotalAmount, 2) }}</span>
            </div>
        </div>
    </div>

    <div style="margin-top: 60px; font-size: 11px; color: #999; text-align: center;">
        <p>This document is a system-generated summary for official audit purposes.</p>
        <p>&copy; {{ date('Y') }} Scholarship Management System</p>
    </div>
</body>
</html>
