<?php

require_once __DIR__ . '/../models/Informe.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Valoracion.php';
require_once __DIR__ . '/../middleware/Auth.php';

class InformeController
{
    /**
     * GET /years — años lectivos disponibles con su cantidad de informes.
     */
    public static function years(): void
    {
        Auth::authenticate();

        Response::success(Informe::availableYears());
    }

    public static function show(): void
    {
        Auth::authenticate();

        $matches = $_SERVER['REQUEST_URI_INFORMES'] ?? [];
        if (count($matches) < 3) {
            Response::error('Ruta inválida');
        }

        $estudiante = $matches[1];
        $anio = $matches[2];

        $informe = Informe::findByAnioEstudiante($anio, $estudiante);

        if ($informe === null) {
            Response::notFound('Informe no encontrado');
        }

        Response::success($informe);
    }

    public static function download(): void
    {
        Auth::authenticate();

        $matches = $_SERVER['REQUEST_URI_INFORMES'] ?? [];
        if (count($matches) < 3) {
            Response::error('Ruta inválida');
        }

        $estudiante = $matches[1];
        $anio = $matches[2];

        $input = json_decode(file_get_contents('php://input'), true);
        $documento = trim($input['documento'] ?? '');
        $nombreEstudiante = trim($input['nombre'] ?? '');

        if ($documento === '') {
            Response::error('El campo documento es requerido');
        }

        $informe = Informe::findByAnioEstudiante($anio, $estudiante);
        if ($informe === null) {
            Response::notFound('Informe no encontrado');
        }

        if ($nombreEstudiante === '') {
            $nombreEstudiante = $informe['estudiante'] ?? '';
        }

        $templatePath = __DIR__ . '/../../public/formato.docx';
        if (!file_exists($templatePath)) {
            Response::error('Template no encontrado', 500);
        }

        $tmpBase = tempnam(sys_get_temp_dir(), 'informe_');
        if ($tmpBase === false) {
            Response::error('No se pudo crear el archivo temporal', 500);
        }

        // `tempnam()` deja un archivo vacío con otro nombre; aquí se trabaja
        // sobre una copia con extensión .docx.
        $tmpFile = $tmpBase . '.docx';
        unlink($tmpBase);

        if (!copy($templatePath, $tmpFile)) {
            Response::error('No se pudo preparar el template', 500);
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            Response::error('No se pudo abrir el template', 500);
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        if ($xmlContent === false) {
            $zip->close();
            unlink($tmpFile);
            Response::error('Template corrupto', 500);
        }

        $xmlContent = self::mergePlaceholderRuns($xmlContent);

        $replacements = [
            '%1' => self::xmlEscape($nombreEstudiante),
            '%2' => self::xmlEscape($documento),
            '%3' => self::xmlEscape(self::gradeLabel($informe)),
            '%4' => self::xmlEscape($informe['anio'] ?? $anio),
            '%5' => self::xmlEscape(self::matriculaNumber($informe)),
        ];
        $xmlContent = str_replace(array_keys($replacements), array_values($replacements), $xmlContent);

        $xmlContent = self::replaceAcademicTable(
            $xmlContent,
            $informe['parsed_informe'] ?? [],
            (string) ($informe['anio'] ?? $anio)
        );

        $xmlContent = self::removeElaboroParagraph($xmlContent);

        $xmlContent = self::replaceIssueDate($xmlContent);

        $zip->addFromString('word/document.xml', $xmlContent);
        $zip->close();

        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $informe['estudiante'] ?? 'informe');
        $fileName = "Certificado_{$safeName}_{$anio}.docx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: no-cache, must-revalidate');

        // Sin limpiar la caché de stat, `filesize()` devuelve el tamaño anterior
        // a la escritura del zip y la descarga se corta.
        clearstatcache(true, $tmpFile);
        header('Content-Length: ' . filesize($tmpFile));

        readfile($tmpFile);

        unlink($tmpFile);
        exit;
    }

    /**
     * Une los marcadores que Word partió en dos runs (`%` y `3`).
     *
     * La versión anterior exigía que el texto del primer run fuese exactamente
     * `%`, pero `%3` viene como `" %"` (con espacio por delante), así que nunca
     * se unía y el certificado salía con un "%3" literal impreso.
     */
    private static function mergePlaceholderRuns(string $xml): string
    {
        $merged = preg_replace(
            '/(<w:r\b[^>]*>(?:(?!<\/w:r>)[\s\S])*?<w:t\b[^>]*>)([^<]*)%(<\/w:t><\/w:r>)\s*'
            . '<w:r\b[^>]*>(?:(?!<\/w:r>)[\s\S])*?<w:t\b[^>]*>(\d)<\/w:t><\/w:r>/',
            '$1$2%$4$3',
            $xml
        );

        return $merged ?? $xml;
    }

    /**
     * Sustituye la cabecera de áreas y sus filas por una tabla real.
     *
     * El regex anterior localizaba el pie con `/<w:p[^>]*>.*?Dado en Anserma/s`.
     * Ese `.*?` puede cruzar párrafos, así que el match arrancaba en el PRIMER
     * `<w:p>` del documento —el título «C E R T I F I C A»— y el tramo final
     * reinsertaba el documento completo: el certificado salía duplicado.
     *
     * Aquí las fronteras se buscan párrafo a párrafo, que es la unidad real, y
     * el reemplazo abarca también el párrafo de cabecera para que los rótulos
     * los ponga la propia tabla.
     */
    private static function replaceAcademicTable(string $xml, array $parsedInforme, string $anio): string
    {
        if (empty($parsedInforme)) {
            return $xml;
        }

        // `(?![^>]*\/>)` descarta los párrafos vacíos autocerrados (`<w:p … />`),
        // que si no se tragarían el párrafo siguiente. Y `<w:p\b` no matchea
        // `<w:pPr>` porque no hay frontera de palabra entre `p` y `P`.
        preg_match_all(
            '/<w:p\b(?![^>]*\/>)[^>]*>(?:(?!<\/w:p>)[\s\S])*?<\/w:p>/',
            $xml,
            $paragraphs,
            PREG_OFFSET_CAPTURE
        );

        if (empty($paragraphs[0])) {
            return $xml;
        }

        $tableStart = null;
        $tableEnd = null;

        foreach ($paragraphs[0] as [$paragraph, $offset]) {
            $text = self::paragraphText($paragraph);

            // La cabecera de la tabla es el párrafo que rotula AREAS … IHS.
            if ($tableStart === null) {
                if (str_contains($text, 'AREAS') && str_contains($text, 'IHS')) {
                    $tableStart = $offset;
                }
                continue;
            }

            // Y el corte, el párrafo de la fecha de expedición.
            if (str_contains($text, 'Dado en Anserma')) {
                $tableEnd = $offset;
                break;
            }
        }

        if ($tableStart === null || $tableEnd === null || $tableEnd < $tableStart) {
            return $xml;
        }

        // El párrafo vacío final separa la tabla del texto que sigue.
        $replacement = self::buildAreaTable($parsedInforme, $anio) . '<w:p/>';

        return substr($xml, 0, $tableStart) . $replacement . substr($xml, $tableEnd);
    }

    /** Texto plano de un párrafo, concatenando el contenido de sus runs. */
    private static function paragraphText(string $paragraph): string
    {
        preg_match_all('/<w:t\b[^>]*>([^<]*)<\/w:t>/', $paragraph, $matches);

        return implode('', $matches[1]);
    }

    /**
     * Pone en el certificado la fecha en que se genera.
     *
     * La plantilla traía una fecha fija («a los 02 días del mes de febrero de
     * 2026»); se sustituye por el día de hoy conservando el mismo formato,
     * incluido el día con dos dígitos y el mes en minúscula.
     */
    private static function replaceIssueDate(string $xml): string
    {
        $hoy = new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE));

        $meses = [
            1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
        ];

        $texto = sprintf(
            'Dado en Anserma Caldas, a los %s días del mes de %s de %s',
            $hoy->format('d'),
            $meses[(int) $hoy->format('n')],
            $hoy->format('Y')
        );

        // Con `preg_replace_callback` el texto de reemplazo no se interpreta
        // como retroreferencia.
        $reemplazado = preg_replace_callback(
            '/Dado en Anserma[^<]*/',
            static fn(): string => $texto,
            $xml,
            1
        );

        return $reemplazado ?? $xml;
    }

    /** Zona horaria de la institución, para que la fecha no se corra a la de UTC. */
    private const TIMEZONE = 'America/Bogota';

    /**
     * Elimina el párrafo «Elaboró: …».
     *
     * Venía en la plantilla original, pero el certificado que emite el sistema
     * no debe llevarlo. Se compara por «Elabor» sin la tilde para no depender
     * de la codificación del texto dentro del XML.
     */
    private static function removeElaboroParagraph(string $xml): string
    {
        $removed = preg_replace_callback(
            '/<w:p\b(?![^>]*\/>)[^>]*>(?:(?!<\/w:p>)[\s\S])*?<\/w:p>/',
            static function (array $matches): string {
                return str_contains(self::paragraphText($matches[0]), 'Elabor') ? '' : $matches[0];
            },
            $xml
        );

        return $removed ?? $xml;
    }

    /** Ancho útil de la página en twips: 12240 menos los márgenes laterales. */
    private const CONTENT_WIDTH = 10205;

    /** Ancho de cada columna; la suma debe dar CONTENT_WIDTH. */
    private const AREA_COLUMNS = [
        'area' => 4405,
        'escala' => 2400,
        'equivalencia' => 1900,
        'ihs' => 1500,
    ];

    /**
     * Tabla de áreas del certificado.
     *
     * Antes las columnas se simulaban con tabulaciones. En Word una tabulación
     * avanza hasta la siguiente parada según el texto ya escrito, así que
     * encabezado y filas nunca quedaban alineados. Una tabla real lo garantiza.
     */
    private static function buildAreaTable(array $parsedInforme, string $anio): string
    {
        $widths = array_values(self::AREA_COLUMNS);

        $borders = '';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $side) {
            $borders .= '<w:' . $side . ' w:val="single" w:sz="4" w:space="0" w:color="BFBFBF"/>';
        }

        $table = '<w:tbl><w:tblPr>'
            . '<w:tblW w:w="' . self::CONTENT_WIDTH . '" w:type="dxa"/>'
            . '<w:tblLayout w:type="fixed"/>'
            . '<w:tblBorders>' . $borders . '</w:tblBorders>'
            . '<w:tblCellMar>'
            . '<w:top w:w="40" w:type="dxa"/><w:left w:w="80" w:type="dxa"/>'
            . '<w:bottom w:w="40" w:type="dxa"/><w:right w:w="80" w:type="dxa"/>'
            . '</w:tblCellMar>'
            . '</w:tblPr><w:tblGrid>';

        foreach ($widths as $width) {
            $table .= '<w:gridCol w:w="' . $width . '"/>';
        }

        $table .= '</w:tblGrid>';

        // Cabecera: `<w:tblHeader/>` la repite si la tabla cruza de página.
        $headers = ['AREAS', 'ESCALA DE VAL. NAL.', 'EQUIVALENCIA', 'IHS'];
        $alignments = ['left', 'center', 'center', 'center'];

        $table .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
        foreach ($headers as $index => $label) {
            $table .= self::tableCell($label, $widths[$index], $alignments[$index], true, 'EDEDED');
        }
        $table .= '</w:tr>';

        foreach ($parsedInforme as $item) {
            $valoracion = $item['valoracion'] ?? '';

            $table .= '<w:tr>'
                . self::tableCell(self::xmlEscape($item['area'] ?? ''), $widths[0], 'left')
                . self::tableCell(self::xmlEscape(Valoracion::normalizar($valoracion)), $widths[1], 'center')
                . self::tableCell(
                    self::xmlEscape(Valoracion::equivalencia($valoracion, $anio)),
                    $widths[2],
                    'center'
                )
                // IHS sigue sin origen de datos fiable; se deja vacía antes que
                // imprimir un valor inventado.
                . self::tableCell('', $widths[3], 'center')
                . '</w:tr>';
        }

        return $table . '</w:tbl>';
    }

    /** Celda de la tabla de áreas. */
    private static function tableCell(
        string $text,
        int $width,
        string $align,
        bool $bold = false,
        string $shading = ''
    ): string {
        $runProperties = '<w:rPr>' . ($bold ? '<w:b/>' : '') . '<w:sz w:val="18"/></w:rPr>';

        $cellProperties = '<w:tcPr><w:tcW w:w="' . $width . '" w:type="dxa"/>';
        if ($shading !== '') {
            $cellProperties .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $shading . '"/>';
        }
        $cellProperties .= '<w:vAlign w:val="center"/></w:tcPr>';

        $paragraph = '<w:p><w:pPr>'
            . '<w:spacing w:before="20" w:after="20" w:line="240" w:lineRule="auto"/>'
            . '<w:jc w:val="' . $align . '"/>'
            . $runProperties
            . '</w:pPr>';

        if ($text !== '') {
            $paragraph .= '<w:r>' . $runProperties
                . '<w:t xml:space="preserve">' . $text . '</w:t></w:r>';
        }

        return '<w:tc>' . $cellProperties . $paragraph . '</w:p></w:tc>';
    }

    /**
     * Nombre de cada grado en la nomenclatura colombiana.
     *
     * @var array<int, string>
     */
    private const GRADOS = [
        0 => 'Transición',
        1 => 'Primero',
        2 => 'Segundo',
        3 => 'Tercero',
        4 => 'Cuarto',
        5 => 'Quinto',
        6 => 'Sexto',
        7 => 'Séptimo',
        8 => 'Octavo',
        9 => 'Noveno',
        10 => 'Décimo',
        11 => 'Undécimo',
    ];

    /**
     * Traduce el aula del informe al nombre completo del grado.
     *
     * El campo `aula` guarda la forma corta («11C», «5°B.») y el certificado
     * necesita la denominación oficial: «Grado Undécimo de Educación Media».
     */
    private static function gradeLabel(array $informe): string
    {
        $aula = trim((string) ($informe['aula'] ?? ''));

        // El aula empieza por el número de grado en la enorme mayoría de filas.
        if (preg_match('/^(\d{1,2})/', $aula, $matches)) {
            $grado = (int) $matches[1];

            if (isset(self::GRADOS[$grado])) {
                return 'Grado ' . self::GRADOS[$grado] . ' ' . self::nivelSuffix($grado);
            }
        }

        // Alguna fila ya trae un nombre utilizable («QUINTO B»); las que solo
        // tienen signos («.») se descartan.
        if (preg_match('/[A-Za-z0-9]/', $aula)) {
            return $aula;
        }

        // Sin dato —cerca de una cuarta parte de los informes no trae aula— se
        // deja un espacio visible para completarlo a mano antes de imprimir.
        return 'grado ____';
    }

    /** Agrupación del grado según el nivel educativo. */
    private static function nivelSuffix(int $grado): string
    {
        if ($grado <= 5) {
            return 'de Educación Básica Primaria';
        }

        if ($grado <= 9) {
            return 'de Educación Básica Secundaria';
        }

        return 'de Educación Media Académica';
    }

    /** Número de matrícula del estudiante. */
    private static function matriculaNumber(array $informe): string
    {
        $codigo = trim((string) ($informe['matricula_codigo'] ?? ''));

        // Sin fila en `matricula` se usa el identificador del propio informe.
        if ($codigo === '') {
            $codigo = trim((string) ($informe['estudiante'] ?? ''));
        }

        // La columna es `bigint(7) unsigned zerofill`, así que MySQL devuelve
        // «0002755»; en el certificado va solo el número, sin el relleno.
        $sinRelleno = ltrim($codigo, '0');

        return $sinRelleno === '' ? $codigo : $sinRelleno;
    }

    private static function xmlEscape(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
