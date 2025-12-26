<!DOCTYPE html>
<html>

<head>
    <title>Laporan Individu</title>
    <style>
        body {
            font-family: sans-serif;
        }

        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .profile {
            margin-bottom: 20px;
        }

        .stats-box {
            float: left;
            width: 30%;
            background: #f9f9f9;
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #eee;
        }

        .clear {
            clear: both;
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
        }

        th {
            background-color: #f4f4f4;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Hasil Belajar</h1>
        <p>RE:SKILL E-Learning Platform</p>
    </div>

    <div class="profile">
        <h3>{{ $user->name }}</h3>
        <p>ID: {{ $user->employee_id }} | Departemen: {{ $user->department->department_name }}</p>
    </div>

    <div>
        <div class="stats-box">
            <strong>Total XP</strong><br>
            <h2>{{ $user->total_points }}</h2>
        </div>
        <div class="stats-box">
            <strong>Rata-rata Nilai</strong><br>
            <h2>{{ round($user->quizAttempts->avg('final_score') ?? 0) }}</h2>
        </div>
        <div class="stats-box">
            <strong>Badges</strong><br>
            <h2>{{ $user->badges->count() }}</h2>
        </div>
        <div class="clear"></div>
    </div>

    <h3>Riwayat Kursus</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Kursus</th>
                <th>Modul Selesai</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($courseProgress as $course)
            <tr>
                <td>{{ $course['title'] }}</td>
                <td>{{ $course['completed'] }} / {{ $course['total'] }}</td>
                <td>
                    {{ $course['completed'] == $course['total'] ? 'Selesai' : 'Sedang Berjalan' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Pencapaian Badge</h3>
    <ul>
        @foreach($user->badges as $badge)
        <li><strong>{{ $badge->badge_name }}</strong> - {{ $badge->description }}</li>
        @endforeach
    </ul>

    <div style="margin-top: 50px; text-align: right;">
        <p>Dicetak pada: {{ $date }}</p>
    </div>
</body>

</html>