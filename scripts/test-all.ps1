$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Invoke-DockerCompose {
    param(
        [Parameter(Mandatory = $true)]
        [string[]] $Args
    )

    Write-Host ""
    Write-Host "==> docker compose $($Args -join ' ')" -ForegroundColor Cyan

    & docker compose @Args

    if ($LASTEXITCODE -ne 0) {
        throw "Command failed with exit code ${LASTEXITCODE}: docker compose $($Args -join ' ')"
    }
}

function Wait-ForE2EBaseUrl {
    $waitCommand = 'attempt=0; until curl -fsS http://creditall-app:8000/ >/dev/null; do attempt=$((attempt+1)); if [ "$attempt" -ge 30 ]; then exit 1; fi; sleep 2; done'

    Invoke-DockerCompose -Args @('exec', '-T', 'e2e', 'sh', '-lc', $waitCommand)
}

Invoke-DockerCompose -Args @('up', '-d', 'app', 'e2e')
Invoke-DockerCompose -Args @('run', '--rm', 'app', 'php', 'artisan', 'test')
Invoke-DockerCompose -Args @('exec', '-T', 'e2e', 'npm', 'ci')
Wait-ForE2EBaseUrl
Invoke-DockerCompose -Args @('exec', '-T', 'e2e', 'npm', 'run', 'e2e:docker')

Write-Host ""
Write-Host "All PHP and E2E tests finished successfully." -ForegroundColor Green
