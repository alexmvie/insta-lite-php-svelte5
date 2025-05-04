#!/bin/bash

# Script to generate fake data for Insta-Lite

# Set the script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PYTHON_DIR="$SCRIPT_DIR/python"

# Check if Python is installed
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 is not installed. Please install Python 3 to continue."
    exit 1
fi

# Create virtual environment if it doesn't exist
if [ ! -d "$SCRIPT_DIR/venv" ]; then
    echo "🔧 Creating Python virtual environment..."
    python3 -m venv "$SCRIPT_DIR/venv"
fi

# Activate virtual environment
source "$SCRIPT_DIR/venv/bin/activate"

# Install required packages
echo "📦 Installing required packages..."
pip install -r "$PYTHON_DIR/requirements.txt"

# Make all Python scripts executable
chmod +x "$PYTHON_DIR/generate_users.py"
chmod +x "$PYTHON_DIR/generate_posts.py"
chmod +x "$PYTHON_DIR/generate_interactions.py"
chmod +x "$PYTHON_DIR/generate_all.py"

# Check if .env file exists, create from example if not
if [ ! -f "$PYTHON_DIR/.env" ]; then
    if [ -f "$PYTHON_DIR/.env.example" ]; then
        echo "⚠️ No .env file found. Creating from .env.example..."
        cp "$PYTHON_DIR/.env.example" "$PYTHON_DIR/.env"
        echo "⚠️ Please edit $PYTHON_DIR/.env with your database credentials before continuing."
        exit 1
    else
        echo "❌ No .env or .env.example file found. Please create a .env file with your database credentials."
        exit 1
    fi
fi

# Parse command line arguments
USERS=5
POSTS=5
MAX_LIKES=10
MAX_COMMENTS=5
MAX_FOLLOWS=3

while [[ $# -gt 0 ]]; do
    case $1 in
        --users)
            USERS="$2"
            shift 2
            ;;
        --posts)
            POSTS="$2"
            shift 2
            ;;
        --max-likes)
            MAX_LIKES="$2"
            shift 2
            ;;
        --max-comments)
            MAX_COMMENTS="$2"
            shift 2
            ;;
        --max-follows)
            MAX_FOLLOWS="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Run the Python script
echo "🚀 Generating fake data..."
python "$PYTHON_DIR/generate_all.py" \
    --users "$USERS" \
    --posts "$POSTS" \
    --max-likes "$MAX_LIKES" \
    --max-comments "$MAX_COMMENTS" \
    --max-follows "$MAX_FOLLOWS"

# Deactivate virtual environment
deactivate

echo "✅ Done!"
