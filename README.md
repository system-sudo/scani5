# Scani5

**Empower Your Security Journey with Seamless Solutions**

Scani5 is a comprehensive developer tool designed to streamline the development and testing processes for Laravel applications.

---

## 🚀 Built With

- PHP
- JavaScript
- Docker
- Composer
- NPM
- Vite
- JSON
- XML
- Adminer
- Axios
- BootStrap
- Saas

---

## 📚 Table of Contents

- Overview
- Getting Started
  - Prerequisites
  - Installation
  - Usage
  - Testing

---

## 🧭 Overview

Scani5 empowers developers with a suite of features that simplify testing, asset management, and user role management. Its core features include:

- **PHPUnit Configuration**: Establishes a structured environment for testing, ensuring code quality and reliability.
- **Vite Integration**: Streamlines asset management and live reloading, enhancing the development workflow.
- **Docker Compose Setup**: Orchestrates a multi-container environment, simplifying local development and testing.
- **Role and Permission Management**: Facilitates user role assignments and access control, enhancing security.
- **Real-time Notifications**: Improves user engagement through timely updates and alerts.

---

## 🛠️ Getting Started

### Prerequisites

Ensure the following dependencies are installed:

- PHP
- Composer
- NPM
- Docker

### Installation

#### 1.	Clone the repository:

```bash
git clone https://github.com/system-sudo/scani5
cd scani5
```
#### 2.	Navigate to the project directory:
```bash
cd scani5
```
#### 3.	Install the dependencies: 
##### a) Build the Docker image:  
```bash
docker build -t system-sudo/scani5 .
```
##### b) Or use Docker Compose:
```bash
docker-compose up --build
```
##### Install frontend dependencies:
```bash
npm install
```
```bash
composer install
```
### Usage
##### a) Using Docker:
```bash
docker run system-sudo/scani5
```
##### b) Using Docker Compose:
```bash
docker-compose up
```
##### c) Using npm:
```bash
npm run dev
```

### Testing 
##### a) Using Docker:
```bash
docker exec -it scani5 php artisan test
```
##### b) Using Docker Compose:
```bash
docker-compose exec app php artisan test
```
##### c) Using npm:
```bash
npm run test
```
