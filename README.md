Tentu, ini dia dokumen `README.md` yang sama, disajikan dalam **Bahasa Inggris**, dengan fokus pada instalasi Laravel, konfigurasi SQLite, dan menjalankan *server* serta *queue worker*.

Anda dapat menyalin dan menempelkan teks ini ke dalam *file* **`README.md`** Anda.

-----

# 🚀 test-yoprint

This project is a web application built using the **Laravel Framework**. This documentation will guide you through the steps to install and run this application locally, utilizing **SQLite** as the database.

## 🛠️ System Requirements

Before you begin, ensure you have the following components installed in your development environment:

  * **PHP** (A version supported by Laravel, php 8.4)
  * **Composer**
  * **Git**

## 📦 Installation and Setup

Follow the steps below to install and configure the project.

### 1\. Clone the Repository

Clone the repository to your local machine and navigate into the directory:

```bash
git clone https://github.com/Syuja010701/test-yoprint.git
cd test-yoprint
```

### 2\. Install Dependencies

Use Composer to install all necessary PHP dependencies for the project:

```bash
composer install
```

### 3\. Environment Configuration

Duplicate the base environment configuration file (`.env.example`) to create your local `.env` file.

```bash
cp .env.example .env
```

### 4\. Generate Application Key

Generate a unique application encryption key. This key will be automatically set in your `.env` file.

```bash
php artisan key:generate
```

## ⚙️ Database Configuration (SQLite)

This project is configured to use **SQLite** for easy local development.

### 5\. Prepare the Database File

Create the SQLite database file inside the `database/` directory:

```bash
touch database/database.sqlite
```

### 6\. Configure Connection in `.env`

Ensure your database connection settings in the **`.env`** file are configured to use SQLite:

```env
DB_CONNECTION=sqlite
# Lines for DB_HOST, DB_DATABASE, etc., should be commented out or removed when using SQLite.
```

### 7\. Run Migrations

Execute the migration command to create all the necessary database tables:

```bash
php artisan migrate
```

## ▶️ Running the Application

Once the installation steps are complete, you can start the application server and the queue worker.

### 8\. Start the Local Server

Run the Laravel development server. Your application will be accessible in your browser.

```bash
php artisan serve
```

> 🌐 **Access Application:** Open `http://127.0.0.1:8000` in your browser.

### 9\. Start the Queue Worker

If your application has background tasks (such as sending emails or heavy processing), you must run the queue worker in a **separate terminal window**:

```bash
php artisan queue:work
```

> **Important Note:** This command will continue running and listening for new jobs. You must keep it running in a dedicated terminal window for queue jobs to be processed.

-----