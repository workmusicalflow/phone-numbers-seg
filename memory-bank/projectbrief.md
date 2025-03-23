# Project Brief: Phone Numbers Segmentation Web Application

## Overview

This project aims to create a web application for segmenting Côte d'Ivoire (Ivory Coast) phone numbers into meaningful components. The application will allow users to input phone numbers and receive a breakdown of various segments (such as country code, operator, etc.) to facilitate better understanding and organization of phone number data.

The application will support the following Côte d'Ivoire phone number formats:

- International format with + prefix: +2250777104936
- International format with 00 prefix: 002250777104936
- Local format: 0777104936

## Core Requirements

### Functional Requirements

- Allow users to input phone numbers for segmentation
- Process phone numbers to identify and extract meaningful segments
- Display segmented components in a clear, user-friendly interface
- Store phone numbers and their segments for future reference
- Support CRUD operations for phone numbers and segments

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

- `phone_numbers`: id, number, date_added
- `segments`: id, phone_number_id, segment_type, value

## Development Approach

The project will follow an incremental, test-driven development approach, with each feature being developed in small, testable increments.

## Success Criteria

- A functional web application that accurately segments phone numbers
- Clean, maintainable code following SOLID principles
- Comprehensive test coverage
- Clear documentation for both users and developers
