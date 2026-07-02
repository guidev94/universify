# Testing Strategy

## Test Frameworks
- **Unit/Integration:** `pytest`
- **E2E:** `pytest-playwright`

## Running Tests
To run all tests:
```bash
pytest
```

To run E2E tests (requires server running):
```bash
pytest tests/test_e2e_features.py
```

## Test Structure
- `tests/test_models.py`: Database model logic
- `tests/test_app.py`: API endpoints and routing
- `tests/test_e2e_features.py`: Full user flow testing with Playwright

## Configuration
Playwright is configured to use `http://localhost:5000` as base URL.
The server should be started in a separate process or via a fixture.
