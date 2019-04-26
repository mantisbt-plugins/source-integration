REM Command line wrapper to start PowerShell hook script
@echo off
set PWSH=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe
%PWSH% -ExecutionPolicy Bypass -NonInteractive -NoProfile -Command "$input | %1\hooks\post-commit.ps1" "%1" "%2" "%3"
if errorlevel 1 exit /b %errorlevel%