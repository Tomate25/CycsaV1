<?php
$schemaContent = file_get_contents('prisma/schema.prisma');

preg_match_all('/model\s+(\w+)\s*\{([^}]+)\}/s', $schemaContent, $matches, PREG_SET_ORDER);

$mermaid = "```mermaid\nerDiagram\n";

foreach ($matches as $m) {
    $modelName = $m[1];
    $fieldsText = trim($m[2]);
    $lines = explode("\n", $fieldsText);
    
    $mermaid .= "    " . $modelName . " {\n";
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '@@') === 0 || strpos($line, '//') === 0) continue;
        
        $parts = preg_split('/\s+/', $line);
        if (count($parts) >= 2) {
            $fieldName = $parts[0];
            $fieldType = $parts[1];
            
            // Skip relation fields
            if (preg_match('/^[A-Z]/', $fieldType) && !in_array($fieldType, ['DateTime', 'Decimal', 'Int', 'String', 'Boolean', 'Float', 'Json', 'Bytes'])) {
                continue;
            }
            
            $isPk = strpos($line, '@id') !== false ? " PK" : "";
            $isFk = (strpos($line, 'id_') === 0 || strpos($line, 'id') === 0 && $fieldName !== 'id') ? " FK" : "";
            
            $mermaid .= "        " . str_replace('?', '', $fieldType) . " " . $fieldName . $isPk . $isFk . "\n";
        }
    }
    $mermaid .= "    }\n\n";
}

$mermaid .= "```\n";

$doc = "# Diagrama Entidad-Relación (ERD) - Base de Datos CYCSA ERP & LIMS\n\n";
$doc .= "Este diagrama representa la estructura de las **27 tablas** de la base de datos `cycsa_db` extraídas con **Prisma**.\n\n";
$doc .= "### 💡 Herramientas Recomendadas para Visualizar en Línea:\n";
$doc .= "1. **Prisma Editor / DrawSQL / Azimutt**: Copia el archivo `prisma/schema.prisma` y pégalo en [https://prismalyser.com](https://prismalyser.com) o [https://drawsql.app](https://drawsql.app) para ver el diagrama interactivo 2D.\n";
$doc .= "2. **Prisma Studio**: Abre `http://localhost:5555` en tu navegador para ver y modificar los datos.\n\n";
$doc .= "### 📐 Diagrama Mermaid de Tablas:\n\n";
$doc .= $mermaid;

file_put_contents('storage/docs/DIAGRAMA_ERD_CYCSA.md', $doc);
echo "DIAGRAMA ERD GENERADO EN storage/docs/DIAGRAMA_ERD_CYCSA.md\n";
