import json,glob,re
OUT='/workspace/cycsa_hojas_calculo/catalogos'
# variables comunes: campos repetidos en >=2 formatos
uso={};meta={}
for p in sorted(glob.glob('/workspace/cycsa_hojas_calculo/formatos/*.json')):
    s=json.load(open(p))
    for c in s['encabezado']+[c for sec in s['secciones'] for c in sec['campos']]:
        uso.setdefault(c['codigo'],set()).add(s['codigo']); meta.setdefault(c['codigo'],c)
    for f in s['formulas']:
        uso.setdefault(f['codigo'],set()).add(s['codigo']); meta.setdefault(f['codigo'],{"codigo":f['codigo'],"etiqueta":f['descripcion'],"tipo":"calculado","unidad":f['unidad'],"decimales":2,"requerido":False})
vc=[dict({k:meta[c][k] for k in ("codigo","etiqueta","tipo","unidad","decimales","requerido") if k in meta[c]},usado_en=sorted(f)) for c,f in sorted(uso.items()) if len(f)>=2]
json.dump({"descripcion":"Variables reutilizables (aparecen en 2+ formatos). Insertar en tabla `variables` solo si el codigo NO existe (INSERT IGNORE / verificación previa). Unidades → `unidades_medida.simbolo`.",
 "unidades_requeridas":sorted({m.get('unidad') for m in meta.values() if m.get('unidad')}),
 "variables":vc},open(f'{OUT}/variables_comunes.json','w'),ensure_ascii=False,indent=2)
eq={"descripcion":"Equipos y constantes de calibración extraídas de los Excel (valores vigentes en los archivos, a verificar contra certificados). Las fórmulas de los formatos referencian estos códigos (ref_equipo); el sistema debe leer la versión vigente por fecha.",
 "nota_tabla":"No existe tabla de equipos en el sistema (solo activos_fijos / mantenimiento_equipos en 003). Proponer tablas NUEVAS equipos_calibracion y equipos_calibracion_constantes (aditivas).",
 "equipos":[
 {"codigo":"PRENSA-01","tipo":"maquina_fuerza","descripcion":"Máquina de fuerza (compresión) usada en RT-FM-61/36/38/57/63","fuente":"RT-FM-61 hoja 'U Carga'","modelo_correccion":"carga_real = a·L³ + b·L² + c·L + d (L = lectura lbf)",
  "constantes":{"a":-4.6765e-14,"b":2.5071e-08,"c":0.99583,"d":3.7217,"a_e14":-4.6765,"b_e8":2.5071},"nota_parser":"EvaluadorFormula no acepta notación científica: usar a_e14·(L/100000)³·10 + b_e8·(L/10000)²",
  "incertidumbre_por_punto":[{"carga_lbf":4973,"u_rel":0.0044,"U_lbf":21.8812},{"carga_lbf":9963,"u_rel":0.0011,"U_lbf":10.9593},{"carga_lbf":24929,"u_rel":0.0025,"U_lbf":62.3225}],"nota_incertidumbre":"Tabla completa en U Carga F24:H33 (10 puntos); se listan los 3 primeros"},
 {"codigo":"FACTOR-LD-C39","tipo":"tabla_norma","descripcion":"Factor de corrección por esbeltez L/D (ASTM C39)","fuente":"RT-FM-61 'CONVERT Y LISTA' F36:H40","constantes":{"a":0.2133,"b":-1.04,"c":1.7667,"d":-0.07},"tabla":[{"ld":1.75,"factor":0.98},{"ld":1.5,"factor":0.96},{"ld":1.25,"factor":0.93},{"ld":1.0,"factor":0.87}],"aplicar_si":"L/D < 1.75"},
 {"codigo":"ESCL-01","tipo":"esclerometro","descripcion":"Esclerómetro (martillo suizo) RT-FM-59","fuente":"RT-FM-59 'datos equipo' y 'datos Mx'","factor_calibracion":0.976,
  "coeficientes_por_posicion":{"A":{"a":-0.0431,"b":5.8982,"c":-51.093,"d":319.54},"B":{"a":-0.0921,"b":10.964,"c":-221.79,"d":2770},"C":{"a":-0.0658,"b":9.4676,"c":-218.13,"d":1863.4}},
  "curva_fabricante":{"psi":[1500,2000,2500,3000,3500,4000,4500,5000,5500,6000,6500,7000],"rebote_B":[None,20,24.5,27,30,32.5,35,37.5,40,42,44.5,47],"rebote_A":[21.5,24.5,27.5,30.5,33,35.5,38,40,42.5,45,47,49.5],"rebote_C":[26.5,29.5,32.5,35,37.5,39.5,42,44,46,48.5,50.5,52.5]},
  "curva_dispersion_incertidumbre":{"nota":"PRESUPUESTO F21:M21 muestra a=-0.0045, b=0.3855, c=-1.1116, d=588.46 (valores mostrados redondeados; no reproducen la U de 'datos Mx'; obtener valores completos del laboratorio)"}},
 {"codigo":"MOLDE-PROCTOR-4IN","tipo":"molde","descripcion":"Molde Proctor 4 in","fuente":"RT-FM-27 Datos PROCTOR fila 11 / hoja Listas","volumen_ml":947.26,"nota":"La hoja Listas contiene la lista de volúmenes de cilindro; registrar cada molde con su volumen calibrado"},
 {"codigo":"MOLDE-CBR-6IN","tipo":"molde","descripcion":"Molde CBR","fuente":"RT-FM-46","volumen_cm3":3200,"masa_g":7849},
 {"codigo":"ANILLO-CBR-01","tipo":"anillo_carga","descripcion":"Anillo de carga CBR","fuente":"RT-FM-46","constante_lbf_por_div":10,"area_piston_in2":3},
 {"codigo":"ARENA-CONO-01","tipo":"material_calibrado","descripcion":"Arena calibrada para cono de arena","fuente":"RT-FM-55 D18","peso_unitario_kgm3":1250},
 {"codigo":"ANILLO-RA-01","tipo":"anillo","descripcion":"Anillo para reemplazo de agua","fuente":"RT-FM-54","diametro_m":1.0},
 {"codigo":"BAL-EQ-6566","tipo":"balanza","descripcion":"Balanza 200 g (humedad)","fuente":"Incert Humedad (RT-FM-25/27/42)","certificado":"CM15022022-03MM","incertidumbre_calibracion_g":0.01,"resolucion_g":0.01,"excentricidad_g":-0.01,"repetibilidad_s_g":0.01,"u_combinada_g":0.0103279555898},
 {"codigo":"BAL-EQ-6633","tipo":"balanza","descripcion":"Balanza 5000 g (granulometría/compactación)","fuente":"Incert Gran / Incert compactacion","nota":"Valores en hojas ocultas; capturar del certificado vigente"},
 {"codigo":"TERM-EQ-233","tipo":"termometro","descripcion":"Termómetro concreto fresco","fuente":"RT-FM-61 'U Temp'"},
 {"codigo":"TERM-EQ-2136","tipo":"termometro","descripcion":"Termómetro calibración de picnómetros","fuente":"RT-FM-56 Hoja1"}],
 "picnometros":{"fuente":"RT-FM-56 hojas 'Balon -02' a 'Balon -10'","densidad_agua":"ρ(T) = 1.00034038 − 7.77e-6·T − 4.95e-6·T²","balones":[]}}
import openpyxl,warnings;warnings.filterwarnings('ignore')
wb=openpyxl.load_workbook(glob.glob('/workspace/archcycsa/Archive/*FM-56*')[0],data_only=True)
for ws in wb.worksheets:
    if not ws.title.startswith('Balon'): continue
    e={"codigo":"PICNO-"+ws.title.split('-')[-1].strip(),"hoja":ws.title,"codigo_lab":ws['B5'].value,"fecha_evaluacion":str(ws['B6'].value)[:10],
       "masa_vacio_prom_g":ws['B16'].value,"masa_lleno_prom_g":ws['C16'].value,"volumen_prom_ml":ws['E16'].value,"desv_volumen_ml":ws['E17'].value}
    eq["picnometros"]["balones"].append(e)
json.dump(eq,open(f'{OUT}/equipos_calibracion.json','w'),ensure_ascii=False,indent=2,default=str)
listas={"descripcion":"Listas desplegables extraídas de hojas ocultas/visibles. Sin nombres de personal: los analistas/técnicos deben venir de la tabla de usuarios del sistema.",
 "analistas":{"nota":"Existen listas de analistas/técnicos en RT-FM-27 'Listas', RT-FM-42 'Listas', RT-FM-25 'Lista', RT-FM-61 'CONVERT Y LISTA' (≈14 nombres). NO migrar; usar usuarios con rol de laboratorio."},
 "tamices":[{"codigo":k,"designacion":d,"abertura_mm":m} for k,d,m in [("2in",'2"',50),("1_1_2in",'1 1/2"',37.5),("1in",'1"',25),("3_4in",'3/4"',19),("1_2in",'1/2"',12.5),("3_8in",'3/8"',9.5),("no4","No. 4",4.75),("no8","No. 8",2.36),("no10","No. 10",2.0),("no16","No. 16",1.18),("no20","No. 20",0.85),("no30","No. 30",0.6),("no40","No. 40",0.425),("no50","No. 50",0.3),("no60","No. 60",0.25),("no80","No. 80",0.18),("no100","No. 100",0.15),("no140","No. 140",0.106),("no200","No. 200",0.075),("fondo","Fondo",0)]],
 "tipos_proctor":[{"codigo":"estandar","etiqueta":"Proctor Estandar","norma":"ASTM D698","masa_piston_kg":2.5,"altura_cm":30.5,"capas":3,"golpes":25},{"codigo":"modificado","etiqueta":"Proctor Modificado","norma":"ASTM D1557","masa_piston_kg":4.54,"altura_cm":45.7,"capas":5,"golpes":25}],
 "metodos_limites":["METODO A","METODO B"],
 "sucs":[{"simbolo":s,"nombre":n} for s,n in [("GW","Grava bien graduada"),("GP","Grava mal graduada"),("GM","Grava limosa"),("GC","Grava arcillosa"),("SW","Arena bien graduada"),("SP","Arena mal graduada"),("SM","Arena limosa"),("SC","Arena arcillosa"),("ML","Limo de baja plasticidad"),("CL","Arcilla de baja plasticidad"),("OL","Limo/arcilla orgánica de baja plasticidad"),("MH","Limo de alta plasticidad"),("CH","Arcilla de alta plasticidad"),("OH","Orgánico de alta plasticidad"),("PT","Turba")]],
 "posiciones_esclerometro":[{"codigo":"A","descripcion":"Horizontal (según curva del fabricante, posición A)"},{"codigo":"B","descripcion":"Vertical hacia abajo (posición B)"},{"codigo":"C","descripcion":"Vertical hacia arriba (posición C)"}],
 "nota_posiciones":"El Excel solo nombra A/B/C; la orientación se infiere de la curva (C da mayor rebote para igual resistencia) – confirmar con el laboratorio",
 "tipos_especimen":["Cilindro","Cilindro 4 X 8","Cilindro 8 X 16","Mortero","Suelo cemento"],
 "formas_especimen":["Circulo","Cuadrado"],
 "tipos_falla_c39":[{"codigo":"Tipo 1","descripcion":"Conos razonablemente bien formados en ambos extremos"},{"codigo":"Tipo 2","descripcion":"Cono bien formado en un extremo, grietas verticales"},{"codigo":"Tipo 3","descripcion":"Grietas columnares verticales a través de ambos extremos"},{"codigo":"Tipo 4","descripcion":"Fractura diagonal sin agrietamiento en los extremos"},{"codigo":"Tipo 5","descripcion":"Fractura lateral en la parte superior o inferior"},{"codigo":"Tipo 6","descripcion":"Similar a 5 pero con el extremo del cilindro en punta"}],
 "nota_tipos_falla":"La migración 006/RoturasModelo usa por defecto 'Tipo 1 (Cono)': reutilizar ese formato de texto",
 "bloques_nton":{"norma":"NTON 12 008-16","tolerancia_mm":3,"filas":[{"dimension":d,"pared_ext_min_mm":e,"pared_int_min_mm":i} for d,e,i in [("9x19x39",20,20),("10x19x39",20,20),("14x19x39",25,25),("15x19x39",25,25),("19x19x39",25,25),("20x19x39",25,25),("25x19x39",32,30),("30x19x39",32,30)]]},
 "acabado_adoquin":[{"codigo":"Liso","factor":1.0},{"codigo":"Biselado","factor":1.16}],
 "masas_ensayo_c88":[{"fraccion":"1\"","masa_g":1000},{"fraccion":'3/4"',"masa_g":500},{"fraccion":'1/2"',"masa_g":670},{"fraccion":'3/8"',"masa_g":330},{"fraccion":"No. 4","masa_g":300}],

 "varillas":[{"numero":n,"diametro_in":d} for n,d in [("2",0.25),("3",0.375),("4",0.5),("5",0.625),("6",0.75),("7",0.875),("8",1.0)]],
 "edades_rotura_tipicas_dias":[1,3,7,14,28,56,90],"materiales_granulometria":["Suelo","Agregado fino","Agregado grueso"]}
json.dump(listas,open(f'{OUT}/listas.json','w'),ensure_ascii=False,indent=2)
print(len(vc),"variables comunes;",len(eq["picnometros"]["balones"]),"balones")
