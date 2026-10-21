import json,os
OUT='/workspace/cycsa_hojas_calculo'
def C(codigo,etiqueta,tipo='numero',unidad=None,dec=2,req=True,esp=False,opciones=None,ayuda=None):
    d={"codigo":codigo,"etiqueta":etiqueta,"tipo":tipo,"unidad":unidad,"decimales":dec if tipo in('numero','calculado') else None,"requerido":req,"por_especimen":esp}
    if opciones: d["opciones_lista"]=opciones
    if ayuda: d["texto_ayuda"]=ayuda
    return d
def F(codigo,expr,desc,unidad=None,red=None,esp=False):
    return {"codigo":codigo,"expresion":expr,"descripcion":desc,"unidad":unidad,"redondeo":red,"por_especimen":esp}
def S(nombre,layout,campos): return {"nombre":nombre,"tipo_layout":layout,"campos":campos}
def K(codigo,desc,valor=None,unidad=None,fuente=None,equipo=None):
    d={"codigo":codigo,"descripcion":desc,"unidad":unidad,"fuente":fuente}
    if equipo: d["ref_equipo"]=equipo; d["valor_referencia"]=valor
    else: d["valor"]=valor
    return d
def CORR(id_,orig,corr,just,dudosa=False):
    return {"id":id_,"original_excel":orig,"corregido":corr,"justificacion":just,"dudosa":dudosa,"requiere_confirmacion_laboratorio":dudosa}
def CASO(nombre,fuente,entradas,esperados,tol=0.001,notas=None):
    d={"nombre":nombre,"fuente":fuente,"entradas":entradas,"esperados":esperados,"tolerancia_relativa":tol}
    if notas: d["notas"]=notas
    return d
ENC_BASE=[C("codigo_muestra","Código de muestra (MS-####-AA, desde recepción)","texto"),C("fecha_ensayo","Fecha de ensayo","fecha"),C("analista_id","Analista (usuario del sistema)","lista",ayuda="Catálogo de usuarios; NO copiar nombres desde Excel")]
def H(*extra): return ENC_BASE+list(extra)
def base(codigo,nombre,norma,impl,origen,modulo,rt22,rotura,variantes=None):
    return {"codigo":codigo,"nombre":nombre,"norma":{"texto":norma,"implicita":impl},"version_origen":origen,"variantes":variantes or [],"modulo_destino":modulo,"formato_informe_rt_fm_22":rt22,"requiere_rotura_por_edad":rotura}
FMT={}
def av(n): return f"({{{n}_1}}+{{{n}_2}}+{{{n}_3}})/3"
TAM=[("2in",'2"'),("1_1_2in",'1 1/2"'),("1in",'1"'),("3_4in",'3/4"'),("1_2in",'1/2"'),("3_8in",'3/8"'),("no4","No. 4"),("no8","No. 8"),("no10","No. 10"),("no16","No. 16"),("no20","No. 20"),("no30","No. 30"),("no40","No. 40"),("no50","No. 50"),("no60","No. 60"),("no80","No. 80"),("no100","No. 100"),("no140","No. 140"),("no200","No. 200"),("fondo","Fondo")]
d=base("RT-FM-25","Contenido de humedad y granulometría por lavado y tamizado (clasificación SUCS)","ASTM C33 (citada); ASTM D2216 / C136 / C117 / D2487 (inferidas)",True,"CYCSA-RT-FM-25 Formato de Hoj Granulometría y humedad 2026 (...)(7).xlsm","Plantillas",{"letra":"F","nuevo":False,"nota":"Alimenta formato_de_granulometria_de_suelo.md / granulomnetria_de_agregados.md (RT-FM-22 F)"},False,
 [{"nombre":"campo","archivo":"Archive (1)/CYCSA-RT-FM-25-Humedad y Granulometría2026 campo (...).xlsm","diferencias":"Misma plantilla, otro conjunto de muestras; ~5.4k fórmulas difieren de laboratorio (versión anterior). Hoja Lista oculta."},{"nombre":"CASA MANAGUA","archivo":"Archive (2)/CYCSA-RT-FM-25-Humedad y Granulometría2026 CASA MANAGUA (2).xlsm","diferencias":"Copia de campo con la muestra MS-15062-26-26 seleccionada (sufijo duplicado); 43 fórmulas distintas a campo."}])
d["encabezado"]=H(C("tipo_material","Tipo de material","lista",opciones=["Suelo","Agregado fino","Agregado grueso"]))
d["secciones"]=[S("Humedad","formulario",[C("tara","Masa tara","numero","g"),C("masa_humeda_tara","Muestra húmeda + tara",unidad="g"),C("masa_seca_tara","Muestra seca + tara",unidad="g")]),
 S("Lavado","formulario",[C("masa_lavada_tara","Masa lavada seca + tara",unidad="g")]),
 S("Granulometría","tabla_matriz",[C(f"ret_{k}",f"Masa retenida tamiz {l}",unidad="g",req=False) for k,l in TAM]),
 S("Límites (opcional, desde RT-FM-42)","formulario",[C("ll","Límite líquido",unidad="%",req=False),C("lp","Límite plástico",unidad="%",req=False)])]
sumret="+".join(f"{{ret_{k}}}" for k,_ in TAM)
d["constantes"]=[K("balanza_humedad","Balanza 200 g (incertidumbre de pesaje)",None,"g","Hoja oculta 'Incert Humedad'","BAL-EQ-6566"),K("balanza_granulometria","Balanza 5000 g",None,"g","Hoja oculta 'Incert Gran'","BAL-EQ-6633")]
d["formulas"]=[F("humedad","({masa_humeda_tara}-{masa_seca_tara})*100/({masa_seca_tara}-{tara})","Contenido de humedad base seca (D2216)","%","ROUND(x,2)"),
 F("humedad_correccion","({masa_humeda_tara}-{masa_seca_tara})*100/({masa_humeda_tara}-{tara})","Humedad base húmeda (corrección de masa)","%","ROUND(x,2)"),
 F("masa_seca","{masa_seca_tara}-{tara}","Masa seca inicial","g"),
 F("perdida_lavado","{masa_seca_tara}-{masa_lavada_tara}","Pérdida por lavado","g"),
 F("total_retenido","{perdida_lavado}+"+sumret,"Pérdida por lavado + suma de retenidos","g"),
 F("perdida_proceso","100-{total_retenido}*100/{masa_seca}","Pérdida en el proceso de tamizado","%","ROUND(x,2)"),
 F("pasa_no200","({perdida_lavado}+{ret_fondo})*100/{masa_seca}","% que pasa No. 200 (lavado + fondo)","%","ROUND(x,1)"),
 F("ip","{ll}-{lp}","Índice de plasticidad","%","ROUND(x,0)")]
d["agregaciones"]=[{"codigo":"curva_granulometrica","funcion":"acumulado","de":"ret_*","descripcion":"% retenido acumulado y % que pasa = 100 − acumulado·100/masa_seca por tamiz (servicio PHP; no cabe en una expresión)","unidad":"%"},{"codigo":"clasificacion_sucs","funcion":"clasificar_sucs","de":"curva_granulometrica, ll, ip","descripcion":"D2487 con catalogos/listas.json → sucs"}]
d["criterios_aceptacion"]=[{"descripcion":"Pérdida en proceso ≤ 0.3 % (ASTM C136)","expresion_validacion":"{perdida_proceso} <= 0.3","implicito":True},{"descripcion":"Agregados: % pasa dentro de bandas ASTM C33 del tamaño nominal (bandas a cargar por el laboratorio)","implicito":False}]
d["salidas"]={"tablas":["Tamiz / % retenido acumulado / % pasa","Indicadores: humedad, pérdida por lavado, pérdida en proceso, LL/LP/IP, SUCS"],"graficos":["Curva granulométrica semilog"]}
d["hojas_ocultas_relevantes"]=[{"hoja":"Incert Humedad (oculta)","contenido":"Incertidumbre de humedad, balanza EQ-6566, cert. CM15022022-03MM","destino":"equipos_calibracion BAL-EQ-6566; módulo de incertidumbre (fase posterior)"},{"hoja":"Incert Gran (oculta)","contenido":"% retenido/pasa e incertidumbre por tamiz (de aquí lee el informe)","destino":"agregación curva_granulometrica"},{"hoja":"Suelo_SUCS (oculta)","contenido":"Tabla y lógica SUCS + gráfico (1 #REF!)","destino":"listas.json sucs"},{"hoja":"Lista (visible en lab, oculta en campo)","contenido":"Material y analistas","destino":"listas.json (sin nombres)"}]
d["correcciones"]=[CORR("FM25-01","Nombres definidos Criteria y Tiempo__s → #REF!; validación IS6:XFD6 → #REF!; vínculo externo externalLink1","Eliminados; listas desde catálogo","Referencias rotas sin uso"),
 CORR("FM25-02","Código = CONCATENATE('MS-',número,'-26') con año fijo; el usuario teclea el código completo → 'MS-15062-26-26'","codigo_muestra viene de recepción","Evita sufijo duplicado y año fijo"),
 CORR("FM25-03","VBA Worksheet_Change desprotege la hoja con contraseña en texto plano y oculta filas de tamices vacíos","La vista oculta filas vacías; sin macros","Seguridad y presentación")]
d["caso_prueba"]=[CASO("MS-0367-26 (suelo)","Archive/CYCSA-RT-FM-25 ... (7).xlsm, hoja 'Datos de Granulometria', columna E",
 {"tara":154.5,"masa_humeda_tara":909.18,"masa_seca_tara":889.63,"masa_lavada_tara":760.87,"ret_2in":0,"ret_1_1_2in":0,"ret_1in":0,"ret_3_4in":46.32,"ret_1_2in":0,"ret_3_8in":78.69,"ret_no4":67.1,"ret_no8":0,"ret_no10":80.3,"ret_no16":0,"ret_no20":84.58,"ret_no30":0,"ret_no40":80.21,"ret_no50":0,"ret_no60":61.46,"ret_no80":0,"ret_no100":50.47,"ret_no140":29.69,"ret_no200":25.36,"ret_fondo":1.8,"ll":0,"lp":0},
 {"humedad":2.6593935766463,"humedad_correccion":2.59050193459479,"masa_seca":735.13,"perdida_lavado":128.76,"total_retenido":734.74,"perdida_proceso":0.0530518411709267,"ip":0})]
FMT["RT-FM-25"]=d
d=base("RT-FM-27","Proctor estándar y modificado (relación humedad–densidad)","ASTM D698 / D1557 (AASHTO T99 / T180)",True,"CYCSA-RT-FM-27 Formato de Hoja de Cálculo para Proctor Estándar y Modificado (...)(3).xlsm","Plantillas",{"letra":"C","nuevo":False,"nota":"Alimenta proctor_estandar.md (RT-FM-22 C): P.Vol.Seco Máximo y Humedad Óptima"},False,
 [{"nombre":"campo","archivo":"Archive (1)/...Proctor... CAMPO (...).xlsm","diferencias":"Misma plantilla; 156 fórmulas difieren (versión previa)"},{"nombre":"CASA MANAGUA","archivo":"Archive (2)/...Proctor... CASA MANAGUA) (1).xlsm","diferencias":"Informe con MS-MCA-003-26-26 (sufijo duplicado); 134 fórmulas distintas a campo"}])
d["encabezado"]=H(C("tipo_proctor","Tipo de Proctor","lista",opciones=["Proctor Estandar","Proctor Modificado"]),C("molde_id","Molde (equipo)","lista",ayuda="equipos_calibracion.json moldes"))
d["secciones"]=[S("Compactación","formulario",[C("golpes","Golpes por capa",dec=0),C("capas","Número de capas",dec=0),C("masa_piston","Masa del pisón","numero","kg"),C("altura_caida","Altura de caída","numero","cm"),C("volumen_molde","Volumen del molde","numero","mL",ayuda="Del equipo seleccionado")]),
 S("Puntos (hasta 6)","tabla_matriz",[C("masa_molde_suelo","Molde + suelo húmedo","numero","g",esp=True),C("masa_molde","Masa del molde","numero","g",esp=True),C("tara_suelo_humedo","Tara + suelo húmedo","numero","g",esp=True),C("tara_suelo_seco","Tara + suelo seco","numero","g",esp=True),C("tara","Tara","numero","g",esp=True)])]
d["constantes"]=[K("volumen_molde","Volumen del molde",947.26,"mL","Datos PROCTOR fila 11 (tecleado por muestra)","MOLDE-PROCTOR-4IN")]
d["formulas"]=[F("energia","{golpes}*{capas}*{masa_piston}*{altura_caida}/{volumen_molde}","Energía de compactación","kg·cm/cm³","ROUND(x,2)"),
 F("humedad","({tara_suelo_humedo}-{tara_suelo_seco})*100/({tara_suelo_seco}-{tara})","Humedad del punto","%","ROUND(x,1)",True),
 F("densidad_seca","(({masa_molde_suelo}-{masa_molde})/{volumen_molde})/(1+{humedad}/100)*1000","Peso volumétrico seco del punto","kg/m³","ROUND(x,0)",True)]
d["agregaciones"]=[{"codigo":"pvs_maximo","funcion":"max_curva","de":"densidad_seca","descripcion":"Máximo de la curva (ajuste parabólico alrededor del máximo; en Excel se lee del gráfico)","unidad":"kg/m³"},{"codigo":"humedad_optima","funcion":"x_de_max_curva","de":"humedad","unidad":"%"}]
d["criterios_aceptacion"]=[{"descripcion":"Al menos 2 puntos a cada lado del óptimo (D698/D1557)","implicito":True}]
d["salidas"]={"tablas":["Datos de compactación y puntos","PVSM y humedad óptima"],"graficos":["Curva de compactación (+ curva de saturación opcional)"]}
d["hojas_ocultas_relevantes"]=[{"hoja":"Listas (oculta en campo/CASA)","contenido":"Analistas, tipos de Proctor, volúmenes de cilindro","destino":"listas.json; equipos_calibracion moldes"},{"hoja":"Incert Humedad / Incert compactacion (ocultas)","contenido":"Incertidumbre balanzas EQ-6566 y EQ-6633 y volumen del molde (9/12 celdas con error)","destino":"equipos_calibracion.json"}]
d["correcciones"]=[CORR("FM27-01","Volumen del molde tecleado en cada muestra","Se toma del molde calibrado","Trazabilidad ISO 17025"),CORR("FM27-02","Vínculo externo y una fórmula #REF! (Datos PROCTOR / CONSOLIDADO)","Eliminados","Referencia rota")]
P27={"golpes":25,"capas":3,"masa_piston":2.5,"altura_caida":30,"volumen_molde":947.26}
d["caso_prueba"]=[CASO("MS-0140-26 punto 1","Archive/...Proctor...(3).xlsm 'Datos PROCTOR' col E",dict(P27,masa_molde_suelo=5573,masa_molde=4161,tara_suelo_humedo=254.7,tara_suelo_seco=240.24,tara=54.58),{"energia":5.93817959166438,"humedad":7.788430464289549,"densidad_seca":1382.9081939810633}),
 CASO("MS-0140-26 punto 5 (máximo)","idem",dict(P27,masa_molde_suelo=5852,masa_molde=4161,tara_suelo_humedo=275.94,tara_suelo_seco=236.97,tara=75.74),{"humedad":24.170439744464424,"densidad_seca":1437.6600006205394})]
FMT["RT-FM-27"]=d
d=base("RT-FM-35","Absorción y gravedades específicas (bulk, SSS, aparente) de agregado fino y grueso","ASTM C128 (fino) / ASTM C127 (grueso)",True,"CYCSA-RT-FM-35 HC %Abs, GE, GE sss, GE Ap 2026 (...)(2).xls","Plantillas",{"letra":"G","nuevo":False,"nota":"Alimenta ensayos_varios.md (RT-FM-22 G); verificar la columna de absorción/GE en el JSON existente"},False,
 [{"nombre":"campo","archivo":"Archive (1)/CYCSA-RT-FM-35 HC Abs, GE, GE sss, GE Ap campo 2026.xls","diferencias":"Misma estructura, otras muestras"},{"nombre":"CASA MANAGUA","archivo":"Archive (2)/CYCSA-RT-FM-35 HC Abs, GE, GE sss, GE Ap CASA MANAGUA.xls","diferencias":"Misma estructura"}])
d["encabezado"]=H(C("fraccion","Fracción","lista",opciones=["Agregado fino","Agregado grueso"]))
d["secciones"]=[S("Agregado fino (C128)","formulario",[C("masa_sss","Masa SSS (B)","numero","g",req=False),C("tara","Tara","numero","g",req=False),C("masa_seca_tara","Masa seca + tara","numero","g",req=False),C("frasco_agua","Frasco + agua al aforo","numero","g",req=False),C("frasco","Masa del frasco","numero","g",req=False),C("frasco_agua_muestra","Frasco + agua + muestra","numero","g",req=False)]),
 S("Agregado grueso (C127)","formulario",[C("g_masa_sss_tara","Masa SSS + tara","numero","g",req=False),C("g_tara","Tara","numero","g",req=False),C("g_masa_seca_tara","Masa seca + tara","numero","g",req=False),C("g_canasta_muestra_sumergida","Canasta + muestra sumergida","numero","g",req=False),C("g_canasta_sumergida","Canasta sumergida","numero","g",req=False)])]
d["constantes"]=[]
d["formulas"]=[F("masa_seca","{masa_seca_tara}-{tara}","Masa seca al horno (A)","g"),F("volumen_frasco","{frasco_agua}-{frasco}","Capacidad del frasco (V)","g"),
 F("masa_agua_agregada","{frasco_agua_muestra}-({masa_sss}+{frasco})","Agua añadida con la muestra (W)","g"),
 F("absorcion","({masa_sss}-{masa_seca})*100/{masa_seca}","Absorción","%","ROUND(x,2)"),
 F("ge_bulk","{masa_seca}/({volumen_frasco}-{masa_agua_agregada})","GE bulk (seca)",None,"ROUND(x,3)"),
 F("ge_sss","{masa_sss}/({volumen_frasco}-{masa_agua_agregada})","GE SSS",None,"ROUND(x,3)"),
 F("ge_aparente","{masa_seca}/(({volumen_frasco}-{masa_agua_agregada})-({masa_sss}-{masa_seca}))","GE aparente",None,"ROUND(x,3)"),
 F("g_masa_sss","{g_masa_sss_tara}-{g_tara}","Grueso: masa SSS (B)","g"),F("g_masa_seca","{g_masa_seca_tara}-{g_tara}","Grueso: masa seca (A)","g"),
 F("g_masa_sumergida","{g_canasta_muestra_sumergida}-{g_canasta_sumergida}","Grueso: masa sumergida (C)","g"),
 F("g_absorcion","({g_masa_sss}-{g_masa_seca})*100/{g_masa_seca}","Grueso: absorción","%","ROUND(x,2)"),
 F("g_ge_bulk","{g_masa_seca}/({g_masa_sss}-{g_masa_sumergida})","Grueso: GE bulk",None,"ROUND(x,3)"),
 F("g_ge_sss","{g_masa_sss}/({g_masa_sss}-{g_masa_sumergida})","Grueso: GE SSS",None,"ROUND(x,3)"),
 F("g_ge_aparente","{g_masa_seca}/({g_masa_seca}-{g_masa_sumergida})","Grueso: GE aparente",None,"ROUND(x,3)")]
d["criterios_aceptacion"]=[{"descripcion":"Precisión entre duplicados (C127/C128, un operador): absorción ≤ 0.31 % fino / 0.25 % grueso; GE ≤ 0.027 / 0.025","implicito":True}]
d["salidas"]={"tablas":["Por muestra: %Abs, GE, GE SSS, GE aparente"],"graficos":[]}
d["hojas_ocultas_relevantes"]=[]
d["correcciones"]=[CORR("FM35-01","Fino: A = B − B·Abs/100 (col F) usado en GE bulk y GE aparente; Abs usa la masa seca real (col K)","A = masa seca real (seco+tara − tara)","B·(1−Abs) ≠ B/(1+Abs): subestima A (446.76 vs 451.88 g). GE SSS y Abs no cambian.",True),
 CORR("FM35-02","Grueso: GE = A/(E−C) con E = 'P sss peso húmedo' que incluye tara (G = E−H es 'sin tara'); A = E·(1−Abs)","B = SSS+tara − tara; A = seco+tara − tara; GE = A/(B−C)","Restar la tara antes de usar B; mismo error de A",True),
 CORR("FM35-03","Archivo .xls; nombres Print_Area → #N/A","Sin efecto","Fórmulas recuperadas convirtiendo a .xlsx con LibreOffice (solo lectura)")]
d["caso_prueba"]=[CASO("MS-0718-26 fino","Archive/...FM-35...xls 'Agregado fino.' fila 10",{"masa_sss":500,"tara":0,"masa_seca_tara":451.88,"frasco_agua":710.71,"frasco":194.57,"frasco_agua_muestra":1003.13},
 {"masa_seca":451.88,"volumen_frasco":516.14,"masa_agua_agregada":308.56,"absorcion":10.648844826060017,"ge_sss":2.408709894980248,"ge_bulk":2.176895654687,"ge_aparente":2.833814},notas="ge_bulk/ge_aparente CORREGIDOS (FM35-01); Excel 2.1522 y 2.8947"),
 CASO("MS-1363-25 grueso","'Agregado Grueso' fila 10",{"g_masa_sss_tara":3187.51,"g_tara":150.38,"g_masa_seca_tara":3145,"g_canasta_muestra_sumergida":2805.05,"g_canasta_sumergida":862.85},
 {"g_masa_sss":3037.13,"g_masa_seca":2994.62,"g_masa_sumergida":1942.2,"g_absorcion":1.419532,"g_ge_bulk":2.734970,"g_ge_sss":2.773794,"g_ge_aparente":2.845461},notas="Corregidos (FM35-02); absorción coincide con Excel N10")]
FMT["RT-FM-35"]=d
d=base("RT-FM-36","Bloques de mampostería de concreto: absorción, humedad, densidad, área neta y resistencia","ASTM C140 / C90; NTON 12 008-16 (C90 y NTON citadas)",False,"CYCSA-RT-FM-36 HOJA DE CALCULO DE MAMPOSTERIA BLOQUE 2026 (2).xlsm","Laboratorio Roturas",{"letra":"A","nuevo":False,"nota":"Alimenta formato_de_resistencia_de_bloques.md (RT-FM-22 A)"},False)
d["encabezado"]=H(C("dimension_nominal","Dimensión nominal","lista",ayuda="listas.json bloques_nton"),C("fecha_fabricacion","Fecha de fabricación","fecha",req=False))
d["secciones"]=[S("Especímenes (3)","tabla_matriz",[C("masa_recibida","Masa al recibir","numero","kg",esp=True),C("masa_seca","Masa seca al horno","numero","kg",esp=True),C("masa_sumergida","Masa sumergida","numero","kg",esp=True),C("masa_saturada","Masa saturada","numero","kg",esp=True),
 C("alto","Altura","numero","cm",1,esp=True),C("largo","Largo","numero","cm",1,esp=True),C("ancho","Ancho","numero","cm",1,esp=True),C("espesor_pared_ext","Espesor pared exterior","numero","cm",1,esp=True),C("espesor_pared_int","Espesor pared interior","numero","cm",1,esp=True),C("carga_lbf","Carga","numero","lbf",0,esp=True)])]
d["constantes"]=[K("cm2_a_in2","cm² → in² (Excel)",0.155,"in²/cm²","fila 79"),K("densidad_agua_lbft3","Densidad del agua",62.4,"lb/ft³","fila 26"),K("psi_por_kgcm2","Conversión del laboratorio",14.2,None,"fila 100"),K("prensa","Polinomio de la máquina de fuerza",None,None,"Hoja U Carga","PRENSA-01")]
d["formulas"]=[F("absorcion_lbft3","({masa_saturada}-{masa_seca})/({masa_saturada}-{masa_sumergida})*62.4","Absorción","lb/ft³","ROUND(x,1)",True),
 F("absorcion_kgm3","({masa_saturada}-{masa_seca})/({masa_saturada}-{masa_sumergida})*1000","Absorción","kg/m³","ROUND(x,0)",True),
 F("absorcion_pct","({masa_saturada}-{masa_seca})*100/{masa_seca}","Absorción","%","ROUND(x,1)",True),
 F("humedad_pct_absorcion","({masa_recibida}-{masa_seca})*100/({masa_saturada}-{masa_seca})","Humedad como % de la absorción total","%","ROUND(x,0)",True),
 F("densidad_seca","{masa_seca}/({masa_saturada}-{masa_sumergida})*1000","Densidad seca","kg/m³","ROUND(x,0)",True),
 F("volumen_neto","({masa_saturada}-{masa_sumergida})*1000","Volumen neto","cm³",None,True),
 F("area_neta","{volumen_neto}/{alto}","Área neta","cm²","ROUND(x,1)",True),F("area_bruta","{ancho}*{largo}","Área bruta","cm²","ROUND(x,1)",True),
 F("resistencia_psi","{carga_lbf}/({area_neta}*0.155)","Resistencia sobre área neta","lbf/in²","ROUND(x,0)",True),F("resistencia_kgcm2","{resistencia_psi}/14.2","Resistencia","kg/cm²","ROUND(x,1)",True)]
d["agregaciones"]=[{"codigo":"resistencia_promedio_psi","funcion":"promedio","de":"resistencia_psi","unidad":"lbf/in²"},{"codigo":"resistencia_promedio_kgcm2","funcion":"promedio","de":"resistencia_kgcm2","unidad":"kg/cm²"},{"codigo":"absorcion_promedio_kgm3","funcion":"promedio","de":"absorcion_kgm3","unidad":"kg/m³"},{"codigo":"densidad_promedio","funcion":"promedio","de":"densidad_seca","unidad":"kg/m³"}]
d["criterios_aceptacion"]=[{"descripcion":"NTON 12 008-16: espesor mínimo de paredes según dimensión nominal (listas.json bloques_nton); tolerancia dimensional ±3 mm","implicito":False},{"descripcion":"Resistencia/absorción mínima por clase (C90/NTON): tabla no presente en el Excel, solicitar","implicito":True}]
d["salidas"]={"tablas":["REPORTE por espécimen y promedio con incertidumbre","Comparación con CRITERIOS NTON"],"graficos":[]}
d["hojas_ocultas_relevantes"]=[{"hoja":"U Area / U Carga / U resistencia (visibles)","contenido":"Incertidumbre de área, carga y repetibilidad","destino":"equipos_calibracion + módulo de incertidumbre"},{"hoja":"CRITERIOS (visible)","contenido":"Tabla NTON de espesores mínimos","destino":"listas.json bloques_nton"}]
d["correcciones"]=[CORR("FM36-01","Conversiones aproximadas 0.155, 2.202, 14.2","Se mantienen para reproducir informes; recomendado 0.15500031, 2.20462, 14.2233","Diferencias < 0.2 %",True),
 CORR("FM36-02","REPORTE con datos ficticios ('Fulano de Tal') y 92 celdas con error","Encabezado desde cliente/proyecto del sistema","Datos de prueba"),CORR("FM36-03","Macros VistaPrevia/imprimir/guardar/ver","No se migran","Solo impresión")]
d["caso_prueba"]=[CASO("MS-1943-26 espécimen 1","Archive/...FM-36...xlsm 'DATOS + CALCULO' col B",{"masa_recibida":14.2,"masa_seca":14.08,"masa_sumergida":8.79,"masa_saturada":14.57,"alto":19.1,"largo":39,"ancho":14,"carga_lbf":154283},
 {"absorcion_lbft3":5.2899653979238765,"absorcion_kgm3":84.77508650519033,"absorcion_pct":3.480113636363638,"humedad_pct_absorcion":24.48979591836718,"densidad_seca":2435.98615916955,"volumen_neto":5780,"area_neta":302.6178010471204,"area_bruta":546,"resistencia_psi":3289.2123004799646}),
 CASO("MS-1943-26 espécimen 2","idem",{"masa_recibida":14.48,"masa_seca":14.38,"masa_sumergida":8.49,"masa_saturada":15.12,"alto":19,"largo":39,"ancho":14,"carga_lbf":137761},{"area_neta":348.94736842105266,"resistencia_psi":2547.033523086654})]
FMT["RT-FM-36"]=d
d=base("RT-FM-38","Módulo de ruptura de viguetas de concreto (flexión)","ASTM C78 (carga a tercios)",True,"CYCSA-RT-FM-38 HOJA DE CALCULO PARA LA RESISTENCIA EN VIGUETA 2026 (...).xlsx","Laboratorio Roturas",{"letra":"E","nuevo":False,"nota":"Alimenta formato_de_resistencia_de_a_la_flexion.md (RT-FM-22 E)"},True)
d["encabezado"]=H(C("fecha_fabricacion","Fecha de fabricación","fecha"),C("edad_dias","Edad","calculado","días",0))
d["secciones"]=[S("Vigueta","formulario",[C(f"ancho_{i}",f"Ancho lectura {i}","numero","in") for i in (1,2,3)]+[C(f"espesor_{i}",f"Espesor lectura {i}","numero","in") for i in (1,2,3)]+[C("luz_apoyos","Luz entre apoyos (L)","numero","in"),C("carga_lbf","Carga máxima","numero","lbf",0),C("fractura_en_tercio_medio","¿Fractura en el tercio medio?","lista",opciones=["Sí","No"])]+[C(f"dist_fractura_{i}",f"Distancia fractura–apoyo {i}","numero","in",req=False) for i in (1,2,3)])]
d["constantes"]=[K("psi_por_kgcm2","Conversión",14.2,None,"DATOS col AA"),K("prensa","Máquina de fuerza",None,None,"CALCULO Uex","PRENSA-01")]
d["formulas"]=[F("ancho_prom",av("ancho"),"Ancho promedio b","in","ROUND(x,2)"),F("espesor_prom",av("espesor"),"Espesor promedio d","in","ROUND(x,2)"),
 F("modulo_ruptura_psi","{carga_lbf}*{luz_apoyos}/({ancho_prom}*{espesor_prom}^2)","MR, fractura en tercio medio: R = PL/bd²","lbf/in²","ROUND(x,0)"),
 F("dist_fractura_prom",av("dist_fractura"),"Distancia promedio fractura–apoyo (a)","in"),
 F("modulo_ruptura_fuera_psi","3*{carga_lbf}*{dist_fractura_prom}/({ancho_prom}*{espesor_prom}^2)","MR fuera del tercio medio (exceso ≤ 5 % de L): R = 3Pa/bd²","lbf/in²","ROUND(x,0)"),
 F("modulo_ruptura_kgcm2","{modulo_ruptura_psi}/14.2","MR","kg/cm²","ROUND(x,0)")]
d["criterios_aceptacion"]=[{"descripcion":"Si la fractura está fuera del tercio medio más de 5 % de L, descartar (C78)","implicito":True}]
d["salidas"]={"tablas":["INDIVIDUAL: MR ± U por vigueta"],"graficos":[]}
d["hojas_ocultas_relevantes"]=[{"hoja":"R +-U (oculta)","contenido":"Cálculo real del MR (P·L/(b·d²)) y caso fuera del tercio","destino":"formulas modulo_ruptura_*"},{"hoja":"CALCULO Uex / OBSERVACIONES (ocultas)","contenido":"Incertidumbre expandida; tabla a,b por clase de exactitud del calibrador (2990 celdas con error en filas vacías)","destino":"módulo incertidumbre"},{"hoja":"Hoja1 (oculta)","contenido":"vacía","destino":"descartar"}]
d["correcciones"]=[CORR("FM38-01","DATOS col Z solo calcula el caso fuera del tercio medio; el caso normal está en la hoja oculta 'R +-U'","Ambas fórmulas explícitas; selección por fractura_en_tercio_medio","Claridad"),CORR("FM38-02","Encabezados 'CYCSA-RT-BIT-10' y 'RT-FM-XX'","RT-FM-38","Código inconsistente"),CORR("FM38-03","Fecha tecleada '20025-08-07'","Validación de fecha","Dato inválido")]
d["caso_prueba"]=[CASO("MS-4053-25","Archive/...FM-38... 'DATOS' fila 7 / 'R +-U' fila 6",{"ancho_1":6.1,"ancho_2":6,"ancho_3":6.2,"espesor_1":6.2,"espesor_2":6,"espesor_3":6,"luz_apoyos":18,"carga_lbf":17297},{"ancho_prom":6.1,"espesor_prom":6.066666666666666,"modulo_ruptura_psi":1386.7979435444756,"modulo_ruptura_kgcm2":97.66182701017435})]
FMT["RT-FM-38"]=d
d=base("RT-FM-42","Límites de consistencia (Atterberg): LL, LP, IP","ASTM D4318 (Método A multipunto / Método B un punto)",True,"CYCSA-RT-FM-42 HC Limites 2026 (3)(1)(1)(1)(5).xlsm","Plantillas",{"letra":"F","nuevo":False,"nota":"LL/LP/IP alimentan la granulometría de suelo (RT-FM-22 F). Si se requiere informe propio de límites → formato nuevo"},False,
 [{"nombre":"campo","archivo":"Archive (1)/CYCSA-RT-FM-42 HC Limites 2026 (2)(2)(1)(2) campo.xlsm","diferencias":"812 fórmulas distintas a laboratorio"},{"nombre":"CASA MANAGUA","archivo":"Archive (2)/CYCSA-RT-FM-42 HC Limites 2026 CASA MANGUA).xlsm","diferencias":"Idéntico a campo (0 diferencias)"}])
d["encabezado"]=H(C("metodo","Método","lista",opciones=["METODO A","METODO B"]))
d["secciones"]=[S("Límite líquido (3 puntos)","tabla_matriz",[C("golpes","Número de golpes","numero",None,0,esp=True),C("tara_humedo","Tara + húmedo","numero","g",esp=True),C("tara_seco","Tara + seco","numero","g",esp=True),C("tara","Tara","numero","g",esp=True)]),
 S("Límite plástico (2-3 réplicas)","tabla_matriz",[C("lp_tara_humedo","Tara + húmedo","numero","g",esp=True),C("lp_tara_seco","Tara + seco","numero","g",esp=True),C("lp_tara","Tara","numero","g",esp=True)])]
d["constantes"]=[K("exponente_ll","Exponente del factor de un punto",0.121,None,"Datos Limite fila 34")]
d["formulas"]=[F("humedad_ll","({tara_humedo}-{tara_seco})*100/({tara_seco}-{tara})","Humedad del punto LL","%","ROUND(x,2)",True),
 F("ll_por_factor","{humedad_ll}*({golpes}/25)^0.121","LL de un punto = w·(N/25)^0.121","%","ROUND(x,1)",True),
 F("humedad_lp","({lp_tara_humedo}-{lp_tara_seco})*100/({lp_tara_seco}-{lp_tara})","Humedad réplica LP","%","ROUND(x,2)",True),
 F("ip","{ll}-{lp}","Índice de plasticidad (ll y lp son agregaciones)","%","ROUND(x,0)")]
d["agregaciones"]=[{"codigo":"ll","funcion":"promedio","de":"ll_por_factor","descripcion":"Excel: promedio de LL por factor. Método A correcto: regresión w vs log N a 25 golpes (FM42-01)","unidad":"%"},{"codigo":"lp","funcion":"promedio","de":"humedad_lp","unidad":"%"}]
d["criterios_aceptacion"]=[{"descripcion":"Método B: 20 ≤ N ≤ 30","implicito":True},{"descripcion":"Réplicas de LP dentro de la repetibilidad de D4318 (valor a confirmar por el laboratorio; usar ControlCalidadReplicas)","implicito":True}]
d["salidas"]={"tablas":["Informe Limites: LL, LP, IP, método"],"graficos":["Curva de fluidez (w vs log N)"]}
d["hojas_ocultas_relevantes"]=[{"hoja":"Listas (oculta)","contenido":"Analistas y métodos A/B","destino":"listas.json"},{"hoja":"Incert Limites / Incert Humedad (ocultas)","contenido":"Incertidumbre LL/LP/IP y tabla de factores (N/25)^0.121","destino":"módulo incertidumbre"}]
d["correcciones"]=[CORR("FM42-01","Método A calculado como promedio de LL por factor de un punto, incluso con N=15 y 31 (fuera del rango del método B)","Método A: regresión lineal w vs log10(N), LL = w a 25 golpes; Método B: factor solo con 20≤N≤30","ASTM D4318",True),
 CORR("FM42-02","MS-0134-25: réplicas LP 26.10 % y 14.87 % promediadas sin control","Advertir/bloquear si superan la repetibilidad","Control de réplicas",True),CORR("FM42-03","Una fórmula #REF! en Informe Limites","Eliminada","Referencia rota")]
d["caso_prueba"]=[CASO("MS-0134-25","Archive/...FM-42... 'Datos Limite' col E",{"golpes":15,"tara_humedo":33.48,"tara_seco":30.02,"tara":21.8,"lp_tara_humedo":32.26,"lp_tara_seco":30.06,"lp_tara":21.63,"ll":39.678001534078085,"lp":20.48268858223},
 {"humedad_ll":42.092457420924546,"ll_por_factor":39.56950182145056,"humedad_lp":26.097271648873065,"ip":19.19531295},notas="ll = promedio Excel de los 3 LL por factor; lp = promedio de réplicas 26.097/14.868 (FM42-02)")]
FMT["RT-FM-42"]=d
d=base("RT-FM-46","Relación de soporte California (CBR) de laboratorio","AASHTO T193 (citada); ASTM D1883",False,"CYCSA-RT-FM-46 - HC de CBR 2026 (1)(2).xlsx","Plantillas",{"letra":None,"nuevo":True,"nota":"Sin formato RT-FM-22 equivalente: informe nuevo de CBR"},False)
d["encabezado"]=H(C("fecha_ingreso","Fecha de ingreso","fecha"),C("tipo_muestra","Tipo de muestra","texto"),C("pvs_maximo","PVSM del Proctor (RT-FM-27)","numero","kg/m³",0),C("condicion","Condición","lista",opciones=["Saturado 96 horas","Sin saturar"]))
d["secciones"]=[S("Moldes (12/25/56 golpes)","tabla_matriz",[C("golpes","Golpes por capa","numero",None,0,esp=True),C("masa_molde_suelo","Molde + suelo húmedo","numero","g",0,esp=True),C("masa_molde","Molde","numero","g",0,esp=True),C("volumen_molde","Volumen del molde","numero","cm³",0,esp=True),C("masa_molde_suelo_sat","Molde + suelo saturado","numero","g",0,esp=True),
 C("tara_humedo_antes","Tara + húmedo (antes)","numero","g",esp=True),C("tara_seco_antes","Tara + seco (antes)","numero","g",esp=True),C("tara_antes","Tara (antes)","numero","g",esp=True),C("tara_humedo_sat","Tara + húmedo (saturado)","numero","g",esp=True),C("tara_seco_sat","Tara + seco (saturado)","numero","g",esp=True),C("tara_sat","Tara (saturado)","numero","g",esp=True),
 C("lectura_01","Lectura a 0.1 in","numero","div",0,esp=True),C("lectura_02","Lectura a 0.2 in","numero","div",0,esp=True),C("expansion_lectura","Lectura de expansión","numero","div",req=False,esp=True)])]
d["constantes"]=[K("area_piston","Área del pistón",3,"in²","celda L31"),K("constante_anillo","Factor lectura → lbf",10,"lbf/div","fórmula lectura*10","ANILLO-CBR-01"),K("altura_ref_expansion","Divisor de expansión",95,None,"K20 = (lectura−inicial)/95·100")]
d["formulas"]=[F("humedad_antes","({tara_humedo_antes}-{tara_seco_antes})*100/({tara_seco_antes}-{tara_antes})","Humedad de moldeo","%","ROUND(x,1)",True),
 F("humedad_sat","({tara_humedo_sat}-{tara_seco_sat})*100/({tara_seco_sat}-{tara_sat})","Humedad tras saturación","%","ROUND(x,1)",True),
 F("densidad_humeda","({masa_molde_suelo}-{masa_molde})/{volumen_molde}*1000","Densidad húmeda","kg/m³","ROUND(x,0)",True),
 F("densidad_seca","{densidad_humeda}*100/(100+{humedad_antes})","Densidad seca","kg/m³","ROUND(x,0)",True),
 F("compactacion","{densidad_seca}*100/{pvs_maximo}","Compactación reproducida","%","ROUND(x,1)",True),
 F("densidad_seca_sat","({masa_molde_suelo_sat}-{masa_molde})/{volumen_molde}*1000*100/(100+{humedad_sat})","Densidad seca saturada","kg/m³","ROUND(x,0)",True),
 F("esfuerzo_01","{lectura_01}*{constante_anillo}/{area_piston}","Esfuerzo a 0.1 in","psi","ROUND(x,0)",True),
 F("esfuerzo_02","{lectura_02}*{constante_anillo}/{area_piston}","Esfuerzo a 0.2 in","psi","ROUND(x,0)",True),
 F("cbr_01","{esfuerzo_01}/1000*100","CBR a 0.1 in (patrón 1000 psi)","%","ROUND(x,1)",True),
 F("cbr_02","{esfuerzo_02}/1500*100","CBR a 0.2 in (patrón 1500 psi)","%","ROUND(x,1)",True),
 F("cbr","MAX({cbr_01},{cbr_02})","CBR del molde","%","ROUND(x,1)",True)]
d["agregaciones"]=[{"codigo":"cbr_diseno","funcion":"interpolar","de":"cbr vs compactacion","descripcion":"CBR al % de compactación especificado, interpolando entre los 3 moldes","unidad":"%"}]
d["criterios_aceptacion"]=[{"descripcion":"Especificación del proyecto (p.ej. CBR mínimo de base/subbase), parámetro de la orden","implicito":True}]
d["salidas"]={"tablas":["Datos de moldes","Penetración vs esfuerzo","CBR por molde y de diseño"],"graficos":["Esfuerzo–penetración (3 moldes)","CBR vs densidad seca"]}
d["hojas_ocultas_relevantes"]=[]
d["correcciones"]=[CORR("FM46-01","Una hoja por muestra (copias de la plantilla)","Un registro por muestra","Evita copias"),
 CORR("FM46-02","Celdas J41-J47 (CBR) mezclan columnas y factores 0.1/0.0667 sin etiqueta; valores no concuerdan con los esfuerzos","CBR = σ0.1/1000·100 y σ0.2/1500·100, se toma el máximo","AASHTO T193",True),
 CORR("FM46-03","% compactación referido a $K$27 vacío → #DIV/0!","pvs_maximo obligatorio desde RT-FM-27","Dato faltante")]
d["caso_prueba"]=[CASO("MS-1693-26 molde 12 golpes","Archive/...FM-46... hoja 'MS-13962-26' col D/E",{"masa_molde_suelo":12268,"masa_molde":7849,"volumen_molde":3200,"masa_molde_suelo_sat":12345,"tara_humedo_antes":225,"tara_seco_antes":204,"tara_antes":75,"tara_humedo_sat":220,"tara_seco_sat":198,"tara_sat":75,"lectura_01":50,"lectura_02":90,"constante_anillo":10,"area_piston":3},
 {"humedad_antes":16.27906976744186,"humedad_sat":17.88617886178862,"densidad_humeda":1380.9375,"densidad_seca":1187.60625,"densidad_seca_sat":1191.8275862068965,"esfuerzo_01":166.66666666666666,"esfuerzo_02":300,"cbr_01":16.666667,"cbr_02":20.0,"cbr":20.0},notas="cbr_* con fórmula corregida FM46-02")]
FMT["RT-FM-46"]=d
d=base("RT-FM-54","Densidad in situ y grado de compactación por reemplazo de agua","ASTM D5030 (el JSON existente cita D4220)",True,"CYCSA-RT-FM-54 - Hoja de calculo para compactación por reemplazo de agua.xlsx","Hoja de Campo",{"letra":"B","nuevo":False,"nota":"Alimenta formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md (RT-FM-22 B)"},False,
 [{"nombre":"campo","archivo":"Archive (1)/...reemplazo de agua campo.xlsx","diferencias":"0 diferencias de fórmulas; 13 valores (columna G)"},{"nombre":"CASA MANAGUA","archivo":"Archive (2)/...reemplazo de agua CASA MANAUA.xlsx","diferencias":"Idéntico a campo"}])
d["encabezado"]=H(C("capa","Capa N°","numero",None,0),C("profundidad","Profundidad del pozo","numero","cm",0),C("pvs_maximo","PVSM (RT-FM-27)","numero","kg/m³",0))
d["secciones"]=[S("Punto","formulario",[C("diametro_anillo","Diámetro del anillo","numero","m",2),C("vol_agua_anillo","Agua para llenar el anillo","numero","L",1),C("peso_excavado","Material excavado húmedo","numero","kg",2),C("vol_agua_total","Agua pozo + anillo","numero","L",1),C("rec_humedo","Recipiente + húmedo","numero","g"),C("rec_seco","Recipiente + seco","numero","g"),C("recipiente","Recipiente","numero","g")])]
d["constantes"]=[K("anillo","Anillo de densidad",1,"m","fila 13","ANILLO-RA-01")]
d["formulas"]=[F("vol_pozo","{vol_agua_total}-{vol_agua_anillo}","Volumen del pozo","L"),F("densidad_humeda","{peso_excavado}/{vol_pozo}*1000","Peso volumétrico húmedo","kg/m³","ROUND(x,0)"),
 F("humedad","({rec_humedo}-{rec_seco})*100/({rec_seco}-{recipiente})","Humedad","%","ROUND(x,1)"),F("densidad_seca","{densidad_humeda}/(1+{humedad}/100)","Peso volumétrico seco","kg/m³","ROUND(x,0)"),F("compactacion","{densidad_seca}*100/{pvs_maximo}","Grado de compactación","%","ROUND(x,1)")]
d["criterios_aceptacion"]=[{"descripcion":"% compactación ≥ especificación del proyecto","implicito":True}]
d["salidas"]={"tablas":["Puntos de compactación → RT-FM-22 B"],"graficos":[]}
d["hojas_ocultas_relevantes"]=[]
d["correcciones"]=[CORR("FM54-01","Encabezado interno 'CYCSA-RT-FM-52'","RT-FM-54","Código erróneo"),CORR("FM54-02","Columna de ejemplo: 368794 kg y densidad del agua 1000 → 1.5·10⁶ kg/m³ y #DIV/0!","Validación de rangos (variables.minimo/maximo)","Datos basura"),CORR("FM54-03","SUM() innecesario en fórmulas","Expresión directa","Limpieza")]
d["caso_prueba"]=[CASO("MS-0419-26","Archive/...FM-54....xlsx col D",{"vol_agua_anillo":57,"peso_excavado":496.5,"vol_agua_total":315,"rec_humedo":500.89,"rec_seco":450.1,"recipiente":66.11,"pvs_maximo":1730},{"vol_pozo":258,"densidad_humeda":1924.418604651163,"humedad":13.22690695070183,"densidad_seca":1699.6124476746863,"compactacion":98.24349408524199})]
FMT["RT-FM-54"]=d
d=base("RT-FM-55","Densidad in situ y grado de compactación por cono de arena","ASTM D1556 (citada)",False,"CYCSA-RT-FM-55 COMPACTACIONES CON CONO DE ARENA 2026.xlsx","Hoja de Campo",{"letra":"B","nuevo":False,"nota":"Alimenta formato_de_compactacion_por_cono_de_arena.md (RT-FM-22 B)"},False)
d["encabezado"]=H(C("procedencia","Procedencia","texto",req=False),C("coord_norte","Coordenada Norte","numero",None,3,req=False),C("coord_este","Coordenada Este","numero",None,3,req=False),C("capa","Capa","texto"),C("profundidad","Profundidad","texto"),C("pvs_maximo","PVSM (RT-FM-27)","numero","kg/m³",0))
d["secciones"]=[S("Calibración del cono","formulario",[C("arena_calib_empleada","Arena empleada","numero","g",0),C("arena_calib_sobrante","Arena sobrante","numero","g",0)]),
 S("Hoyo","formulario",[C("material_tara","Material excavado + tara","numero","g",0),C("tara","Tara","numero","g",0),C("arena_empleada","Arena empleada","numero","g",0),C("arena_sobrante","Arena sobrante","numero","g",0)]),
 S("Humedad","formulario",[C("rec_humedo","Recipiente + húmedo","numero","g"),C("rec_seco","Recipiente + seco","numero","g"),C("recipiente","Recipiente","numero","g")])]
d["constantes"]=[K("pu_arena","Peso unitario de la arena calibrada",1250,"kg/m³","celda D18","ARENA-CONO-01")]
d["formulas"]=[F("factor_cono","{arena_calib_empleada}-{arena_calib_sobrante}","Arena que llena el cono","g"),F("masa_excavada","{material_tara}-{tara}","Masa excavada","g"),
 F("arena_en_hoyo","{arena_empleada}-{factor_cono}-{arena_sobrante}","Arena en la excavación","g"),F("volumen_hoyo","{arena_en_hoyo}/{pu_arena}","Volumen de excavación","L","ROUND(x,4)"),
 F("densidad_humeda","{masa_excavada}/{volumen_hoyo}","P.V.H. in situ","kg/m³","ROUND(x,0)"),F("humedad","({rec_humedo}-{rec_seco})*100/({rec_seco}-{recipiente})","Humedad","%","ROUND(x,1)"),
 F("densidad_seca","{densidad_humeda}*100/({humedad}+100)","P.V.S. in situ","kg/m³","ROUND(x,0)"),F("compactacion","{densidad_seca}*100/{pvs_maximo}","% compactación","%","ROUND(x,1)")]
d["criterios_aceptacion"]=[{"descripcion":"% compactación ≥ especificación del proyecto","implicito":True}]
d["salidas"]={"tablas":["Puntos → RT-FM-22 B"],"graficos":[]}
d["hojas_ocultas_relevantes"]=[]
d["correcciones"]=[CORR("FM55-01","P.V.H. in situ (E41) tecleado 1403 en lugar de fórmula","masa_excavada / volumen_hoyo (=1403.24)","Valor manual"),CORR("FM55-02","Peso seco (E49) tecleado","rec_seco − recipiente","Coincide en el caso (171)"),
 CORR("FM55-03","Hojas con nombre de proyecto ('COMPACTACIONES EN WIWILI') y código de formato ajeno (DCA-SGP-F-DAM-LES7-001); 13-15 celdas con error","Proyecto desde la orden","Plantilla genérica")]
d["caso_prueba"]=[CASO("Punto PCI perímetro este","Archive/...FM-55... primera hoja col E",{"arena_calib_empleada":3000,"arena_calib_sobrante":1586,"material_tara":3156,"tara":15,"arena_empleada":5000,"arena_sobrante":788,"pu_arena":1250,"rec_humedo":257,"rec_seco":225,"recipiente":54,"pvs_maximo":1204},
 {"factor_cono":1414,"masa_excavada":3141,"arena_en_hoyo":2798,"volumen_hoyo":2.2384,"humedad":18.71345029239766,"densidad_humeda":1403.234453,"densidad_seca":1182.0386,"compactacion":98.17596},notas="densidad con FM55-01; Excel con 1403 tecleado: 1181.84 y 98.159 %")]
FMT["RT-FM-55"]=d
d=base("RT-FM-56","Calibración de picnómetros (balones) para gravedad específica de suelos","ASTM D854 (calibración del picnómetro)",True,"CYCSA-RT-FM-56 Gravedad Especifica del suelos 2026.xlsx","Equipos y Calibracion",{"letra":None,"nuevo":True,"nota":"No genera informe a cliente; produce el volumen calibrado y la curva Mpw,t por balón"},False)
d["encabezado"]=[C("balon_id","Picnómetro / balón","lista",ayuda="equipos_calibracion picnometros"),C("fecha_calibracion","Fecha de calibración","fecha"),C("realizado_por","Responsable (usuario)","lista")]
d["secciones"]=[S("Réplicas (3-5)","tabla_matriz",[C("masa_vacio","Picnómetro vacío","numero","g",esp=True),C("masa_lleno","Picnómetro + agua","numero","g",esp=True),C("temperatura","Temperatura del agua","numero","°C",1,esp=True)])]
d["constantes"]=[]
d["formulas"]=[F("densidad_agua","1.00034038-0.00000777*{temperatura}-0.00000495*{temperatura}^2","Densidad del agua a T","g/mL","ROUND(x,5)",True),
 F("masa_agua","{masa_lleno}-{masa_vacio}","Masa de agua","g",None,True),F("volumen","{masa_agua}/{densidad_agua}","Volumen calibrado","mL","ROUND(x,2)",True)]
d["agregaciones"]=[{"codigo":"volumen_promedio","funcion":"promedio","de":"volumen","unidad":"mL"},{"codigo":"volumen_desv","funcion":"desv_est","de":"volumen","unidad":"mL"},{"codigo":"curva_mpw","funcion":"regresion_cuadratica","de":"masa_lleno vs temperatura","descripcion":"Mpw,t = a·T² + b·T + c (Excel: 0.0006T² − 0.0945T + 277.93 en un balón) guardada en el equipo"}]
d["criterios_aceptacion"]=[{"descripcion":"Desviación estándar del volumen ≤ 0.05 mL (D854)","expresion_validacion":"{volumen_desv} <= 0.05","implicito":True}]
d["salidas"]={"tablas":["Registro de calibración por balón"],"graficos":["Mpw vs temperatura"]}
d["hojas_ocultas_relevantes"]=[{"hoja":"Hoja1 (visible)","contenido":"Notas de calibración de balones 3+ (termómetro Eq-2136)","destino":"equipos_calibracion"}]
d["correcciones"]=[CORR("FM56-01","Cada réplica usa la densidad de otra temperatura ($B$29/$B$39/$B$48 = 27.9/28.9/29.8 °C) aunque el encabezado dice 27.9 °C","Cada réplica registra su temperatura","Inconsistencia",True),CORR("FM56-02","Desviación sobre 3 filas fijas; balón -09 con 12 filas ocultas","Agregación sobre réplicas capturadas","Robustez"),CORR("FM56-03","No hay hoja de cálculo de Gs de muestras","Plantilla Gs (D854) a definir con el laboratorio","Falta",True)]
d["caso_prueba"]=[CASO("Balón 3 réplica 1","Archive/...FM-56... 'Balon -03' fila 11 (T de B29 = 27.9 °C)",{"masa_vacio":183.49,"masa_lleno":680.7,"temperatura":27.9},{"densidad_agua":0.99627042,"masa_agua":497.21,"volumen":499.0713026430246})]
FMT["RT-FM-56"]=d
d=base("RT-FM-57","Resistencia a compresión de cubos de mortero","ASTM C109",True,"CYCSA-RT-FM-57 HC Cubo de Mortero 2026 (...).xlsx","Laboratorio Roturas",{"letra":"A","nuevo":False,"nota":"Alimenta resistencia_de_mortero.md (RT-FM-22 A)"},True)
d["encabezado"]=H(C("fecha_fabricacion","Fecha de fabricación","fecha"),C("resistencia_diseno_psi","Diseño de mortero","numero","lbf/in²",0,req=False))
d["secciones"]=[S("Cubo","formulario",[C("alto","Alto","numero","cm",1),C("largo","Largo","numero","cm",1),C("ancho","Ancho","numero","cm",1),C("masa","Peso","numero","kg",3),C("carga_lbf","Carga","numero","lbf",0)])]
d["constantes"]=[K("lb_por_kg","Conversión (S6)",2.202,"lb/kg","fila 6"),K("cm2_por_in2","(2.54)² (R6)",6.4516,None,"fila 6")]
d["formulas"]=[F("area_in2","{ancho}*{largo}/6.4516","Área","in²","ROUND(x,2)"),F("resistencia_psi","ROUND({carga_lbf}/{area_in2}/10,0)*10","Resistencia redondeada a 10 psi","lbf/in²"),
 F("carga_kg","{carga_lbf}/2.202","Carga","kg","ROUND(x,0)"),F("resistencia_kgcm2","{resistencia_psi}/(2.202*6.45)","Resistencia","kg/cm²","ROUND(x,1)"),
 F("volumen_cm3","{area_in2}*6.4516*{alto}","Volumen","cm³"),F("peso_volumetrico","{masa}*1000000/{volumen_cm3}","Peso volumétrico","kg/m³","ROUND(x,0)")]
d["agregaciones"]=[{"codigo":"resistencia_promedio_psi","funcion":"promedio","de":"resistencia_psi","descripcion":"Por edad (3 cubos)","unidad":"lbf/in²"}]
d["criterios_aceptacion"]=[{"descripcion":"C109: rango de cubos de la misma edad dentro del límite de la norma","implicito":True}]
d["salidas"]={"tablas":["Resultados por edad → RT-FM-22 A"],"graficos":["Resistencia vs edad"]}
d["hojas_ocultas_relevantes"]=[]
d["correcciones"]=[CORR("FM57-01","Hoja 'cilindros' con ~15 700 filas, 7 columnas ocultas y 11 443 celdas con error","Un registro por cubo en especimenes/roturas","Estructura"),CORR("FM57-02","kg/cm² sobre psi ya redondeado y con 2.202·6.45 (=14.203)","Se mantiene; recomendado psi sin redondear / 14.2233","Doble redondeo",True),CORR("FM57-03","Códigos tecleados en formatos mixtos; Excel convirtió '7193-01-25' en fecha","codigo desde especimenes","Integridad")]
d["caso_prueba"]=[CASO("MS-7191-25","Archive/...FM-57... 'cilindros' fila 8",{"alto":5.3,"largo":5.3,"ancho":5.3,"masa":0.25,"carga_lbf":3941},{"area_in2":4.353958707917416,"resistencia_psi":910,"carga_kg":1789.7366030881017,"resistencia_kgcm2":64.07142203352836,"peso_volumetrico":1679.238566064604})]
FMT["RT-FM-57"]=d
d=base("RT-FM-58","Sanidad de agregados por intemperismo acelerado (sulfatos)","ASTM C88",True,"CYCSA-RT-FM-58 HOJA DE CALCULO INTERPERISMO_ACELERADO_2026.xlsx","Plantillas",{"letra":None,"nuevo":True,"nota":"Podría ser fila de RT-FM-22 G; por ahora informe nuevo"},False)
d["encabezado"]=H(C("fraccion","Fracción","lista",opciones=["Agregado grueso","Agregado fino"]),C("solucion","Solución","lista",opciones=["Sulfato de sodio","Sulfato de magnesio"]),C("ciclos","Ciclos","numero",None,0))
d["secciones"]=[S("Fracciones","tabla_matriz",[C("tamano","Fracción","texto",esp=True),C("retenido_original","Retenido en la gradación original","numero","g",esp=True),C("masa_inicial","Masa inicial","numero","g",esp=True),C("masa_final","Masa final retenida","numero","g",esp=True)])]
d["constantes"]=[]
d["formulas"]=[F("perdida_masa","{masa_inicial}-{masa_final}","Pérdida","g",None,True),F("perdida_pct","({masa_inicial}-{masa_final})*100/{masa_inicial}","Pérdida por fracción","%","ROUND(x,2)",True),
 F("gradacion_pct","{retenido_original}*100/{total_retenido_original}","% de la fracción en la gradación original","%","ROUND(x,2)",True),F("perdida_corregida","{perdida_pct}*{gradacion_pct}/100","Pérdida ponderada","%","ROUND(x,2)",True)]
d["agregaciones"]=[{"codigo":"total_retenido_original","funcion":"suma","de":"retenido_original","unidad":"g"},{"codigo":"perdida_total","funcion":"suma","de":"perdida_corregida","unidad":"%"}]
d["criterios_aceptacion"]=[{"descripcion":"ASTM C33: pérdida ponderada máxima según solución y fracción (Na₂SO₄ 12 % grueso / 10 % fino; MgSO₄ 18 % / 15 %)","implicito":True}]
d["salidas"]={"tablas":["Fracciones con pérdida ponderada y total"],"graficos":[]}
d["hojas_ocultas_relevantes"]=[{"hoja":"Hoja1 (visible)","contenido":"Borrador del mismo cálculo y masas de ensayo por tamaño (1000/500/670/330/300 g)","destino":"listas.json masas_ensayo_c88"}]
d["correcciones"]=[CORR("FM58-01","Fracción 1½\"–¾\" capturada en kg (1.005/0.988) y el resto en g","Unidad única g","Mezcla de unidades",True),CORR("FM58-02","Fracción 3/8\"–No.4 con valores de relleno 1/1","Fracción opcional","Datos ficticios")]
d["caso_prueba"]=[CASO("MS-5491 fracción 2½\"–1½\"","Archive/...FM-58... 'interperismo Agregado grueso'",{"retenido_original":4767,"total_retenido_original":6287,"masa_inicial":4767,"masa_final":4621},{"perdida_masa":146,"perdida_pct":3.0627228865114327,"gradacion_pct":75.82312708764117,"perdida_corregida":2.3222522665818355})]
FMT["RT-FM-58"]=d
d=base("RT-FM-59","Número de rebote con esclerómetro (martillo suizo) y resistencia estimada","ASTM C805",True,"CYCSA-RT-FM-59 HOJA DE CALCULO MARTILLO SUIZO (2).xlsx","Plantillas",{"letra":"D","nuevo":False,"nota":"Alimenta resistencia_de_martillo_suizo.md (RT-FM-22 D)"},False)
d["encabezado"]=H(C("elemento","Elemento ensayado","texto"),C("resistencia_diseno_psi","Diseño","numero","lbf/in²",0,req=False),C("posicion","Posición","lista",opciones=["A","B","C"],ayuda="listas.json posiciones_esclerometro"),C("esclerometro_id","Esclerómetro","lista"))
d["secciones"]=[S("Lecturas (10)","formulario",[C(f"rebote_{i}",f"Rebote {i}","numero",None,0) for i in range(1,11)])]
d["constantes"]=[K("coef_a","Coeficiente a según posición",-0.0431,None,"datos equipo B25:E27","ESCL-01"),K("coef_b","Coeficiente b",5.8982,None,"idem","ESCL-01"),K("coef_c","Coeficiente c",-51.093,None,"idem","ESCL-01"),K("coef_d","Coeficiente d",319.54,None,"idem","ESCL-01"),K("factor_calibracion","Factor de calibración (yunque)",0.976,None,"datos Mx fila 19","ESCL-01")]
prom="("+"+".join(f"{{rebote_{i}}}" for i in range(1,11))+")/10"
d["formulas"]=[F("rebote_promedio",prom,"Promedio de 10 rebotes",None,"ROUND(x,0)"),F("limite_inferior","{rebote_promedio}-6","Límite inferior"),F("limite_superior","{rebote_promedio}+6","Límite superior"),
 F("resistencia_psi","({coef_a}*{rebote_promedio}^3+{coef_b}*{rebote_promedio}^2+{coef_c}*{rebote_promedio}+{coef_d})*{factor_calibracion}","Resistencia estimada (curva del fabricante × factor)","lbf/in²","ROUND(x,0)"),F("resistencia_kgcm2","{resistencia_psi}/14.2","Resistencia estimada","kg/cm²","ROUND(x,0)")]
d["criterios_aceptacion"]=[{"descripcion":"C805: descartar lecturas que difieran más de 6 unidades del promedio; si más de 2 se descartan, descartar el conjunto","implicito":False}]
d["salidas"]={"tablas":["Rebote promedio y resistencia estimada ± U"],"graficos":["Curva del fabricante por posición"]}
d["hojas_ocultas_relevantes"]=[{"hoja":"datos equipo / PRESUPUESTO / Lista (visibles)","contenido":"Curva 1500-7000 psi por posición, coeficientes cúbicos, curva de dispersión para U, conversiones","destino":"equipos_calibracion ESCL-01"}]
d["correcciones"]=[CORR("FM59-01","Resistencia con IFS() por posición","El servicio carga coef_a..d del catálogo según 'posicion'","EvaluadorFormula no tiene IF"),CORR("FM59-02","Las lecturas fuera de ±6 no se descartan","Recalcular promedio excluyéndolas (C805)","Norma",True),CORR("FM59-03","614 celdas con error en columnas vacías","N/A","Plantilla")]
d["caso_prueba"]=[CASO("MS-957-26 posición A","Archive/...FM-59... 'datos Mx' col C",dict({f"rebote_{i}":v for i,v in enumerate([22,23,20,23,20,22,24,23,21,22],1)},coef_a=-0.0431,coef_b=5.8982,coef_c=-51.093,coef_d=319.54,factor_calibracion=0.976),{"rebote_promedio":22,"limite_inferior":16,"limite_superior":28,"resistencia_psi":1553.1029439999995})]
FMT["RT-FM-59"]=d
d=base("RT-FM-61","Resistencia a compresión de cilindros de concreto (revenimiento, temperatura, densidad, incertidumbre)","ASTM C39 / C143 / C1064 / C31",True,"CYCSA-RT-FM-61HOJA DE CALCULO CILINDRO 2026 central.xlsm","Laboratorio Roturas",{"letra":"A","nuevo":False,"nota":"Alimenta resistencia_de_concreto.md y formato_de_reveniemiento_y_temperatura.md (RT-FM-22 A) vía roturas / informes_rotura"},True,
 [{"nombre":"campo","archivo":"Archive (1)/CYCSA-RT-FM-61 HC Resistencia de Cilindro2026 (...)(3).xlsx","diferencias":"Plantilla ANTIGUA tabular 'cilindros' (2023, fila por espécimen, sin polinomio de prensa ni incertidumbre)"},{"nombre":"CASA MANAGUA","archivo":"Archive (2)/CYCSA-RT-FM-61 HC Resistencia de Cilindro2026 (...).xlsx","diferencias":"Igual a campo (1 fórmula distinta)"}])
d["encabezado"]=H(C("tipo_especimen","Tipo de espécimen","lista",ayuda="listas.json tipos_especimen"),C("fecha_moldeo","Fecha de elaboración","fecha",ayuda="especimenes.fecha_moldeo"),C("fecha_rotura","Fecha de rotura","fecha"),C("edad_dias","Edad (fecha_rotura − fecha_moldeo)","calculado","días",0),C("resistencia_diseno_psi","f'c de diseño","numero","lbf/in²",0),C("prensa_id","Máquina de fuerza","lista",ayuda="PRENSA-01"))
d["secciones"]=[S("Concreto fresco","formulario",[C("revenimiento_cm","Revenimiento","numero","cm",1,req=False),C("temperatura_c","Temperatura del concreto","numero","°C",1,req=False)]),
 S("Espécimen","formulario",[C("diametro_1","Diámetro 1","numero","cm",2),C("diametro_2","Diámetro 2","numero","cm",2),C("largo_1","Longitud 1","numero","cm",2),C("largo_2","Longitud 2","numero","cm",2),C("masa_kg","Masa","numero","kg",3),C("tipo_falla","Tipo de ruptura","lista",ayuda="listas.json tipos_falla_c39"),C("lectura_carga_lbf","Lectura de carga del equipo","numero","lbf",0)])]
d["constantes"]=[K("prensa_a_e14","Coef. a ×10¹⁴",-4.6765,None,"U Carga F20 = -4.6765E-14","PRENSA-01"),K("prensa_b_e8","Coef. b ×10⁸",2.5071,None,"U Carga G20 = 2.5071E-08","PRENSA-01"),K("prensa_c","Coef. c",0.99583,None,"U Carga H20","PRENSA-01"),K("prensa_d","Coef. d",3.7217,"lbf","U Carga I20","PRENSA-01"),
 K("ld_a","Coef. a factor L/D",0.2133,None,"CONVERT Y LISTA G37","FACTOR-LD-C39"),K("ld_b","Coef. b",-1.04,None,"G38","FACTOR-LD-C39"),K("ld_c","Coef. c",1.7667,None,"G39","FACTOR-LD-C39"),K("ld_d","Coef. d",-0.07,None,"G40","FACTOR-LD-C39"),K("psi_por_kgcm2","Conversión del laboratorio",14.2,None,"CALCULO fila 70 y export MS-9574")]
d["formulas"]=[F("revenimiento_in","{revenimiento_cm}/2.54","Revenimiento","in","ROUND(x,1)"),
 F("diametro_prom","({diametro_1}+{diametro_2})/2","Diámetro promedio","cm","ROUND(x,2)"),F("largo_prom","({largo_1}+{largo_2})/2","Longitud promedio","cm","ROUND(x,2)"),F("relacion_ld","{largo_prom}/{diametro_prom}","Relación L/D",None,"ROUND(x,2)"),
 F("factor_ld","1-CEIL(MAX(0,MIN(1,1.75-{relacion_ld})))*(1-({ld_a}*{relacion_ld}^3+{ld_b}*{relacion_ld}^2+{ld_c}*{relacion_ld}+{ld_d}))","Factor L/D: 1 si L/D ≥ 1.75; polinomio si L/D < 1.75 (C39). Sin IF: CEIL(MAX(0,MIN(1,1.75−L/D))) vale 1 o 0",None,"ROUND(x,3)"),
 F("area_1_cm2","PI()*{diametro_1}^2/4","Área con diámetro 1","cm²"),F("area_2_cm2","PI()*{diametro_2}^2/4","Área con diámetro 2","cm²"),F("area_cm2","({area_1_cm2}+{area_2_cm2})/2","Área promedio","cm²","ROUND(x,2)"),F("area_in2","{area_cm2}/6.4516","Área","in²","ROUND(x,2)"),
 F("volumen_cm3","({area_1_cm2}*{largo_1}+{area_2_cm2}*{largo_2})/2","Volumen promedio","cm³"),F("densidad","({masa_kg}*1000000/({area_1_cm2}*{largo_1})+{masa_kg}*1000000/({area_2_cm2}*{largo_2}))/2","Densidad","kg/m³","ROUND(x,0)"),
 F("carga_real_lbf","{prensa_a_e14}*({lectura_carga_lbf}/100000)^3*10+{prensa_b_e8}*({lectura_carga_lbf}/10000)^2+{prensa_c}*{lectura_carga_lbf}+{prensa_d}","Carga corregida por calibración (polinomio cúbico con coeficientes escalados)","lbf","ROUND(x,0)"),
 F("carga_real_kg","{carga_real_lbf}/2.2046","Carga","kg","ROUND(x,0)"),
 F("esfuerzo_psi","{carga_real_lbf}*{factor_ld}/{area_in2}","Resistencia a compresión → roturas.esfuerzo_psi","lbf/in²"),F("esfuerzo_psi_reportado","ROUND({esfuerzo_psi}/10,0)*10","Resistencia redondeada a 10 psi","lbf/in²"),
 F("esfuerzo_kgcm2","{esfuerzo_psi_reportado}/14.2","Resistencia → roturas.esfuerzo_kgcm2","kg/cm²","ROUND(x,0)"),F("porcentaje_fc","{esfuerzo_psi_reportado}*100/{resistencia_diseno_psi}","% de f'c → roturas.porcentaje_fc","%","ROUND(x,1)")]
d["criterios_aceptacion"]=[{"descripcion":"ACI 318: promedio de 3 pruebas consecutivas ≥ f'c y ningún resultado < f'c − 500 psi (f'c ≤ 5000) o < 0.9·f'c (f'c > 5000); evaluar en el consolidado","implicito":True},{"descripcion":"Tolerancia de edad de rotura (C39) → plan_rotura_edades.tolerancia_horas","implicito":False}]
d["salidas"]={"tablas":["REPORTE: datos generales, resultado, R ± U","Informe parcial por edad y consolidado (informes_rotura)"],"graficos":["REPORTE GRAFICO H: resistencia vs edad"]}
d["hojas_ocultas_relevantes"]=[{"hoja":"U resistencia (oculta)","contenido":"Repetibilidad, Vernier, combinación de incertidumbre","destino":"módulo de incertidumbre (fase posterior)"},{"hoja":"CONVERT Y LISTA (oculta)","contenido":"Conversor de unidades, tipos de espécimen, técnicos, tipos de ruptura 1-6, polinomio L/D","destino":"listas.json + FACTOR-LD-C39"},{"hoja":"U Temp / U revenimiento / U Area (ocultas)","contenido":"Incertidumbre termómetro EQ-233, cono de revenimiento, calibrador","destino":"equipos_calibracion"},{"hoja":"U Carga (visible)","contenido":"Polinomio de la prensa y U por punto de calibración","destino":"PRENSA-01"}]
d["correcciones"]=[CORR("FM61-01","El factor L/D se calcula (fila 34) pero nunca se aplica; el polinomio da > 1 para L/D > 1.75","Aplicar solo si L/D < 1.75; 1 en otro caso","ASTM C39",True),
 CORR("FM61-02","Área en in² con /6.45 (fila 39) y /6.4516 (fila 62)","6.4516 en todo el cálculo","psi +0.025 % (7311.87 → 7313.69)"),
 CORR("FM61-03","Coeficientes de la prensa en notación científica","Constantes escaladas (×10¹⁴, ×10⁸)","EvaluadorFormula no tokeniza 'E'"),
 CORR("FM61-04","kg/cm² = promedio(carga/área1, carga/área2); psi = carga/área promedio","Un único esfuerzo; kg/cm² = psi/14.2","Coherencia",True),
 CORR("FM61-05","Campo/CASA usan plantilla 2023 sin polinomio de prensa","Solo plantilla 'central'","Unificación")]
CE={"prensa_a_e14":-4.6765,"prensa_b_e8":2.5071,"prensa_c":0.99583,"prensa_d":3.7217,"ld_a":0.2133,"ld_b":-1.04,"ld_c":1.7667,"ld_d":-0.07}
d["caso_prueba"]=[CASO("MS-0213-26 (cilindro 4x8, 3 días)","Archive/CYCSA-RT-FM-61HOJA DE CALCULO CILINDRO 2026 central.xlsm 'CALCULO' col B",dict(CE,revenimiento_cm=14.5,diametro_1=10.1,diametro_2=10.2,largo_1=20.2,largo_2=20.2,masa_kg=4.02,lectura_carga_lbf=91932),
 {"revenimiento_in":5.708661417322834,"diametro_prom":10.15,"relacion_ld":1.9901477832512318,"factor_ld":1,"area_1_cm2":80.1184666481737,"area_2_cm2":81.71282491987051,"area_cm2":80.9156457840221,"volumen_cm3":1634.4960448372462,"densidad":2459.712421856533,"carga_real_lbf":91727.91797465623,"carga_real_kg":41607.510648034215,"esfuerzo_psi":7313.69,"esfuerzo_psi_reportado":7310},notas="esfuerzo_psi con /6.4516 (FM61-02); Excel 7311.87"),
 CASO("MS-9574-26 export (3 días)","Archive (2)/MS-9574-9578-26.xls",{"area_in2":12.42,"carga_real_lbf":63492,"factor_ld":1,"resistencia_diseno_psi":6000},{"esfuerzo_psi":5112.077,"esfuerzo_psi_reportado":5110,"esfuerzo_kgcm2":359.859,"porcentaje_fc":85.1667},notas="El export reporta 5110 psi y 360 kg/cm²"),
 CASO("MS-9577-26 export (28 días)","idem",{"area_in2":12.42,"carga_real_lbf":82069,"factor_ld":1,"resistencia_diseno_psi":6000},{"esfuerzo_psi_reportado":6610,"esfuerzo_kgcm2":465.493,"porcentaje_fc":110.1667},notas="Export: 6610 psi / 465 kg/cm²"),
 CASO("MS-9578-26 export (56 días)","idem",{"area_in2":12.42,"carga_real_lbf":84230,"factor_ld":1,"resistencia_diseno_psi":6000},{"esfuerzo_psi_reportado":6780,"esfuerzo_kgcm2":477.465},notas="Export: 6780 psi / 477 kg/cm²")]
FMT["RT-FM-61"]=d
d=base("RT-FM-62","Infiltración / percolación de suelo","Sin norma citada (fórmula tipo prueba de percolación)",True,"CYCSA-RT-FM-62 HC infiltración 2026 (2).xls","Hoja de Campo",{"letra":None,"nuevo":True,"nota":"Sin formato RT-FM-22 equivalente"},False)
d["encabezado"]=H(C("ubicacion","Ubicación del pozo","texto",req=False))
d["secciones"]=[S("Descenso acumulado","formulario",[C(f"descenso_{m}min",f"Descenso acumulado a {m} min","numero","cm",1) for m in (5,10,15,20,25,30)])]
d["constantes"]=[K("coef_percolacion","Coeficiente",315.5,None,"L12 = 315.5*((AVERAGE(J12:K12)/300))^0.5"),K("lm2_por_galft2","L/m²·día → gal/ft²·día",40.77,None,"Hoja1")]
d["formulas"]=[F("dif_25","{descenso_25min}-{descenso_20min}","Descenso 20–25 min","cm"),F("dif_30","{descenso_30min}-{descenso_25min}","Descenso 25–30 min","cm"),
 F("tasa_lm2_dia","315.5*SQRT((({dif_25}+{dif_30})/2)/300)","Tasa Q con el promedio de los dos últimos intervalos","L/m²·día","ROUND(x,2)"),F("tasa_galft2_dia","{tasa_lm2_dia}/40.77","Tasa G","gal/ft²·día","ROUND(x,3)")]
d["criterios_aceptacion"]=[{"descripcion":"No definidos en el Excel; solicitar","implicito":True}]
d["salidas"]={"tablas":["Lecturas, diferencias, Q y G"],"graficos":["Descenso vs tiempo (opcional)"]}
d["hojas_ocultas_relevantes"]=[{"hoja":"Hoja1 (visible)","contenido":"Derivación de 40.77 (1 gal = 3.785 L; 1 m = 3.282 ft)","destino":"constante lm2_por_galft2"}]
d["correcciones"]=[CORR("FM62-01","Q = 315.5·√(prom. últimas 2 diferencias / 300) sin fuente ni unidades","Se conserva; documentar fuente de 315.5 y 300","Trazabilidad",True),CORR("FM62-02","1 m = 3.282 ft (correcto 3.2808)","Se mantiene 40.77; correcto ≈ 40.75","0.06 %",True),CORR("FM62-03","Lecturas incoherentes (MS-2925-26: 1.17 → 79 → 450 cm)","Validación de rango y monotonía","Error de captura / unidades",True)]
d["caso_prueba"]=[CASO("MS-1315-26","Archive/...FM-62...xls filas 11-12 (fórmulas vía LibreOffice)",{f"descenso_{m}min":v for m,v in zip((5,10,15,20,25,30),(2.5,3,4,5.5,6.7,8.2))},{"dif_25":1.2,"dif_30":1.5,"tasa_lm2_dia":21.164383407035505,"tasa_galft2_dia":0.5191165908029312}),
 CASO("MS-11129-26","filas 23-24",{f"descenso_{m}min":v for m,v in zip((5,10,15,20,25,30),(3,4.5,6,7.2,8.5,10.2))},{"tasa_lm2_dia":22.30921894643557,"tasa_galft2_dia":0.5471969327062931})]
FMT["RT-FM-62"]=d
d=base("RT-FM-63","Adoquines de concreto: resistencia a compresión y absorción","NTON 12 008-16 / ASTM C140 (según resistencia_de_adoquines.md)",True,"CYCSA-RT-FM-63 HOJA DE CALCULO ADOQUINES 2026 (2)(3)(1).xlsx","Laboratorio Roturas",{"letra":"A","nuevo":False,"nota":"Alimenta resistencia_de_adoquines.md (RT-FM-22 A)"},True)
d["encabezado"]=H(C("acabado","Acabado","lista",opciones=["Liso","Biselado"]),C("resistencia_diseno_psi","Diseño","numero","lbf/in²",0,req=False))
d["secciones"]=[S("Dimensiones (3 lecturas)","formulario",[C(f"{n}_{i}",f"{e} lectura {i}","numero","cm",1) for n,e in (("largo","Largo"),("ancho","Ancho"),("altura","Altura"),("base_mayor","Base mayor"),("base_menor","Base menor")) for i in (1,2,3)]),
 S("Carga","formulario",[C(f"carga_{i}",f"Carga lectura {i}","numero","lbf",0) for i in (1,2,3)]),
 S("Absorción (3 especímenes)","tabla_matriz",[C("masa_recibida","Masa al recibir","numero","kg",esp=True,req=False),C("masa_seca","Masa seca","numero","kg",esp=True,req=False),C("masa_sumergida","Masa sumergida","numero","kg",esp=True,req=False),C("masa_saturada","Masa saturada","numero","kg",esp=True,req=False)])]
d["constantes"]=[K("factor_acabado","Factor liso (1) / biselado (1.16)",1.16,None,"Datos fila 41"),K("cm2_por_in2_excel","Conversión usada",6.45,None,"fila 36")]
d["formulas"]=[F("largo_prom",av("largo"),"Largo promedio","cm"),F("ancho_prom",av("ancho"),"Ancho promedio","cm"),F("altura_prom",av("altura"),"Altura promedio","cm"),F("base_mayor_prom",av("base_mayor"),"Base mayor promedio","cm"),F("base_menor_prom",av("base_menor"),"Base menor promedio","cm"),
 F("area_neta_cm2","{largo_prom}*{ancho_prom}+2*(({base_mayor_prom}+{base_menor_prom})/2)*{altura_prom}","Área neta según columnas D/E (sin /2) — DUDOSA","cm²","ROUND(x,2)"),F("area_neta_in2","{area_neta_cm2}/6.45","Área neta","in²","ROUND(x,2)"),
 F("carga_prom",av("carga"),"Carga promedio","lbf","ROUND(x,0)"),F("resistencia_psi","{carga_prom}/{area_neta_in2}*{factor_acabado}","Resistencia","lbf/in²","ROUND(x,0)"),F("resistencia_kgcm2","{resistencia_psi}/14.2","Resistencia","kg/cm²","ROUND(x,0)"),
 F("absorcion_kgm3","({masa_saturada}-{masa_seca})/({masa_saturada}-{masa_sumergida})*1000","Absorción","kg/m³","ROUND(x,0)",True),F("absorcion_pct","({masa_saturada}-{masa_seca})*100/{masa_seca}","Absorción","%","ROUND(x,1)",True)]
d["criterios_aceptacion"]=[{"descripcion":"NTON 12 008-16 / ASTM C936: resistencia y absorción por tipo de tráfico; tabla no presente en el Excel, solicitar","implicito":True}]
d["salidas"]={"tablas":["Calculo: código, área neta, resistencia, absorción"],"graficos":[]}
d["hojas_ocultas_relevantes"]=[{"hoja":"Dimensiones del adoquin (visible)","contenido":"Esquema para el área del adoquín","destino":"texto de ayuda"},{"hoja":"Hoja1 (visible)","contenido":"vacía","destino":"descartar"}]
d["correcciones"]=[CORR("FM63-01","Área neta: columnas B y C dividen entre 2, D y E no","Se adopta D/E (sin /2) provisionalmente; CONFIRMAR geometría (la fórmula suma planta + caras laterales)","Inconsistencia de fórmula",True),
 CORR("FM63-02","Resistencia en col B usa solo la lectura 1 (B26) y no el promedio","Promedio de 3 lecturas","Error de referencia",True),
 CORR("FM63-03","Pesos de absorción idénticos en todas las columnas (son los del bloque FM-36)","Captura por muestra","Datos copiados",True),CORR("FM63-04","107 celdas con error; 19 filas ocultas","N/A","Plantilla")]
d["caso_prueba"]=[CASO("MS-10108-26 (biselado)","Archive/...FM-63... 'Datos' col D",dict(largo_1=25.2,largo_2=25.2,largo_3=25.2,ancho_1=15.2,ancho_2=15.2,ancho_3=15.2,altura_1=3.7,altura_2=3.7,altura_3=3.7,base_mayor_1=13.5,base_mayor_2=13.4,base_mayor_3=13.5,base_menor_1=10.1,base_menor_2=10.1,base_menor_3=10.1,carga_1=201240,carga_2=200612,carga_3=205495,factor_acabado=1.16),
 {"area_neta_cm2":470.23666666666657,"area_neta_in2":72.9049095607235,"carga_prom":202449,"resistencia_psi":3221.1937634240917,"resistencia_kgcm2":226.84463122704872})]
FMT["RT-FM-63"]=d
d=base("RT-FM-64","Ensayo de tensión de varillas de acero de refuerzo","ASTM A370 / E8 / A615 (procedimiento CYCSA-PE-61)",True,"CYCSA-RT-FM-64-HOJA DE CALCULO PARA MÉTODOS DE PRUEBAS ESTÁNDAR DE TENSIÓN PARA MATERIALES METÁLICOS (2).xlsx","Laboratorio Roturas",{"letra":None,"nuevo":True,"nota":"Sin formato RT-FM-22 equivalente: informe nuevo de acero"},False)
d["encabezado"]=H(C("numero_varilla","No. de varilla (in nominal)","lista",ayuda="listas.json varillas"),C("grado","Grado A615","lista",req=False,opciones=["40","60","75"]))
d["secciones"]=[S("Medición","formulario",[C("diametro_mm","Diámetro inicial","numero","mm",2),C("masa_kg","Peso de la varilla","numero","kg",3),C("longitud_cm","Largo de la varilla","numero","cm",1),C("espaciamiento_corruga","Espaciamiento de corruga","numero","mm",2),C("altura_corruga","Altura de corruga","numero","mm",2),
 C("longitud_calibrada_cm","Longitud calibrada entre marcas (L0)","numero","cm",1),C("longitud_final_cm","Largo final de la varilla","numero","cm",2),C("longitud_final_marcas_cm","Longitud final entre marcas (Lf) – NUEVO","numero","cm",2,req=False),C("diametro_final_mm","Diámetro final (estricción)","numero","mm",2)]),
 S("Ensayo","formulario",[C("fuerza_max_kn","Fuerza máxima Fm","numero","kN",2),C("fluencia_mpa","Fluencia Rel","numero","MPa",2)])]
d["constantes"]=[K("lbf_por_kn","Conversión",224.809,None,"fila 23"),K("psi_por_mpa","Conversión",145.038,None,"RESULTADOS fila 10"),K("kgcm2_por_psi","Conversión",0.07031,None,"RESULTADOS fila 9")]
d["formulas"]=[F("peso_unitario","{masa_kg}/({longitud_cm}/100)","Peso unitario","kg/m","ROUND(x,3)"),F("area_mm2","3.1416*{diametro_mm}^2/4","Sección (Excel usa 3.1416)","mm²","ROUND(x,2)"),F("area_in2","{area_mm2}/645.16","Sección","in²","ROUND(x,4)"),
 F("reduccion_area","({diametro_mm}^2-{diametro_final_mm}^2)*100/{diametro_mm}^2","Reducción de área","%","ROUND(x,1)"),
 F("elongacion_excel","({longitud_final_cm}-{longitud_cm})*100/{longitud_calibrada_cm}","Elongación como en el Excel (DUDOSA, FM64-01)","%","ROUND(x,1)"),
 F("elongacion","({longitud_final_marcas_cm}-{longitud_calibrada_cm})*100/{longitud_calibrada_cm}","Elongación A370: (Lf − L0)/L0","%","ROUND(x,1)"),
 F("fuerza_lbf","{fuerza_max_kn}*224.809","Fuerza máxima","lbf","ROUND(x,0)"),F("resistencia_tension_psi","{fuerza_lbf}/{area_in2}","Resistencia a la tensión","lbf/in²","ROUND(x,0)"),F("resistencia_tension_kgcm2","{resistencia_tension_psi}*0.07031","Resistencia a la tensión","kg/cm²","ROUND(x,0)"),F("fluencia_psi","{fluencia_mpa}*145.038","Fluencia","lbf/in²","ROUND(x,0)")]
d["criterios_aceptacion"]=[{"descripcion":"ASTM A615: fy/fu mínimos por grado (Gr60: 60/90 ksi; Gr40: 40/60 ksi), elongación mínima en 8 in por No. de varilla, peso unitario ≥ 94 % del nominal","implicito":True}]
d["salidas"]={"tablas":["RESULTADOS: tensión, fluencia, diámetro, peso, corrugas, elongación, reducción de área"],"graficos":[]}
d["hojas_ocultas_relevantes"]=[{"hoja":"Hoja1 (oculta)","contenido":"Borrador de resultados de MS-10607-26","destino":"descartar"}]
d["correcciones"]=[CORR("FM64-01","Elongación = (largo final total − largo inicial total)/20 cm: mezcla longitud total de la varilla con la longitud calibrada","(Lf entre marcas − L0)/L0 con el nuevo campo longitud_final_marcas_cm; elongacion_excel se conserva hasta confirmar","ASTM A370 / E8",True),
 CORR("FM64-02","Fila 'Diámetro de reducción' es reducción de área en fracción","Reducción de área en %","Etiqueta/unidad"),CORR("FM64-03","π = 3.1416","Se mantiene","Diferencia despreciable"),CORR("FM64-04","MS-0371-26: fluencia 169.17 MPa igual a una fuerza de otra columna","Validar fy < fu y rangos","Posible copia",True)]
d["caso_prueba"]=[CASO("MS-0411-26 varilla 3/8","Archive/...FM-64... 'DATOS BITACORA' col B / 'RESULTADOS' col B",{"diametro_mm":9.06,"masa_kg":0.265,"longitud_cm":49.7,"longitud_calibrada_cm":20,"longitud_final_cm":56.5,"diametro_final_mm":6.8,"fuerza_max_kn":44.52,"fluencia_mpa":516.75},
 {"peso_unitario":0.5331991951710261,"area_mm2":64.46845944,"area_in2":0.09992631198462398,"reduccion_area":43.66718808629252,"elongacion_excel":34.0,"fuerza_lbf":10008.49668,"resistencia_tension_psi":100158.77181117264,"resistencia_tension_kgcm2":7042.1632460435485,"fluencia_psi":74948.38650000001})]
FMT["RT-FM-64"]=d
for k,v in FMT.items():
    v.setdefault("agregaciones",[])
    json.dump(v,open(f"{OUT}/formatos/{k}.json","w"),ensure_ascii=False,indent=2)
print(len(FMT),"formatos")
