[CmdletBinding()]
param (
    [switch]$Leer,
    [int]$N = 5,
    [string]$De,
    [string]$Para,
    [string]$Asunto,
    [string]$Msg
)

$scriptPy = Join-Path $PSScriptRoot "chat_agentes.py"

if ($Leer -or (-not $De -and -not $Msg)) {
    python $scriptPy --leer --n $N
    exit $LASTEXITCODE
}

$argsList = @("--de", $De, "--msg", $Msg)
if ($Para) { $argsList += @("--para", $Para) }
if ($Asunto) { $argsList += @("--asunto", $Asunto) }

python $scriptPy @argsList
exit $LASTEXITCODE
