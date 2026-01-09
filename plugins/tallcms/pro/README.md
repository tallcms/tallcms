# TallCMS Pro

Advanced blocks, analytics integration, and premium features for TallCMS.

## Features

### 9 Premium Blocks

| Block | Description |
|-------|-------------|
| **Accordion** | Collapsible content sections with multi-open option |
| **Tabs** | Horizontal/vertical tabbed content |
| **Counter** | Animated number counters with prefix/suffix |
| **Table** | Responsive data tables with styling options |
| **Comparison** | Side-by-side feature comparison tables |
| **Video** | YouTube, Vimeo, and self-hosted video embed |
| **Before/After** | Image comparison slider (horizontal/vertical) |
| **Code Snippet** | Syntax highlighting with 10+ themes and copy button |
| **Map** | OpenStreetMap, Google Maps, and Mapbox integration |

### Analytics Dashboard

- Google Analytics 4 integration
- Visitors, pageviews, bounce rate, session duration
- Top pages and traffic sources
- Visitor trend chart
- Period selector (24h, 7d, 30d, 90d)

### License System

- Anystack.sh integration for license validation
- 24-hour validation cache
- 7-day offline grace period
- Dashboard license status widget

## Requirements

- TallCMS ^1.0
- PHP ^8.2
- Valid TallCMS Pro license key

## Installation

1. Log in to your TallCMS admin panel
2. Go to **Settings > Plugins**
3. Click **Upload Plugin**
4. Select the `tallcms-pro-x.x.x.zip` file
5. Click **Install**

The plugin manager will automatically run migrations and register the plugin.

## License Activation

1. Go to **Settings > Pro License** in the admin panel
2. Enter your license key from your Anystack purchase
3. Click **Activate License**

Your license will be validated and cached for 24 hours. If our validation server is unreachable, your license will continue working for up to 7 days.

## Block Usage

All Pro blocks appear in the Rich Content Editor under the "Pro" category.

### Accordion Block

Collapsible sections perfect for FAQs and organized content.

**Options:**
- Allow multiple sections open simultaneously
- Default expanded state
- Custom section titles and content

### Tabs Block

Organize content into tabbed sections.

**Options:**
- Horizontal or vertical layout
- Custom tab labels
- Rich content per tab

### Counter Block

Animated statistics that count up when visible.

**Options:**
- Start and end values
- Animation duration
- Prefix/suffix (e.g., "$", "%", "+")
- Decimal places
- Title and description

### Table Block

Responsive data tables with professional styling.

**Options:**
- Header row toggle
- Striped rows
- Hover highlighting
- Column alignment
- Responsive horizontal scroll

### Comparison Block

Side-by-side feature comparison tables.

**Options:**
- Multiple comparison columns
- Feature rows with check/cross icons
- Highlight featured column
- Custom column headers

### Video Block

Embed videos from multiple sources.

**Supported Sources:**
- YouTube (with privacy-enhanced mode option)
- Vimeo
- Self-hosted (MP4, WebM)

**Options:**
- Autoplay, loop, muted
- Custom aspect ratios
- Lazy loading

### Before/After Block

Image comparison slider for showing transformations.

**Options:**
- Horizontal or vertical orientation
- Custom slider position
- Before/after labels
- Rounded corners

### Code Snippet Block

Syntax-highlighted code blocks with copy functionality.

**Supported Languages:**
JavaScript, TypeScript, PHP, Python, HTML, CSS, JSON, Bash, SQL, and more.

**Themes:**
Tomorrow Night, Dracula, GitHub Dark, One Dark, Nord, Solarized Dark, Material Dark, Monokai, VS Code Dark, Atom Dark

**Options:**
- Line numbers toggle
- Copy button
- Custom filename display
- Line highlighting (coming soon)

### Map Block

Interactive maps with marker support.

**Providers:**
- OpenStreetMap (free, no API key)
- Google Maps (requires API key)
- Mapbox (requires access token)

**Options:**
- Custom coordinates
- Zoom level (1-20)
- Marker with popup
- Map height

## Google Analytics 4 Setup

The Analytics Dashboard widget requires GA4 Data API access.

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable the **Google Analytics Data API**

### Step 2: Create Service Account

1. Go to **IAM & Admin > Service Accounts**
2. Click **Create Service Account**
3. Name it (e.g., "TallCMS Analytics")
4. Click **Create and Continue**
5. Skip the optional steps, click **Done**

### Step 3: Generate JSON Key

1. Click on your new service account
2. Go to **Keys** tab
3. Click **Add Key > Create new key**
4. Select **JSON** format
5. Download the key file

### Step 4: Add to Google Analytics

1. Go to [Google Analytics](https://analytics.google.com/)
2. Navigate to **Admin > Property > Property Access Management**
3. Click **Add users**
4. Enter the service account email (from JSON file: `client_email`)
5. Grant **Viewer** role

### Step 5: Configure in TallCMS

1. Go to **Settings > Pro Settings > Analytics**
2. Select **Google Analytics 4**
3. Enter your **Property ID** (numeric, found in GA4 Admin > Property Settings)
4. Paste the entire JSON key contents into **Service Account Credentials**
5. Click **Save**

The Analytics Overview widget will now appear on your dashboard.

## Maps Configuration

### OpenStreetMap (Default)

No configuration required. Free and open-source.

### Google Maps

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Enable **Maps JavaScript API**
3. Create an API key
4. Go to **Settings > Pro Settings > Maps**
5. Select **Google Maps**
6. Enter your API key

### Mapbox

1. Go to [Mapbox](https://www.mapbox.com/)
2. Create an account and get an access token
3. Go to **Settings > Pro Settings > Maps**
4. Select **Mapbox**
5. Enter your access token

## Troubleshooting

### Blocks not appearing

- Ensure the plugin is installed and migrations have run
- Check that your license is active
- Clear cache: `php artisan cache:clear`

### Analytics widget shows "Not Configured"

- Verify GA4 Property ID is numeric (e.g., `123456789`)
- Ensure service account JSON is valid
- Check service account has Viewer access to GA4 property
- Test connection in Pro Settings

### Maps not loading

- Verify API key/token is correct
- Check browser console for errors
- Ensure APIs are enabled in Google Cloud (for Google Maps)

### License validation failed

- Check internet connection
- Verify license key is correct
- Contact support if issue persists

## Support

For support, feature requests, or bug reports:

- Email: hello@tallcms.com
- Website: https://tallcms.com/pro

## Changelog

### 1.0.0

- Initial release
- 9 premium blocks
- Google Analytics 4 dashboard widget
- Anystack.sh license integration
