#!/usr/bin/env python3
"""
Fake Data Generator for Insta-Lite

This script generates fake users and posts for the Insta-Lite application.
It downloads images from free stock photo sites, converts them to WebP format,
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
from io import BytesIO
import time
from tqdm import tqdm
import argparse
import tempfile

# Initialize Faker
fake = Faker()

# Configuration
NUM_USERS = 5
POSTS_PER_USER = 5
IMAGE_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))), 'public/uploads')
AVATAR_DIR = os.path.join(IMAGE_DIR, 'avatars')
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

# Comment templates for posts
COMMENT_TEMPLATES = [
    "Love this! 😍",
    "Wow, amazing shot!",
    "This is so cool 🔥",
    "Beautiful view!",
    "I need to visit this place",
    "You look great!",
    "This is everything ✨",
    "Goals!",
    "Perfect moment captured",
    "This made my day",
    "Absolutely stunning",
    "Can't wait to see more",
    "This is incredible",
    "Love the vibes here",
    "So inspiring!",
    "This is my favorite",
    "Perfection 👌",
    "I'm obsessed with this",
    "Looks like paradise",
    "You're killing it!"
]

def ensure_directories():
    """Ensure that the required directories exist."""
    os.makedirs(AVATAR_DIR, exist_ok=True)
    os.makedirs(POST_DIR, exist_ok=True)
    print(f"✅ Created directories: {AVATAR_DIR} and {POST_DIR}")

def download_image(url, save_path):
    """Download an image from a URL and save it to the specified path."""
    try:
        response = requests.get(url, timeout=10)
        response.raise_for_status()
        
        with open(save_path, 'wb') as f:
            f.write(response.content)
        return True
    except Exception as e:
        print(f"Error downloading image: {e}")
        return False

def convert_to_webp(input_path, output_path, size=(800, 800)):
    """Convert an image to WebP format and resize it."""
    try:
        img = Image.open(input_path)
        img = img.resize(size)
        img.save(output_path, 'WEBP', quality=85)
        return True
    except Exception as e:
        print(f"Error converting image: {e}")
        return False

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

def create_fake_users(connection):
    """Create fake users in the database."""
    cursor = connection.cursor()
    users = []
    
    print("\n📝 Creating fake users...")
    
    for i in range(NUM_USERS):
        try:
            # Generate user data
            username = fake.user_name() + str(random.randint(100, 999))
            email = fake.email()
            password_hash = hashlib.sha256(fake.password().encode()).hexdigest()
            bio = fake.text(max_nb_chars=150)
            
            # Generate avatar filename
            avatar_filename = f"avatar_{username}_{int(time.time())}.webp"
            avatar_path = os.path.join(AVATAR_DIR, avatar_filename)
            
            # Get avatar from DiceBear API
            avatar_url = f"https://api.dicebear.com/7.x/avataaars/png?seed={username}&backgroundColor=b6e3f4,c0aede,d1d4f9"
            
            # Download and convert avatar
            temp_avatar_path = os.path.join(AVATAR_DIR, f"temp_{avatar_filename}.png")
            if download_image(avatar_url, temp_avatar_path):
                if convert_to_webp(temp_avatar_path, avatar_path, (400, 400)):
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
                    print(f"✅ Created user: {username} (ID: {user_id})")
                else:
                    print(f"❌ Failed to convert avatar for user: {username}")
            else:
                print(f"❌ Failed to download avatar for user: {username}")
            
            # Commit after each user to avoid losing all data if connection is lost
            connection.commit()
        except Exception as e:
            print(f"❌ Error creating user: {e}")
    
    return users

def create_fake_posts(connection, users):
    """Create fake posts for each user."""
    cursor = connection.cursor()
    posts = []
    
    print("\n📸 Creating fake posts...")
    
    for user in users:
        user_id = user['id']
        username = user['username']
        print(f"\nGenerating posts for user: {username}")
        
        for i in tqdm(range(POSTS_PER_USER), desc="Posts"):
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
                
                # Get random image from Picsum
                image_id = random.randint(1, 1000)
                image_url = f"https://picsum.photos/id/{image_id}/800/800"
                
                # Download and convert image
                temp_image_path = os.path.join(POST_DIR, f"temp_{image_filename}.jpg")
                if download_image(image_url, temp_image_path):
                    if convert_to_webp(temp_image_path, image_path):
                        # Remove temporary file
                        os.remove(temp_image_path)
                        
                        # Insert post into database
                        query = "INSERT INTO posts (user_id, caption, image_url, created_at) VALUES (%s, %s, %s, %s)"
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
                        
                        # Commit after each post to avoid losing all data if connection is lost
                        connection.commit()
                    else:
                        print(f"❌ Failed to convert image for post")
                else:
                    print(f"❌ Failed to download image for post")
            except Exception as e:
                print(f"❌ Error creating post: {e}")
                try:
                    if not connection.is_connected():
                        print("Reconnecting to database...")
                        connection.reconnect(attempts=3, delay=5)
                except:
                    print("Could not reconnect to database. Continuing with next post...")
    
    return posts

def create_likes_and_comments(connection, users, posts):
    """Create likes and comments for posts."""
    cursor = connection.cursor()
    
    print("\n❤️ Creating likes and comments...")
    
    for post in tqdm(posts, desc="Processing"):
        post_id = post['id']
        post_user_id = post['user_id']
        post_date = post['date']
        
        # Add random likes (0-10 likes per post)
        likes_count = random.randint(0, 10)
        for i in range(likes_count):
            try:
                # Get a random user that's not the post creator
                liker_candidates = [u for u in users if u['id'] != post_user_id]
                if liker_candidates:
                    liker = random.choice(liker_candidates)
                    liker_id = liker['id']
                    
                    # Generate a random like date after post creation but before now
                    now = datetime.now()
                    like_date = post_date + timedelta(
                        seconds=random.randint(0, int((now - post_date).total_seconds()))
                    )
                    
                    # Check if like already exists
                    cursor.execute("SELECT id FROM likes WHERE user_id = %s AND post_id = %s", (liker_id, post_id))
                    if not cursor.fetchone():
                        # Insert like into database
                        cursor.execute(
                            "INSERT INTO likes (user_id, post_id, created_at) VALUES (%s, %s, %s)",
                            (liker_id, post_id, like_date.strftime('%Y-%m-%d %H:%M:%S'))
                        )
            except Exception as e:
                print(f"❌ Error creating like: {e}")
        
        # Add random comments (0-5 comments per post)
        comments_count = random.randint(0, 5)
        for i in range(comments_count):
            try:
                # Get a random user (could be post creator or another user)
                commenter = random.choice(users)
                commenter_id = commenter['id']
                
                # Generate a random comment date after post creation but before now
                now = datetime.now()
                comment_date = post_date + timedelta(
                    seconds=random.randint(0, int((now - post_date).total_seconds()))
                )
                
                # Get random comment text
                comment_text = random.choice(COMMENT_TEMPLATES)
                
                # Insert comment into database
                # First check if comments table exists
                cursor.execute("SHOW TABLES LIKE 'comments'")
                if not cursor.fetchone():
                    # Create comments table if it doesn't exist
                    cursor.execute("""
                    CREATE TABLE comments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        post_id INT NOT NULL,
                        content TEXT NOT NULL,
                        created_at DATETIME NOT NULL,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                    )
                    """)
                
                # Insert comment
                cursor.execute(
                    "INSERT INTO comments (user_id, post_id, content, created_at) VALUES (%s, %s, %s, %s)",
                    (commenter_id, post_id, comment_text, comment_date.strftime('%Y-%m-%d %H:%M:%S'))
                )
            except Exception as e:
                print(f"❌ Error creating comment: {e}")
        
        # Commit after processing each post
        try:
            connection.commit()
        except Exception as e:
            print(f"❌ Error committing likes and comments: {e}")
            try:
                if not connection.is_connected():
                    print("Reconnecting to database...")
                    connection.reconnect(attempts=3, delay=5)
            except:
                print("Could not reconnect to database. Continuing with next post...")

def create_followers(connection, users):
    """Create follower relationships between users."""
    cursor = connection.cursor()
    
    print("\n👥 Creating follower relationships...")
    
    for user in users:
        user_id = user['id']
        
        # Each user follows 1-3 random users
        follow_count = random.randint(1, min(3, len(users) - 1))
        followee_candidates = [u for u in users if u['id'] != user_id]
        
        if followee_candidates:
            followees = random.sample(followee_candidates, min(follow_count, len(followee_candidates)))
            
            for followee in followees:
                followee_id = followee['id']
                try:
                    # Check if relationship already exists
                    cursor.execute("SELECT id FROM followers WHERE follower_id = %s AND followee_id = %s", (user_id, followee_id))
                    if not cursor.fetchone():
                        # Insert follower relationship
                        cursor.execute(
                            "INSERT INTO followers (follower_id, followee_id, created_at) VALUES (%s, %s, %s)",
                            (user_id, followee_id, datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
                        )
                except Exception as e:
                    print(f"❌ Error creating follower relationship: {e}")
    
    # Commit all follower relationships
    try:
        connection.commit()
    except Exception as e:
        print(f"❌ Error committing follower relationships: {e}")

def update_user_stats(connection, users):
    """Update user statistics (post_count, follower_count, following_count)."""
    cursor = connection.cursor()
    
    print("\n📊 Updating user statistics...")
    
    for user in users:
        user_id = user['id']
        
        try:
            # Count posts
            cursor.execute("SELECT COUNT(*) FROM posts WHERE user_id = %s", (user_id,))
            post_count = cursor.fetchone()[0]
            
            # Count followers
            cursor.execute("SELECT COUNT(*) FROM followers WHERE followee_id = %s", (user_id,))
            follower_count = cursor.fetchone()[0]
            
            # Count following
            cursor.execute("SELECT COUNT(*) FROM followers WHERE follower_id = %s", (user_id,))
            following_count = cursor.fetchone()[0]
            
            # Update user stats
            cursor.execute(
                "UPDATE users SET post_count = %s, follower_count = %s, following_count = %s WHERE id = %s",
                (post_count, follower_count, following_count, user_id)
            )
        except Exception as e:
            print(f"❌ Error updating stats for user {user['username']}: {e}")
    
    # Commit all stat updates
    try:
        connection.commit()
    except Exception as e:
        print(f"❌ Error committing user stats: {e}")

def main():
    """Main function to generate fake data."""
    global NUM_USERS, POSTS_PER_USER
    
    parser = argparse.ArgumentParser(description='Generate fake data for Insta-Lite')
    parser.add_argument('--users', type=int, default=NUM_USERS, help='Number of users to create')
    parser.add_argument('--posts', type=int, default=POSTS_PER_USER, help='Number of posts per user')
    args = parser.parse_args()
    
    NUM_USERS = args.users
    POSTS_PER_USER = args.posts
    
    print(f"🚀 Starting fake data generation for Insta-Lite")
    print(f"📊 Creating {NUM_USERS} users with {POSTS_PER_USER} posts each")
    
    # Ensure directories exist
    ensure_directories()
    
    # Connect to database
    connection = get_database_connection()
    
    try:
        # Create fake users
        users = create_fake_users(connection)
        
        # Create fake posts
        posts = create_fake_posts(connection, users)
        
        # Create likes and comments
        create_likes_and_comments(connection, users, posts)
        
        # Create follower relationships
        create_followers(connection, users)
        
        # Update user statistics
        update_user_stats(connection, users)
        
        print("\n✅ Fake data generation complete!")
    except Exception as e:
        print(f"\n❌ Error during data generation: {e}")
    finally:
        if connection.is_connected():
            connection.close()
            print("\n🔌 Database connection closed")

if __name__ == "__main__":
    main()
    """Create fake posts for each user."""
    cursor = connection.cursor()
    post_ids = []
    
    # Categories for images
    categories = ['nature', 'city', 'people', 'animals', 'food', 'travel', 'technology', 'architecture', 'business', 'fashion']
    
    for user in users:
        user_id = user['id']
        username = user['username']
        print(f"\nGenerating posts for user: {username}")
        
        # Create posts with progress bar
        for i in tqdm(range(POSTS_PER_USER), desc="Posts"):
            try:
                # Generate a random date within the last 30 days
                days_ago = random.randint(0, 30)
                post_date = datetime.now() - timedelta(days=days_ago, 
                                                    hours=random.randint(0, 23), 
                                                    minutes=random.randint(0, 59))
                
                # Generate random caption
                caption = fake.paragraph(nb_sentences=random.randint(1, 3))
                
                # Generate image filename
                image_filename = f"post_{username}_{int(time.time())}_{i}.webp"
                image_path = os.path.join(POST_DIR, image_filename)
                
                # Download and convert image
                category = random.choice(categories)
                
                if download_and_convert_image(category, image_path):
                    # Insert post into database
                    query = "INSERT INTO posts (user_id, image_url, caption, created_at) VALUES (%s, %s, %s, %s)"
                    values = (user_id, f"/uploads/posts/{image_filename}", caption, post_date.strftime('%Y-%m-%d %H:%M:%S'))
                    
                    cursor.execute(query, values)
                    post_id = cursor.lastrowid
                    post_ids.append({'id': post_id, 'user_id': user_id, 'date': post_date})
                    
                    # Commit after each post to avoid losing all data if connection is lost
                    connection.commit()
                        # Get a random user that's not the post creator
                        liker_candidates = [u for u in users if u['id'] != user['id']]
                        if liker_candidates:
                            liker = random.choice(liker_candidates)
                            like_query = """
                            INSERT INTO likes (post_id, user_id, created_at)
                            VALUES (%s, %s, %s)
                            """
                            like_date = post_date + timedelta(minutes=random.randint(1, 60*24))
                            if like_date > now:
                                like_date = now - timedelta(minutes=random.randint(1, 60))
                            
                            try:
                                cursor.execute(like_query, (
                                    post_id,
                                    liker['id'],
                                    like_date.strftime('%Y-%m-%d %H:%M:%S')
                                ))
                            except:
                                # Skip if duplicate
                                pass
                    
                    # Add some random comments
                    comments_count = random.randint(0, 5)  # 0-5 comments per post
                    for j in range(comments_count):
                        # Get a random user that may or may not be the post creator
                        commenter = random.choice(users)
                        comment_text = random.choice(COMMENT_TEMPLATES)
                        
                        # Generate comment time after post but before now
                        comment_date = post_date + timedelta(minutes=random.randint(1, 60*24*7))
                        if comment_date > now:
                            comment_date = now - timedelta(minutes=random.randint(1, 60))
                        
                        # Check if comments table exists
                        try:
                            comment_query = """
                            INSERT INTO comments (post_id, user_id, comment_text, created_at)
                            VALUES (%s, %s, %s, %s)
                            """
                            cursor.execute(comment_query, (
                                post_id,
                                commenter['id'],
                                comment_text,
                                comment_date.strftime('%Y-%m-%d %H:%M:%S')
                            ))
                        except Error as e:
                            # If table doesn't exist, create it
                            if "Table 'insta_lite.comments' doesn't exist" in str(e):
                                print("\n⚠️ Comments table doesn't exist. Creating it...")
                                create_comments_table = """
                                CREATE TABLE IF NOT EXISTS comments (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    post_id INT NOT NULL,
                                    user_id INT NOT NULL,
                                    comment_text TEXT NOT NULL,
                                    created_at DATETIME NOT NULL,
                                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                                )
                                """
                                cursor.execute(create_comments_table)
                                connection.commit()
                                
                                # Try inserting again
                                cursor.execute(comment_query, (
                                    post_id,
                                    commenter['id'],
                                    comment_text,
                                    comment_date.strftime('%Y-%m-%d %H:%M:%S')
                                ))
                            else:
                                print(f"Error adding comment: {e}")
                else:
                    print(f"❌ Failed to download image for post")
            
        connection.commit()
        print("\n✅ Created all posts successfully")
    except Error as e:
        print(f"Error creating fake posts: {e}")
        connection.rollback()
    finally:
        cursor.close()

def update_user_stats(connection):
    """Update user stats (post_count, follower_count, following_count)."""
    cursor = connection.cursor()
    
    try:
        # Update post counts
        cursor.execute("""
        UPDATE users u
        SET post_count = (
            SELECT COUNT(*) FROM posts p WHERE p.user_id = u.id
        )
        """)
        
        # Create some random follows between users
        users_query = "SELECT id FROM users"
        cursor.execute(users_query)
        user_ids = [row[0] for row in cursor.fetchall()]
        
        for follower_id in user_ids:
            # Each user follows 1-2 other users
            for _ in range(random.randint(1, 2)):
                followee_id = random.choice([uid for uid in user_ids if uid != follower_id])
                
                follow_query = """
                INSERT IGNORE INTO follows (follower_id, followee_id, created_at)
                VALUES (%s, %s, %s)
                """
                
                follow_date = datetime.now() - timedelta(days=random.randint(0, 14))
                cursor.execute(follow_query, (
                    follower_id,
                    followee_id,
                    follow_date.strftime('%Y-%m-%d %H:%M:%S')
                ))
        
        # Update follower and following counts
        cursor.execute("""
        UPDATE users u
        SET follower_count = (
            SELECT COUNT(*) FROM follows f WHERE f.followee_id = u.id
        ),
        following_count = (
            SELECT COUNT(*) FROM follows f WHERE f.follower_id = u.id
        )
        """)
        
        connection.commit()
        print("✅ Updated user stats successfully")
    except Error as e:
        print(f"Error updating user stats: {e}")
        connection.rollback()
    finally:
        cursor.close()

def main():
    """Main function to generate fake data."""
    global NUM_USERS, POSTS_PER_USER
    
    parser = argparse.ArgumentParser(description='Generate fake data for Insta-Lite')
    parser.add_argument('--users', type=int, default=NUM_USERS, help='Number of users to create')
    parser.add_argument('--posts', type=int, default=POSTS_PER_USER, help='Number of posts per user')
    args = parser.parse_args()
    
    NUM_USERS = args.users
    POSTS_PER_USER = args.posts
    
    print("🚀 Starting fake data generation for Insta-Lite")
    print(f"📊 Creating {NUM_USERS} users with {POSTS_PER_USER} posts each")
    
    # Ensure directories exist
    ensure_directories()
    
    # Connect to database
    connection = get_database_connection()
    
    if connection:
        try:
            # Create fake users
            print("\n📝 Creating fake users...")
            users = create_fake_users(connection)
            
            if users:
                # Create fake posts
                print("\n📸 Creating fake posts...")
                create_fake_posts(connection, users)
                
                # Update user stats
                print("\n🔄 Updating user statistics...")
                update_user_stats(connection)
                
                print("\n✨ Fake data generation completed successfully!")
            else:
                print("❌ No users were created. Exiting.")
        finally:
            connection.close()

if __name__ == "__main__":
    main()
