# Virtual-Chikitsa Telemedicine Platform

## Chatbot module (`chatbot/`)

A separate chatbot module is available at `chatbot/index.php`.

### Features
- Symptom/problem prompts -> doctor specialty suggestion + safe general next steps
- Medicine prompts -> general medicine information (uses, dosage guidance ranges, side effects, warnings, contraindications)
- Appointment availability prompts -> reads doctors and current bookings from DB to return available doctors and next open slots
- Session conversation storage using `$_SESSION['chatbot_conversation']`
- Mandatory medical disclaimer + consent shown in chatbot UI and included in chatbot responses
- Logo and shared site branding/header/footer shown on chatbot page

### Files
- `chatbot/index.php` - chatbot UI page
- `chatbot/chat.php` - AJAX endpoint
- `chatbot/chatbot_service.php` - intent routing and DB-backed response services
- `chatbot/tests/intent_test.php` - minimal intent/specialty unit-style test script

### Setup
1. Import DB schema:
   - Use existing `medi.sql`
2. Configure DB connection in `database.php`
3. Run locally (example):
   - `php -S localhost:8000`
4. Open:
   - Main site: `http://localhost:8000/index.php`
   - Chatbot: `http://localhost:8000/chatbot/index.php`

### Validation
- PHP syntax checks:
  - `find . -name "*.php" -print0 | xargs -0 -n1 php -l`
- Chatbot targeted tests:
  - `php chatbot/tests/intent_test.php`
