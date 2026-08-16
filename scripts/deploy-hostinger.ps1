# Despliegue manual a Hostinger por FTP.
# Configura en PowerShell antes de ejecutar:
#   $env:FTP_SERVER = 'ftp.sanvalx.com'   # o la IP del panel Hostinger
#   $env:FTP_USERNAME = 'u123456789'
#   $env:FTP_PASSWORD = 'tu-contraseña'
#   $env:FTP_REMOTE_DIR = '/domains/sanvalx.com/public_html'
#
# Ejecutar: .\scripts\deploy-hostinger.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

foreach ($key in @('FTP_SERVER', 'FTP_USERNAME', 'FTP_PASSWORD')) {
    if (-not $env:$key) {
        Write-Error "Falta `$env:$key. Configura las variables FTP antes de ejecutar."
    }
}

$remoteDir = if ($env:FTP_REMOTE_DIR) { $env:FTP_REMOTE_DIR } else { '/public_html' }

$files = @(
    'contacto.php',
    'procesar_lead.php',
    'sitemap.xml',
    'includes/functions.php',
    'includes/contacto_submit.php',
    'api/contacto.php',
    '.htaccess'
)

$cred = New-Object System.Net.NetworkCredential($env:FTP_USERNAME, $env:FTP_PASSWORD)
$base = "ftp://$($env:FTP_SERVER)$remoteDir"

function Upload-FtpFile {
    param([string]$LocalPath, [string]$RemotePath)
    $uri = "$base/$RemotePath".Replace('\', '/')
    $request = [System.Net.FtpWebRequest]::Create($uri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $request.Credentials = $cred
    $request.UseBinary = $true
    $request.UsePassive = $true
    $bytes = [System.IO.File]::ReadAllBytes($LocalPath)
    $stream = $request.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Close()
    $response = $request.GetResponse()
    $status = $response.StatusDescription
    $response.Close()
    Write-Output "[OK] $RemotePath ($status)"
}

function Ensure-FtpDir {
    param([string]$RemoteDirPath)
    $parts = $RemoteDirPath.Trim('/').Split('/')
    $current = ''
    foreach ($part in $parts) {
        if ($part -eq '') { continue }
        $current += "/$part"
        $uri = "$base$current"
        try {
            $request = [System.Net.FtpWebRequest]::Create($uri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $request.Credentials = $cred
            $request.UsePassive = $true
            $response = $request.GetResponse()
            $response.Close()
        } catch {
            # Ya existe
        }
    }
}

Ensure-FtpDir 'storage/rate_limit'
Ensure-FtpDir 'includes'
Ensure-FtpDir 'api'

foreach ($rel in $files) {
    $local = Join-Path $root $rel
    if (-not (Test-Path $local)) {
        Write-Warning "Omitido (no existe): $rel"
        continue
    }
    Upload-FtpFile -LocalPath $local -RemotePath ($rel -replace '\\', '/')
}

Write-Output 'Despliegue FTP completado. Prueba https://sanvalx.com/contacto.html'
