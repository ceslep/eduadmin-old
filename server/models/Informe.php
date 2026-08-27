<?php

require_once __DIR__ . '/Database.php';

class Informe extends Model
{
    protected static string $table = 'informes';

    public static function findByAnioEstudiante(string $anio, string $estudiante): ?array
    {
        $db = self::getLatin1Connection();
        $stmt = $db->prepare(
            "SELECT * FROM informes WHERE anio = :anio AND estudiante = :estudiante LIMIT 1"
        );
        $stmt->execute(['anio' => $anio, 'estudiante' => $estudiante]);
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        $result['informe'] = self::fixEncoding($result['informe']);
        $result['observaciones'] = self::fixEncoding($result['observaciones'] ?? '');
        $result['parsed_informe'] = self::parseInforme($result['informe']);
        return $result;
    }

    private static function getLatin1Connection(): PDO
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $db = getenv('DB_NAME') ?: 'eduadmin';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=latin1";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    private static function fixEncoding(string $text): string
    {
        $result = '';
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($text[$i]);
            if ($byte < 0x80) {
                $result .= $text[$i];
            } elseif (($byte & 0xE0) === 0xC0) {
                $b2 = ($i + 1 < $len) ? ord($text[$i + 1]) : 0;
                if (($b2 & 0xC0) === 0x80) {
                    $cp = (($byte & 0x1F) << 6) | ($b2 & 0x3F);
                    $result .= chr($cp);
                    $i++;
                } else {
                    $result .= $text[$i];
                }
            } elseif (($byte & 0xF0) === 0xE0) {
                $result .= $text[$i];
                if ($i + 1 < $len) $result .= $text[++$i];
                if ($i + 1 < $len) $result .= $text[++$i];
            } elseif (($byte & 0xF8) === 0xF0) {
                $result .= $text[$i];
                if ($i + 1 < $len) $result .= $text[++$i];
                if ($i + 1 < $len) $result .= $text[++$i];
                if ($i + 1 < $len) $result .= $text[++$i];
            } else {
                $result .= $text[$i];
            }
        }
        return $result;
    }

    private static function parseInforme(string $informe): array
    {
        $rows = explode('~', $informe);
        $parsed = [];

        foreach ($rows as $row) {
            $row = trim($row);
            if ($row === '' || $row === ';') continue;

            $cols = explode(';', $row);
            $cols = array_map(fn($c) => trim(str_replace("\r", ' ', $c)), $cols);

            if (count($cols) >= 4 && $cols[1] !== '' && $cols[1] !== 'AREA') {
                $parsed[] = [
                    'numero' => $cols[0],
                    'area' => $cols[1],
                    'descripcion' => $cols[2],
                    'valoracion' => $cols[3],
                ];
            }
        }

        return $parsed;
    }
}
