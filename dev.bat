@echo off
title ATCL26 Local Development Server
echo ==============================================
echo   Starting ATCL26 Local Server on Port 8000
echo ==============================================

:: Set database environment variables for local testing
set DB_HOST=ls-c3d8514ce12a75dbc20ba0d470549242c4ead39a.cpqysuqk8rgb.ap-southeast-1.rds.amazonaws.com
set DB_NAME=atcl26
set DB_USER=dbmasteruser
set DB_PASS=ATCL26zhenbang
set APP_BASE_URL=/

echo Connecting to AWS RDS database...
echo Server running at: http://localhost:8000
echo Press Ctrl+C to stop.
echo.

php -S localhost:8000 router.php
pause
