# Laravel Banking System

Welcome to the Laravel Banking System project! This project implements a simple banking system with support for deposit and withdrawal operations for Individual and Business users.

## Prerequisites

- PHP >= 8.1
- Composer (https://getcomposer.org/)
- MySQL or other compatible database
- Laravel (https://laravel.com/docs/10.x/installation)

## Getting Started

1. Clone the repository:

   ```bash
   git clone git@github.com:shamsPathan/coding-test.git
   
2. Navigate to the project directory:

   ```bash
    cd coding-test
3. Install project dependencies:

    ```bash
    composer install
4. Copy the .env.example file to .env and configure your database settings:
    ```bash
    cp .env.example .env
    (Update .env file with your database credentials.)
5. Generate an application key:
    ```bash
    php artisan key:generate
    
6. Run the database migrations to set up the database:
    ```bash
    php artisan migrate
    
7. Start the development server:
    ```bash
    php artisan serve

Your Laravel development server should now be running at http://localhost:8000.

## API Endpoints

- **POST /api/users:** Create a new user with name, account type, email, and password.
- **POST /api/login:** Login user with email and password.
- **GET /api/:** Show all transactions and current balance.
- **GET /api/deposit:** Show all deposited transactions.
- **POST /api/deposit:** Accept user ID and amount, and update user's balance.
- **GET /api/withdrawal:** Show all withdrawal transactions.
- **POST /api/withdrawal:** Accept user ID and amount, and process withdrawal.


## Testing

To run the tests, use the following command:

    php artisan test
    
    
## Contributing

Feel free to contribute by opening issues or submitting pull requests.

## License
[![License: GPL-3.0](https://img.shields.io/badge/License-GPL--3.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)


This project is licensed under the GNU General Public License, Version 3.0. 

