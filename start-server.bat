@echo off
cd /d "%~dp0"
echo Starting College Lost and Found at http://localhost:8000  (press Ctrl+C to stop)
"C:\php\php.exe" -c "C:\php\php.ini" -d upload_max_filesize=4M -d post_max_size=6M -S localhost:8000 -t "%~dp0"
