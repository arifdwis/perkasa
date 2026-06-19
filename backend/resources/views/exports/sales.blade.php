@if(isset($is_pdf))
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
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
    <div class="title">LAPORAN REKAP PENJUALAN TOKO</div>
    <div class="subtitle">Dicetak pada: {{ now()->format('d-m-Y H:i:s') }}</div>
@endif

<table>
    <thead>
        @if(!isset($is_pdf))
        <tr>
            <th colspan="9" style="font-size: 14px; font-weight: bold; text-align: center;">LAPORAN REKAP PENJUALAN TOKO</th>
        </tr>
        <tr>
            <th colspan="9" style="font-size: 10px; text-align: center; font-style: italic;">Dicetak pada: {{ now()->format('d-m-Y H:i:s') }}</th>
        </tr>
        <tr></tr>
        @endif
        <tr style="background-color: #006756; color: #ffffff; font-weight: bold;">
            <th>No</th>
            <th>Nomor Order</th>
            <th>Pembeli</th>
            <th>Toko</th>
            <th>Subtotal</th>
            <th>Biaya Antar</th>
            <th>Total</th>
            <th>Status</th>
            <th>Tanggal Transaksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->order_number }}</td>
                <td>{{ $item->user->name ?? '-' }}</td>
                <td>{{ $item->store->name ?? '-' }}</td>
                <td>Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                <td>Rp{{ number_format($item->biaya_antar, 0, ',', '.') }}</td>
                <td>Rp{{ number_format($item->total, 0, ',', '.') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                <td>{{ $item->created_at ? $item->created_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if(isset($is_pdf))
</body>
</html>
@endif
