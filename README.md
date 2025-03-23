# Phone Numbers Segmentation Web Application

A web application for segmenting Côte d'Ivoire phone numbers into meaningful components.

## Overview

This application allows users to input phone numbers from Côte d'Ivoire and receive a breakdown of various segments (such as country code, operator, etc.) to facilitate better understanding and organization of phone number data.

## Supported Phone Number Formats

- International format with + prefix: +2250777104936
- International format with 00 prefix: 002250777104936
- Local format: 0777104936

## Features

- Input phone numbers for segmentation
- Process phone numbers to identify and extract meaningful segments
- Display segmented components in a clear, user-friendly interface
- Store phone numbers and their segments for future reference
- Support CRUD operations for phone numbers and segments

## Technology Stack

- **Backend**: PHP, SQLite
- **Frontend**: HTML/CSS, HTMX, Alpine.js
- **Testing**: PHPUnit

## Project Structure

- `src/`: Code source (models, services, controllers)
- `public/`: Publicly accessible files (HTML, CSS, JS)
- `tests/`: Unit and integration tests

## Installation

1. Clone the repository:

   ```
   git clone https://github.com/workmusicalflow/phone-numbers-seg.git
   ```

2. Install dependencies:

   ```
   composer install
   ```

3. Configure the database connection

4. Start the development server:
   ```
   php -S localhost:8000 -t public
   ```

## Development

This project follows a three-layer architecture:

- Presentation layer using HTMX and Alpine.js
- Business logic layer with PHP services
- Data layer with SQLite database

## Testing

Run tests with PHPUnit:

```
composer test
```

## License

Proprietary
