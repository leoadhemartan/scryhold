# Scryhold - Magic: The Gathering Collection Manager

A modern web application for managing Magic: The Gathering card collections with integration to the Scryfall API.

## Features

### Public Pages
- **Set Library** (`/set-library`) – Browse all MTG sets with search and sort
- **Card Search** (`/cards/search`) – Search and browse cards (placeholder)
- **Home** (`/`) – Landing page
- **About** (`/about`) – Application info (placeholder)

### Admin Features
- **Dashboard** (`/dashboard`) – Admin overview
- **Manage Cards** (`/admin/cards`)
   - Paginated grid view of card collection (18 cards per page)
   - Card lookup via Scryfall API (collection number, set code, language)
   - Location-based inventory management with quantity tracking
   - Visual card display with:
     - Card name (prominent header)
     - Card image (front face for multi-faced cards)
     - Conditional type line display (handles split/transform cards)
     - Layout and language information table
     - Quantity breakdown table by location with totals
   - Smart card instance management:
     - Auto-increment quantity for duplicate cards in same location
     - Support for cards across multiple storage locations
   - Multi-faced card support (split, transform, modal double-faced)
   - Extracts card face data from Scryfall JSON when needed
   - Update Set Library from Scryfall with real-time progress tracking
- **Manage Locations** (`/admin/locations`)
   - Full CRUD for storage locations (Decks, Side Decks, Storage)
   - Modal-based add/edit forms with conditional fields
   - Sortable table with columns: Name, Location Type, Deck Type, Card Count, Default, Commander, Side Deck Parent, Actions
   - Card count column (aggregated from all card instances in location)
   - Delete confirmation dialog
   - Side Deck parent selection for eligible decks

### Data Model
- **Normalized schema**: Cards and Locations linked via `mtg_card_instances` junction table
- **Multi-location support**: Cards can exist in multiple locations with different quantities
- **Card instance tracking**: Each card-location combination tracks quantity independently
- **Multi-faced card support**: Handles split, transform, flip, and modal double-faced cards with separate face data storage
- **Scryfall JSON preservation**: Complete API response stored for data integrity and future parsing
- **User roles**: Admin user seeded by default

### Technology
- **Backend**: Laravel 12+, Eloquent ORM
- **Frontend**: Vue 3, Inertia.js, Vite, Tailwind CSS (dark theme)
- **Database**: SQLite
- **API**: Scryfall API

## Technology Stack

- **Backend**: Laravel 12+
- **Frontend**: Vue 3 + Inertia.js + Vite
- **Database**: SQLite
- **API**: Scryfall API (https://scryfall.com/docs/api)
- **Styling**: Tailwind CSS (Dark Theme)

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- SQLite

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd scryhold-2026/scryhold
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```
   This creates an admin user:
   - Email: `admin@example.com`
   - Password: `password`

6. **Create storage symlink**
   ```bash
   php artisan storage:link
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

## Development

1. **Start the Laravel development server**
   ```bash
   php artisan serve
   ```
   Server runs at http://127.0.0.1:8000

2. **Build frontend assets (development mode with hot reload)**
   ```bash
   npm run dev
   ```

## Project Structure

```
scryhold/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Application controllers
│   │   │   ├── Admin/          # Admin-only controllers
│   │   │   │   ├── CardsController.php
│   │   │   │   └── LocationsController.php
│   │   │   └── SetLibraryController.php
│   │   └── Middleware/         # Custom middleware
│   └── Models/                 # Eloquent models
│       ├── User.php
│       ├── MtgCard.php
│       ├── MtgCardInstance.php
│       ├── MtgLocation.php
│       └── MtgSet.php
├── database/
│   ├── migrations/             # Database schema migrations
│   └── seeders/                # Database seeders
│       └── AdminUserSeeder.php
├── resources/
│   ├── js/
│   │   ├── Components/         # Reusable Vue components
│   │   ├── Layouts/            # Page layouts
│   │   │   ├── GuestLayout.vue        # Public pages layout with sidebar
│   │   │   └── AuthenticatedLayout.vue # Admin pages layout with sidebar
│   │   └── Pages/              # Inertia.js pages
│   │       ├── SetLibrary.vue
│   │       └── Admin/
│   │           ├── Cards/
│   │           │   └── List.vue
│   │           └── Locations/
│   │               ├── Index.vue
│   │               ├── Create.vue
│   │               ├── Edit.vue
│   │               └── Show.vue
│   └── css/                    # Stylesheets
├── routes/
│   ├── web.php                 # Web routes
│   └── auth.php                # Authentication routes
├── spec/                       # Project specifications
│   ├── spec-architecture-app.md
│   ├── spec-architecture-pages.md
│   ├── spec-page-manage-cards.md
│   ├── spec-page-manage-locations.md
│   ├── spec-page-set-library.md
│   ├── spec-schema-data-models.md
│   ├── spec-schema-mtg-cards.md
│   ├── spec-schema-mtg-card-instances.md
│   ├── spec-schema-mtg-locations.md
│   ├── spec-schema-users.md
│   ├── spec-single-card-json-parsing.md
│   ├── spec-split-card-json-parsing.md
│   └── spec-transform-card-json-parsing.md
└── storage/
    └── app/
        └── public/
            ├── sets/           # MTG set SVG icons (auto-downloaded)
            ├── front/          # Front face card images
            └── back/           # Back face card images (for multi-faced cards)
```

## Key Features

### Sidebar Navigation
- **Desktop**: Fixed left sidebar (256px) with organized navigation
- **Mobile**: Hamburger menu with slide-out sidebar
- **Public Section**: Home, Set Library, Card Search, About
- **Admin Section**: Dashboard, Manage Cards, Manage Locations

### Set Library Integration
- Fetches all MTG sets from Scryfall API
- Downloads and stores set SVG icons locally
- Displays sets in paginated grid (24 per page)
- Search by set name
- Sort by name (A-Z, Z-A) or release date (oldest, newest)

### Admin Card Management
- Modal-based card lookup with Scryfall API integration
- Supports 17 languages (English, Japanese, Spanish, French, German, etc.)
- Displays card data with proper handling for:
  - Single-faced cards
  - Multi-faced cards (split, transform, flip, modal double-faced)
  - Shared vs. separate card face images
  - Rotated display for split cards
- Location selector for inventory placement
- Smart quantity management:
  - Auto-increment for duplicates in same location
  - Independent quantity tracking per location
  - Aggregate quantity display across all locations
- Real-time Set Library updates with progress logging
- Card grid display features:
  - Responsive layout (1-6 columns based on screen size)
  - Card name, image, and conditional type line
  - Layout/language information table
  - Detailed quantity breakdown by location
  - Alphabetically sorted location display

## Database Schema

### mtg_cards Table
- `id` - Primary key
- `scryfall_id` - Unique Scryfall card identifier (UUID)
- `name` - Card name
- `layout` - Card layout type (normal, split, transform, etc.)
- `lang` - Language code (en, ja, etc.)
- `type_line` - Card type line
- `mana_cost` - Mana cost string
- `oracle_text` - Rules text
- `cfl_name`, `cfl_type_line`, `cfl_mana_cost`, `cfl_oracle_text` - Card face left (front) data
- `cfr_name`, `cfr_type_line`, `cfr_mana_cost`, `cfr_oracle_text` - Card face right (back) data
- `image_uri` - Path to card image (single-faced or shared image)
- `cfl_image_uri` - Path to front face image (multi-faced cards)
- `cfr_image_uri` - Path to back face image (multi-faced cards)
- `scryfall_json` - Complete Scryfall API response (JSON)
- `created_at`, `updated_at` - Timestamps

### mtg_card_instances Table
- `id` - Primary key
- `scryfall_id` - Foreign key to mtg_cards
- `location_id` - Foreign key to mtg_locations
- `quantity` - Number of copies in this location
- `created_at`, `updated_at` - Timestamps

### mtg_locations Table
- `id` - Primary key
- `name` - Location name
- `location_type` - Type (deck, side_deck, storage)
- `deck_type` - Deck format (commander, standard, etc.)
- `is_default` - Default location flag
- `commander_id` - Foreign key to commander card (for commander decks)
- `side_deck_parent_id` - Foreign key to parent deck (for side decks)
- `created_at`, `updated_at` - Timestamps

### mtg_sets Table
- `id` - Primary key
- `set_code` - Unique set identifier (e.g., "NEO", "MH2")
- `set_name` - Full set name
- `set_svg_url` - Path to stored SVG icon
- `set_release_date` - Release date (YYYY-MM-DD)
- `set_type` - Set type (core, expansion, etc.)
- `created_at`, `updated_at` - Timestamps

### users Table
- Laravel default user schema with `username` column added
- Supports admin role via seeder

## API Integration

### Scryfall API
- **Base URL**: https://api.scryfall.com
- **Sets Endpoint**: `/sets` - Fetches all MTG set data
- **Cards Endpoint**: `/cards/:set/:number/:lang` - Fetches specific card by set, collector number, and language
- **Rate Limit**: 10 requests per second (handled automatically)
- **Icon Format**: SVG files downloaded and stored locally
- **Card Images**: Downloaded and stored locally in `/storage/app/public/front/` and `/storage/app/public/back/`
- **Data Preservation**: Complete JSON response stored in database for data integrity

## Testing

Run PHPUnit tests:
```bash
php artisan test
```

## Deployment

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Run `php artisan view:cache`
6. Build production assets: `npm run build`

## Middleware

- **AdminOnly**: Restricts routes to admin users only (defined in `bootstrap/app.php`)
- **Auth**: Standard Laravel authentication
- **Guest**: Redirects authenticated users away from auth pages

## License

This project is open-sourced software licensed under the MIT license.

## GitHub Copilot Integration

This project includes comprehensive GitHub Copilot tooling to accelerate development and maintain code quality through AI-assisted workflows.

### `.github/agents/`

Custom GitHub Copilot agents tailored for this project:

- **`laravel-expert-agent.agent.md`** - World-class Laravel expert specializing in Laravel 12+ applications, Eloquent ORM, Artisan commands, testing, and best practices. Provides guidance on routing, middleware, authentication, and database patterns.

- **`specification.agent.md`** - Generates and updates specification documents for new or existing functionality. Creates AI-ready specifications with clear requirements, constraints, and interfaces structured for effective machine consumption.

### `.github/instructions/`

Project-wide instructions applied automatically during development:

- **`spec-driven-workflow-v1.instructions.md`** - Comprehensive specification-driven workflow enforcing a 6-phase development loop (ANALYZE → DESIGN → IMPLEMENT → VALIDATE → REFLECT → HANDOFF). Ensures requirements are clearly defined, designs are meticulously planned, and implementations are thoroughly documented using EARS notation.

### `.github/prompts/`

Reusable prompt templates for common development tasks:

- **`create-specification.prompt.md`** - Template for creating new specification files with AI-ready formatting, precise requirements, and structured documentation standards.

- **`update-specification.prompt.md`** - Template for updating existing specifications while maintaining consistency and ensuring machine-readable updates.

### `spec/`

Project specifications documenting architecture, schemas, and page requirements:

- **`spec-architecture-app.md`** - Application-level architecture and design patterns
- **`spec-architecture-pages.md`** - Page layout architecture, sidebar navigation, and responsive design
- **`spec-page-manage-cards.md`** - Admin card management page specification
- **`spec-page-set-library.md`** - Set Library page specification with search and sort requirements
- **`spec-schema-data-models.md`** - Database schema and data model definitions
- **`spec-schema-users.md`** - User schema and authentication specifications

**Usage**: These specifications follow EARS notation (Easy Approach to Requirements Syntax) and serve as the source of truth for implementation. AI agents reference these documents to ensure consistency and adherence to requirements.

## Acknowledgments

- [Laravel Framework](https://laravel.com)
- [Inertia.js](https://inertiajs.com)
- [Vue.js](https://vuejs.org)
- [Scryfall API](https://scryfall.com/docs/api)
- [Tailwind CSS](https://tailwindcss.com)
