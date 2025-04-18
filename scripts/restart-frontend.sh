#!/bin/bash

echo "Stopping any running frontend development servers..."
pkill -f "node.*vite" || echo "No running Vite processes found."

echo "Clearing node_modules/.vite cache..."
rm -rf frontend/node_modules/.vite

echo "Rebuilding frontend..."
cd frontend
npm run build

echo "Starting development server..."
npm run dev
