/**
 * Environment Configuration
 * 
 * This file provides utilities for accessing environment variables in a type-safe way.
 * It handles fallbacks and provides error logging for missing variables.
 */

/**
 * Get an environment variable with type safety and fallback support
 * 
 * @param key The environment variable key (without VITE_ prefix)
 * @param defaultValue Optional default value if the environment variable is not set
 * @returns The environment variable value or the default value
 */
export const getEnvironmentVariable = (key: string, defaultValue?: string): string => {
  // Vite prefixes environment variables with VITE_
  const fullKey = `VITE_${key}`;
  
  // Access the environment variable from import.meta.env
  // This is how Vite exposes environment variables
  const value = import.meta.env[fullKey] ?? defaultValue;
  
  if (value === undefined) {
    console.error(`Environment variable ${key} is not defined and no default value was provided`);
    return '';
  }
  
  return value;
};

/**
 * Check if the current environment is development
 * 
 * @returns True if the current environment is development
 */
export const isDevelopment = (): boolean => {
  return import.meta.env.DEV === true;
};

/**
 * Check if the current environment is production
 * 
 * @returns True if the current environment is production
 */
export const isProduction = (): boolean => {
  return import.meta.env.PROD === true;
};
