# Playwright Screenshots

This repo is a PHP site, but you can use Playwright to capture screenshots of every major module for both doctor and patient.

## 1) Install Playwright

From `C:\\xampp\\htdocs\\telemed2`:

```powershell
npm init -y
npm i -D playwright
npx playwright install
```

## 2) Run screenshot capture

```powershell
node .\\tools\\playwright-screenshots.mjs
```

Screenshots will be saved in `C:\\xampp\\htdocs\\telemed2\\screenshots`.

## Optional env vars

```powershell
$env:BASE_URL = "http://localhost/telemed2"
$env:OUT_DIR = "C:\\xampp\\htdocs\\telemed2\\screenshots"
$env:DOCTOR_EMAIL = "devendra@gmail.com"
$env:DOCTOR_PASSWORD = "1234"
$env:PATIENT_EMAIL = "shakti@gmail.com"
$env:PATIENT_PASSWORD = "1234"
node .\\tools\\playwright-screenshots.mjs
```

