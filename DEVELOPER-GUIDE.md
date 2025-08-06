# FOV Calculator for Sim Racing - Developer Guide

## Project Overview
**Plugin Name**: FOV Calculator for Sim Racing  
**Current Version**: 1.0.1  
**Purpose**: Calculate optimal field of view settings for sim racing monitor setups  
**Website**: https://simracingcockpit.gg/fov-calculator  
**Author**: Richard Baxter

## Plugin Architecture

### Directory Structure
```
fov-calculator/
├── fov-calculator.php          # Main plugin file
├── readme.txt                  # WordPress.org readme
├── README.md                   # GitHub readme
├── CHANGELOG.md                # Version history
├── .gitignore                  # Git exclusions
├── assets/                     # Frontend resources
│   ├── css/
│   │   └── fov-calculator.css  # Responsive styles
│   └── js/
│       └── fov-calculator.js   # Vanilla JS (no jQuery)
└── includes/
    └── class-fov-calculator.php # Main plugin class
```

## Key Components

### Main Plugin File (fov-calculator.php)
- **Version Locations**:
  - Plugin header: `* Version: 1.0.1`
  - PHP constant: `define('FOV_CALC_VERSION', '1.0.1');`
- **Entry Point**: Initialises plugin on `plugins_loaded` hook
- **Constants Defined**:
  - `FOV_CALC_VERSION` - Plugin version
  - `FOV_CALC_PLUGIN_DIR` - Plugin directory path
  - `FOV_CALC_PLUGIN_URL` - Plugin URL
  - `FOV_CALC_PLUGIN_FILE` - Main plugin file path

### Main Class (includes/class-fov-calculator.php)
- **Core Methods**:
  - `init()` - Register shortcode, assets, and AJAX handlers
  - `enqueue_assets()` - Load CSS/JS only on pages with shortcode
  - `render_calculator()` - Output calculator HTML
  - `ajax_calculate()` - Handle AJAX calculation requests
  - `calculate_fov()` - Server-side FOV calculations
  - `get_game_configurations()` - Game-specific FOV settings

### JavaScript (assets/js/fov-calculator.js)
- **Pure vanilla JavaScript** - No jQuery dependency
- **Key Features**:
  - Debounced input handling for performance
  - AJAX calculations via native fetch API
  - Responsive event binding
  - Error handling with user feedback
  - Console logging for debugging
### CSS Styling (assets/css/fov-calculator.css)
- **Design Philosophy**: Modern gradient design with racing theme
- **Responsive Breakpoints**:
  - Desktop: Default styles
  - Tablet: ≤768px adjustments
  - Mobile: ≤600px full responsive
- **Key Design Elements**:
  - Gradient background (#000F41 to #1a2356)
  - Orange accent colour (#FB643C)
  - Smooth animations and transitions
  - Modern form controls with custom styling

## Shortcode System

### Basic Usage
```
[fov_calculator]
```

### Supported Attributes
- `theme` - Visual theme (default: 'default')

### Future Extensibility
The shortcode system is designed to support additional attributes:
```php
$atts = shortcode_atts(array(
    'theme' => 'default',
    // Future: 'units' => 'metric',
    // Future: 'games' => 'all',
    // Future: 'style' => 'compact'
), $atts);
```

## Calculation Engine

### Supported Monitor Configurations
- **Screen Ratios**: 16:9, 16:10, 21:9, 24:10, 32:9, 32:10, 5:4, 4:3
- **Monitor Setups**: Single screen, Triple screens
- **Curved Monitors**: Adjustable radius support (1000-4000mm)

### Input Parameters
1. **Screen Size**: 10-65 inches (diagonal)
2. **Distance**: 20-150 cm from screen
3. **Bezel Width**: 0-50mm (for triple screens)
4. **Curved Radius**: 1000-4000mm (optional)

### Calculation Methods
- **Triangular Angle**: For flat monitors
- **Curved Angle**: For curved monitors using radius
- **Vertical FOV**: Calculated from horizontal FOV and aspect ratio

### Game Configurations
The plugin supports multiple calculation types:
- **hFov**: Horizontal field of view (most games)
- **vFov**: Vertical field of view
- **hFovRad**: Horizontal FOV in radians
- **tAngle**: Triple screen angle

## Supported Games List

### Racing Simulators
- **iRacing** (20-170°, 1 decimal)
- **Assetto Corsa / ACC** (10-120°)
- **Project CARS 1/2** (35-180°)
- **Automobilista 2** (20-160°)
- **rFactor 2** (30-150°)- **RaceRoom Racing Experience** (35-180°)
- **Le Mans Ultimate** (30-150°)
- **BeamNG.drive** (10-120°)

### Rally Games
- **DiRT Rally 1/2** (30-150°)
- **EA Sports WRC** (30-150°)
- **Richard Burns Rally** (radians)

### Formula 1
- **F1 23/24** (-0.5 to 0.5 offset)

### Truck Simulators
- **Euro Truck Simulator 2** (35-180°)
- **American Truck Simulator** (35-180°)

## AJAX Implementation

### Request Format
```javascript
{
    action: 'fov_calculate',
    nonce: 'security_nonce',
    ratio: '16_9',
    screens: '1',
    screensize: '27',
    distance: '60',
    bezel: '10',
    curved: false,
    radius: '1800'
}
```

### Response Format
```javascript
{
    success: true,
    data: [
        {
            game: "iRacing",
            value: "74.5",
            unit: "°",
            type: "hfov"
        },
        // ... more games
    ]
}
```

### Error Handling
- Nonce verification for security
- Input validation and sanitisation
- Graceful error messages to user

## Security Considerations

### Direct Access Prevention
```php
if (!defined('ABSPATH')) {
    exit;
}
```

### AJAX Security
- Nonce verification: `wp_verify_nonce()`
- Capability checks where needed
- Input sanitisation for all parameters
- Server-side calculations prevent manipulation

### Data Validation
- Screen size: 10-65 inches
- Distance: 20-150 cm
- Bezel: 0-50 mm
- Radius: 1000-4000 mm
## Development Workflow

### Version Update Process
1. Update version in 3 locations:
   - `fov-calculator.php` (header comment)
   - `fov-calculator.php` (FOV_CALC_VERSION constant)
   - `readme.txt` (Stable tag)
   - `CHANGELOG.md` (new entry)

2. Update version in asset files:
   - `fov-calculator.js` header comment
   - `fov-calculator.css` header comment

3. Commit with message:
   ```
   chore(release): bump version to X.Y.Z
   ```

### Adding New Features

#### New Game Support
1. Add to `get_game_configurations()` method
2. Specify calculation type (hfov, vfov, etc.)
3. Set min/max limits if applicable
4. Define decimal places for display
5. Test calculations thoroughly

#### New Input Controls
1. Add HTML in `render_calculator()`
2. Add event binding in JavaScript
3. Include in AJAX data object
4. Validate in `ajax_calculate()`
5. Update calculation logic if needed

#### New Screen Ratios
1. Add option to ratio dropdown
2. Format as `X_Y` (e.g., '16_9')
3. Test with various screen sizes

## Performance Optimisation

### Asset Loading
- **Conditional enqueueing**: Only loads on pages with shortcode
- **Version hashing**: Ensures cache busting on updates
- **No external dependencies**: Fast loading, no CDN delays

### JavaScript Performance
- **Debounced inputs**: 100ms delay prevents excessive calculations
- **Event delegation**: Efficient event handling
- **Native APIs**: No jQuery overhead

### Server Performance
- **Lightweight calculations**: Pure PHP maths
- **No database queries**: All calculations in memory
- **Caching friendly**: Results can be cached by browsers

## Responsive Design

### Mobile Adaptations
```css
@media (max-width: 600px) {
    /* Stack controls vertically */
    /* Larger touch targets */
    /* Adjusted typography */
}
```
### Tablet Optimisations
- Two-column layout for controls
- Maintained visual hierarchy
- Touch-friendly slider controls

## Internationalisation

### Text Domain
- **Domain**: `fov-calculator`
- **Path**: `/languages`

### Translatable Strings
```php
__('FOV Calculator for Sim Racing', 'fov-calculator');
_e('Calculate the perfect field of view', 'fov-calculator');
```

### Adding Translations
1. Generate POT file using tools
2. Create PO/MO files for languages
3. Place in `/languages` directory
4. Name format: `fov-calculator-{locale}.mo`

## Testing Checklist

### Functionality Tests
- [ ] Single screen calculations correct
- [ ] Triple screen calculations correct
- [ ] Curved monitor maths accurate
- [ ] All games show appropriate values
- [ ] Min/max limits respected
- [ ] AJAX requests successful
- [ ] Error handling works

### Browser Testing
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari
- [ ] Mobile browsers

### Responsive Testing
- [ ] Desktop (1200px+)
- [ ] Tablet (768px)
- [ ] Mobile (320px-600px)

### Security Testing
- [ ] Nonce verification works
- [ ] Invalid inputs handled
- [ ] Direct file access blocked

## Common Issues & Solutions

### Calculator Not Appearing
- Check shortcode is correct: `[fov_calculator]`
- Verify JavaScript console for errors
- Ensure no JavaScript conflicts

### AJAX Errors
- Check nonce is being generated
- Verify AJAX URL is correct
- Check PHP error logs

### Styling Issues
- Theme conflicts - use more specific selectors
- Check for !important overrides
- Verify CSS is loading
## Future Enhancement Ideas

### Planned Features
1. **Save/Load Configurations**
   - User profiles
   - Preset configurations
   - Share via URL

2. **Advanced Calculations**
   - Multi-monitor bezels
   - Angled side monitors
   - VR headset support

3. **Visual Representations**
   - Canvas/SVG diagram
   - 3D visualisation
   - Setup preview

4. **Export Options**
   - PDF guide generation
   - Game config files
   - Setup checklist

### Code Extensibility Points
- Shortcode attributes for customisation
- Filter hooks for calculations
- Action hooks for output
- JavaScript events for integration

## Deployment & Distribution

### WordPress.org Submission
1. Ensure GPL v2+ compliance
2. Remove any external service dependencies
3. Follow WordPress coding standards
4. Include comprehensive readme.txt

### GitHub Repository
- Public repository for transparency
- Use semantic versioning
- Tag releases properly
- Include comprehensive documentation

### Auto-updates
Consider implementing GitHub updater class similar to SimRacing Affiliate Tables for seamless updates.

## Debug Mode

### Enable Debugging
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### JavaScript Debugging
Console logs are included throughout:
```javascript
console.log('FOV Calculator Script Loaded');
console.log('Sending data:', data);
console.log('Calculation complete:', results);
```

## Contributing Guidelines

### Code Standards
- Follow WordPress Coding Standards
- Use meaningful variable names
- Comment complex calculations
- Maintain consistent formatting
### Pull Request Process
1. Fork repository
2. Create feature branch
3. Make changes with tests
4. Submit PR with description
5. Ensure all checks pass

### Commit Message Format
```
type(scope): subject

- feat: New feature
- fix: Bug fix
- docs: Documentation
- style: Formatting
- refactor: Code restructuring
- test: Test additions
- chore: Maintenance
```

## Technical Calculations Reference

### Basic FOV Formula
```
FOV = 2 * arctan(screen_width / (2 * distance))
```

### Triple Screen Adjustment
```
Total FOV = Single Screen FOV * 3
```

### Curved Monitor Calculation
```
Arc angle = screen_width / radius
Adjusted FOV based on viewing angle
```

### Aspect Ratio Conversion
```
Vertical FOV = 2 * arctan(tan(hFOV/2) * height/width)
```

## Resources & References

### Calculation Sources
- iRacing FOV calculator methodology
- Real-world optics principles
- Community-validated formulas

### WordPress Development
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Security Best Practices](https://developer.wordpress.org/plugins/security/)

### Sim Racing Communities
- r/simracing FOV discussions
- iRacing forums
- Various sim racing setup guides

---

**Plugin developed by Richard Baxter**  
**Website**: https://simracingcockpit.gg  
**License**: GPL v2 or later