<!DOCTYPE html>
<html>

<head>
    <title>Laporan Peserta</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .meta {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Laporan Rekapitulasi Peserta - RE:SKILL</h2>
    </div>

    <div class="meta">
        <p><strong>Departemen:</strong> {{ $filter }}</p>
        <p><strong>Tanggal Cetak:</strong> {{ $date }}</p>
        <p><strong>Total Peserta:</strong> {{ count($users) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Peserta</th>
                <th>Departemen</th>
                <th>Modul Selesai</th>
                <th>Rata-rata Nilai</th>
                <th>Total XP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $index => $user)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $user['name'] }}</td>
                <td>{{ $user['department'] }}</td>
                <td>{{ $user['completed_modules'] }}</td>
                <td>{{ $user['avg_score'] }}</td>
                <td>{{ $user['total_xp'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>