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

- **phone_numbers**: Stores phone numbers with business information

  - `id`: Primary key
  - `number`: The phone number string
  - `name`: Name associated with the phone number
  - `company`: Company associated with the phone number
  - `sector`: Business sector associated with the phone number
  - `notes`: Additional notes about the phone number
  - `date_added`: Timestamp of when the number was added

- **technical_segments**: Stores the technical segments extracted from phone numbers

  - `id`: Primary key
  - `phone_number_id`: Foreign key to phone_numbers table
  - `segment_type`: Type of segment (e.g., country_code, operator_code)
  - `value`: The actual segment value

- **custom_segments**: Stores business-oriented segments for grouping phone numbers

  - `id`: Primary key
  - `name`: Name of the segment (e.g., "VIP Clients", "Healthcare Sector")
  - `description`: Description of the segment

- **phone_number_segments**: Stores the many-to-many relationship between phone numbers and custom segments
  - `phone_number_id`: Foreign key to phone_numbers table
  - `custom_segment_id`: Foreign key to custom_segments table
  - `date_added`: Timestamp of when the association was created

### Relationships

- One-to-many relationship between phone_numbers and technical_segments (one phone number can have multiple technical segments)
- Many-to-many relationship between phone_numbers and custom_segments (through phone_number_segments)

## API Design

### Endpoints

#### Phone Numbers

- `GET /phones`: List all phone numbers (with pagination)
- `GET /phones/{id}`: Get a specific phone number with its segments
- `POST /phones`: Add a new phone number
- `PUT /phones/{id}`: Update a phone number
- `DELETE /phones/{id}`: Delete a phone number

#### Segmentation

- `POST /segment`: Segment a phone number without saving it
- `POST /batch-segment`: Segment multiple phone numbers without saving them
- `POST /batch-phones`: Create multiple phone numbers with segmentation

#### Custom Segments

- `GET /segments`: List all custom segments
- `GET /segments/{id}`: Get a specific custom segment with its phone numbers
- `POST /segments`: Create a new custom segment
- `PUT /segments/{id}`: Update a custom segment
- `DELETE /segments/{id}`: Delete a custom segment

#### Segment Associations

- `POST /phones/{id}/segments/{segmentId}`: Add a phone number to a custom segment
- `DELETE /phones/{id}/segments/{segmentId}`: Remove a phone number from a custom segment
- `GET /segments/{id}/phones`: Get all phone numbers in a custom segment

#### Search

- `GET /phones/search`: Search phone numbers by various criteria

## Deployment Considerations

- The application will be deployed as a standard PHP web application
- Database migrations should be run during deployment
- Static assets should be properly cached
- Sample custom segments should be created during initial deployment
