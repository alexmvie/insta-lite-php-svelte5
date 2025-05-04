#!/usr/bin/env python3
"""
Main Generator Script for Insta-Lite

This script runs all the individual generator scripts in sequence
to create a complete fake dataset for the Insta-Lite application.
"""

import os
import sys
import subprocess
import argparse

def run_script(script_name, args=None):
    """Run a Python script with the given arguments."""
    script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), script_name)
    
    cmd = [sys.executable, script_path]
    if args:
        cmd.extend(args)
    
    print(f"\n{'='*50}")
    print(f"Running {script_name}...")
    print(f"{'='*50}\n")
    
    result = subprocess.run(cmd)
    
    if result.returncode != 0:
        print(f"\n❌ {script_name} failed with return code {result.returncode}")
        return False
    
    print(f"\n✅ {script_name} completed successfully")
    return True

def main():
    """Main function to run all generator scripts."""
    parser = argparse.ArgumentParser(description='Generate all fake data for Insta-Lite')
    parser.add_argument('--users', type=int, default=5, help='Number of users to create')
    parser.add_argument('--posts', type=int, default=5, help='Number of posts per user')
    parser.add_argument('--max-likes', type=int, default=10, help='Maximum likes per post')
    parser.add_argument('--max-comments', type=int, default=5, help='Maximum comments per post')
    parser.add_argument('--max-follows', type=int, default=3, help='Maximum follows per user')
    args = parser.parse_args()
    
    print("🚀 Starting complete data generation for Insta-Lite")
    
    # Run user generation
    if not run_script('generate_users.py', ['--users', str(args.users)]):
        sys.exit(1)
    
    # Run post generation
    if not run_script('generate_posts.py', ['--posts', str(args.posts)]):
        sys.exit(1)
    
    # Run interaction generation
    if not run_script('generate_interactions.py', [
        '--max-likes', str(args.max_likes),
        '--max-comments', str(args.max_comments),
        '--max-follows', str(args.max_follows)
    ]):
        sys.exit(1)
    
    print("\n✨ Complete data generation finished successfully!")
    print(f"Created {args.users} users with {args.posts} posts each")

if __name__ == "__main__":
    main()
