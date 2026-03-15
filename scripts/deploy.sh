#!/bin/bash

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

VERSION_FILE="VERSION"
MASTER_BEFORE=""
DEPLOY_START=""

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
        echo -e "${RED}=== Deploy failed! Cleaning up... ===${NC}"

        # If we're on master mid-merge, abort and go back to dev
        if [ "$(git branch --show-current)" = "master" ]; then
            git merge --abort 2>/dev/null
            if [ -n "$MASTER_BEFORE" ]; then
                echo -e "${YELLOW}Resetting master to pre-deploy state (${MASTER_BEFORE})...${NC}"
                git reset --hard "$MASTER_BEFORE"
            fi
            echo -e "${YELLOW}Switching back to dev...${NC}"
            git checkout dev
        fi

        echo -e "${RED}Deploy aborted. No changes were made to production.${NC}"
    fi
    exit $exit_code
}

trap cleanup EXIT

echo -e "${GREEN}=== MetCon Crew Command — Deploy ===${NC}"
echo ""

# 1. Must be on dev
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "dev" ]; then
    echo -e "${RED}Error: Must be on 'dev' branch. Currently on '${CURRENT_BRANCH}'.${NC}"
    exit 1
fi

# 2. Version bump (auto-increment beta)
CURRENT_VERSION=$(get_current_version)
NEW_VERSION=$(bump_beta "$CURRENT_VERSION")
echo -e "Version: ${CYAN}v${CURRENT_VERSION}${NC} → ${GREEN}v${NEW_VERSION}${NC}"
echo ""

# 3. Make sure dev is up to date with remote (before committing anything)
echo -e "${YELLOW}Fetching latest from origin...${NC}"
git fetch origin
REMOTE_DEV=$(git rev-parse origin/dev 2>/dev/null || echo "")
if [ -n "$REMOTE_DEV" ] && ! git merge-base --is-ancestor "$REMOTE_DEV" dev 2>/dev/null; then
    echo -e "${RED}Error: origin/dev has commits not in local dev. Pull first.${NC}"
    exit 1
fi

# 4. Prompt for commit message and commit any changes
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}Uncommitted changes found:${NC}"
    git status --short
    echo ""
    read -rp "Enter commit message (or Ctrl+C to cancel): " COMMIT_MSG
    if [ -z "$COMMIT_MSG" ]; then
        echo -e "${RED}Error: Commit message cannot be empty.${NC}"
        exit 1
    fi
    git add -A
    git commit -m "$COMMIT_MSG"
    echo -e "${GREEN}✓ Changes committed${NC}"
fi

# 5. Write version file and commit
echo "$NEW_VERSION" > "$VERSION_FILE"
git add "$VERSION_FILE"
git commit -m "Release v${NEW_VERSION}"
echo -e "${GREEN}✓ Version bumped to v${NEW_VERSION}${NC}"

# 6. Push dev
echo -e "${YELLOW}Pushing dev to GitHub...${NC}"
if ! git push origin dev; then
    echo -e "${RED}Error: Failed to push dev. Check your connection and permissions.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ dev pushed${NC}"

# 7. Switch to master and record current state for rollback
echo -e "${YELLOW}Checking out master...${NC}"
git checkout master

echo -e "${YELLOW}Pulling latest master...${NC}"
git pull origin master
MASTER_BEFORE=$(git rev-parse HEAD)
echo -e "  Master is at: ${MASTER_BEFORE:0:8}"

# 8. Merge dev into master
echo -e "${YELLOW}Merging dev into master...${NC}"
if ! git merge dev --no-edit; then
    echo -e "${RED}Error: Merge conflict! Resolve manually or abort.${NC}"
    echo -e "${YELLOW}To abort: git merge --abort && git checkout dev${NC}"
    exit 1
fi
MASTER_AFTER=$(git rev-parse HEAD)

if [ "$MASTER_BEFORE" = "$MASTER_AFTER" ]; then
    echo -e "${YELLOW}Nothing new to deploy — master is already up to date with dev.${NC}"
    git checkout dev
    exit 0
fi

# 9. Tag the release
echo -e "${YELLOW}Tagging release v${NEW_VERSION}...${NC}"
git tag -a "v${NEW_VERSION}" -m "Release v${NEW_VERSION}"
echo -e "${GREEN}✓ Tagged v${NEW_VERSION}${NC}"

# 10. Push master and tags (triggers GitHub Action)
echo -e "${YELLOW}Pushing master and tags to GitHub...${NC}"
DEPLOY_START=$(date +%s)
if ! git push origin master --tags; then
    echo -e "${RED}Error: Failed to push master.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ master pushed — GitHub Action deploying...${NC}"

# 11. Switch back to dev
echo -e "${YELLOW}Switching back to dev...${NC}"
git checkout dev

# 12. Summary
echo ""
echo -e "${GREEN}=== v${NEW_VERSION} deployed! ===${NC}"
echo -e "  Version:  ${CYAN}v${CURRENT_VERSION}${NC} → ${GREEN}v${NEW_VERSION}${NC}"
echo -e "  Commits:  ${YELLOW}${MASTER_BEFORE:0:8}${NC} → ${GREEN}${MASTER_AFTER:0:8}${NC}"

REPO_URL=$(git remote get-url origin | sed 's/.*github.com[:/]\(.*\)\.git/\1/' 2>/dev/null || echo "")
if [ -n "$REPO_URL" ]; then
    echo -e "  Actions:  ${YELLOW}https://github.com/${REPO_URL}/actions${NC}"
    echo -e "  Release:  ${YELLOW}https://github.com/${REPO_URL}/releases/tag/v${NEW_VERSION}${NC}"
    echo -e "  Diff:     ${YELLOW}https://github.com/${REPO_URL}/compare/${MASTER_BEFORE:0:8}...v${NEW_VERSION}${NC}"
fi

echo ""
echo -e "${YELLOW}If production breaks, rollback with:${NC}"
echo -e "  git checkout master && git reset --hard ${MASTER_BEFORE:0:8} && git push --force-with-lease origin master && git checkout dev"
