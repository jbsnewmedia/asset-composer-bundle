# Project Development Guide

This document provides essential information for AI and developers working on the AssetComposerBundle project.

## Project Context
AssetComposerBundle is a Symfony bundle that helps you manage and serve assets directly from the `vendor` directory. It ensures all files are kept up-to-date by leveraging file modification timestamps for cache busting.

## Development Commands
All commands should be executed within the Docker container.

### Testing
- **Run PHPUnit tests:**
  `docker exec asset-composer-bundle-web-1 composer test`
- **Goal:** Maintain 100% code coverage.

### Code Quality & Static Analysis
- **PHPStan (Static Analysis):**
  `docker exec asset-composer-bundle-web-1 composer bin-phpstan`
- **Easy Coding Standard (ECS) - Fix issues:**
  `docker exec asset-composer-bundle-web-1 composer bin-ecs-fix`
- **Rector (Automated Refactoring):**
  `docker exec asset-composer-bundle-web-1 composer bin-rector-process`

## Project Structure Highlights
- `.junie/`: AI-specific configuration and documentation.
- `src/Controller`: Asset management controller.
- `src/Service`: Core logic for asset composition.
- `src/Twig`: Twig extensions for easy asset integration.


