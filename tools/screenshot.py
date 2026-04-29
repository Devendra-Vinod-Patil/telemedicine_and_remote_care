# save as: C:\xampp\htdocs\telemed2\tools\screenshot_all.py
# pip install playwright
# python -m playwright install
# then run (PowerShell):
#   $env:BASE_URL="http://localhost/telemed2"
#   $env:OUT_DIR="C:\xampp\htdocs\telemed2\screenshots"
#   python .\screenshot_all.py

import os
import re
from pathlib import Path
from urllib.parse import urljoin, urlparse

from playwright.sync_api import sync_playwright

BASE_URL = os.getenv("BASE_URL", "http://localhost/telemed2").rstrip("/") + "/"
OUT_DIR = Path(os.getenv("OUT_DIR", str(Path.cwd() / "screenshots")))
OUT_DIR.mkdir(parents=True, exist_ok=True)

DOCTOR_EMAIL = os.getenv("DOCTOR_EMAIL", "devendra@gmail.com")
DOCTOR_PASSWORD = os.getenv("DOCTOR_PASSWORD", "1234")
PATIENT_EMAIL = os.getenv("PATIENT_EMAIL", "shakti@gmail.com")
PATIENT_PASSWORD = os.getenv("PATIENT_PASSWORD", "1234")

VIEWPORT = {"width": 1440, "height": 900}
NAV_TIMEOUT_MS = 120_000

# Pages that are POST handlers / side-effect endpoints (skip)
SKIP_BASENAMES = {
    "add_to_cart.php",
    "remove_from_cart.php",
    "clear_cart.php",
    "update_status.php",
    "archive_appointment.php",
    "appointment_pay.php",
    "save_delivery.php",
    "logout.php",
}

def safe_name(s: str) -> str:
    s = re.sub(r"[^a-zA-Z0-9._-]+", "_", s)
    return s.strip("_")[:180] or "page"

def same_site(url: str) -> bool:
    a = urlparse(BASE_URL)
    b = urlparse(url)
    return (a.scheme, a.netloc) == (b.scheme, b.netloc)

def should_skip(url: str) -> bool:
    p = urlparse(url)
    base = Path(p.path).name.lower()
    if not base.endswith((".php", ".html")):
        return True
    if base in SKIP_BASENAMES:
        return True
    # Skip direct file downloads/uploads
    if any(ext in p.path.lower() for ext in [".png", ".jpg", ".jpeg", ".webp", ".svg", ".css", ".js"]):
        return True
    return False

def goto_and_shot(page, url: str, filename: str):
    page.goto(url, wait_until="domcontentloaded", timeout=NAV_TIMEOUT_MS)
    page.wait_for_load_state("load", timeout=NAV_TIMEOUT_MS)
    page.wait_for_timeout(700)
    page.screenshot(path=str(OUT_DIR / f"{filename}.png"), full_page=True)

def collect_links(page) -> set[str]:
    hrefs = page.eval_on_selector_all("a[href]", "els => els.map(e => e.getAttribute('href'))")
    urls: set[str] = set()
    for href in hrefs:
        if not href:
            continue
        href = href.strip()
        if href.startswith("#") or href.lower().startswith("javascript:") or href.lower().startswith("mailto:"):
            continue
        abs_url = urljoin(page.url, href)
        if not same_site(abs_url):
            continue
        if should_skip(abs_url):
            continue
        urls.add(abs_url)
    return urls

def login(page, role: str, email: str, password: str):
    page.goto(urljoin(BASE_URL, "login.php"), wait_until="domcontentloaded", timeout=NAV_TIMEOUT_MS)
    page.select_option("#role", role)
    page.fill("#email", email)
    page.fill("#password", password)
    with page.expect_navigation(wait_until="domcontentloaded", timeout=NAV_TIMEOUT_MS):
        page.click('button[type="submit"]')
    page.wait_for_load_state("load", timeout=NAV_TIMEOUT_MS)

def crawl_and_capture(context, start_urls: list[tuple[str, str]], prefix: str, max_pages: int = 200):
    page = context.new_page()
    page.set_default_navigation_timeout(NAV_TIMEOUT_MS)
    page.set_default_timeout(NAV_TIMEOUT_MS)

    queue: list[tuple[str, str]] = []
    seen: set[str] = set()

    for url, name in start_urls:
        queue.append((url, f"{prefix}_{safe_name(name)}"))

    count = 0
    while queue and count < max_pages:
        url, name = queue.pop(0)
        if url in seen:
            continue
        seen.add(url)

        try:
            goto_and_shot(page, url, name)
            count += 1
        except Exception as e:
            # still save an error screenshot if possible
            try:
                page.screenshot(path=str(OUT_DIR / f"{name}__ERROR.png"), full_page=True)
            except Exception:
                pass
            continue

        # discover more module links from this page
        try:
            for link in collect_links(page):
                if link not in seen:
                    link_name = f"{prefix}_{safe_name(urlparse(link).path.replace('/', '_'))}"
                    # include query into filename for pages like prescription.php?appointment_id=...
                    if urlparse(link).query:
                        link_name += "__" + safe_name(urlparse(link).query)
                    queue.append((link, link_name))
        except Exception:
            pass

    page.close()
    return count

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)

        # PUBLIC (no login)
        public_ctx = browser.new_context(viewport=VIEWPORT)
        public_start = [
            (urljoin(BASE_URL, "index.php"), "index"),
            (urljoin(BASE_URL, "about.php"), "about"),
            (urljoin(BASE_URL, "contact.php"), "contact"),
            (urljoin(BASE_URL, "doctors.php"), "doctors"),
            (urljoin(BASE_URL, "registration.html"), "registration"),
            (urljoin(BASE_URL, "login.php"), "login"),
            (urljoin(BASE_URL, "storeindex.php"), "storeindex_requires_login"),
        ]
        crawl_and_capture(public_ctx, public_start, "public", max_pages=80)
        public_ctx.close()

        # PATIENT
        patient_ctx = browser.new_context(viewport=VIEWPORT)
        patient_page = patient_ctx.new_page()
        patient_page.set_default_navigation_timeout(NAV_TIMEOUT_MS)
        patient_page.set_default_timeout(NAV_TIMEOUT_MS)
        login(patient_page, "patient", PATIENT_EMAIL, PATIENT_PASSWORD)
        patient_page.close()

        patient_start = [
            (urljoin(BASE_URL, "patient_dashboard.php"), "patient_dashboard"),
            (urljoin(BASE_URL, "storeindex.php"), "storeindex"),
            (urljoin(BASE_URL, "cart.php"), "cart"),
            (urljoin(BASE_URL, "delivery_form.php"), "delivery_form"),
        ]
        crawl_and_capture(patient_ctx, patient_start, "patient", max_pages=250)
        patient_ctx.close()

        # DOCTOR
        doctor_ctx = browser.new_context(viewport=VIEWPORT)
        doctor_page = doctor_ctx.new_page()
        doctor_page.set_default_navigation_timeout(NAV_TIMEOUT_MS)
        doctor_page.set_default_timeout(NAV_TIMEOUT_MS)
        login(doctor_page, "doctor", DOCTOR_EMAIL, DOCTOR_PASSWORD)
        doctor_page.close()

        doctor_start = [
            (urljoin(BASE_URL, "doctors_dashboard.php"), "doctor_dashboard"),
            (urljoin(BASE_URL, "storeindex.php"), "storeindex"),
            (urljoin(BASE_URL, "cart.php"), "cart"),
        ]
        crawl_and_capture(doctor_ctx, doctor_start, "doctor", max_pages=250)
        doctor_ctx.close()

        browser.close()

    print(f"Saved screenshots to: {OUT_DIR}")

if __name__ == "__main__":
    main()
