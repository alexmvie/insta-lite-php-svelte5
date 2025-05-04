#!/usr/bin/env python3
"""
Post Cleanup Script for Insta-Lite

This script deletes all posts without an image, except those from the user 'alexm_vie'.
"""

import os
import sys
import mysql.connector
from dotenv import load_dotenv
from datetime import datetime
import argparse

# Load environment variables
script_dir = os.path.dirname(os.path.abspath(__file__))
env_path = os.path.join(script_dir, '.env')
load_dotenv(env_path)

# Database connection parameters
DB_HOST = os.getenv('DB_HOST')
DB_USER = os.getenv('DB_USER')
DB_PASSWORD = os.getenv('DB_PASSWORD')
DB_NAME = os.getenv('DB_NAME')

# Directory paths
APP_ROOT = os.path.abspath(os.path.join(script_dir, '..', '..'))
UPLOADS_DIR = os.path.join(APP_ROOT, 'public', 'uploads')
POST_DIR = os.path.join(UPLOADS_DIR, 'posts')

def get_database_connection():
    """Connect to the MySQL database."""
    try:
        connection = mysql.connector.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME
        )
        if connection.is_connected():
            print("✅ Connected to MySQL database")
            return connection
    except mysql.connector.Error as e:
        print(f"❌ Error connecting to MySQL database: {e}")
        sys.exit(1)

def cleanup_posts(connection, dry_run=False):
    """Delete posts without images except for user 'alexm_vie'."""
    cursor = connection.cursor(dictionary=True)
    
    try:
        # First, get the user ID for 'alexm_vie'
        cursor.execute("SELECT id FROM users WHERE username = 'alexm_vie'")
        result = cursor.fetchone()
        alexm_vie_id = result['id'] if result else None
        
        if alexm_vie_id:
            print(f"Found user 'alexm_vie' with ID: {alexm_vie_id}")
        else:
            print("⚠️ User 'alexm_vie' not found. Will exclude no users.")
        
        # Get all posts to check
        query = """
        SELECT p.id, p.user_id, p.image_path, u.username 
        FROM posts p
        JOIN users u ON p.user_id = u.id
        """
        
        cursor.execute(query)
        all_posts = cursor.fetchall()
        
        # Filter posts with missing images (either in DB or filesystem)
        posts = []
        for post in all_posts:
            # Check if image_path is NULL or empty in database
            if not post['image_path'] or post['image_path'] == '':
                post['reason'] = "Missing image path in database"
                posts.append(post)
                continue
                
            # Check if the image file exists on the filesystem
            image_path = post['image_path']
            if image_path.startswith('/'):
                # Remove leading slash if present
                image_path = image_path[1:]
                
            full_path = os.path.join(APP_ROOT, 'public', image_path)
            if not os.path.exists(full_path):
                post['reason'] = f"Image file not found: {full_path}"
                posts.append(post)
        
        print(f"Checked {len(all_posts)} total posts")
        
        if not posts:
            print("✅ No posts with missing images found.")
            return 0
        
        print(f"Found {len(posts)} posts with missing images:")
        for post in posts:
            print(f"  - Post ID {post['id']} by {post['username']}: {post['reason']}")
        
        # Count posts to delete
        posts_to_delete = [post for post in posts if post['user_id'] != alexm_vie_id]
        
        if not posts_to_delete:
            print("✅ No posts to delete after excluding user 'alexm_vie'.")
            return 0
        
        print(f"Will delete {len(posts_to_delete)} posts (excluding {len(posts) - len(posts_to_delete)} from 'alexm_vie')")
        
        # Show breakdown of posts to delete
        user_counts = {}
        for post in posts_to_delete:
            username = post['username']
            if username not in user_counts:
                user_counts[username] = 0
            user_counts[username] += 1
        
        print("Posts to delete by user:")
        for username, count in user_counts.items():
            print(f"  - {username}: {count} posts")
        
        if dry_run:
            print("DRY RUN: No posts will be deleted")
            for post in posts_to_delete:
                print(f"  Would delete post ID {post['id']} from user {post['username']}")
            return len(posts_to_delete)
        
        # Delete posts
        deleted_count = 0
        for post in posts_to_delete:
            try:
                # Delete associated likes
                cursor.execute("DELETE FROM likes WHERE post_id = %s", (post['id'],))
                
                # Delete associated comments
                cursor.execute("DELETE FROM comments WHERE post_id = %s", (post['id'],))
                
                # Delete the post
                cursor.execute("DELETE FROM posts WHERE id = %s", (post['id'],))
                
                print(f"✅ Deleted post ID {post['id']} from user {post['username']}")
                deleted_count += 1
                
                # Commit after each post to avoid large transactions
                connection.commit()
                
            except Exception as e:
                print(f"❌ Error deleting post ID {post['id']}: {e}")
                connection.rollback()
        
        print(f"✅ Successfully deleted {deleted_count} posts with missing images")
        return deleted_count
        
    except Exception as e:
        print(f"❌ Error during cleanup: {e}")
        return 0
    finally:
        cursor.close()

def main():
    """Main function to run the cleanup script."""
    parser = argparse.ArgumentParser(description='Delete posts without images except for user alexm_vie')
    parser.add_argument('--dry-run', action='store_true', help='Show what would be deleted without actually deleting')
    args = parser.parse_args()
    
    print("🧹 Starting post cleanup for Insta-Lite")
    print(f"{'DRY RUN MODE: ' if args.dry_run else ''}Will delete posts without images (except from user 'alexm_vie')")
    
    # Connect to database
    connection = get_database_connection()
    
    # Cleanup posts
    deleted_count = cleanup_posts(connection, args.dry_run)
    
    # Close database connection
    if connection.is_connected():
        connection.close()
        print("🔌 Database connection closed")
    
    print(f"✅ Cleanup complete! {deleted_count} posts {'would be' if args.dry_run else 'were'} deleted.")

if __name__ == "__main__":
    main()
