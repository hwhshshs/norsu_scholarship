<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disbursement Report - {{ $batch->batch ?? 'N/A' }}</title>
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
            font-weight: 600;
        }
        .report-info {
            display: flex;
            justify-content: flex-start; /* Move both columns to the left */
            gap: 60px; /* Small gap between the two columns */
            margin-bottom: 30px;
            font-size: 13px;
            width: 100%;
        }
        .report-info-col {
            width: auto; /* Columns will take only as much space as they need */
            min-width: 200px;
        }
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 3px;
        }
        .info-label {
            font-weight: 700;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
            width: 130px; /* Fixed width to keep label and value close but aligned */
            flex-shrink: 0;
        }
        .info-value {
            font-weight: 600;
            color: #002d54;
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        th {
            background-color: #f8f9fa;
            color: #002d54;
            font-weight: 700;
            text-transform: uppercase;
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin-top: 40px;
            text-align: center;
            padding-top: 5px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
            button { display: none; }
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
    <button class="btn-print no-print" onclick="window.print()">Print to PDF</button>

    <div class="norsu-letterhead">
        <img src="{{ asset('assets/img/norsu_logo.png') }}" class="norsu-logo" alt="NORSU Logo">
        <div class="norsu-details">
            <p style="text-transform: uppercase; font-size: 10px; color: #777; margin-bottom: 0;">Republic of the Philippines</p>
            <h2>NEGROS ORIENTAL STATE UNIVERSITY</h2>
            <p>Main Campus, Dumaguete City, Negros Oriental</p>
            <p>Scholarship and Financial Assistance Office</p>
        </div>
    </div>

    <div class="header" style="border-bottom: 2px solid #002d54;">
        <h1>Scholarship Disbursement Report</h1>
        <p>Scholarship Management System | Official Audit Document</p>
    </div>

    <div class="report-info">
        <div class="report-info-col">
            <div class="info-row">
                <span class="info-label">Program:</span>
                <span class="info-value">{{ $batch->program }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Academic Year:</span>
                <span class="info-value">{{ $batch->ay }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Semester:</span>
                <span class="info-value">{{ $batch->semester }}</span>
            </div>
        </div>
        <div class="report-info-col">
            <div class="info-row">
                <span class="info-label">ADA Number:</span>
                <span class="info-value">{{ $batch->ada_no ?: 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">ADA Date:</span>
                <span class="info-value">{{ $batch->ada_date ? \Illuminate\Support\Carbon::parse($batch->ada_date)->format('M d, Y') : 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Report Date:</span>
                <span class="info-value">{{ now()->format('M d, Y') }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Award No</th>
                <th>Degree Program</th>
                <th>Year</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $individualAmount = $batch->scholar_count > 0 ? $batch->amount / $batch->scholar_count : 0; @endphp
            @foreach($scholars as $scholar)
            <tr>
                <td>{{ $scholar->student_id_no }}</td>
                <td>{{ $scholar->last_name }}, {{ $scholar->given_name }} {{ $scholar->middle_initial ? $scholar->middle_initial . '.' : '' }}</td>
                <td>{{ $scholar->tdp_tes_award_no }}</td>
                <td>{{ $scholar->degree_program }}</td>
                <td>{{ $scholar->year_level }}</td>
                <td style="text-align: right;">₱{{ number_format($individualAmount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f8f9fa;">
                <td colspan="5" style="text-align: right;">TOTAL DISBURSED</td>
                <td style="text-align: right; color: #002d54;">₱{{ number_format($batch->amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div>
            <p>Prepared By:</p>
            <div class="signature-line">
                {{ $batch->creator_name ?: auth()->user()->name }}
                <br><span style="font-size: 10px; font-weight: normal; color: #666;">Scholarship Coordinator</span>
            </div>
        </div>
        <div>
            <p>Approved By:</p>
            <div class="signature-line">
                <br><span style="font-size: 10px; font-weight: normal; color: #666;">University Administrator</span>
            </div>
        </div>
    </div>

    <script>
        // Auto-open print dialog
        window.onload = function() {
            // window.print();
        }
    </script>
</body>
</html>
