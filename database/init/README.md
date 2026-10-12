# Database Initialization Directory

Place your `.sql` database backup dump file (e.g. `backup.sql` or `atcl26.sql`) in this folder BEFORE running `docker compose up -d` for the very first time.

The official MySQL Docker container automatically reads and imports all `.sql` files found in this directory upon first initialization.
