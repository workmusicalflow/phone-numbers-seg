<?php

/**
 * URL Configuration
 * 
 * This file defines all URL constants used throughout the application.
 * URLs are read from environment variables when available, with fallbacks to default values.
 * 
 * Usage:
 * - Always use these constants instead of hardcoding URLs
 * - Access via UrlConfig::getXxxUrl() methods
 */

namespace App\Config;

class UrlConfig
{
    /**
     * Base URLs
     */

    /**
     * Get the base API URL
     * 
     * @return string The base API URL
     */
    public static function getApiBaseUrl(): string
    {
        return $_ENV['API_BASE_URL'] ?? 'http://localhost:8000/api';
    }

    /**
     * Get the base frontend URL
     * 
     * @return string The base frontend URL
     */
    public static function getFrontendBaseUrl(): string
    {
        return $_ENV['FRONTEND_BASE_URL'] ?? 'http://localhost:3000';
    }

    /**
     * API Endpoints
     */

    /**
     * Get the users API endpoint
     * 
     * @return string The users API endpoint
     */
    public static function getUsersEndpoint(): string
    {
        return self::getApiBaseUrl() . '/users';
    }

    /**
     * Get the contacts API endpoint
     * 
     * @return string The contacts API endpoint
     */
    public static function getContactsEndpoint(): string
    {
        return self::getApiBaseUrl() . '/contacts';
    }

    /**
     * Get the contact groups API endpoint
     * 
     * @return string The contact groups API endpoint
     */
    public static function getContactGroupsEndpoint(): string
    {
        return self::getApiBaseUrl() . '/contact-groups';
    }

    /**
     * Get the SMS API endpoint
     * 
     * @return string The SMS API endpoint
     */
    public static function getSmsEndpoint(): string
    {
        return self::getApiBaseUrl() . '/sms';
    }

    /**
     * Get the SMS history API endpoint
     * 
     * @return string The SMS history API endpoint
     */
    public static function getSmsHistoryEndpoint(): string
    {
        return self::getSmsEndpoint() . '/history';
    }

    /**
     * Get the GraphQL API endpoint
     * 
     * @return string The GraphQL API endpoint
     */
    public static function getGraphqlEndpoint(): string
    {
        return $_ENV['GRAPHQL_ENDPOINT'] ?? '/graphql.php';
    }

    /**
     * External Services
     */

    /**
     * Get the Orange API URL
     * 
     * @return string The Orange API URL
     */
    public static function getOrangeApiUrl(): string
    {
        return $_ENV['ORANGE_API_URL'] ?? 'https://api.orange.com';
    }

    /**
     * Get the Orange API token URL
     * 
     * @return string The Orange API token URL
     */
    public static function getOrangeApiTokenUrl(): string
    {
        return self::getOrangeApiUrl() . '/oauth/v3/token';
    }

    /**
     * Get the Orange API SMS URL
     * 
     * @return string The Orange API SMS URL
     */
    public static function getOrangeApiSmsUrl(): string
    {
        return self::getOrangeApiUrl() . '/smsmessaging/v1/outbound';
    }

    /**
     * Frontend Routes
     */

    /**
     * Get the login page URL
     * 
     * @return string The login page URL
     */
    public static function getLoginPageUrl(): string
    {
        return self::getFrontendBaseUrl() . '/login';
    }

    /**
     * Get the dashboard page URL
     * 
     * @return string The dashboard page URL
     */
    public static function getDashboardPageUrl(): string
    {
        return self::getFrontendBaseUrl() . '/dashboard';
    }

    /**
     * Get the contacts page URL
     * 
     * @return string The contacts page URL
     */
    public static function getContactsPageUrl(): string
    {
        return self::getFrontendBaseUrl() . '/contacts';
    }

    /**
     * Get the SMS page URL
     * 
     * @return string The SMS page URL
     */
    public static function getSmsPageUrl(): string
    {
        return self::getFrontendBaseUrl() . '/sms';
    }
}
