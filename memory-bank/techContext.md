# Technical Context: Phone Numbers Segmentation Web Application

## Technology Stack

### Backend

- **PHP**: Core programming language for server-side logic
- **Composer**: Dependency management for PHP packages
- **SQLite**: Lightweight, file-based relational database

### Frontend

- **HTML/CSS**: Basic structure and styling
- **HTMX**: For AJAX requests and dynamic content updates without writing JavaScript
- **Alpine.js**: Lightweight JavaScript framework for adding reactivity to the frontend

### Testing

- **PHPUnit**: Testing framework for PHP applications

### Version Control

- **Git**: Source code management

## Development Environment Setup

### Prerequisites

- PHP 8.0 or higher
- Composer
- SQLite
- Web server (Apache/Nginx) or PHP's built-in server for development

### Installation Steps

1. Clone the repository
2. Run `composer install` to install dependencies
3. Configure the database connection
4. Run database migrations
5. Start the development server

## Project Dependencies

### PHP Packages

- PHPUnit for testing
- (Other packages will be added as needed)

### Frontend Libraries

- HTMX (loaded via CDN)
- Alpine.js (loaded via CDN)

## Technical Constraints

### Performance Considerations

- The application should respond to user requests within a reasonable time frame
- Database queries should be optimized for performance
- Frontend should be responsive and not block user interaction

### Security Considerations

- Input validation to prevent SQL injection
- Protection against XSS attacks
- Proper error handling to avoid leaking sensitive information

### Compatibility

- The application should work on modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile-friendly design

## Database Design

### Schema

- **phone_numbers**: Stores the raw phone numbers

  - `id`: Primary key
  - `number`: The phone number string
  - `date_added`: Timestamp of when the number was added

- **segments**: Stores the segments extracted from phone numbers
  - `id`: Primary key
  - `phone_number_id`: Foreign key to phone_numbers table
  - `segment_type`: Type of segment (e.g., country code, operator)
  - `value`: The actual segment value

### Relationships

- One-to-many relationship between phone_numbers and segments (one phone number can have multiple segments)

## API Design

### Endpoints

- `POST /phone-numbers`: Add a new phone number
- `GET /phone-numbers`: List all phone numbers
- `GET /phone-numbers/{id}`: Get a specific phone number with its segments
- `PUT /phone-numbers/{id}`: Update a phone number
- `DELETE /phone-numbers/{id}`: Delete a phone number
- `POST /phone-numbers/{id}/segments`: Add a segment to a phone number
- `GET /phone-numbers/{id}/segments`: Get all segments for a phone number

## Deployment Considerations

- The application will be deployed as a standard PHP web application
- Database migrations should be run during deployment
- Static assets should be properly cached
