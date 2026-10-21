"""Réplica en Python de app/Core/EvaluadorFormula.php (operadores + - * / % ^, funciones permitidas,
variables {codigo}, unario '-' como 0-x) para verificar los caso_prueba de cada formato.
Uso: python3 tools/verificar_casos.py [carpeta_paquete]"""
import json,math,re,sys,glob,os
FUN={'ROUND':(1,2),'SQRT':(1,1),'POW':(2,2),'ABS':(1,1),'MIN':(1,10),'MAX':(1,10),'CEIL':(1,1),'FLOOR':(1,1),'PI':(0,0),'LOG':(1,2),'EXP':(1,1)}
OPS={'+':(1,'L'),'-':(1,'L'),'*':(2,'L'),'/':(2,'L'),'%':(2,'L'),'^':(3,'R')}
def php_round(x,p=0):
    m=10**p; v=abs(x)*m; r=math.floor(v+0.5); return math.copysign(r/m,x)
def php_str(v):
    s=repr(float(v)); 
    if 'e' in s or 'E' in s: raise ValueError(f"valor {v} se convertiría a notación científica en PHP")
    return s
def tokens(e):
    out=[];i=0;last='INI'
    while i<len(e):
        c=e[i]
        if c.isspace(): i+=1;continue
        if c.isdigit() or (c=='.' and i+1<len(e) and e[i+1].isdigit()):
            j=i
            while j<len(e) and (e[j].isdigit() or e[j]=='.'): j+=1
            out.append(('N',float(e[i:j])));i=j;last='N';continue
        if c.isalpha():
            j=i
            while j<len(e) and e[j].isalpha(): j+=1
            f=e[i:j].upper()
            if f not in FUN: raise ValueError('función no permitida '+f)
            out.append(('F',f));i=j;last='F';continue
        if c=='-' and last in('INI','O','(',','):
            out+= [('N',0.0),('O','-')];last='O';i+=1;continue
        if c in OPS: out.append(('O',c));last='O';i+=1;continue
        if c in '(),': out.append((c,c));last=c;i+=1;continue
        raise ValueError('carácter no permitido '+c)
    return out
def rpn(tk):
    out=[];st=[];argc=[]
    for t,v in tk:
        if t=='N': out.append((t,v))
        elif t=='F': st.append((t,v))
        elif t==',':
            while st and st[-1][0]!='(': out.append(st.pop())
            argc[-1]+=1
        elif t=='O':
            while st and st[-1][0]=='O':
                p2,_=OPS[st[-1][1]];p1,a=OPS[v]
                if (a=='L' and p1<=p2) or (a=='R' and p1<p2): out.append(st.pop())
                else: break
            st.append((t,v))
        elif t=='(':
            st.append(('(','(')); argc.append(1)
        elif t==')':
            while st[-1][0]!='(': out.append(st.pop())
            st.pop(); n=argc.pop()
            if st and st[-1][0]=='F':
                f=st.pop()[1]; out.append(('F',(f,0 if f=='PI' else n)))
    while st: out.append(st.pop())
    return out
def ev(expr,vals):
    for var in re.findall(r'\{([a-zA-Z0-9_\-]+)\}',expr):
        if vals.get(var) is None: return None
        v=vals[var]; s=php_str(v); s=f'({s})' if v<0 else s
        expr=expr.replace('{'+var+'}',s)
    expr=re.sub(r'\bPI\(\)',repr(math.pi),expr,flags=re.I)
    st=[]
    for t,v in rpn(tokens(expr)):
        if t=='N': st.append(v)
        elif t=='O':
            b=st.pop();a=st.pop()
            if v in '/%' and b==0: return None
            st.append({'+':lambda:a+b,'-':lambda:a-b,'*':lambda:a*b,'/':lambda:a/b,'%':lambda:math.fmod(a,b),'^':lambda:a**b}[v]())
        else:
            f,n=v; args=[st.pop() for _ in range(n)][::-1]
            st.append({'ROUND':lambda:php_round(args[0],int(args[1]) if n>1 else 0),'SQRT':lambda:math.sqrt(args[0]),'POW':lambda:args[0]**args[1],'ABS':lambda:abs(args[0]),
             'MIN':lambda:min(args),'MAX':lambda:max(args),'CEIL':lambda:math.ceil(args[0]),'FLOOR':lambda:math.floor(args[0]),'PI':lambda:math.pi,
             'LOG':lambda:math.log(args[0]) if n==1 else math.log(args[0],args[1]),'EXP':lambda:math.exp(args[0])}[f]())
    return st[0]
def verificar(spec):
    fallas=[];total=0
    for caso in spec.get('caso_prueba',[]):
        vals=dict(caso['entradas'])
        for _ in range(len(spec['formulas'])+1):
            for f in spec['formulas']:
                if f['codigo'] in caso['entradas']: continue
                try: r=ev(f['expresion'],vals)
                except Exception as e: fallas.append(f"{caso['nombre']}: {f['codigo']} error {e}"); r=None
                if r is not None: vals[f['codigo']]=r
        for k,esp in caso['esperados'].items():
            total+=1; got=vals.get(k)
            tol=caso.get('tolerancia_relativa',0.001)
            if got is None or abs(got-esp)>max(abs(esp)*tol,1e-9):
                fallas.append(f"{caso['nombre']}: {k} esperado {esp} obtenido {got}")
    return total,fallas
if __name__=='__main__':
    base=sys.argv[1] if len(sys.argv)>1 else os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    ok=True
    for p in sorted(glob.glob(os.path.join(base,'formatos','*.json'))):
        s=json.load(open(p,encoding='utf-8')); n,f=verificar(s)
        print(f"{s['codigo']}: {'PASA' if not f else 'FALLA'} ({n-len(f)}/{n} valores)")
        for x in f: print('   -',x)
        ok&=not f
    sys.exit(0 if ok else 1)
