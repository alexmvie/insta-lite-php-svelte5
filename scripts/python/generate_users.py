#!/usr/bin/env python3
"""
User Generator for Insta-Lite

This script generates fake users for the Insta-Lite application.
It downloads avatars from DiceBear API, converts them to WebP format,
and inserts the data into the MySQL database.
"""

import os
import sys
import random
import hashlib
import requests
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv
from datetime import datetime, timedelta
from faker import Faker
from PIL import Image
import time
import argparse

# Initialize Faker
fake = Faker()

# Configuration
NUM_USERS = 5
IMAGE_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))), 'public/uploads')
AVATAR_DIR = os.path.join(IMAGE_DIR, 'avatars')

def ensure_directories():
    """Ensure that the required directories exist."""
    os.makedirs(AVATAR_DIR, exist_ok=True)
    print(f"✅ Created directory: {AVATAR_DIR}")

def get_database_connection():
    """Connect to the MySQL database using environment variables."""
    load_dotenv()
    
    try:
        connection = mysql.connector.connect(
            host=os.getenv('DB_HOST', 'localhost'),
            user=os.getenv('DB_USER', 'root'),
            password=os.getenv('DB_PASSWORD', ''),
            database=os.getenv('DB_NAME', 'insta_lite'),
            connect_timeout=30
        )
        
        if connection.is_connected():
            print("✅ Connected to MySQL database")
            return connection
        else:
            print("❌ Failed to connect to MySQL database")
            sys.exit(1)
    except Error as e:
        print(f"❌ Error connecting to MySQL database: {e}")
        sys.exit(1)

def download_avatar(username, save_path):
    """Download an avatar from DiceBear API."""
    try:
        # Get avatar from DiceBear API
        avatar_url = f"https://api.dicebear.com/7.x/avataaars/png"
        
        # Download the avatar image
        response = requests.get(avatar_url, params={
            'seed': username,
            'backgroundColor': random.choice(['b6e3f4', 'c0aede', 'd1d4f9']),
            'radius': 50
        }, timeout=10)
        
        response.raise_for_status()
        
        # Save the avatar image
        with open(save_path, 'wb') as f:
            f.write(response.content)
        
        return True
    except Exception as e:
        print(f"Error downloading avatar: {e}")
        return False

def convert_to_webp(input_path, output_path):
    """Convert an image to WebP format."""
    try:
        img = Image.open(input_path)
        img = img.resize((400, 400))
        img.save(output_path, 'WEBP', quality=90)
        return True
    except Exception as e:
        print(f"Error converting image: {e}")
        return False

def create_users(connection, num_users):
    """Create fake users in the database."""
    cursor = connection.cursor()
    users = []
    
    print(f"\n📝 Creating {num_users} fake users...")
    
    for i in range(num_users):
        try:
            # Generate user data
            username = fake.user_name() + str(random.randint(100, 999))
            email = fake.email()
            password_hash = hashlib.sha256(fake.password().encode()).hexdigest()
            bio = fake.text(max_nb_chars=150)
            
            # Generate avatar filename
            avatar_filename = f"avatar_{username}_{int(time.time())}.webp"
            temp_avatar_path = os.path.join(AVATAR_DIR, f"temp_{avatar_filename}.png")
            avatar_path = os.path.join(AVATAR_DIR, avatar_filename)
            
            # Download and convert avatar
            if download_avatar(username, temp_avatar_path):
                if convert_to_webp(temp_avatar_path, avatar_path):
                    # Remove temporary file
                    os.remove(temp_avatar_path)
                    
                    # Create user in database
                    created_at = datetime.now() - timedelta(days=random.randint(1, 30))
                    query = "INSERT INTO users (username, email, password_hash, bio, profile_picture, verified, created_at) VALUES (%s, %s, %s, %s, %s, %s, %s)"
                    cursor.execute(query, (
                        username,
                        email,
                        password_hash,
                        bio,
                        f"/uploads/avatars/{avatar_filename}",
                        1,
                        created_at.strftime('%Y-%m-%d %H:%M:%S')
                    ))
                    
                    user_id = cursor.lastrowid
                    users.append({
                        'id': user_id,
                        'username': username,
                        'created_at': created_at
                    })
                    
                    # Commit after each user
                    connection.commit()
                    
                    print(f"✅ Created user: {username} (ID: {user_id})")
                else:
                    print(f"❌ Failed to convert avatar for user: {username}")
            else:
                print(f"❌ Failed to download avatar for user: {username}")
        except Exception as e:
            print(f"❌ Error creating user: {e}")
    
    return users

def main():
    """Main function to generate fake users."""
    parser = argparse.ArgumentParser(description='Generate fake users for Insta-Lite')
    parser.add_argument('--users', type=int, default=NUM_USERS, help='Number of users to create')
    args = parser.parse_args()
    
    num_users = args.users
    
    print(f"🚀 Starting fake user generation for Insta-Lite")
    
    # Ensure directories exist
    ensure_directories()
    
    # Connect to database
    connection = get_database_connection()
    
    try:
        # Create fake users
        users = create_users(connection, num_users)
        
        # Save user IDs to a file for other scripts to use
        with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'user_ids.txt'), 'w') as f:
            for user in users:
                f.write(f"{user['id']},{user['username']},{user['created_at'].strftime('%Y-%m-%d %H:%M:%S')}\n")
        
        print(f"\n✅ Successfully created {len(users)} users")
    except Exception as e:
        print(f"\n❌ Error during user generation: {e}")
    finally:
        if connection.is_connected():
            connection.close()
            print("\n🔌 Database connection closed")

if __name__ == "__main__":
    main()
