<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 10px;
        }
        body {
            font-family: sans-serif;
            font-size: 9px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #333;
            padding: 3px 4px;
            word-wrap: break-word;
            text-align: left;
        }
        th {
            background-color: #eee;
            font-weight: bold;
        }
        h3 {
            margin: 0 0 8px 0;
        }
    </style>
</head>
<body>

    <h3>Missing Buildings Report - Ward {{ $ward->id ?? '' }}</h3>

    <table>
        <thead>
            <tr>
                <th>Corp ID</th>
                <th>GIS ID</th>
                <th>Ward No</th>
                <th>Assessment</th>
                <th>Old Assessment</th>
                <th>Road Name</th>
                <th>Owner Name</th>
                <th>Old Door No</th>
                <th>New Door No</th>
                <th>Phone Number</th>
                <th>Plot Area</th>
                <th>Half Year Tax</th>
                <th>Balance</th>
                <th>Usage</th>
                <th>Type</th>
                <th>Zone</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($missingbill as $row)
                <tr>
                    <td>{{ $row->corporation_id ?? '' }}</td>
                    <td>{{ $row->gisid ?? '' }}</td>
                    <td>{{ $row->ward_no ?? '' }}</td>
                    <td>{{ $row->assessment ?? '' }}</td>
                    <td>{{ $row->old_assessment ?? '' }}</td>
                    <td>{{ $row->road_name ?? '' }}</td>
                    <td>{{ $row->owner_name ?? '' }}</td>
                    <td>{{ $row->old_door_no ?? '' }}</td>
                    <td>{{ $row->new_door_no ?? '' }}</td>
                    <td>{{ $row->phone_number ?? '' }}</td>
                    <td>{{ $row->plot_area ?? '' }}</td>
                    <td>{{ $row->half_year_tax ?? '' }}</td>
                    <td>{{ $row->balance ?? '' }}</td>
                    <td>{{ $row->usage ?? '' }}</td>
                    <td>{{ $row->type ?? '' }}</td>
                    <td>{{ $row->zone ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
