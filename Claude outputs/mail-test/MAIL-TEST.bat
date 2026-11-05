@echo off
setlocal DisableDelayedExpansion
title Avenir - mail connection test (nothing is saved)
rem The PHP that WAMP's Apache runs (8.1.33); failing that, any PHP 8 under C:\wamp64\bin\php
set "PHP=C:\wamp64\bin\php\php8.1.33\php.exe"
if not exist "%PHP%" for /d %%D in ("C:\wamp64\bin\php\php8*") do if exist "%%D\php.exe" set "PHP=%%D\php.exe"
if not exist "%PHP%" (
  echo Could not find php.exe under C:\wamp64\bin\php. Nothing was done.
  pause
  exit /b 1
)
echo.
echo  Avenir Holidays - mail connection test
echo  -------------------------------------
echo  Sends ONE test e-mail from htl.res@avenirholidays.com to Shanth@lionroyal.co.jp
echo  (smtp.lolipop.jp, port 465, SSL) and opens the inbox READ-ONLY (imap.lolipop.jp, port 993, SSL).
echo  The password you type is used for this run only: it is not saved or shown anywhere.
echo.
for /f "usebackq delims=" %%P in (`powershell -NoProfile -Command "$s = Read-Host 'Password for htl.res@avenirholidays.com' -AsSecureString; $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR($b) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }"`) do set "AVENIR_MAIL_TEST_PW=%%P"
if not defined AVENIR_MAIL_TEST_PW (
  echo No password typed. Nothing was done.
  pause
  exit /b 1
)
"%PHP%" "%~dp0mail_test.php" %*
set "AVENIR_MAIL_TEST_PW="
echo.
pause
