@echo off
cd /d "%~dp0"
if exist ".tools\php\php.exe" (
  echo Helio portal: http://localhost:8080
  ".tools\php\php.exe" -d extension_dir="%~dp0.tools\php\ext" -d extension=pdo_sqlite -S localhost:8080 -t public
) else (
  where php >nul 2>nul
  if errorlevel 1 (
    echo PHP 8.2 or newer is required. See README.md for setup.
    pause
    exit /b 1
  )
  echo Helio portal: http://localhost:8080
  php -S localhost:8080 -t public
)
