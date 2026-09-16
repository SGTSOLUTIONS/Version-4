<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Point Data - Ward {{ $ward_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2   { margin: 0 0 6px 0; }
        .meta { margin-bottom: 10px; color: #555; }
        .polygon-wrap {
            margin-bottom: 12px;
            padding: 6px;
            border: 1px solid #ccc;
            background: #fafafa;
        }
        .polygon-wrap img { max-width: 100%; max-height: 260px; display: block; }
        .polygon-path { font-size: 9px; color: #777; margin-top: 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td {
            border: 1px solid #999;
            padding: 3px 5px;
            word-wrap: break-word;
            vertical-align: top;
        }
        th { background: #eee; }
        tr:nth-child(even) td { background: #f9f9f9; }
    </style>
</head>
<body>

    <h2>Point Data — Ward #{{ $ward_id }}</h2>
    <div class="meta">Total rows: {{ count($rows) }}</div>

    {{-- Polygon asset image --}}
    @if ($polygonImageBase64)
        <div class="polygon-wrap">
            <img src="{{ $polygonImageBase64 }}" alt="Polygon Image">
            @if ($polygonImagePath)
                <div class="polygon-path">Source: {{ $polygonImagePath }}</div>
            @endif
        </div>
    @elseif ($polygonImagePath)
        <div class="polygon-wrap">
            <em>Asset image not found: {{ $polygonImagePath }}</em>
        </div>
    @endif

    {{-- Point data table --}}
    <table>
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($columns as $col)
                        <td>{{ $row[$col] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
