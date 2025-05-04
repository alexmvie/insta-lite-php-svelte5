#!/usr/bin/env python3
"""
Post Image Verification Script for Insta-Lite

This script verifies that all post images exist at the expected locations
and provides options to fix issues with missing images.
"""

import os
import sys
import mysql.connector
import random
import requests
from PIL import Image, ImageDraw
import argparse
from dotenv import load_dotenv
from datetime import datetime
import time
from tqdm import tqdm

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

def download_image(url, save_path):
    """Download an image from a URL."""
    try:
        response = requests.get(url, stream=True, timeout=10)
        response.raise_for_status()
        
        with open(save_path, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        return True
    except Exception as e:
        print(f"Error downloading image: {e}")
        return False

def convert_to_webp(input_path, output_path):
    """Convert an image to WebP format."""
    try:
        img = Image.open(input_path)
        img.save(output_path, 'WEBP')
        return True
    except Exception as e:
        print(f"Error converting image: {e}")
        return False

def create_placeholder_image(output_path, post_id, username):
    """Create a placeholder image with post info."""
    try:
        # Create a colored background
        color = (random.randint(200, 255), random.randint(200, 255), random.randint(200, 255))
        img = Image.new('RGB', (800, 800), color=color)
        d = ImageDraw.Draw(img)
        
        # Add some shapes
        for _ in range(5):
            x1 = random.randint(0, 700)
            y1 = random.randint(0, 700)
            x2 = x1 + random.randint(50, 100)
            y2 = y1 + random.randint(50, 100)
            shape_color = (random.randint(0, 200), random.randint(0, 200), random.randint(0, 200))
            d.rectangle([x1, y1, x2, y2], fill=shape_color)
        
        # Add text (basic implementation without font selection)
        d.text((400, 400), f"Post ID: {post_id}\nUser: {username}", fill=(0, 0, 0))
        
        # Save the image
        img.save(output_path, 'WEBP')
        return True
    except Exception as e:
        print(f"Error creating placeholder image: {e}")
        return False

def verify_images(connection, fix_missing=False, use_placeholders=False):
    """Verify all post images and optionally fix missing ones."""
    cursor = connection.cursor(dictionary=True)
    
    try:
        # Get all posts
        cursor.execute("""
            SELECT p.id, p.user_id, p.image_path, p.caption, u.username 
            FROM posts p
            JOIN users u ON p.user_id = u.id
        """)
        posts = cursor.fetchall()
        
        if not posts:
            print("No posts found in the database.")
            return
        
        print(f"Found {len(posts)} posts in the database.")
        
        # Ensure uploads directory exists
        os.makedirs(POST_DIR, exist_ok=True)
        
        # Track statistics
        valid_images = []
        missing_images = []
        empty_paths = []
        fixed_images = []
        failed_fixes = []
        
        # Check each post
        for post in tqdm(posts, desc="Verifying posts"):
            post_id = post['id']
            image_path = post['image_path']
            username = post['username']
            
            if not image_path:
                empty_paths.append(post)
                continue
                
            # Remove leading slash if present
            if image_path.startswith('/'):
                image_path = image_path[1:]
                
            # Get full path to image
            full_path = os.path.join(APP_ROOT, 'public', image_path)
            
            # Check if image exists
            if os.path.exists(full_path) and os.path.getsize(full_path) > 0:
                valid_images.append(post)
            else:
                missing_images.append(post)
                
                # Fix missing image if requested
                if fix_missing:
                    fixed = False
                    
                    # Try to download a new image
                    if not use_placeholders:
                        # List of image sources to try
                        image_sources = [
                            f"https://picsum.photos/id/{random.randint(1, 1000)}/800/800",
                            "https://picsum.photos/800/800",
                            f"https://loremflickr.com/800/800/nature?random={random.randint(1, 1000)}",
                            f"https://source.unsplash.com/random/800x800?nature,{random.choice(['water','mountains','forest','beach','sunset'])}",
                        ]
                        
                        # Try each source
                        for source in image_sources:
                            if fixed:
                                break
                                
                            temp_path = os.path.join(POST_DIR, f"temp_{post_id}.jpg")
                            if download_image(source, temp_path):
                                if convert_to_webp(temp_path, full_path):
                                    fixed = True
                                    fixed_images.append(post)
                                    
                                    # Clean up temp file
                                    if os.path.exists(temp_path):
                                        os.remove(temp_path)
                    
                    # Use placeholder if download failed or placeholders requested
                    if not fixed:
                        if create_placeholder_image(full_path, post_id, username):
                            fixed = True
                            fixed_images.append(post)
                    
                    # If still not fixed, track failure
                    if not fixed:
                        failed_fixes.append(post)
        
        # Print summary
        print("\n=== Image Verification Summary ===")
        print(f"Total posts: {len(posts)}")
        print(f"Posts with valid images: {len(valid_images)}")
        print(f"Posts with missing images: {len(missing_images)}")
        print(f"Posts with empty image paths: {len(empty_paths)}")
        
        if fix_missing:
            print(f"\nFixed {len(fixed_images)} posts with missing images")
            print(f"Failed to fix {len(failed_fixes)} posts")
        
        # Detailed report for posts with issues
        if missing_images or empty_paths:
            print("\n=== Detailed Issues ===")
            
            if empty_paths:
                print("\nPosts with empty image paths:")
                for post in empty_paths:
                    print(f"  - Post ID {post['id']} by {post['username']}")
            
            if missing_images and not fix_missing:
                print("\nPosts with missing images:")
                for post in missing_images:
                    print(f"  - Post ID {post['id']} by {post['username']}: {post['image_path']}")
            
            if failed_fixes:
                print("\nPosts that could not be fixed:")
                for post in failed_fixes:
                    print(f"  - Post ID {post['id']} by {post['username']}: {post['image_path']}")
        
        # Provide next steps
        if missing_images and not fix_missing:
            print("\nTo fix missing images, run this script with the --fix flag:")
            print("python verify_post_images.py --fix")
        
        return {
            'total': len(posts),
            'valid': len(valid_images),
            'missing': len(missing_images),
            'empty': len(empty_paths),
            'fixed': len(fixed_images) if fix_missing else 0,
            'failed': len(failed_fixes) if fix_missing else 0
        }
        
    except Exception as e:
        print(f"❌ Error verifying images: {e}")
        return None
    finally:
        cursor.close()

def fix_image_paths(connection):
    """Fix posts with image_url instead of image_path."""
    cursor = connection.cursor(dictionary=True)
    
    try:
        # Check if image_url column exists
        cursor.execute("SHOW COLUMNS FROM posts LIKE 'image_url'")
        has_image_url = cursor.fetchone() is not None
        
        if not has_image_url:
            print("No image_url column found in posts table. No fixes needed.")
            return 0
        
        # Get posts with image_url but not image_path
        cursor.execute("""
            SELECT id, image_url 
            FROM posts 
            WHERE image_url IS NOT NULL 
            AND (image_path IS NULL OR image_path = '')
        """)
        posts = cursor.fetchall()
        
        if not posts:
            print("No posts found with image_url but missing image_path.")
            return 0
        
        print(f"Found {len(posts)} posts with image_url but missing image_path.")
        
        # Update each post
        fixed_count = 0
        for post in tqdm(posts, desc="Fixing image paths"):
            try:
                cursor.execute(
                    "UPDATE posts SET image_path = %s WHERE id = %s",
                    (post['image_url'], post['id'])
                )
                connection.commit()
                fixed_count += 1
            except Exception as e:
                print(f"Error updating post {post['id']}: {e}")
                connection.rollback()
        
        print(f"✅ Fixed image paths for {fixed_count} posts")
        return fixed_count
        
    except Exception as e:
        print(f"❌ Error fixing image paths: {e}")
        return 0
    finally:
        cursor.close()

def main():
    """Main function to run the verification script."""
    parser = argparse.ArgumentParser(description='Verify and fix post images for Insta-Lite')
    parser.add_argument('--fix', action='store_true', help='Fix missing images')
    parser.add_argument('--placeholders', action='store_true', help='Use placeholder images instead of downloading new ones')
    parser.add_argument('--fix-paths', action='store_true', help='Fix image_url to image_path column issues')
    args = parser.parse_args()
    
    print("🔍 Starting post image verification for Insta-Lite")
    
    # Connect to database
    connection = get_database_connection()
    
    # Fix image paths if requested
    if args.fix_paths:
        fix_image_paths(connection)
    
    # Verify images
    verify_images(connection, fix_missing=args.fix, use_placeholders=args.placeholders)
    
    # Close database connection
    if connection.is_connected():
        connection.close()
        print("🔌 Database connection closed")
    
    print("✅ Verification complete!")

if __name__ == "__main__":
    main()
