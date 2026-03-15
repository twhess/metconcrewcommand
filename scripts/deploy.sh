#!/bin/bash

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

MASTER_BEFORE=""
DEPLOY_START=""

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

# 2. No uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${RED}Error: Uncommitted changes found. Commit or stash first.${NC}"
    git status --short
    exit 1
fi

# 3. Make sure dev is up to date with remote
echo -e "${YELLOW}Fetching latest from origin...${NC}"
git fetch origin
LOCAL_DEV=$(git rev-parse dev)
REMOTE_DEV=$(git rev-parse origin/dev 2>/dev/null || echo "")
if [ -n "$REMOTE_DEV" ] && [ "$LOCAL_DEV" != "$REMOTE_DEV" ]; then
    echo -e "${RED}Error: Local dev is out of sync with origin/dev. Pull first.${NC}"
    exit 1
fi

# 4. Push dev
echo -e "${YELLOW}Pushing dev to GitHub...${NC}"
if ! git push origin dev; then
    echo -e "${RED}Error: Failed to push dev. Check your connection and permissions.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ dev pushed${NC}"

# 5. Switch to master and record current state for rollback
echo -e "${YELLOW}Checking out master...${NC}"
git checkout master

echo -e "${YELLOW}Pulling latest master...${NC}"
git pull origin master
MASTER_BEFORE=$(git rev-parse HEAD)
echo -e "  Master is at: ${MASTER_BEFORE:0:8}"

# 6. Merge dev into master
echo -e "${YELLOW}Merging dev into master...${NC}"
if ! git merge dev --no-edit; then
    echo -e "${RED}Error: Merge conflict! Resolve manually or abort.${NC}"
    echo -e "${YELLOW}To abort: git merge --abort && git checkout dev${NC}"
    # cleanup trap will handle reset
    exit 1
fi
MASTER_AFTER=$(git rev-parse HEAD)

if [ "$MASTER_BEFORE" = "$MASTER_AFTER" ]; then
    echo -e "${YELLOW}Nothing new to deploy — master is already up to date with dev.${NC}"
    git checkout dev
    exit 0
fi

# 7. Push master (triggers GitHub Action)
echo -e "${YELLOW}Pushing master to GitHub...${NC}"
DEPLOY_START=$(date +%s)
if ! git push origin master; then
    echo -e "${RED}Error: Failed to push master.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ master pushed — GitHub Action deploying...${NC}"

# 8. Switch back to dev
echo -e "${YELLOW}Switching back to dev...${NC}"
git checkout dev

# 9. Summary
echo ""
echo -e "${GREEN}=== Deploy triggered! ===${NC}"
echo -e "  Deployed: ${YELLOW}${MASTER_BEFORE:0:8}${NC} → ${GREEN}${MASTER_AFTER:0:8}${NC}"

REPO_URL=$(git remote get-url origin | sed 's/.*github.com[:/]\(.*\)\.git/\1/' 2>/dev/null || echo "")
if [ -n "$REPO_URL" ]; then
    echo -e "  Actions:  ${YELLOW}https://github.com/${REPO_URL}/actions${NC}"
    echo -e "  Diff:     ${YELLOW}https://github.com/${REPO_URL}/compare/${MASTER_BEFORE:0:8}...${MASTER_AFTER:0:8}${NC}"
fi

echo ""
echo -e "${YELLOW}If production breaks, rollback with:${NC}"
echo -e "  git checkout master && git reset --hard ${MASTER_BEFORE:0:8} && git push --force-with-lease origin master && git checkout dev"
