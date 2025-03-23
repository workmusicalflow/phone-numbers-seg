# Project Brief: Phone Numbers Segmentation Web Application

## Overview

This project aims to create a web application for segmenting Côte d'Ivoire (Ivory Coast) phone numbers into meaningful components and grouping them by business criteria. The application allows users to input phone numbers and receive a breakdown of various technical segments (such as country code, operator, etc.) while also supporting custom business segmentation for SMS campaigns and customer management.

The application supports the following Côte d'Ivoire phone number formats:

- International format with + prefix: +2250777104936
- International format with 00 prefix: 002250777104936
- Local format: 0777104936

## Core Requirements

### Functional Requirements

- Allow users to input phone numbers for technical segmentation
- Process phone numbers to identify and extract meaningful technical segments
- Create and manage custom business segments for grouping phone numbers
- Assign phone numbers to business segments for SMS campaigns
- Display segmented components in a clear, user-friendly interface
- Store phone numbers, their technical segments, and business segment associations
- Support CRUD operations for phone numbers and segments
- Support batch processing for multiple phone numbers

### Technical Requirements

- Develop a PHP-based web application with a modular architecture
- Implement a three-layer architecture:
  - Presentation layer using HTMX and Alpine.js
  - Business logic layer with PHP services
  - Data layer with SQLite database
- Follow SOLID principles for maintainable, testable code
- Implement test-driven development (TDD) using PHPUnit
- Use Git for version control

## Project Structure

- `src/`: Code source (models, services, controllers)
- `public/`: Publicly accessible files (HTML, CSS, JS)
- `tests/`: Unit and integration tests

## Database Schema

- `phone_numbers`: id, number, name, company, sector, notes, date_added
- `technical_segments`: id, phone_number_id, segment_type, value
- `custom_segments`: id, name, description
- `phone_number_segments`: phone_number_id, custom_segment_id, date_added

## Segmentation Types

### Technical Segmentation

Technical segmentation extracts information embedded in the phone number structure:

- Country code (e.g., 225 for Côte d'Ivoire)
- Operator code (e.g., 07 for MTN)
- Subscriber number
- Operator name (e.g., MTN, Orange, Moov)

### Business Segmentation

Business segmentation groups phone numbers by business criteria:

- Sector (e.g., healthcare, education, finance)
- Company
- Client type (e.g., VIP, business, individual)
- Custom categories defined by the user

## Development Approach

The project follows an incremental, test-driven development approach, with each feature being developed in small, testable increments.

## Success Criteria

- A functional web application that accurately segments phone numbers
- Support for both technical and business-oriented segmentation
- Ability to manage custom segments for business purposes
- Clean, maintainable code following SOLID principles
- Comprehensive test coverage
- Clear documentation for both users and developers
