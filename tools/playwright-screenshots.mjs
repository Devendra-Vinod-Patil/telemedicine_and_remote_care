import fs from "node:fs";
import path from "node:path";
import { chromium } from "playwright";

const BASE_URL = process.env.BASE_URL || "http://localhost/telemed2";
const OUT_DIR = process.env.OUT_DIR || path.resolve("screenshots");

const DOCTOR_EMAIL = process.env.DOCTOR_EMAIL || "devendra@gmail.com";
const DOCTOR_PASSWORD = process.env.DOCTOR_PASSWORD || "1234";
const PATIENT_EMAIL = process.env.PATIENT_EMAIL || "shakti@gmail.com";
const PATIENT_PASSWORD = process.env.PATIENT_PASSWORD || "1234";

fs.mkdirSync(OUT_DIR, { recursive: true });

function file(name) {
  return path.join(OUT_DIR, `${name}.png`);
}

async function gotoAndShot(page, url, name) {
  await page.goto(url, { waitUntil: "networkidle" });
  await page.waitForTimeout(500);
  await page.screenshot({ path: file(name), fullPage: true });
}

async function login(page, role, email, password) {
  await page.goto(`${BASE_URL}/login.php`, { waitUntil: "domcontentloaded" });
  await page.selectOption("#role", role);
  await page.fill("#email", email);
  await page.fill("#password", password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: "networkidle" }),
    page.click('button[type="submit"]'),
  ]);
}

const browser = await chromium.launch();

try {
  // Public pages
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();

  await gotoAndShot(page, `${BASE_URL}/index.php`, "public_index");
  await gotoAndShot(page, `${BASE_URL}/about.php`, "public_about");
  await gotoAndShot(page, `${BASE_URL}/contact.php`, "public_contact");
  await gotoAndShot(page, `${BASE_URL}/doctors.php`, "public_doctors");
  await gotoAndShot(page, `${BASE_URL}/registration.html`, "public_registration");
  await gotoAndShot(page, `${BASE_URL}/login.php`, "public_login");
  await gotoAndShot(page, `${BASE_URL}/storeindex.php`, "public_store_requires_login");

  await context.close();

  // Patient
  const patientContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const patientPage = await patientContext.newPage();
  await login(patientPage, "patient", PATIENT_EMAIL, PATIENT_PASSWORD);
  await patientPage.screenshot({ path: file("patient_dashboard"), fullPage: true });
  await gotoAndShot(patientPage, `${BASE_URL}/storeindex.php`, "patient_storeindex");
  await gotoAndShot(patientPage, `${BASE_URL}/cart.php`, "patient_cart");
  await gotoAndShot(patientPage, `${BASE_URL}/delivery_form.php`, "patient_delivery_form");
  await patientContext.close();

  // Doctor
  const doctorContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const doctorPage = await doctorContext.newPage();
  await login(doctorPage, "doctor", DOCTOR_EMAIL, DOCTOR_PASSWORD);
  await doctorPage.screenshot({ path: file("doctor_dashboard"), fullPage: true });
  await gotoAndShot(doctorPage, `${BASE_URL}/storeindex.php`, "doctor_storeindex");
  await gotoAndShot(doctorPage, `${BASE_URL}/cart.php`, "doctor_cart");
  await doctorContext.close();

  console.log(`Saved screenshots to: ${OUT_DIR}`);
} finally {
  await browser.close();
}

