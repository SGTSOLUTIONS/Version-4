<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Point Data - Ward {{ $ward_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h3 { margin: 0 0 8px 0; }
        .img-wrap { text-align: center; margin-bottom: 12px; }
        .img-wrap img { max-width: 100%; max-height: 300px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; }
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
                <th style="width: 60px;">#</th>
                <th>GISID</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gisids as $i => $gisid)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $gisid }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
