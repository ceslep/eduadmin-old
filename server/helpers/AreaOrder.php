<?php

/**
 * Orden institucional de las áreas en el certificado.
 *
 * El informe guarda el nombre largo del área («CIENCIAS NATURALES Y EDUCACION
 * AMBIENTAL»), mientras que el orden oficial está expresado sobre códigos
 * cortos («C. NATURA»). Aquí se relacionan ambos.
 *
 * Cuando varias reglas coinciden gana la del patrón más largo, es decir la más
 * específica: así «EDUCACION FISICA, RECREACION Y DEPORTES» cae en el orden 12
 * y no en el 2, que es donde apuntaría el patrón suelto «FISICA». Las áreas que
 * no coinciden con ninguna regla se conservan al final, en su orden original,
 * para no perder ninguna.
 *
 * Nota sobre «C. POLITICAS»: en la base heredada algunas filas guardaron
 * «CONSTITUCIÓN POLÍTICA» con la vocal acentuada reemplazada por un salto de
 * línea («CONSTITUCI\rN POL\rTICA»), así que se compara contra el prefijo
 * «CONSTITUCI», que sí sobrevive a esa corrupción.
 */
final class AreaOrder
{
    /**
     * `codigo` solo documenta la nomenclatura oficial; el emparejamiento se
     * hace con `patrones`, que se comparan contra el nombre normalizado.
     *
     * @var list<array{codigo: string, orden: int, patrones: list<string>}>
     */
    private const REGLAS = [
        ['codigo' => 'C. NATURA',    'orden' => 1,  'patrones' => ['CIENCIAS NATURALES']],
        ['codigo' => 'FISICA',       'orden' => 2,  'patrones' => ['FISICA']],
        ['codigo' => 'QUIMICA',      'orden' => 3,  'patrones' => ['QUIMICA']],
        ['codigo' => 'TALLER AMB.',  'orden' => 4,  'patrones' => ['TALLER AMB', 'TALLER AMBIENTAL']],
        ['codigo' => 'SOCIALES',     'orden' => 5,  'patrones' => ['CIENCIAS SOCIALES', 'SOCIALES']],
        ['codigo' => 'TALLER CIUD.', 'orden' => 6,  'patrones' => ['TALLER CIUD']],
        ['codigo' => 'EMPREND.',     'orden' => 7,  'patrones' => ['EMPREND']],
        ['codigo' => 'ED. ARTIST',   'orden' => 8,  'patrones' => ['EDUCACION ARTISTICA', 'ARTISTICA']],
        ['codigo' => 'DANZAS',       'orden' => 9,  'patrones' => ['DANZAS']],
        ['codigo' => 'TEATRO',       'orden' => 9,  'patrones' => ['TEATRO']],
        ['codigo' => 'ETICA-PV Y R', 'orden' => 10, 'patrones' => ['ETICA PV', 'PROYECTOS DE VIDA']],
        ['codigo' => 'ETICA',        'orden' => 11, 'patrones' => ['ETICA']],
        ['codigo' => 'ED.FISICA',    'orden' => 12, 'patrones' => ['EDUCACION FISICA', 'ED FISICA']],
        ['codigo' => 'TALLER DEPOR', 'orden' => 13, 'patrones' => ['TALLER DEPOR']],
        ['codigo' => 'RELIGION',     'orden' => 14, 'patrones' => ['EDUCACION RELIGIOSA', 'RELIGION']],
        ['codigo' => 'CASTELLAN',    'orden' => 15, 'patrones' => ['HUMANIDADES', 'CASTELLAN']],
        ['codigo' => 'LECTURA',      'orden' => 16, 'patrones' => ['LECTURA']],
        ['codigo' => 'H. INGLES',    'orden' => 17, 'patrones' => ['INGLES']],
        ['codigo' => 'TALLER LIT.',  'orden' => 18, 'patrones' => ['TALLER LIT']],
        ['codigo' => 'MATEMATI',     'orden' => 19, 'patrones' => ['MATEMATICAS', 'MATEMATI']],
        ['codigo' => 'TECNOLOG',     'orden' => 20, 'patrones' => ['TECNOLOGIA', 'TECNOLOG']],
        ['codigo' => 'C. POLITICAS', 'orden' => 21, 'patrones' => ['CONSTITUCI', 'CONSTITUCION', 'INSTRUCCION CIVICA', 'POLITIC']],
        ['codigo' => 'FILOSOFIA',    'orden' => 22, 'patrones' => ['FILOSOFIA']],
        ['codigo' => 'COMP.SOC',     'orden' => 23, 'patrones' => ['COMPORTAMIENTO SOCIAL']],
    ];

    /** Posición de un área, o `null` si ninguna regla la reconoce. */
    public static function ordenDe(?string $area): ?int
    {
        $normalizada = self::normalizar((string) $area);

        if ($normalizada === '') {
            return null;
        }

        $orden = null;
        $largoGanador = 0;

        foreach (self::REGLAS as $regla) {
            foreach ($regla['patrones'] as $patron) {
                $largo = strlen($patron);

                if ($largo > $largoGanador && str_contains($normalizada, $patron)) {
                    $orden = $regla['orden'];
                    $largoGanador = $largo;
                }
            }
        }

        return $orden;
    }

    /**
     * Reordena un informe. Las áreas no reconocidas quedan al final.
     *
     * @param  list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    public static function sort(array $items): array
    {
        $decorados = [];

        foreach ($items as $posicion => $item) {
            $decorados[] = [
                'orden' => self::ordenDe(isset($item['area']) ? (string) $item['area'] : null) ?? PHP_INT_MAX,
                'posicion' => $posicion,
                'item' => $item,
            ];
        }

        usort(
            $decorados,
            static fn(array $a, array $b): int =>
                $a['orden'] <=> $b['orden'] ?: $a['posicion'] <=> $b['posicion']
        );

        return array_map(static fn(array $d): array => $d['item'], $decorados);
    }

    /**
     * Deja el nombre comparable: sin tildes, sin signos y en mayúsculas.
     *
     * Los informes vienen de una base latin1, así que primero se pasa el texto
     * a UTF-8 para poder quitar las tildes con `strtr`.
     */
    private static function normalizar(string $texto): string
    {
        $texto = trim($texto);

        if ($texto === '') {
            return '';
        }

        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        }

        $texto = mb_strtoupper($texto, 'UTF-8');

        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Ñ' => 'N', 'Ü' => 'U',
        ]);

        return trim(preg_replace('/[^A-Z0-9]+/', ' ', $texto) ?? $texto);
    }
}
