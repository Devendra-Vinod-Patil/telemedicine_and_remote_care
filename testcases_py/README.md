# Playwright Smoke Tests

This folder contains a simple Python Playwright smoke suite for the telemed2 project.

## Setup

1. Install dependencies:

```bash
pip install -r requirements.txt
python -m playwright install
```

2. Run the tests:

```bash
python smoke_tests.py
```

## Notes

- The script measures total execution time.
- If all checks pass, it prints `TEST IS COMPLETE`.
- The default base URL is `http://localhost/telemed2`.
