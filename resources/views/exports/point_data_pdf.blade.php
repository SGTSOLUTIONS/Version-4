<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Point Data - Ward {{ $ward_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #999; padding: 3px; word-wrap: break-word; }
        th { background: #f0f0f0; }
        .img-wrap { text-align: center; margin-bottom: 10px; }
        .img-wrap img { max-width: 100%; max-height: 250px; }
    </style>
</head>
<body>
    <h3>Point Data — Ward {{ $ward_id }}</h3>

    @if(!empty($polygonImageBase64))
        <div class="img-wrap">
            <img src="{{ $polygonImageBase64 }}" alt="Polygon Image">
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($columns as $col)
                        <td>{{ \Illuminate\Support\Str::limit((string)($row[$col] ?? ''), 200) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
