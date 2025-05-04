#!/usr/bin/env python3
"""
Post Generator for Insta-Lite

This script generates fake posts for the Insta-Lite application.
It downloads images from Picsum, converts them to WebP format,
and inserts the data into the MySQL database.
"""

import os
import sys
import random
import requests
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv
from datetime import datetime, timedelta
from PIL import Image
import time
from tqdm import tqdm
import argparse

# Configuration
POSTS_PER_USER = 5
IMAGE_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))), 'public/uploads')
POST_DIR = os.path.join(IMAGE_DIR, 'posts')

# Post captions
POST_CAPTIONS = [
    "Just enjoying the moment ✨",
    "Can't believe how beautiful today is!",
    "New adventures await 🌟",
    "This view never gets old",
    "Making memories with the best people",
    "Sometimes you just need to take a break",
    "Living my best life 💯",
    "The journey is the destination",
    "Good vibes only ✌️",
    "Grateful for days like these",
    "This is what dreams are made of",
    "Finding beauty in the everyday",
    "Life is short, enjoy the little things",
    "Chasing sunsets and good times",
    "When nothing goes right, go left",
    "Adventure is out there",
    "Embracing the journey, one step at a time",
    "Collecting moments, not things",
    "Find joy in the ordinary",
    "Living for these moments"
]

def ensure_directories():
    """Ensure that the required directories exist."""
    os.makedirs(POST_DIR, exist_ok=True)
    print(f"✅ Created directory: {POST_DIR}")

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

def download_image(url, save_path):
    """Download an image from a URL."""
    try:
        response = requests.get(url, timeout=10)
        response.raise_for_status()
        
        with open(save_path, 'wb') as f:
            f.write(response.content)
        
        return True
    except Exception as e:
        print(f"Error downloading image: {e}")
        return False

def convert_to_webp(input_path, output_path):
    """Convert an image to WebP format."""
    try:
        img = Image.open(input_path)
        img = img.resize((800, 800))
        img.save(output_path, 'WEBP', quality=85)
        return True
    except Exception as e:
        print(f"Error converting image: {e}")
        return False

def load_users():
    """Load user IDs from the file created by generate_users.py."""
    users = []
    try:
        with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'user_ids.txt'), 'r') as f:
            for line in f:
                parts = line.strip().split(',')
                if len(parts) >= 3:
                    users.append({
                        'id': int(parts[0]),
                        'username': parts[1],
                        'created_at': datetime.strptime(parts[2], '%Y-%m-%d %H:%M:%S')
                    })
        return users
    except Exception as e:
        print(f"❌ Error loading users: {e}")
        return []

def create_posts(connection, users, posts_per_user):
    """Create fake posts for each user."""
    cursor = connection.cursor()
    posts = []
    
    print(f"\n📸 Creating posts ({posts_per_user} per user)...")
    
    for user in users:
        user_id = user['id']
        username = user['username']
        print(f"\nGenerating posts for user: {username}")
        
        for i in tqdm(range(posts_per_user), desc="Posts"):
            try:
                # Generate random post date after user creation but before now
                user_created = user['created_at']
                now = datetime.now()
                post_date = user_created + timedelta(
                    seconds=random.randint(0, int((now - user_created).total_seconds()))
                )
                
                # Get random caption
                caption = random.choice(POST_CAPTIONS)
                
                # Generate image filename
                image_filename = f"post_{username}_{int(time.time())}_{i}.webp"
                image_path = os.path.join(POST_DIR, image_filename)
                
                # Try multiple image sources with fallbacks
                temp_image_path = os.path.join(POST_DIR, f"temp_{image_filename}.jpg")
                image_downloaded = False
                
                # List of image sources to try
                image_sources = [
                    # Source 1: Picsum Photos with specific ID
                    lambda: f"https://picsum.photos/id/{random.randint(1, 1000)}/800/800",
                    # Source 2: Picsum Photos random
                    lambda: "https://picsum.photos/800/800",
                    # Source 3: Lorem Flickr
                    lambda: f"https://loremflickr.com/800/800/nature?random={random.randint(1, 1000)}",
                    # Source 4: Unsplash Source
                    lambda: f"https://source.unsplash.com/random/800x800?nature,{random.choice(['water','mountains','forest','beach','sunset'])}",
                    # Source 5: Placeholder.com
                    lambda: "https://via.placeholder.com/800x800.jpg",
                    # Source 6: DummyImage
                    lambda: "https://dummyimage.com/800x800/f5f5f5/000000.jpg",
                    # Source 7: Lorem Picsum (alternative endpoint)
                    lambda: f"https://i.picsum.photos/id/{random.randint(1, 1000)}/800/800.jpg",
                    # Source 8: Random Fox
                    lambda: "https://randomfox.ca/images/{}.jpg".format(random.randint(1, 123))
                ]
                
                # Try each source until one works
                for get_url in image_sources:
                    if image_downloaded:
                        break
                        
                    try:
                        image_url = get_url()
                        print(f"Trying image source: {image_url}")
                        
                        if download_image(image_url, temp_image_path):
                            if convert_to_webp(temp_image_path, image_path):
                                # Remove temporary file
                                os.remove(temp_image_path)
                                image_downloaded = True
                                
                                # Insert post into database
                                query = "INSERT INTO posts (user_id, caption, image_path, created_at) VALUES (%s, %s, %s, %s)"
                                cursor.execute(query, (
                                    user_id,
                                    caption,
                                    f"/uploads/posts/{image_filename}",
                                    post_date.strftime('%Y-%m-%d %H:%M:%S')
                                ))
                                
                                post_id = cursor.lastrowid
                                posts.append({
                                    'id': post_id,
                                    'user_id': user_id,
                                    'date': post_date
                                })
                                
                                # Commit after each post
                                connection.commit()
                                break
                            else:
                                print(f"❌ Failed to convert image for post")
                        else:
                            print(f"❌ Failed to download image from {image_url}")
                    except Exception as e:
                        print(f"Error with image source: {e}")
                            
                # If all image sources failed, create a post with a default image
                if not image_downloaded:
                    print("⚠️ All image sources failed, using local fallback image")
                    
                    # Create a simple colored image as fallback
                    from PIL import Image, ImageDraw
                    
                    # Create a colored background with text
                    img = Image.new('RGB', (800, 800), color=(random.randint(200, 255), 
                                                             random.randint(200, 255), 
                                                             random.randint(200, 255)))
                    d = ImageDraw.Draw(img)
                    
                    # Add some shapes
                    for _ in range(5):
                        x1 = random.randint(0, 700)
                        y1 = random.randint(0, 700)
                        x2 = x1 + random.randint(50, 100)
                        y2 = y1 + random.randint(50, 100)
                        color = (random.randint(0, 200), random.randint(0, 200), random.randint(0, 200))
                        d.rectangle([x1, y1, x2, y2], fill=color)
                    
                    # Save the image
                    img.save(image_path, 'WEBP')
                    
                    # Insert post into database
                    query = "INSERT INTO posts (user_id, caption, image_path, created_at) VALUES (%s, %s, %s, %s)"
                    cursor.execute(query, (
                        user_id,
                        caption,
                        f"/uploads/posts/{image_filename}",
                        post_date.strftime('%Y-%m-%d %H:%M:%S')
                    ))
                    
                    post_id = cursor.lastrowid
                    posts.append({
                        'id': post_id,
                        'user_id': user_id,
                        'date': post_date
                    })
                    
                    # Commit after each post
                    connection.commit()
            except Exception as e:
                print(f"❌ Error creating post: {e}")
                try:
                    if not connection.is_connected():
                        print("Reconnecting to database...")
                        connection.reconnect(attempts=3, delay=5)
                except Exception:
                    print("Could not reconnect to database. Continuing with next post...")
    
    # Save post IDs to a file for other scripts to use
    with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'post_ids.txt'), 'w') as f:
        for post in posts:
            f.write(f"{post['id']},{post['user_id']},{post['date'].strftime('%Y-%m-%d %H:%M:%S')}\n")
    
    return posts

def update_post_counts(connection, users):
    """Update post counts for each user."""
    cursor = connection.cursor()
    
    print("\n🔄 Updating post counts...")
    
    for user in users:
        user_id = user['id']
        
        try:
            # Count posts
            cursor.execute("SELECT COUNT(*) FROM posts WHERE user_id = %s", (user_id,))
            post_count = cursor.fetchone()[0]
            
            # Check if post_count column exists in users table
            cursor.execute("SHOW COLUMNS FROM users LIKE 'post_count'")
            if cursor.fetchone():
                # Update user stats if the column exists
                cursor.execute(
                    "UPDATE users SET post_count = %s WHERE id = %s",
                    (post_count, user_id)
                )
            else:
                print(f"Note: post_count column not found in users table. Skipping update.")
            
            connection.commit()
            print(f"✅ Updated post count for user {user['username']}: {post_count} posts")
        except Exception as e:
            print(f"❌ Error updating post count for user {user['username']}: {e}")

def main():
    """Main function to generate fake posts."""
    parser = argparse.ArgumentParser(description='Generate fake posts for Insta-Lite')
    parser.add_argument('--posts', type=int, default=POSTS_PER_USER, help='Number of posts per user')
    args = parser.parse_args()
    
    posts_per_user = args.posts
    
    print(f"🚀 Starting fake post generation for Insta-Lite")
    
    # Ensure directories exist
    ensure_directories()
    
    # Load users
    users = load_users()
    if not users:
        print("❌ No users found. Please run generate_users.py first.")
        sys.exit(1)
    
    print(f"✅ Loaded {len(users)} users")
    
    # Connect to database
    connection = get_database_connection()
    
    try:
        # Create fake posts
        posts = create_posts(connection, users, posts_per_user)
        
        # Update post counts
        update_post_counts(connection, users)
        
        print(f"\n✅ Successfully created {len(posts)} posts")
    except Exception as e:
        print(f"\n❌ Error during post generation: {e}")
    finally:
        if connection.is_connected():
            connection.close()
            print("\n🔌 Database connection closed")

if __name__ == "__main__":
    main()
