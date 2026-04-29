# Cloudflare Web Analytics Extension

A Paymenter extension that integrates Cloudflare Web Analytics to track visitor metrics, page views, and performance data across your Paymenter installation.

## Features

- **Automatic Tracking**: Tracks all page views across your entire Paymenter installation
- **Privacy-Friendly**: Cloudflare Web Analytics is cookie-free and respects user privacy
- **Performance Metrics**: Monitor Core Web Vitals and page load times
- **Visitor Insights**: Track unique visitors, page views, referrers, and more
- **Easy Setup**: Simple configuration through the Paymenter admin panel

## Installation

1. **Copy Extension Files**

   Place the `CloudflareAnalytics` folder in your Paymenter installation:
   ```
   /var/www/paymenter/extensions/Others/CloudflareAnalytics/
   ```

2. **Register Extension**

   Run the following commands to register the extension:
   ```bash
   composer dump-autoload
   php artisan cache:clear
   ```

3. **Install Extension in Admin Panel**

   - Navigate to your Paymenter admin panel
   - Go to **Extensions** → **Others**
   - Click the **Install Extension** button
   - Select **CloudflareAnalytics** from the list
   - Click **Install**

## Configuration

### 1. Get Your Cloudflare Beacon Token

1. Log in to your [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Navigate to **Analytics & Logs** → **Web Analytics**
3. Click **Add a site** or select an existing site
4. Copy your **Beacon Token** (it looks like: `abc123def456...`)

### 2. Configure the Extension

1. In your Paymenter admin panel, go to **Extensions** → **Others**
2. Find **CloudflareAnalytics** and click **Edit**
3. Enable the extension by toggling the **Enabled** switch
4. Paste your **Beacon Token** in the configuration field
5. Click **Save**

## What Gets Tracked

Once enabled, Cloudflare Web Analytics automatically tracks:

- **Page Views**: Total number of pages viewed
- **Unique Visitors**: Number of unique visitors to your site
- **Top Pages**: Most visited pages on your Paymenter installation
- **Referrers**: Where your visitors are coming from
- **Countries**: Geographic distribution of your visitors
- **Devices**: Desktop vs Mobile traffic
- **Browsers**: Browser usage statistics
- **Core Web Vitals**: Performance metrics (LCP, FID, CLS)

## Viewing Analytics

After installation and configuration:

1. Visit your [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Go to **Analytics & Logs** → **Web Analytics**
3. Select your site to view detailed analytics
4. Data typically appears within a few minutes of the first visit

## Technical Details

### How It Works

The extension injects the Cloudflare Web Analytics beacon script into the `<head>` section of every page on your Paymenter installation:

```html
<!-- Cloudflare Web Analytics -->
<script defer src='https://static.cloudflareinsights.com/beacon.min.js'
        data-cf-beacon='{"token": "your_token_here"}'></script>
<!-- End Cloudflare Web Analytics -->
```

### Files Structure

```
CloudflareAnalytics/
├── CloudflareAnalytics.php          # Main extension class
├── README.md                         # This file
└── resources/
    └── views/
        └── script.blade.php          # Analytics script template
```

### Extension Metadata

- **Name**: Cloudflare Web Analytics
- **Version**: 1.0.0
- **Author**: Onyx
- **Author URL**: https://builtbybit.com/members/onyxdigital.600543/

## Privacy & GDPR Compliance

Cloudflare Web Analytics is designed with privacy in mind:

- **Cookie-free**: No cookies are set on visitors' browsers
- **No personal data collection**: Does not collect personally identifiable information
- **GDPR compliant**: Respects user privacy and complies with GDPR requirements
- **No fingerprinting**: Does not use device fingerprinting techniques

## Troubleshooting

### Extension doesn't appear in the list

1. Make sure files are in the correct location: `extensions/Others/CloudflareAnalytics/`
2. Run `composer dump-autoload` and `php artisan cache:clear`
3. Refresh your browser and check again

### Script not appearing on pages

1. Verify the extension is **enabled** in the admin panel
2. Make sure you've entered a valid **Beacon Token**
3. Clear browser cache and check page source (right-click → View Page Source)
4. Search for "cloudflare" or "beacon.min.js" in the HTML

### No data in Cloudflare Dashboard

1. Wait a few minutes - data can take time to appear
2. Visit your Paymenter site to generate some traffic
3. Verify the beacon token is correct
4. Check that the script is loading without errors (F12 → Console)

## Support

For support or questions about this extension:

- **Author**: Onyx
- **Profile**: https://builtbybit.com/members/onyxdigital.600543/

For Cloudflare Web Analytics support:
- **Cloudflare Docs**: https://developers.cloudflare.com/analytics/web-analytics/
- **Cloudflare Support**: https://support.cloudflare.com/

## License

This extension is provided as-is for use with Paymenter installations.

## Changelog

### Version 1.0.0
- Initial release
- Cloudflare Web Analytics integration
- Admin panel configuration
- Automatic script injection on all pages
