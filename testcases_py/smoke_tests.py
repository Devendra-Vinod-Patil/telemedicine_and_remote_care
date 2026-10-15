import os
import time
from dataclasses import dataclass

from playwright.sync_api import sync_playwright


BASE_URL = os.environ.get("BASE_URL", "http://localhost/telemed2")


@dataclass(frozen=True)
class PageCheck:
    name: str
    path: str
    expected_title_contains: str | None = None


PAGE_CHECKS = [
    PageCheck("Home", "/index.php", "Telemed2"),
    PageCheck("About", "/about.php"),
    PageCheck("Contact", "/contact.php"),
    PageCheck("Doctors Listing", "/doctors.php"),
    PageCheck("Login", "/login.php"),
    PageCheck("Registration", "/registration.html"),
    PageCheck("Patient Dashboard", "/patient_dashboard.php"),
    PageCheck("Doctor Dashboard", "/doctors_dashboard.php"),
    PageCheck("Appointment Page", "/appointment.php"),
    PageCheck("Video Call", "/video.php"),
    PageCheck("Prescription", "/prescription.php"),
    PageCheck("Appointment Payment", "/appointment_payment.php"),
    PageCheck("Appointment Bill", "/appointment_bill.php"),
    PageCheck("Store Home", "/storeindex.php"),
    PageCheck("Cart", "/cart.php"),
    PageCheck("Delivery Form", "/delivery_form.php"),
    PageCheck("Store Bill", "/bill.php"),
    PageCheck("Admin Login", "/admin/login.php"),
    PageCheck("Admin Dashboard", "/admin/index.php"),
    PageCheck("Admin Doctors", "/admin/doctors.php"),
    PageCheck("Admin Patients", "/admin/patients.php"),
    PageCheck("Admin Medicines", "/admin/medicines.php"),
]


def run_checks() -> None:
    started_at = time.perf_counter()
    failures = []

    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(headless=True)
        page = browser.new_page(viewport={"width": 1365, "height": 900})

        for check in PAGE_CHECKS:
            url = f"{BASE_URL}{check.path}"
            try:
                response = page.goto(url, wait_until="domcontentloaded", timeout=30000)
                status = response.status if response else "no-response"
                title = page.title().strip()

                if response is not None and response.status >= 400:
                    failures.append(f"{check.name}: HTTP {response.status} at {url}")
                    continue

                if check.expected_title_contains and check.expected_title_contains.lower() not in title.lower():
                    failures.append(f"{check.name}: title mismatch at {url} (got '{title}')")
                    continue

                print(f"PASS | {check.name} | HTTP {status} | {title}")
            except Exception as exc:
                failures.append(f"{check.name}: {exc}")

        browser.close()

    elapsed_seconds = time.perf_counter() - started_at
    print(f"EXECUTION TIME: {elapsed_seconds:.2f} seconds")

    if failures:
        print("FAILED CHECKS:")
        for failure in failures:
            print(f"- {failure}")
        raise SystemExit(1)

    print("TEST IS COMPLETE")


if __name__ == "__main__":
    run_checks()
