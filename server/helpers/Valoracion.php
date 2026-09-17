<?php

/**
 * Escala de valoración del Decreto 230 de 2002 y su equivalencia numérica.
 *
 * Aplica a los certificados de 2002 a 2009, que es el período que cubre el
 * archivo histórico de informes. Desde el Decreto 1290 rige otra escala
 * (Superior / Alto / Básico / Bajo), así que fuera de ese rango la equivalencia
 * queda vacía en vez de imprimir un valor que no corresponde.
 */
final class Valoracion
{
    /**
     * Equivalencia que se imprime en el certificado.
     *
     * El rango de cada escala es EXCELENTE 8.0–10.0, SOBRESALIENTE 7.0–8.9,
     * ACEPTABLE 6.0–6.9, INSUFICIENTE 3.0–5.9 y DEFICIENTE 1.0–2.9; el valor
     * que va en la tabla es el representativo de cada rango.
     */
    private const EQUIVALENCIAS = [
        'EXCELENTE'     => '9.5',
        'SOBRESALIENTE' => '8.5',
        'ACEPTABLE'     => '6.5',
        'INSUFICIENTE'  => '4.0',
        'DEFICIENTE'    => '2.0',
    ];

    /** Escala que quedó como sufijo en las filas desalineadas de la base. */
    private const MARCADORES = [
        'S' => 'SOBRESALIENTE',
        'E' => 'EXCELENTE',
        'A' => 'ACEPTABLE',
        'I' => 'INSUFICIENTE',
        'D' => 'DEFICIENTE',
    ];

    private const ANIO_DESDE = 2002;
    private const ANIO_HASTA = 2009;

    /**
     * Valoración en su forma canónica.
     *
     * En la base heredada unas pocas filas quedaron desalineadas y el campo de
     * valoración terminó conteniendo un fragmento de la descripción; en esos
     * casos la escala real viaja como sufijo «(S)», «(E)», «(A)», «(I)» o «(D)».
     */
    public static function normalizar(?string $valoracion): string
    {
        $texto = self::limpiar((string) $valoracion);

        if ($texto === '') {
            return '';
        }

        $mayusculas = self::aMayusculas($texto);

        if (isset(self::EQUIVALENCIAS[$mayusculas])) {
            return $mayusculas;
        }

        if (preg_match('/\(([SEAID])\)\s*\.?\s*$/', $mayusculas, $encontrado)) {
            return self::MARCADORES[$encontrado[1]] ?? '';
        }

        // No se reconoce la escala: se devuelve el texto tal como venía.
        return $texto;
    }

    /** Equivalencia numérica, o cadena vacía si la escala no aplica al año. */
    public static function equivalencia(?string $valoracion, ?string $anio): string
    {
        if (!self::aplicaAlAnio($anio)) {
            return '';
        }

        return self::EQUIVALENCIAS[self::normalizar($valoracion)] ?? '';
    }

    public static function aplicaAlAnio(?string $anio): bool
    {
        $anio = trim((string) $anio);

        if ($anio === '' || !ctype_digit($anio)) {
            return false;
        }

        $numero = (int) $anio;

        return $numero >= self::ANIO_DESDE && $numero <= self::ANIO_HASTA;
    }

    private static function limpiar(string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', $texto) ?? $texto);
    }

    /** El texto viene de una base latin1; se pasa a UTF-8 antes de comparar. */
    private static function aMayusculas(string $texto): string
    {
        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        }

        return mb_strtoupper($texto, 'UTF-8');
    }
}
