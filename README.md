# Exchain – Backend API

## Overview

Exchain is a full-stack money transfer platform designed to facilitate secure, fast, and user-friendly cross-border transactions.

This repository contains the backend API built with Laravel, handling authentication, transactions, real-time updates, payments, and integrations with third-party services.

---

## Features

### Authentication & Authorization

* JWT-based authentication
* Social login (Google, GitHub)
* Role-based access control:

  * Admin
  * User (Sender/Receiver)
  * Agent / Partner Store

### Core Functionalities

* Money transfer management
* Multi-currency support with real-time exchange rates
* Beneficiary management
* Transaction tracking and history
* Refunds and dispute handling
* Reviews and ratings system

### Payments

* Stripe integration for secure payments
* Support for multiple payment methods

### Real-Time System

* Real-time notifications using Laravel Reverb
* Event broadcasting for:

  * Transfer status updates
  * Agent requests
  * Notifications

### External Integrations

* Currency exchange API
* AI chatbot powered by Gemini (with PDF-based contextual knowledge)
* Email automation system

### Admin Features

* Agent approval system
* Transaction monitoring
* Fraud detection handling (basic)
* Reports and analytics

---

## Tech Stack

* Laravel
* MySQL / PostgreSQL
* JWT Authentication
* Stripe API
* Laravel Reverb (WebSockets)
* Gemini AI API
* RESTful API architecture

---

## Project Structure

```
app/
routes/
database/
config/
```

---

## Installation

### Prerequisites

* PHP >= 8.x
* Composer
* MySQL or PostgreSQL
* Node.js (for assets if needed)

### Setup

```bash
git clone https://github.com/dev-loves-code/Exchain.git
cd Exchain

composer install
cp .env.example .env
php artisan key:generate
```

### Configure Environment

Update `.env` with:

* Database credentials
* JWT secret
* Stripe keys
* Reverb configuration
* Gemini API key
* Mail configuration

```bash
php artisan migrate
php artisan serve
```

---

## Real-Time Setup (Reverb)

```bash
php artisan reverb:start
```

Make sure broadcasting is properly configured in `.env`.

---

## API Documentation

* RESTful endpoints available
* Use Postman or Swagger (if configured)

---

## Roles & Permissions

| Role  | Capabilities                        |
| ----- | ----------------------------------- |
| Admin | Full system control                 |
| User  | Send/receive money, manage accounts |
| Agent | Handle payouts and cash operations  |

---

## Key Highlights

* Secure authentication using JWT
* Real-time system using WebSockets
* AI-powered support chatbot
* Scalable and modular architecture
* Third-party integrations (Stripe, currency APIs)

---

## Future Improvements

* Advanced fraud detection
* Microservices architecture
* Enhanced analytics dashboard

