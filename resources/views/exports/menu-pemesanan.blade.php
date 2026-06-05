<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table style="width: 100%;">
        <!-- BARIS 1: Logo Space (Kiri) dan Teks MEJA (Kanan) -->
        <tr>
            <td colspan="4" rowspan="2"></td> <!-- A1-D2 Kosong untuk Logo dari WithDrawings -->
            <td></td> <!-- E1 Spacer -->
            <td colspan="3"></td> <!-- F1-H1 -->
            <td style="font-weight: bold; font-size: 11px; text-align: left; font-family: Arial; vertical-align: middle;">MEJA :</td> <!-- I1 -->
        </tr>
        <!-- BARIS 2: Kotak MEJA -->
        <tr>
            <td></td> <!-- E2 Spacer -->
            <td colspan="3"></td> <!-- F2-H2 -->
            <!-- I2 Kotak Kosong di bawah teks MEJA -->
            <td style="border: 1px solid #000000; height: 35px; width: 40px; background-color: #ffffff;"></td> 
        </tr>

        <!-- BARIS 3: Spacer Kosong -->
        <tr style="height: 15px;">
            <td colspan="9"></td>
        </tr>

        <!-- BARIS 4 & 5: Judul Kiri Bawah Logo -->
        <tr>
            <td colspan="4" style="font-weight: bold; font-size: 14px; font-family: Arial; text-align: left;">DAFTAR HARGA</td>
            <td colspan="5"></td>
        </tr>
        <tr>
            <td colspan="4" style="font-weight: bold; font-size: 14px; font-family: Arial; text-align: left;">MAKANAN DAN MINUMAN</td>
            <td colspan="5"></td>
        </tr>

        <!-- BARIS 6: Spacer Kosong -->
        <tr style="height: 20px;">
            <td colspan="9"></td>
        </tr>

        <!-- Dynamic Menu Grid -->
        @foreach ($rows as $row)
            <tr>
                <!-- Left Column (A-D) -->
                @if ($row['left']['type'] === 'header')
                    <td colspan="4" style="font-weight: bold; font-size: 11px; text-align: left; font-family: Arial; vertical-align: bottom;">{{ $row['left']['name'] }}</td>
                @elseif ($row['left']['type'] === 'product')
                    <td style="text-align: left; font-size: 9px; font-family: Arial;">{{ $row['left']['number'] }}</td>
                    <td style="text-align: left; font-size: 9px; font-family: Arial;">{{ $row['left']['name'] }}</td>
                    <td style="text-align: left; font-size: 9px; font-family: Arial;">{{ $row['left']['price'] }}</td>
                    <td style="border: 1px solid #000000; text-align: center; background-color: #ffffff;"></td>
                @else
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                @endif

                <!-- Spacer Column (E) -->
                <td></td>

                <!-- Right Column (F-I) -->
                @if ($row['right']['type'] === 'header')
                    <td colspan="4" style="font-weight: bold; font-size: 11px; text-align: left; font-family: Arial; vertical-align: bottom;">{{ $row['right']['name'] }}</td>
                @elseif ($row['right']['type'] === 'product')
                    <td style="text-align: left; font-size: 9px; font-family: Arial;">{{ $row['right']['number'] }}</td>
                    <td style="text-align: left; font-size: 9px; font-family: Arial;">{{ $row['right']['name'] }}</td>
                    <td style="text-align: left; font-size: 9px; font-family: Arial;">{{ $row['right']['price'] }}</td>
                    <td style="border: 1px solid #000000; text-align: center; background-color: #ffffff;"></td>
                @else
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
</body>
</html>
