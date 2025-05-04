#!/usr/bin/env python3
"""
Interaction Generator for Insta-Lite

This script generates likes, comments, and follower relationships
for the Insta-Lite application.
"""

import os
import sys
import random
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv
from datetime import datetime, timedelta
from tqdm import tqdm
import argparse

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

def load_posts():
    """Load post IDs from the file created by generate_posts.py."""
    posts = []
    try:
        with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'post_ids.txt'), 'r') as f:
            for line in f:
                parts = line.strip().split(',')
                if len(parts) >= 3:
                    posts.append({
                        'id': int(parts[0]),
                        'user_id': int(parts[1]),
                        'date': datetime.strptime(parts[2], '%Y-%m-%d %H:%M:%S')
                    })
        return posts
    except Exception as e:
        print(f"❌ Error loading posts: {e}")
        return []

def ensure_comments_table(connection):
    """Ensure the comments table exists."""
    cursor = connection.cursor()
    
    try:
        # Check if comments table exists
        cursor.execute("SHOW TABLES LIKE 'comments'")
        if not cursor.fetchone():
            # Create comments table if it doesn't exist
            print("Creating comments table...")
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
            connection.commit()
            print("✅ Comments table created")
        else:
            print("✅ Comments table already exists")
    except Exception as e:
        print(f"❌ Error ensuring comments table: {e}")
        sys.exit(1)

def create_likes(connection, users, posts, max_likes_per_post=10):
    """Create likes for posts."""
    cursor = connection.cursor()
    
    print(f"\n❤️ Creating likes (up to {max_likes_per_post} per post)...")
    
    likes_count = 0
    
    for post in tqdm(posts, desc="Processing posts"):
        post_id = post['id']
        post_user_id = post['user_id']
        post_date = post['date']
        
        # Add random likes (0 to max_likes_per_post likes per post)
        num_likes = random.randint(0, max_likes_per_post)
        
        # Get users who could like this post (excluding the post creator)
        liker_candidates = [u for u in users if u['id'] != post_user_id]
        
        if liker_candidates and num_likes > 0:
            # Select random users to like this post
            likers = random.sample(liker_candidates, min(num_likes, len(liker_candidates)))
            
            for liker in likers:
                liker_id = liker['id']
                
                try:
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
                        likes_count += 1
                        
                        # Commit every 10 likes
                        if likes_count % 10 == 0:
                            connection.commit()
                except Exception as e:
                    print(f"❌ Error creating like: {e}")
    
    # Final commit
    connection.commit()
    print(f"✅ Created {likes_count} likes")
    
    return likes_count

def create_comments(connection, users, posts, max_comments_per_post=5):
    """Create comments for posts."""
    cursor = connection.cursor()
    
    print(f"\n💬 Creating comments (up to {max_comments_per_post} per post)...")
    
    comments_count = 0
    
    for post in tqdm(posts, desc="Processing posts"):
        post_id = post['id']
        post_date = post['date']
        
        # Add random comments (0 to max_comments_per_post comments per post)
        num_comments = random.randint(0, max_comments_per_post)
        
        if users and num_comments > 0:
            for _ in range(num_comments):
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
                    
                    # Insert comment
                    cursor.execute(
                        "INSERT INTO comments (user_id, post_id, content, created_at) VALUES (%s, %s, %s, %s)",
                        (commenter_id, post_id, comment_text, comment_date.strftime('%Y-%m-%d %H:%M:%S'))
                    )
                    comments_count += 1
                    
                    # Commit every 10 comments
                    if comments_count % 10 == 0:
                        connection.commit()
                except Exception as e:
                    print(f"❌ Error creating comment: {e}")
    
    # Final commit
    connection.commit()
    print(f"✅ Created {comments_count} comments")
    
    return comments_count

def create_followers(connection, users, max_follows_per_user=3):
    """Create follower relationships between users."""
    cursor = connection.cursor()
    
    print(f"\n👥 Creating follower relationships (up to {max_follows_per_user} per user)...")
    
    followers_count = 0
    
    for user in tqdm(users, desc="Processing users"):
        user_id = user['id']
        
        # Each user follows 1 to max_follows_per_user random users
        follow_count = random.randint(1, min(max_follows_per_user, len(users) - 1))
        followee_candidates = [u for u in users if u['id'] != user_id]
        
        if followee_candidates and follow_count > 0:
            # Select random users to follow
            followees = random.sample(followee_candidates, min(follow_count, len(followee_candidates)))
            
            for followee in followees:
                followee_id = followee['id']
                
                try:
                    # Check if relationship already exists
                    query = "SELECT id FROM follows WHERE follower_id = %s AND following_id = %s"
                    cursor.execute(query, (user_id, followee_id))
                    if not cursor.fetchone():
                        # Insert follow relationship
                        query = "INSERT INTO follows (follower_id, following_id, created_at) VALUES (%s, %s, %s)"
                        cursor.execute(query, (
                            user_id,
                            followee_id,
                            datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                        ))
                        followers_count += 1
                        
                        # Commit every 10 followers
                        if followers_count % 10 == 0:
                            connection.commit()
                except Exception as e:
                    print(f"❌ Error creating follower relationship: {e}")
    
    # Final commit
    connection.commit()
    print(f"✅ Created {followers_count} follower relationships")
    
    return followers_count

def update_user_stats(connection, users):
    """Update user statistics (follower_count, following_count)."""
    cursor = connection.cursor()
    
    print("\n📊 Updating user statistics...")
    
    for user in tqdm(users, desc="Updating users"):
        user_id = user['id']
        
        try:
            # Count followers and following
            cursor.execute("SELECT COUNT(*) FROM follows WHERE following_id = %s", (user_id,))
            follower_count = cursor.fetchone()[0]
            
            cursor.execute("SELECT COUNT(*) FROM follows WHERE follower_id = %s", (user_id,))
            following_count = cursor.fetchone()[0]
            
            # Check if follower_count and following_count columns exist in users table
            cursor.execute("SHOW COLUMNS FROM users LIKE 'follower_count'")
            has_follower_count = cursor.fetchone() is not None
            cursor.execute("SHOW COLUMNS FROM users LIKE 'following_count'")
            has_following_count = cursor.fetchone() is not None
            
            # Update user stats if the columns exist
            if has_follower_count and has_following_count:
                cursor.execute(
                    "UPDATE users SET follower_count = %s, following_count = %s WHERE id = %s",
                    (follower_count, following_count, user_id)
                )
            elif has_follower_count:
                cursor.execute(
                    "UPDATE users SET follower_count = %s WHERE id = %s",
                    (follower_count, user_id)
                )
            elif has_following_count:
                cursor.execute(
                    "UPDATE users SET following_count = %s WHERE id = %s",
                    (following_count, user_id)
                )
            else:
                print(f"Note: follower_count and following_count columns not found in users table. Skipping update.")
        except Exception as e:
            print(f"❌ Error updating stats for user {user['username']}: {e}")
    
    # Commit all stat updates
    connection.commit()
    print("✅ Updated user statistics")

def main():
    """Main function to generate interactions."""
    parser = argparse.ArgumentParser(description='Generate interactions for Insta-Lite')
    parser.add_argument('--max-likes', type=int, default=10, help='Maximum likes per post')
    parser.add_argument('--max-comments', type=int, default=5, help='Maximum comments per post')
    parser.add_argument('--max-follows', type=int, default=3, help='Maximum follows per user')
    args = parser.parse_args()
    
    print("🚀 Starting interaction generation for Insta-Lite")
    
    # Load users and posts
    users = load_users()
    if not users:
        print("❌ No users found. Please run generate_users.py first.")
        sys.exit(1)
    
    posts = load_posts()
    if not posts:
        print("❌ No posts found. Please run generate_posts.py first.")
        sys.exit(1)
    
    print(f"✅ Loaded {len(users)} users and {len(posts)} posts")
    
    # Connect to database
    connection = get_database_connection()
    
    try:
        # Ensure comments table exists
        ensure_comments_table(connection)
        
        # Create likes
        create_likes(connection, users, posts, args.max_likes)
        
        # Create comments
        create_comments(connection, users, posts, args.max_comments)
        
        # Create followers
        create_followers(connection, users, args.max_follows)
        
        # Update user statistics
        update_user_stats(connection, users)
        
        print("\n✅ Interaction generation complete!")
    except Exception as e:
        print(f"\n❌ Error during interaction generation: {e}")
    finally:
        if connection.is_connected():
            connection.close()
            print("\n🔌 Database connection closed")

if __name__ == "__main__":
    main()
