<#
.SYNOPSIS
  Windows/PowerShell equivalent of build-plugin.sh, for environments without
  a `zip` binary. Produces dist/form-looq.zip with a single top-level
  `form-looq/` folder, forward-slash entry paths (required — a zip built
  with backslash paths does not extract as a folder on Linux hosting), and
  none of the development-only files that live outside the form-looq/
  directory in this repo.
#>

$ErrorActionPreference = 'Stop'

$RootDir   = Split-Path -Parent $PSScriptRoot
$PluginDir = Join-Path $RootDir 'form-looq'
$DistDir   = Join-Path $RootDir 'dist'
$ZipFile   = Join-Path $DistDir 'form-looq.zip'

if (-not (Test-Path $PluginDir)) {
    throw "Plugin directory not found: $PluginDir"
}

$HeaderLine = Select-String -Path (Join-Path $PluginDir 'form-looq.php') -Pattern '^\s*\*\s*Version:\s*(\S+)' | Select-Object -First 1
if (-not $HeaderLine) { throw 'Could not read Version header from form-looq.php.' }
$HeaderVersion = $HeaderLine.Matches[0].Groups[1].Value.Trim()

$ReadmeLine = Select-String -Path (Join-Path $PluginDir 'readme.txt') -Pattern '^Stable tag:\s*(\S+)' | Select-Object -First 1
if (-not $ReadmeLine) { throw 'Could not read Stable tag from readme.txt.' }
$StableTag = $ReadmeLine.Matches[0].Groups[1].Value.Trim()

if ($HeaderVersion -ne $StableTag) {
    throw "Version mismatch: plugin header='$HeaderVersion', readme stable tag='$StableTag'"
}

if (-not (Test-Path $DistDir)) {
    New-Item -ItemType Directory -Path $DistDir | Out-Null
}

if (Test-Path $ZipFile) {
    Remove-Item $ZipFile -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$ExcludeNames = @('.DS_Store', 'Thumbs.db')
$ExcludeExts  = @('.log', '.map')
$DevReferencePattern = 'vendor/|node_modules/|tests/|\.git/'
$Flagged = @()

$zip = [System.IO.Compression.ZipFile]::Open($ZipFile, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    $files = Get-ChildItem -Path $PluginDir -Recurse -File
    foreach ($file in $files) {
        if ($ExcludeNames -contains $file.Name) { continue }
        if ($ExcludeExts -contains $file.Extension.ToLowerInvariant()) { continue }

        $relative = $file.FullName.Substring($PluginDir.Length + 1) -replace '\\', '/'
        $entryPath = "form-looq/$relative"

        $content = Get-Content -Path $file.FullName -Raw -ErrorAction SilentlyContinue
        if ($null -ne $content -and $content -match $DevReferencePattern) {
            $Flagged += $relative
        }

        $entry = $zip.CreateEntry($entryPath, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        try {
            $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
            $entryStream.Write($bytes, 0, $bytes.Length)
        } finally {
            $entryStream.Close()
        }
    }
} finally {
    $zip.Dispose()
}

if ($Flagged.Count -gt 0) {
    Remove-Item $ZipFile -Force
    throw "Unexpected development-only path reference found in: $($Flagged -join ', ')"
}

# Verify: every entry lives under form-looq/, uses forward slashes, and no
# macOS metadata (__MACOSX/, .DS_Store, AppleDouble ._* files) crept in.
$verify = [System.IO.Compression.ZipFile]::OpenRead($ZipFile)
try {
    $badPaths = @()
    foreach ($entry in $verify.Entries) {
        $name = $entry.FullName
        if (-not $name.StartsWith('form-looq/') -or $name.Contains('\') -or $name.Contains('__MACOSX') -or (Split-Path $name -Leaf).StartsWith('._')) {
            $badPaths += $name
        }
    }
    if ($badPaths.Count -gt 0) {
        throw "Unexpected entries in built zip: $($badPaths -join ', ')"
    }
    $entryCount = $verify.Entries.Count
} finally {
    $verify.Dispose()
}

Write-Output "Built $ZipFile (version $HeaderVersion, $entryCount files)"
