<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MapeoCompactacionRtFm22Test extends TestCase {

    public function testResolverArchivoMarkdownEnsayoParaNormasCompactacion(): void {
        // D6938 (Densímetro nuclear)
        $itemD6938 = [
            'norma_astm' => 'ASTM D6938',
            'descripcion_ensayo' => 'Densidad y humedad in situ mediante métodos nucleares',
            'formato_archivo' => 'rotura_compresion_cilindros_c39.md' // Simulando catálogo erróneo previo
        ];
        $this->assertSame('compactacion_densimetro_nuclear.md', resolverArchivoMarkdownEnsayo($itemD6938));

        // D1556 (Cono de arena)
        $itemD1556 = [
            'norma_astm' => 'ASTM D1556',
            'descripcion_ensayo' => 'Densidad de suelo in situ por el método del cono de arena',
            'formato_archivo' => null
        ];
        $this->assertSame('formato_de_compactacion_por_cono_de_arena.md', resolverArchivoMarkdownEnsayo($itemD1556));

        // D5030 (Reemplazo de agua)
        $itemD5030 = [
            'norma_astm' => 'ASTM D5030',
            'descripcion_ensayo' => 'Densidad de suelo in situ por reemplazo de agua',
            'formato_archivo' => ''
        ];
        $this->assertSame('formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md', resolverArchivoMarkdownEnsayo($itemD5030));

        // Proctor Modificado / Estándar (D698 / D1557)
        $itemProctor = [
            'norma_astm' => 'ASTM D1557',
            'descripcion_ensayo' => 'Relaciones de Humedad-Densidad en Suelos (Proctor Modificado)',
            'formato_archivo' => null
        ];
        $this->assertSame('proctor_estandar.md', resolverArchivoMarkdownEnsayo($itemProctor));
    }

    public function testResolverArchivoMarkdownEnsayoParaConcreto(): void {
        $itemConcreto = [
            'norma_astm' => 'ASTM C39',
            'descripcion_ensayo' => 'Resistencia a la compresión de especímenes cilíndricos de concreto',
            'formato_archivo' => 'resistencia_de_concreto.md'
        ];
        $this->assertSame('resistencia_de_concreto.md', resolverArchivoMarkdownEnsayo($itemConcreto));
    }

    public function testEsItemCompactacionDiferenciaInSituDeLaboratorio(): void {
        // Ensayos in situ (terreno): sin custodia física de laboratorio
        $inSituD6938 = ['norma_astm' => 'ASTM D6938', 'descripcion_ensayo' => 'Densidad con densímetro nuclear'];
        $inSituCono = ['norma_astm' => 'ASTM D1556', 'descripcion_ensayo' => 'Cono de arena'];
        $this->assertTrue(esItemCompactacion($inSituD6938));
        $this->assertTrue(esItemCompactacion($inSituCono));

        // Proctor: es ensayo de laboratorio físico (requiere muestras MS en recepción y custodia)
        $proctorLab = ['norma_astm' => 'ASTM D1557', 'descripcion_ensayo' => 'Proctor Modificado'];
        $this->assertFalse(esItemCompactacion($proctorLab), 'El Proctor no debe marcarse como compactación in situ');

        // Concreto: compresión C39 en laboratorio
        $concretoLab = ['norma_astm' => 'ASTM C39', 'descripcion_ensayo' => 'Compresión de cilindros'];
        $this->assertFalse(esItemCompactacion($concretoLab));
    }

    public function testGenerarCodigoInformeEnsayoDistingueDiferentesOrdenesDeServicio(): void {
        $anio = date('y');
        $filasSinMuestraLab = [
            ['Punto de Muestreo' => 'P-1', 'Densidad húmeda' => '2.10']
        ];

        $codigoOS1 = 'OS-2026-0005';
        $inf1 = generarCodigoInformeEnsayo($filasSinMuestraLab, '2026-03-01', 'Suelos', $codigoOS1);

        $codigoOS2 = 'OS-2026-0006';
        $inf2 = generarCodigoInformeEnsayo($filasSinMuestraLab, '2026-03-01', 'Suelos', $codigoOS2);

        $this->assertSame("CYCSA-INF-MS-0005-{$anio}", $inf1);
        $this->assertSame("CYCSA-INF-MS-0006-{$anio}", $inf2);
        $this->assertNotSame($inf1, $inf2, 'Órdenes de servicio distintas deben generar números de informe distintos');
    }
}
