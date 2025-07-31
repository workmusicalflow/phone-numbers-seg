#!/bin/bash

# Script to merge changes to main branch and push to remote repository

# Get the current branch name
CURRENT_BRANCH=$(git branch --show-current)
echo "Current branch: $CURRENT_BRANCH"

# Make sure all changes are committed
echo "Checking for uncommitted changes..."
if [[ -n $(git status --porcelain) ]]; then
  echo "You have uncommitted changes. Please commit them first."
  echo "You can use: git add . && git commit -m \"Your commit message\""
  exit 1
fi

# Checkout main branch
echo "Checking out main branch..."
git checkout main

# Merge changes from the current branch
echo "Merging changes from $CURRENT_BRANCH into main..."
git merge $CURRENT_BRANCH

# Push changes to remote repository with force flag to overwrite remote changes
echo "Pushing changes to remote repository (force push)..."
git push --force origin main

# Return to the original branch and force push it too
echo "Returning to $CURRENT_BRANCH branch..."
git checkout $CURRENT_BRANCH
echo "Force pushing $CURRENT_BRANCH to remote repository..."
git push --force origin $CURRENT_BRANCH

echo "Done! Your changes have been merged to main and pushed to the remote repository."
