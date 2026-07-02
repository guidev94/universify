# Coding Conventions

## Python
- Follow PEP 8
- Use type hints where possible
- Use `logging` instead of `print`
- Keep controllers (blueprints) thin; logic in models or services

## Database
- Use `database.get_connection()` for all DB access
- Use Query Builder or bound parameters (SQLite `?`)
- Always close connections in `finally` blocks
- Use WAL mode for better concurrency

## Frontend
- Tailwind CSS classes for styling
- jQuery for DOM manipulation and AJAX
- Lucide icons for UI elements
- Responsive design with `sm:`, `md:`, `lg:` prefixes

## Git
- Conventional commits (`feat:`, `fix:`, `refactor:`, `test:`, `chore:`)
- Short-lived branches for features and fixes
