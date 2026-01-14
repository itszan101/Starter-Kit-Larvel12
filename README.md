# Laravel API Starter Kit (Open Source)

A minimal, opinionated **Laravel API starter kit** with authentication, role-based access control (RBAC), and permission management out of the box.

This project is intended to be a **clean foundation** for building production-ready APIs without reinventing authentication, authorization, and user management every single time.

---

## Features

* Token-based authentication using **Laravel Sanctum**
* User registration & login API
* User management (CRUD)
* Role management
* Permission management
* Role–permission assignment
* User–role assignment
* Endpoint protection using permission middleware

Designed to be:

* Framework-native (no weird abstractions)
* Frontend-agnostic (web, mobile, or anything else)
* Easy to extend

---

## Tech Stack

* PHP >= 8.3
* Laravel
* Laravel Sanctum
* Spatie Laravel Permission
* MySQL / PostgreSQL

---

## Installation

Clone the repository:

```bash
git clone <repository-url>
cd <project-name>
```

Install dependencies:

```bash
composer install
```

Create environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`, then run:

```bash
php artisan migrate
php artisan db:seed
```

Run the application:

```bash
php artisan serve
```

---

## Authentication

This project uses **Bearer Token authentication** via Laravel Sanctum.

After login or register, the API will return an access token.

Include it in every authenticated request:

```
Authorization: Bearer <your-token>
```

---

## API Endpoints

### Public Endpoints

| Method | Endpoint        | Description       |
| ------ | --------------- | ----------------- |
| POST   | `/api/register` | Register new user |
| POST   | `/api/login`    | Login user        |

---

### Protected Endpoints

All routes below require:

* `auth:sanctum`
* Proper permission

---

### User Management

| Method | Endpoint            | Permission         |
| ------ | ------------------- | ------------------ |
| GET    | `/api/users`        | `user.view`        |
| POST   | `/api/users`        | `user.create`      |
| GET    | `/api/users/{id}`   | `user.view`        |
| PUT    | `/api/users/{id}`   | `user.update`      |
| DELETE | `/api/users/{id}`   | `user.delete`      |
| PUT    | `/api/user/profile` | Authenticated user |

---

### Role Management

| Method | Endpoint                        | Permission    |
| ------ | ------------------------------- | ------------- |
| GET    | `/api/roles`                    | `role.view`   |
| POST   | `/api/roles`                    | `role.create` |
| DELETE | `/api/roles/{id}`               | `role.delete` |
| GET    | `/api/roles/{role}/permissions` | `role.view`   |

---

### Permission Management

| Method | Endpoint                               | Permission              |
| ------ | -------------------------------------- | ----------------------- |
| GET    | `/api/permissions`                     | `permission.view`       |
| POST   | `/api/permissions`                     | `permission.create`     |
| DELETE | `/api/permissions/{id}`                | `permission.delete`     |
| POST   | `/api/roles/{role}/permissions/update` | `permission.assignRole` |

---

### User Role Assignment

| Method | Endpoint                       | Permission        |
| ------ | ------------------------------ | ----------------- |
| POST   | `/api/users/{id}/roles/update` | `role.assignUser` |

---

## Authorization Model

* Permissions are checked using middleware
* Roles act as permission groups
* Users can have multiple roles
* Permissions can be assigned directly to roles

This allows fine-grained access control without hardcoding logic in controllers.

---

## Recommended Usage

* Create roles & permissions via seeder or admin endpoint
* Assign roles to users instead of direct permissions
* Keep one `super-admin` role with full access

---

## Contributing

Contributions are welcome.

* Fork the repository
* Create a feature branch
* Submit a pull request with clear explanation

Keep it simple. No unnecessary magic.

---

## 📄 License

This project is open-source and available under the **MIT License**.

Use it, modify it, ship it. Just don’t pretend you wrote it from scratch.
