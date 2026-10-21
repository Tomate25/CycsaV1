"""Valida que cada formatos/*.json cumple _esquema.json y que toda {referencia} de las fórmulas existe
(campo, constante, fórmula, agregación). Requiere: pip install jsonschema"""
import json,glob,os,re,sys
from jsonschema import Draft202012Validator
base=sys.argv[1] if len(sys.argv)>1 else os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
v=Draft202012Validator(json.load(open(os.path.join(base,'_esquema.json'),encoding='utf-8')))
ok=True
for p in sorted(glob.glob(os.path.join(base,'formatos','*.json'))):
    s=json.load(open(p,encoding='utf-8'))
    errs=[f"{'/'.join(map(str,e.path))}: {e.message}" for e in v.iter_errors(s)]
    defin={c['codigo'] for c in s['encabezado']}|{c['codigo'] for sec in s['secciones'] for c in sec['campos']}|{k['codigo'] for k in s['constantes']}|{f['codigo'] for f in s['formulas']}|{a['codigo'] for a in s['agregaciones']}
    defin|={f"{c}_{i}" for c in list(defin) for i in range(1,11)}
    for f in s['formulas']:
        for r in re.findall(r'\{([a-zA-Z0-9_\-]+)\}',f['expresion']):
            if r not in defin: errs.append(f"formula {f['codigo']}: referencia no definida {{{r}}}")
        if re.search(r'\d[eE][-+]?\d',f['expresion']): errs.append(f"formula {f['codigo']}: notación científica no soportada")
    print(f"{os.path.basename(p)}: {'OK' if not errs else 'ERRORES'}"); [print('   -',e) for e in errs]; ok&=not errs
for p in glob.glob(os.path.join(base,'catalogos','*.json'))+[os.path.join(base,'_esquema.json')]:
    json.load(open(p,encoding='utf-8')); print(os.path.relpath(p,base),': JSON válido')
sys.exit(0 if ok else 1)
