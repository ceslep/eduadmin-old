<?php

require_once __DIR__ . '/../models/Informe.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/Auth.php';

class InformeController
{
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

        $tmpFile = tempnam(sys_get_temp_dir(), 'informe_') . '.docx';
        copy($templatePath, $tmpFile);

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
            '%3' => self::xmlEscape($informe['aula'] ?? ''),
            '%4' => self::xmlEscape($informe['anio'] ?? $anio),
            '%5' => self::xmlEscape((string)($informe['ind'] ?? '')),
        ];
        $xmlContent = str_replace(array_keys($replacements), array_values($replacements), $xmlContent);

        $xmlContent = self::replaceAcademicTable($xmlContent, $informe['parsed_informe'] ?? []);

        $zip->addFromString('word/document.xml', $xmlContent);
        $zip->close();

        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $informe['estudiante'] ?? 'informe');
        $fileName = "Certificado_{$safeName}_{$anio}.docx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('Cache-Control: no-cache, must-revalidate');

        readfile($tmpFile);

        unlink($tmpFile);
        exit;
    }

    private static function mergePlaceholderRuns(string $xml): string
    {
        return preg_replace(
            '/<w:r[^>]*><w:rPr>(.*?)<\/w:rPr><w:t[^>]*>%<\/w:t><\/w:r>\s*<w:r[^>]*><w:rPr>(.*?)<\/w:rPr><w:t[^>]*>(\d)<\/w:t><\/w:r>/',
            '<w:r><w:rPr>$1</w:rPr><w:t>%$3</w:t></w:r>',
            $xml
        );
    }

    private static function replaceAcademicTable(string $xml, array $parsedInforme): string
    {
        if (empty($parsedInforme)) {
            return $xml;
        }

        preg_match(
            '/<w:p[^>]*>.*?<w:t[^>]*>AREAS<\/w:t>.*?<w:t>IHS<\/w:t>.*?<\/w:p>/s',
            $xml,
            $headerMatch,
            PREG_OFFSET_CAPTURE
        );

        if (empty($headerMatch)) {
            return $xml;
        }

        $headerEndPos = $headerMatch[0][1] + strlen($headerMatch[0][0]);

        preg_match(
            '/<w:p[^>]*>.*?Dado en Anserma/s',
            $xml,
            $footerMatch,
            PREG_OFFSET_CAPTURE
        );

        if (empty($footerMatch)) {
            return $xml;
        }

        $footerStartPos = $footerMatch[0][1];

        $tableRowsXml = '';
        foreach ($parsedInforme as $item) {
            $area = self::xmlEscape($item['area'] ?? '');
            $valoracion = self::xmlEscape($item['valoracion'] ?? '');
            $descripcion = self::xmlEscape($item['descripcion'] ?? '');
            $numero = self::xmlEscape($item['numero'] ?? '');

            $tableRowsXml .= '<w:p w14:paraId="' . uniqid() . '" w14:textId="' . uniqid() . '" w:rsidR="00C35DDA" w:rsidRPr="004F743B" w:rsidRDefault="00C35DDA" w:rsidP="00C35DDA"><w:pPr><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr></w:pPr>';
            $tableRowsXml .= '<w:r><w:rPr><w:sz w:val="20"/></w:rPr><w:t>' . $area . '</w:t></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:tab/></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:tab/></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:tab/></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:tab/></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:t>' . $valoracion . '</w:t></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:tab/></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:tab/></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:tab/></w:r>';
            $tableRowsXml .= '<w:r><w:rPr><w:b w:val="0"/><w:sz w:val="20"/></w:rPr><w:t>0</w:t></w:r>';
            $tableRowsXml .= '</w:p>';
        }

        $before = substr($xml, 0, $headerEndPos);
        $after = substr($xml, $footerStartPos);

        return $before . $tableRowsXml . $after;
    }

    private static function xmlEscape(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
