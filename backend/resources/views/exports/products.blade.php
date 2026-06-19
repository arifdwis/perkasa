@if(isset($is_pdf))
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Produk</title>
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
    <div class="title">LAPORAN DATA PRODUK ALUMNI FEB UNMUL</div>
    <div class="subtitle">Dicetak pada: {{ now()->format('d-m-Y H:i:s') }}</div>
@endif

<table>
    <thead>
        @if(!isset($is_pdf))
        <tr>
            <th colspan="10" style="font-size: 14px; font-weight: bold; text-align: center;">LAPORAN DATA PRODUK ALUMNI FEB UNMUL</th>
        </tr>
        <tr>
            <th colspan="10" style="font-size: 10px; text-align: center; font-style: italic;">Dicetak pada: {{ now()->format('d-m-Y H:i:s') }}</th>
        </tr>
        <tr></tr>
        @endif
        <tr style="background-color: #006756; color: #ffffff; font-weight: bold;">
            <th>No</th>
            <th>Nama Produk</th>
            <th>Toko</th>
            <th>Pemilik</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Status</th>
            <th>Unggulan</th>
            <th>Tanggal Dibuat</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->store->name ?? '-' }}</td>
                <td>{{ $item->store->alumniProfile->user->name ?? '-' }}</td>
                <td>{{ $item->category->name ?? '-' }}</td>
                <td>Rp{{ number_format($item->price, 0, ',', '.') }}</td>
                <td>{{ $item->stock }}</td>
                <td>{{ ucfirst($item->status) }}</td>
                <td>{{ $item->is_featured ? 'Ya' : 'Tidak' }}</td>
                <td>{{ $item->created_at ? $item->created_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if(isset($is_pdf))
</body>
</html>
@endif
