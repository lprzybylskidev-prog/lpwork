param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string] $ApplicationName,

    [Parameter(Position = 1)]
    [string] $TargetParentDirectory = (Get-Location).Path
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ReleaseArchiveUrl = 'https://github.com/lprzybylskidev-prog/lpwork/archive/refs/tags/v1.0.1.zip'
$ReleaseArchivePlaceholder = '__LPWORK_RELEASE_ARCHIVE_URL__'

function Fail([string] $Message) {
    Write-Error "LPWork installer: $Message"
    exit 1
}

function Remove-ApplicationPath([string] $Root, [string] $RelativePath) {
    $Path = Join-Path $Root $RelativePath

    if (Test-Path -LiteralPath $Path) {
        Remove-Item -LiteralPath $Path -Recurse -Force
    }
}

function Assert-ConfiguredReleaseUrl {
    if ([string]::IsNullOrWhiteSpace($ReleaseArchiveUrl) -or $ReleaseArchiveUrl -eq $ReleaseArchivePlaceholder) {
        Fail 'no immutable LPWork release archive URL is configured. Fill $ReleaseArchiveUrl with a tagged release archive before distributing this installer.'
    }

    if ($ReleaseArchiveUrl -notmatch '/archive/refs/tags/' -and
        $ReleaseArchiveUrl -notmatch 'releases/download/' -and
        $ReleaseArchiveUrl -notmatch 'codeload\.github\.com/.+/zip/refs/tags/' -and
        $ReleaseArchiveUrl -notmatch 'codeload\.github\.com/.+/tar\.gz/refs/tags/') {
        Fail 'release archive URL must point at an immutable tag archive, not a moving branch or local source.'
    }
}

function Assert-ApplicationName([string] $ApplicationName) {
    if ([string]::IsNullOrWhiteSpace($ApplicationName) -or
        $ApplicationName -eq '.' -or
        $ApplicationName -eq '..' -or
        $ApplicationName.Contains('/') -or
        $ApplicationName.Contains('\')) {
        Fail 'application name must be a single directory name.'
    }
}

function Expand-ReleaseArchive([string] $ArchivePath, [string] $ExtractPath) {
    if ($ReleaseArchiveUrl.EndsWith('.tar.gz') -or
        $ReleaseArchiveUrl.EndsWith('.tgz') -or
        $ReleaseArchiveUrl -match 'codeload\.github\.com/.+/tar\.gz/') {
        tar -xzf $ArchivePath -C $ExtractPath
        if ($LASTEXITCODE -ne 0) {
            Fail 'tar could not extract the release archive.'
        }

        return
    }

    if ($ReleaseArchiveUrl.EndsWith('.zip') -or
        $ReleaseArchiveUrl -match '/archive/refs/tags/' -or
        $ReleaseArchiveUrl -match 'codeload\.github\.com/.+/zip/') {
        Expand-Archive -LiteralPath $ArchivePath -DestinationPath $ExtractPath
        return
    }

    Fail 'release archive must be a .zip, .tar.gz, or GitHub tagged archive URL.'
}

function Get-ReleaseRoot([string] $ExtractPath) {
    $Roots = @(Get-ChildItem -LiteralPath $ExtractPath -Directory)

    if ($Roots.Count -ne 1) {
        Fail 'release archive must contain one top-level project directory.'
    }

    return $Roots[0].FullName
}

function Prepare-ApplicationSnapshot([string] $ReleaseRoot, [string] $TargetDirectory) {
    New-Item -ItemType Directory -Path $TargetDirectory | Out-Null
    Get-ChildItem -LiteralPath $ReleaseRoot -Force | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $TargetDirectory -Recurse -Force
    }

    Remove-ApplicationPath $TargetDirectory '.git'
    Remove-ApplicationPath $TargetDirectory '.git/hooks'
    Remove-ApplicationPath $TargetDirectory '.githooks'
    Remove-ApplicationPath $TargetDirectory 'hooks'
    Remove-ApplicationPath $TargetDirectory 'lpwork-roadmap'
    Remove-ApplicationPath $TargetDirectory 'AGENTS.md'
    Remove-ApplicationPath $TargetDirectory 'vendor'
    Remove-ApplicationPath $TargetDirectory 'node_modules'
    Remove-ApplicationPath $TargetDirectory 'installers'
    Remove-ApplicationPath $TargetDirectory 'storage/cache'
    Remove-ApplicationPath $TargetDirectory 'storage/log'
    Remove-ApplicationPath $TargetDirectory 'storage/playwright'
    Remove-ApplicationPath $TargetDirectory 'storage/test-reports'
    Remove-ApplicationPath $TargetDirectory 'storage/tmp'
    Remove-ApplicationPath $TargetDirectory 'storage/testing'
    Remove-ApplicationPath $TargetDirectory 'storage/database.sqlite'
    Remove-ApplicationPath $TargetDirectory '.phpunit.cache'
    Remove-ApplicationPath $TargetDirectory '.phpstan.cache'
    Remove-ApplicationPath $TargetDirectory '.php-cs-fixer.cache'

    $AgentTemplate = Join-Path $TargetDirectory 'docs/.AGENTS.md'
    if (-not (Test-Path -LiteralPath $AgentTemplate -PathType Leaf)) {
        Fail 'release archive is missing docs/.AGENTS.md for generated application guidance.'
    }

    Copy-Item -LiteralPath $AgentTemplate -Destination (Join-Path $TargetDirectory 'AGENTS.md') -Force

    New-Item -ItemType Directory -Force -Path `
        (Join-Path $TargetDirectory 'storage/cache'), `
        (Join-Path $TargetDirectory 'storage/log'), `
        (Join-Path $TargetDirectory 'storage/framework'), `
        (Join-Path $TargetDirectory 'storage/tmp') | Out-Null
}

Assert-ConfiguredReleaseUrl
Assert-ApplicationName $ApplicationName

$TargetParent = Resolve-Path -LiteralPath $TargetParentDirectory -ErrorAction Stop
$TargetDirectory = Join-Path $TargetParent.Path $ApplicationName

if (Test-Path -LiteralPath $TargetDirectory) {
    Fail "target directory [$TargetDirectory] already exists."
}

$TemporaryDirectory = Join-Path ([System.IO.Path]::GetTempPath()) ([System.Guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $TemporaryDirectory | Out-Null

try {
    $ArchivePath = Join-Path $TemporaryDirectory 'lpwork-release'
    $ExtractPath = Join-Path $TemporaryDirectory 'extracted'
    New-Item -ItemType Directory -Path $ExtractPath | Out-Null

    Invoke-WebRequest -Uri $ReleaseArchiveUrl -OutFile $ArchivePath
    Expand-ReleaseArchive $ArchivePath $ExtractPath
    Prepare-ApplicationSnapshot (Get-ReleaseRoot $ExtractPath) $TargetDirectory

    Write-Output "LPWork application created at: $TargetDirectory"
    Write-Output 'Open it in VS Code and use the included devcontainer to install PHP, Composer, Node, npm, and browser tooling.'
} finally {
    Remove-Item -LiteralPath $TemporaryDirectory -Recurse -Force -ErrorAction SilentlyContinue
}
