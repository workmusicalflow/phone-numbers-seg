// Simple notification service fallback for stores
// (since useQuasar() can only be used in Vue components)

const notification = {
  success: (message: string) => {
    console.log(`[SUCCESS] ${message}`);
  },
  
  error: (message: string) => {
    console.error(`[ERROR] ${message}`);
  },
  
  warning: (message: string) => {
    console.warn(`[WARNING] ${message}`);
  },
  
  info: (message: string) => {
    console.info(`[INFO] ${message}`);
  }
};

export default notification;