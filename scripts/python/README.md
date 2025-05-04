# Fake Data Generator for Insta-Lite

This Python script generates fake users with avatars and posts with images for the Insta-Lite application.

## Features

- Creates 3 fake users with unique avatars
- Generates 5 posts per user with realistic timestamps
- Downloads and converts images to WebP format
- Adds random likes between users
- Creates follower relationships
- Updates user statistics (post count, follower count, following count)

## Requirements

- Python 3.7+
- MySQL database
- Environment variables for database connection

## Installation

1. Install the required Python packages:

```bash
pip install -r requirements.txt
```

2. Make sure your database credentials are set in your `.env` file:

```
DB_HOST=localhost
DB_USER=your_username
DB_PASSWORD=your_password
DB_NAME=insta_lite
```

## Usage

Run the script with default settings (3 users, 5 posts each):

```bash
python generate_fake_data.py
```

Or customize the number of users and posts:

```bash
python generate_fake_data.py --users 5 --posts 8
```

## Directory Structure

The script will create the following directories if they don't exist:

- `public/uploads/avatars`: For user avatar images
- `public/uploads/posts`: For post images

## Notes

- All images are downloaded from Unsplash and converted to WebP format
- User avatars are generated using DiceBear Avataaars API
- The script adds random timestamps to make the timeline more realistic
- Make sure your MySQL database has the correct schema before running the script
