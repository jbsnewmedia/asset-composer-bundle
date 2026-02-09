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
- **Strict Rule:** `@codeCoverageIgnore` must never be used. All code paths must be tested.

### Code Quality & Static Analysis
- **PHPStan (Static Analysis):**
  `docker exec asset-composer-bundle-web-1 composer bin-phpstan`
- **Easy Coding Standard (ECS) - Fix issues:**
  `docker exec asset-composer-bundle-web-1 composer bin-ecs-fix`
- **Rector (Automated Refactoring):**
  `docker exec asset-composer-bundle-web-1 composer bin-rector-process`

## Code Style & Comments
- **Minimal Commenting**: All comments (`//` and DocBlocks) that are not strictly necessary for Code Quality (e.g., PHPStan types) must be removed.
- **No Unnecessary Explanations**: Code should be self-explanatory. DocBlocks that only repeat method names or trivial logic are forbidden.
- **Cleanup Command**: If comments have been added, they can be cleaned up using `composer bin-ecs-fix` (if configured) or manually.

## Project Structure Highlights
- `.developer/`: Additional development documentation.
- `.junie/`: AI-specific configuration and documentation.
- `src/Controller`: Asset management controller.
- `src/Service`: Core logic for asset composition.
- `src/Twig`: Twig extensions for easy asset integration.
- `src/DependencyInjection`: Configuration processing and automatic route installation.
- `tests/`: Comprehensive test suite for core and plugin functionalities.


