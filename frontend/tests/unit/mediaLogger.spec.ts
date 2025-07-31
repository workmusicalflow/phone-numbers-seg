import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { MediaLogger, LogLevel } from '../../src/services/mediaLogger';

describe('MediaLogger', () => {
  let mediaLogger: MediaLogger;
  let localStorageMock: Record<string, string> = {};

  beforeEach(() => {
    // Mock localStorage
    localStorageMock = {};
    
    global.localStorage = {
      getItem: vi.fn((key) => localStorageMock[key] || null),
      setItem: vi.fn((key, value) => { localStorageMock[key] = value.toString(); }),
      removeItem: vi.fn((key) => { delete localStorageMock[key]; }),
      clear: vi.fn(() => { localStorageMock = {}; }),
      length: 0,
      key: vi.fn((index) => ''),
    };
    
    // Mock console methods
    global.console.debug = vi.fn();
    global.console.info = vi.fn();
    global.console.warn = vi.fn();
    global.console.error = vi.fn();
    
    // Mock navigator
    Object.defineProperty(global.navigator, 'onLine', { value: true, writable: true });
    Object.defineProperty(global.navigator, 'userAgent', { value: 'test-agent', writable: true });
    Object.defineProperty(global.navigator, 'language', { value: 'fr-FR', writable: true });
    Object.defineProperty(global.navigator, 'platform', { value: 'test-platform', writable: true });
    Object.defineProperty(global.navigator, 'vendor', { value: 'test-vendor', writable: true });
    
    // Create new instance for each test
    mediaLogger = new MediaLogger();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should create a correlation ID', () => {
    const id = mediaLogger.createCorrelationId();
    expect(id).toBeTruthy();
    expect(typeof id).toBe('string');
    expect(mediaLogger.getCorrelationId()).toBe(id);
  });

  it('should set and get correlation ID', () => {
    const testId = 'test-correlation-id';
    mediaLogger.setCorrelationId(testId);
    expect(mediaLogger.getCorrelationId()).toBe(testId);
  });

  it('should log messages with different levels', () => {
    mediaLogger.debug('Debug message');
    mediaLogger.info('Info message');
    mediaLogger.warn('Warning message');
    mediaLogger.error('Error message');

    expect(console.debug).toHaveBeenCalledWith(expect.stringContaining('Debug message'), expect.anything());
    expect(console.info).toHaveBeenCalledWith(expect.stringContaining('Info message'), expect.anything());
    expect(console.warn).toHaveBeenCalledWith(expect.stringContaining('Warning message'), expect.anything());
    expect(console.error).toHaveBeenCalledWith(expect.stringContaining('Error message'), expect.anything());
  });

  it('should store logs in localStorage', () => {
    mediaLogger.info('Test log message');
    
    expect(localStorage.setItem).toHaveBeenCalled();
    const storageKey = (mediaLogger as any).STORAGE_KEY;
    expect(localStorageMock[storageKey]).toBeTruthy();
    
    const logs = JSON.parse(localStorageMock[storageKey]);
    expect(logs.length).toBeGreaterThan(0);
    expect(logs[0].message).toBe('Test log message');
    expect(logs[0].level).toBe(LogLevel.INFO);
  });

  it('should retrieve logs by level', () => {
    mediaLogger.debug('Debug message');
    mediaLogger.info('Info message');
    mediaLogger.warn('Warning message');
    mediaLogger.error('Error message');
    
    const errorLogs = mediaLogger.getLogsByLevel(LogLevel.ERROR);
    expect(errorLogs.length).toBe(1);
    expect(errorLogs[0].message).toBe('Error message');
  });

  it('should retrieve logs by operation', () => {
    mediaLogger.info('Operation 1 log', null, 'operation1');
    mediaLogger.info('Operation 2 log', null, 'operation2');
    mediaLogger.info('Another operation 1 log', null, 'operation1');
    
    const op1Logs = mediaLogger.getLogsByOperation('operation1');
    expect(op1Logs.length).toBe(2);
    expect(op1Logs[0].operation).toBe('operation1');
    expect(op1Logs[1].operation).toBe('operation1');
  });

  it('should include correlation ID in logs when set', () => {
    const testId = 'test-correlation-id';
    mediaLogger.setCorrelationId(testId);
    mediaLogger.info('Test message with correlation');
    
    const logs = mediaLogger.getLogs();
    expect(logs[0].correlationId).toBe(testId);
  });

  it('should clear all logs', () => {
    mediaLogger.info('Test log');
    expect(mediaLogger.getLogs().length).toBeGreaterThan(0);
    
    mediaLogger.clearLogs();
    expect(mediaLogger.getLogs().length).toBe(0);
  });

  it('should generate diagnostics', () => {
    const diagnostics = mediaLogger.generateDiagnostics();
    
    // Check basic structure
    expect(diagnostics).toHaveProperty('timestamp');
    expect(diagnostics).toHaveProperty('browser');
    expect(diagnostics).toHaveProperty('networkStatus');
    expect(diagnostics).toHaveProperty('storage');
    expect(diagnostics).toHaveProperty('recentErrors');
    
    // Check browser info
    expect(diagnostics.browser).toHaveProperty('userAgent', 'test-agent');
    expect(diagnostics.browser).toHaveProperty('language', 'fr-FR');
    expect(diagnostics.browser).toHaveProperty('platform', 'test-platform');
    
    // Check network status
    expect(diagnostics.networkStatus).toBe(true);
  });

  it('should sanitize sensitive data before logging', () => {
    const sensitiveData = {
      file: new File(['test'], 'test.txt', { type: 'text/plain' }),
      url: 'blob:https://example.com/123-456',
      password: 'secret123'
    };
    
    mediaLogger.info('Test with sensitive data', sensitiveData);
    
    const logs = mediaLogger.getLogs();
    const loggedData = logs[0].data;
    
    // File should be simplified
    expect(loggedData.file).toHaveProperty('name', 'test.txt');
    expect(loggedData.file).toHaveProperty('type', 'text/plain');
    expect(loggedData.file).toHaveProperty('size');
    
    // Blob URL should be masked
    expect(loggedData.url).toBe('[Blob URL]');
    
    // Other data should pass through
    expect(loggedData.password).toBe('secret123');
  });

  it('should cleanup old logs', () => {
    // Create some old logs
    const oldDate = new Date();
    oldDate.setDate(oldDate.getDate() - 5); // 5 days old
    
    const recentDate = new Date();
    
    const oldLog = {
      timestamp: oldDate.toISOString(),
      level: LogLevel.INFO,
      message: 'Old log'
    };
    
    const recentLog = {
      timestamp: recentDate.toISOString(),
      level: LogLevel.INFO,
      message: 'Recent log'
    };
    
    localStorage.setItem((mediaLogger as any).STORAGE_KEY, JSON.stringify([oldLog, recentLog]));
    
    // Clean up logs older than 3 days
    mediaLogger.cleanupOldLogs(3);
    
    // Verify only recent logs remain
    const logs = mediaLogger.getLogs();
    expect(logs.length).toBe(1);
    expect(logs[0].message).toBe('Recent log');
  });
});