# Insta-Lite Modern Codebase

This project is a modernized, full-stack Instagram-lite clone using Svelte (frontend) and PHP (Slim Framework, backend).

---

## Getting Started

### 1. Clone the Repository
```bash
git clone https://github.com/alexmvie/insta-lite-php-svelte5.git
cd insta-lite-php-svelte5
```

### 2. Install Dependencies

#### Backend (PHP, Slim)
```
cd server
composer install
```

#### Frontend (Svelte)
```
cd ../client
npm install
```

### 3. Environment Configuration

- **Backend:**
  - Copy the example database config:
    ```bash
    cp server/config/database.example.php server/config/database.php
    ```
  - Edit `server/config/database.php` with your database credentials.
  - (Optional) Set up a `.env` file for additional secrets (see `.env.example` if provided).

- **Frontend:**
  - Configure environment variables if needed (see `.env` files in `/client`).

### 4. Run the Development Servers

#### Backend (from `server` directory):
```
php -S localhost:8000 -t public
```

#### Frontend (from `client` directory):
```
npm run dev
```

- Frontend: http://localhost:5173
- Backend API: http://localhost:8000/api

### 5. First Login/Register
- Register a user via the UI or API if no users exist.
- Login with your credentials.

---

## Notes
- **`vendor/` and `node_modules/` are not committed to git.** You must run `composer install` and `npm install` after cloning.
- **Sensitive files** like `server/config/database.php` and `.env` are gitignored. Use the provided example files as templates.
- For production, ensure environment variables and secrets are set securely.

---

## Troubleshooting
- If you see errors about missing dependencies, make sure you ran `composer install` in `/server` and `npm install` in `/client`.
- For CORS/API issues, ensure both servers are running and accessible from your browser.

---

## Project Structure
- `/client` — Svelte frontend
- `/server` — PHP Slim backend

---

## License
MIT
