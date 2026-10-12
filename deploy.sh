#!/bin/bash
# ATCL26 One-Command Production Deployment Script
set -e

echo "🚀 [1/3] Pulling latest code from GitHub..."
git pull

echo "📦 [2/3] Rebuilding and updating web container..."
docker compose up -d --build web

echo "🔄 [3/3] Running database migrations..."
docker compose exec web php database/migrate.php

echo "✅ Deployment completed successfully! Site is live at https://atcl.site"
