#!/bin/bash
# Install git hooks for this project
HOOK_DIR="$(git rev-parse --git-dir)/hooks"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

ln -sf "$SCRIPT_DIR/post-commit" "$HOOK_DIR/post-commit"
chmod +x "$HOOK_DIR/post-commit"
echo "Git hooks installed."
