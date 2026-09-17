<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Point Data - Ward {{ $ward_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h3 { margin: 0 0 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; vertical-align: top; }
        th { background: #f0f0f0; }
        .img-cell img { max-width: 240px; max-height: 180px; }
        .no-img { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <h3>Point Data — Ward {{ $ward_id }}</h3>

    @if(!empty($items) && count($items) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th style="width: 130px;">POINT GISID</th>
                    <th>Building Image</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item['gisid'] }}</td>
                        <td class="img-cell">
                            @if(!empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="Building">
                            @else
                                <span class="no-img">No image</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No point data available.</p>
    @endif
</body>
</html>
