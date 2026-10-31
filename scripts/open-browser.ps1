param(
    [ValidateSet(8765, 8766)] [int] $Port,
    [switch] $WaitForServer
)

$url = "http://127.0.0.1:$Port/"

function Test-DetectorReady {
    try {
        $response = Invoke-RestMethod -Uri ($url + 'api/bootstrap') -TimeoutSec 1
        return [bool] ($response.token -and $response.schema.nboards.kind -eq 'integer')
    } catch { return $false }
}

function Open-DetectorPage {
    $chrome = @(
        "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
        "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
        "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
    ) | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
    if ($chrome) { Start-Process -FilePath $chrome -ArgumentList $url }
    else { Start-Process -FilePath $url }
}

if (-not $WaitForServer) {
    if (Test-DetectorReady) {
        Open-DetectorPage
        exit 10 # Tell the launcher to reuse the running service.
    }
    # The helper waits in the background; the launcher retains the server console.
    Start-Process -FilePath 'powershell.exe' -WindowStyle Hidden -ArgumentList @(
        '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass',
        '-File', ('"{0}"' -f $PSCommandPath), '-Port', $Port, '-WaitForServer'
    )
    exit 0
}

$deadline = [DateTime]::UtcNow.AddSeconds(60)
while ([DateTime]::UtcNow -lt $deadline) {
    if (Test-DetectorReady) {
        Open-DetectorPage
        exit 0
    }
    Start-Sleep -Milliseconds 300
}
exit 1
