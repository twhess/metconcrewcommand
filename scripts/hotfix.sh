#!/bin/bash

# Hotfix workflow:
#   1. Branch from master into hotfix/<name>
#   2. You make your fix and commit
#   3. Run this script again to deploy the fix and merge back to dev

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

VERSION_FILE="VERSION"
MASTER_BEFORE=""

get_current_version() {
    if [ -f "$VERSION_FILE" ]; then
        cat "$VERSION_FILE"
    else
        echo "1.0.0-beta.0"
    fi
}

bump_beta() {
    local version=$1
    local base beta_num

    if [[ "$version" == *"-beta."* ]]; then
        base="${version%%-beta.*}"
        beta_num="${version##*-beta.}"
        echo "${base}-beta.$((beta_num + 1))"
    else
        echo "${version}-beta.1"
    fi
}

cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        echo ""
        echo -e "${RED}=== Hotfix failed! Cleaning up... ===${NC}"

        local current=$(git branch --show-current)

        # If we're on master mid-merge, abort
        if [ "$current" = "master" ]; then
            git merge --abort 2>/dev/null
            if [ -n "$MASTER_BEFORE" ]; then
                echo -e "${YELLOW}Resetting master to pre-hotfix state (${MASTER_BEFORE})...${NC}"
                git reset --hard "$MASTER_BEFORE"
            fi
        fi

        # If we're on dev mid-merge, abort
        if [ "$current" = "dev" ]; then
            git merge --abort 2>/dev/null
        fi

        echo -e "${RED}Hotfix aborted. Review the state and try again.${NC}"
    fi
    exit $exit_code
}

trap cleanup EXIT

CURRENT_BRANCH=$(git branch --show-current)

# ─── Mode 1: Create a new hotfix branch ───────────────────────────────────────
if [ "$CURRENT_BRANCH" = "dev" ] || [ "$CURRENT_BRANCH" = "master" ]; then

    echo -e "${GREEN}=== MetCon Crew Command — New Hotfix ===${NC}"
    echo ""

    # Get hotfix name
    HOTFIX_NAME="$1"
    if [ -z "$HOTFIX_NAME" ]; then
        read -rp "Hotfix name (short, no spaces): " HOTFIX_NAME
    fi

    if [ -z "$HOTFIX_NAME" ]; then
        echo -e "${RED}Error: Hotfix name is required.${NC}"
        exit 1
    fi

    # Sanitize name
    HOTFIX_NAME=$(echo "$HOTFIX_NAME" | tr ' ' '-' | tr '[:upper:]' '[:lower:]')
    HOTFIX_BRANCH="hotfix/${HOTFIX_NAME}"

    # Check for uncommitted changes
    if [ -n "$(git status --porcelain)" ]; then
        echo -e "${RED}Error: Uncommitted changes found. Commit or stash first.${NC}"
        git status --short
        exit 1
    fi

    # Ensure master is up to date
    echo -e "${YELLOW}Fetching latest from origin...${NC}"
    git fetch origin

    echo -e "${YELLOW}Creating hotfix branch from master...${NC}"
    git checkout master
    git pull origin master
    git checkout -b "$HOTFIX_BRANCH"

    # Tag the start of the hotfix
    TIMESTAMP=$(date +%Y%m%d-%H%M%S)
    TAG_NAME="hotfix-start/${HOTFIX_NAME}-${TIMESTAMP}"
    git tag -a "$TAG_NAME" -m "Hotfix started: ${HOTFIX_NAME}"
    echo -e "${GREEN}✓ Tagged: ${CYAN}${TAG_NAME}${NC}"

    echo ""
    echo -e "${GREEN}=== Hotfix branch created: ${CYAN}${HOTFIX_BRANCH}${GREEN} ===${NC}"
    echo ""
    echo -e "Next steps:"
    echo -e "  1. Make your fix and commit"
    echo -e "  2. Run ${CYAN}./scripts/hotfix.sh${NC} again to deploy and merge back to dev"
    exit 0
fi

# ─── Mode 2: Deploy hotfix from hotfix/* branch ───────────────────────────────
if [[ "$CURRENT_BRANCH" != hotfix/* ]]; then
    echo -e "${RED}Error: Must be on 'dev', 'master', or a 'hotfix/*' branch.${NC}"
    echo -e "  Current branch: ${CURRENT_BRANCH}"
    echo ""
    echo -e "Usage:"
    echo -e "  ${CYAN}./scripts/hotfix.sh [name]${NC}  — Create a new hotfix branch from master"
    echo -e "  ${CYAN}./scripts/hotfix.sh${NC}         — Deploy current hotfix branch"
    exit 1
fi

echo -e "${GREEN}=== MetCon Crew Command — Deploy Hotfix ===${NC}"
echo -e "  Branch: ${CYAN}${CURRENT_BRANCH}${NC}"
echo ""

# Must have commits
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${RED}Error: Uncommitted changes found. Commit your fix first.${NC}"
    git status --short
    exit 1
fi

MASTER_COMMITS=$(git log master.."$CURRENT_BRANCH" --oneline 2>/dev/null)
if [ -z "$MASTER_COMMITS" ]; then
    echo -e "${RED}Error: No commits on this hotfix branch. Make your fix and commit first.${NC}"
    exit 1
fi

echo -e "${YELLOW}Hotfix commits:${NC}"
echo "$MASTER_COMMITS"
echo ""

# Version bump (always patch for hotfixes)
CURRENT_VERSION=$(get_current_version)
NEW_VERSION=$(bump_beta "$CURRENT_VERSION")
echo -e "Version bump: ${CYAN}v${CURRENT_VERSION}${NC} → ${GREEN}v${NEW_VERSION}${NC} (hotfix)"
echo ""

read -rp "Deploy this hotfix to production? [y/N]: " CONFIRM
if [[ "$CONFIRM" != "y" && "$CONFIRM" != "Y" ]]; then
    echo -e "${YELLOW}Cancelled.${NC}"
    exit 1
fi

# Write version and commit on hotfix branch
echo "$NEW_VERSION" > "$VERSION_FILE"
git add "$VERSION_FILE"
git commit -m "Release v${NEW_VERSION} (hotfix)"

# Fetch latest
echo -e "${YELLOW}Fetching latest from origin...${NC}"
git fetch origin

# ── Merge hotfix into master ──
echo -e "${YELLOW}Merging hotfix into master...${NC}"
git checkout master
git pull origin master
MASTER_BEFORE=$(git rev-parse HEAD)

if ! git merge "$CURRENT_BRANCH" --no-edit; then
    echo -e "${RED}Error: Merge conflict on master! Resolve manually.${NC}"
    echo -e "${YELLOW}After resolving: git add . && git commit && git push origin master${NC}"
    exit 1
fi
MASTER_AFTER=$(git rev-parse HEAD)

# Tag the release
echo -e "${YELLOW}Tagging release v${NEW_VERSION}...${NC}"
git tag -a "v${NEW_VERSION}" -m "Hotfix v${NEW_VERSION}"

# Push master and tags
echo -e "${YELLOW}Pushing master to production...${NC}"
if ! git push origin master --tags; then
    echo -e "${RED}Error: Failed to push master.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ master pushed — GitHub Action deploying...${NC}"

# ── Merge hotfix into dev ──
echo -e "${YELLOW}Merging hotfix into dev...${NC}"
git checkout dev
git pull origin dev

if ! git merge "$CURRENT_BRANCH" --no-edit; then
    echo -e "${RED}Error: Merge conflict on dev! Resolve manually.${NC}"
    echo -e "${YELLOW}After resolving: git add . && git commit && git push origin dev${NC}"
    echo -e "${YELLOW}Then delete the hotfix branch: git branch -d ${CURRENT_BRANCH}${NC}"
    exit 1
fi

if ! git push origin dev; then
    echo -e "${RED}Error: Failed to push dev.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ dev updated with hotfix${NC}"

# ── Clean up hotfix branch ──
echo -e "${YELLOW}Cleaning up hotfix branch...${NC}"
git branch -d "$CURRENT_BRANCH"
git push origin --delete "$CURRENT_BRANCH" 2>/dev/null

# ── Summary ──
echo ""
echo -e "${GREEN}=== Hotfix v${NEW_VERSION} deployed! ===${NC}"
echo -e "  Version:  ${CYAN}v${CURRENT_VERSION}${NC} → ${GREEN}v${NEW_VERSION}${NC}"
echo -e "  Commits:  ${YELLOW}${MASTER_BEFORE:0:8}${NC} → ${GREEN}${MASTER_AFTER:0:8}${NC}"
echo -e "  Merged:   master ✓  dev ✓"
echo -e "  Branch:   ${CURRENT_BRANCH} deleted"

REPO_URL=$(git remote get-url origin | sed 's/.*github.com[:/]\(.*\)\.git/\1/' 2>/dev/null || echo "")
if [ -n "$REPO_URL" ]; then
    echo -e "  Actions:  ${YELLOW}https://github.com/${REPO_URL}/actions${NC}"
fi

echo ""
echo -e "${YELLOW}If production breaks, rollback with:${NC}"
echo -e "  git checkout master && git reset --hard ${MASTER_BEFORE:0:8} && git push --force-with-lease origin master && git checkout dev"
