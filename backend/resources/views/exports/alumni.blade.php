@if(isset($is_pdf))
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Alumni</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #006756; color: white; font-weight: bold; }
        .title { font-size: 14px; font-weight: bold; text-align: center; color: #006756; }
        .subtitle { font-size: 10px; text-align: center; font-style: italic; color: #666; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="title">LAPORAN DATA ALUMNI FEB UNMUL</div>
    <div class="subtitle">Dicetak pada: {{ now()->format('d-m-Y H:i:s') }}</div>
@endif

<table>
    <thead>
        @if(!isset($is_pdf))
        <tr>
            <th colspan="11" style="font-size: 14px; font-weight: bold; text-align: center;">LAPORAN DATA ALUMNI FEB UNMUL</th>
        </tr>
        <tr>
            <th colspan="11" style="font-size: 10px; text-align: center; font-style: italic;">Dicetak pada: {{ now()->format('d-m-Y H:i:s') }}</th>
        </tr>
        <tr></tr>
        @endif
        <tr style="background-color: #006756; color: #ffffff; font-weight: bold;">
            <th>No</th>
            <th>Nama</th>
            <th>Email</th>
            <th>NIM</th>
            <th>Program Studi</th>
            <th>Tahun Masuk</th>
            <th>Tahun Lulus</th>
            <th>WhatsApp</th>
            <th>Domisili</th>
            <th>Status Verifikasi</th>
            <th>Tanggal Terdaftar</th>
        </tr>
    </thead>
    <tbody>
        @foreach($alumni as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->user->name ?? '-' }}</td>
                <td>{{ $item->user->email ?? '-' }}</td>
                <td>{{ $item->nim }}</td>
                <td>{{ $item->program_studi }}</td>
                <td>{{ $item->tahun_masuk }}</td>
                <td>{{ $item->tahun_lulus }}</td>
                <td>{{ $item->whatsapp }}</td>
                <td>{{ $item->domisili ?? '-' }}</td>
                <td>{{ ucfirst($item->status_verifikasi) }}</td>
                <td>{{ $item->created_at ? $item->created_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if(isset($is_pdf))
</body>
</html>
@endif
