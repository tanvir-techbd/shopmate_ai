@echo off
REM Double-clickable Windows launcher for ShopMate AI.
REM
REM start.ps1 holds the actual logic; this wrapper exists because PowerShell's
REM default execution policy on Windows 11 is Restricted, which refuses to run
REM .ps1 files at all - so typing `.\start.ps1` fails out of the box with
REM "running scripts is disabled on this system". Passing -ExecutionPolicy
REM Bypass applies to this one process only and changes nothing system-wide,
REM so there is no need to loosen the machine's policy just to start the app.

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0start.ps1" %*

REM Keep the window open if it failed, so a double-click still shows the error.
if errorlevel 1 pause
