<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../helpers/Env.php';
require_once __DIR__ . '/../helpers/AreaOrder.php';

class Informe extends Model
{
    protected static string $table = 'informes';

    /**
     * Años lectivos realmente disponibles en la tabla `informes`, con el
     * número de informes cargados. Se usa para poblar el selector de periodo
     * en lugar de una lista fija.
     *
     * @return list<array{anio: string, total: int}>
     */
    public static function availableYears(): array
    {
        $db = self::getLatin1Connection();

        $rows = $db->query(
            "SELECT anio, COUNT(*) AS total
               FROM informes
              WHERE anio IS NOT NULL AND anio <> ''
              GROUP BY anio
              ORDER BY anio DESC"
        )->fetchAll();

        return array_map(
            static fn(array $row): array => [
                'anio'  => (string) $row['anio'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }

    public static function findByAnioEstudiante(string $anio, string $estudiante): ?array
    {
        $db = self::getLatin1Connection();

        // `codigo` es el número de matrícula del estudiante y vive en otra
        // tabla, así que se trae en la misma consulta.
        $stmt = $db->prepare(
            "SELECT i.*, m.codigo AS matricula_codigo
               FROM informes i
               LEFT JOIN matricula m ON m.ind = i.estudiante
              WHERE i.anio = :anio AND i.estudiante = :estudiante
              LIMIT 1"
        );
        $stmt->execute(['anio' => $anio, 'estudiante' => $estudiante]);
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        $result['informe'] = self::fixEncoding($result['informe']);
        $result['observaciones'] = self::fixEncoding($result['observaciones'] ?? '');
        $result['aula'] = self::fixEncoding($result['aula'] ?? '');
        $result['p1'] = self::fixEncoding($result['p1'] ?? '');
        $result['p2'] = self::fixEncoding($result['p2'] ?? '');
        $result['p3'] = self::fixEncoding($result['p3'] ?? '');
        $result['director_de_grupo'] = self::fixEncoding($result['director_de_grupo'] ?? '');
        $result['parsed_informe'] = self::parseInforme($result['informe']);
        return $result;
    }

    /**
     * Los datos heredados están almacenados en latin1 dentro de una base
     * utf8mb4, así que se abre una conexión aparte para leerlos sin que MySQL
     * los reinterprete.
     */
    private static function getLatin1Connection(): PDO
    {
        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $db   = Env::get('DB_NAME', 'eduadmin');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=latin1";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
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

        // El certificado (y la vista web) muestran las áreas en el orden
        // institucional, no en el que quedaron guardadas en el informe.
        return AreaOrder::sort($parsed);
    }
}
