<?php
/**
 * Main FOV Calculator Plugin Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FOV_Calculator {
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Register shortcode
        add_shortcode('fov_calculator', array($this, 'render_calculator'));
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add AJAX handlers
        add_action('wp_ajax_fov_calculate', array($this, 'ajax_calculate'));
        add_action('wp_ajax_nopriv_fov_calculate', array($this, 'ajax_calculate'));
    }
    
    /**
     * Enqueue plugin assets
     */
    public function enqueue_assets() {
        // Only load on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'fov_calculator')) {
            wp_enqueue_style(
                'fov-calculator-style',
                FOV_CALC_PLUGIN_URL . 'assets/css/fov-calculator.css',
                array(),
                FOV_CALC_VERSION
            );
            
            wp_enqueue_script(
                'fov-calculator-script',
                FOV_CALC_PLUGIN_URL . 'assets/js/fov-calculator.js',
                array(), // No dependencies
                FOV_CALC_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('fov-calculator-script', 'fov_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fov_calculator_nonce')
            ));
        }
    }
    
    /**
     * Render the calculator shortcode
     */
    public function render_calculator($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'theme' => 'default'
        ), $atts);
        
        ob_start();
        ?>
        <div id="fov-calculator" class="fov-theme-<?php echo esc_attr($atts['theme']); ?>">
            <div class="fov-header">
                <h2><?php _e('FOV Calculator for Sim Racing', 'fov-calculator'); ?></h2>
                <div class="fov-separator"></div>
                <p class="fov-subtitle"><?php _e('Calculate the perfect field of view for your sim racing setup', 'fov-calculator'); ?></p>
            </div>
            
            <div class="fov-controls">
                <div class="controls-grid">
                    <div class="control-group">
                        <label for="ratio"><?php _e('Screen Ratio', 'fov-calculator'); ?></label>
                        <div class="select-wrapper">
                            <select name="ratio" id="ratio">
                                <option value="16_9">16:9</option>
                                <option value="16_10">16:10</option>
                                <option value="21_9">21:9</option>
                                <option value="24_10">24:10</option>
                                <option value="32_9">32:9</option>
                                <option value="32_10">32:10</option>
                                <option value="5_4">5:4</option>
                                <option value="4_3">4:3</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label for="screens"><?php _e('Monitor Setup', 'fov-calculator'); ?></label>
                        <div class="select-wrapper">
                            <select name="screens" id="screens">
                                <option value="1"><?php _e('Single Screen', 'fov-calculator'); ?></option>
                                <option value="3"><?php _e('Triple Screens', 'fov-calculator'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="slider-group">
                    <div class="control-group">
                        <div class="label-row">
                            <label for="screensize"><?php _e('Screen Size', 'fov-calculator'); ?></label>
                            <span class="value-display"><span id="screensizeValue">27</span> <?php _e('inch', 'fov-calculator'); ?></span>
                        </div>
                        <input type="range" id="screensize" min="20" max="80" value="27" class="modern-slider">
                    </div>
                    
                    <div class="control-group">
                        <div class="label-row">
                            <label for="distance"><?php _e('Distance to Screen', 'fov-calculator'); ?></label>
                            <span class="value-display"><span id="distanceValue">50</span> <?php _e('cm', 'fov-calculator'); ?></span>
                        </div>
                        <input type="range" id="distance" min="30" max="200" value="50" class="modern-slider">
                    </div>
                    
                    <div class="control-group">
                        <div class="label-row">
                            <label for="bezel"><?php _e('Bezel Thickness', 'fov-calculator'); ?></label>
                            <span class="value-display"><span id="bezelValue">0</span> <?php _e('mm', 'fov-calculator'); ?></span>
                        </div>
                        <input type="range" id="bezel" min="0" max="100" value="0" class="modern-slider">
                    </div>
                </div>
                
                <div class="checkbox-section">
                    <div class="control-group checkbox-group">
                        <label for="curved" class="modern-checkbox">
                            <input type="checkbox" id="curved" name="curved"> 
                            <span class="checkbox-custom"></span>
                            <span><?php _e('Curved monitor(s)', 'fov-calculator'); ?></span>
                        </label>
                    </div>
                    
                    <div id="radiusContainer" class="control-group" style="display:none;">
                        <div class="label-row">
                            <label for="radius"><?php _e('Radius of curve', 'fov-calculator'); ?></label>
                            <span class="value-display"><span id="radiusValue">1000</span> <?php _e('mm', 'fov-calculator'); ?></span>
                        </div>
                        <input type="range" id="radius" min="50" max="3000" value="1000" step="10" class="modern-slider">
                    </div>
                </div>
            </div>
            
            <div id="fov" class="fov-results">
                <div class="fov-loading"><?php _e('Calculating...', 'fov-calculator'); ?></div>
            </div>
            
            <div class="fov-footer">
                <p class="fov-copyright">
                    <?php _e('FOV Calculator V2 by SIMRACINGCOCKPIT.gg', 'fov-calculator'); ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle AJAX calculation requests
     */
    public function ajax_calculate() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fov_calculator_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        try {
            // Get input values
            $ratio = sanitize_text_field($_POST['ratio']);
            $screens = intval($_POST['screens']);
            $screensize = floatval($_POST['screensize']);
            $distance = floatval($_POST['distance']);
            $bezel = floatval($_POST['bezel']);
            $curved = isset($_POST['curved']) && $_POST['curved'] === 'true';
            $radius = floatval($_POST['radius']);
            
            // Validate inputs
            if ($distance <= 0 || $screensize <= 0) {
                wp_send_json_error('Invalid input values');
                return;
            }
            
            // Perform calculations server-side for security
            $results = $this->calculate_fov($ratio, $screens, $screensize, $distance, $bezel, $curved, $radius);
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            wp_send_json_error('Calculation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Core FOV calculation logic
     */
    private function calculate_fov($ratio, $screens, $screensize, $distance, $bezel, $curved, $radius) {
        // This keeps the calculations server-side and harder to copy
        $ratio_parts = explode('_', $ratio);
        $x = intval($ratio_parts[0]);
        $y = intval($ratio_parts[1]);
        
        $screensizeDiagonal = $screensize * 2.54;
        $distanceToScreenInCm = $distance;
        $bezelThickness = ($bezel / 10) * 2;
        $radiusInMm = $radius;
        
        $aspectRatioToSize = sqrt(($screensizeDiagonal * $screensizeDiagonal) / (($x * $x) + ($y * $y)));
        $width = ($x * $aspectRatioToSize) + ($screens > 1 ? $bezelThickness : 0);
        
        if ($curved) {
            $hAngle = $this->calc_curved_angle($width, $radiusInMm, $distanceToScreenInCm);
        } else {
            $hAngle = $this->calc_triangular_angle($width, $distanceToScreenInCm);
        }
        
        $vAngle = 2 * atan2(tan($hAngle / 2) * $y, $x);
        $arcConstant = 180 / M_PI;
        
        // Game configurations
        $games = $this->get_game_configurations();
        $results = array();
        
        foreach ($games as $calcGroup => $gameList) {
            foreach ($gameList as $gameName => $game) {
                if ($gameName === 'hFov' || $gameName === 'vFov') continue;
                
                $value = 0;
                $unit = '°';
                
                switch ($calcGroup) {
                    case 'hfov':
                        $value = $arcConstant * ($hAngle * $screens);
                        break;
                    case 'vfov':
                        $value = $arcConstant * $vAngle;
                        break;
                    case 'hfovrad':
                        $value = $arcConstant * $this->calc_triangular_angle($width / $x * $y / 3 * 4, $distanceToScreenInCm);
                        $unit = 'rad';
                        break;
                    case 'tangle':
                        $value = $arcConstant * $hAngle;
                        break;
                }
                
                $value *= $game['factor'];
                
                if (isset($game['min'])) {
                    $value = max($value, $game['min']);
                }
                if (isset($game['max'])) {
                    $value = min($value, $game['max']);
                }
                
                if ($calcGroup === 'hfovrad') {
                    $value *= (M_PI / 180);
                }
                
                $results[] = array(
                    'game' => $gameName,
                    'value' => number_format($value, $game['decimals']),
                    'unit' => $unit,
                    'type' => $calcGroup
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Calculate triangular angle
     */
    private function calc_triangular_angle($baseInCm, $distanceToMonitorInCm) {
        return atan2($baseInCm / 2, $distanceToMonitorInCm) * 2;
    }
    
    /**
     * Calculate curved angle
     */
    private function calc_curved_angle($baseInCm, $radiusInMm, $distanceToMonitorInCm) {
        $radiusInCm = $radiusInMm / 10;
        $arcAngleAtRadius = $baseInCm / $radiusInCm;
        $b = $radiusInCm * (1 - cos($arcAngleAtRadius / 2));
        $c = sqrt((2 * $radiusInCm * $b) - ($b * $b));
        return 2 * atan2($c, $distanceToMonitorInCm - $b);
    }
    
    /**
     * Get game configurations
     */
    private function get_game_configurations() {
        return array(
            'hfov' => array(
                "hFov" => array(
                    'decimals' => 0,
                    'factor' => 1
                ),
                "iRacing" => array(
                    'min' => 20,
                    'max' => 170,
                    'decimals' => 1,
                    'factor' => 1
                ),
                "Project CARS 1/2" => array(
                    'min' => 35,
                    'max' => 180,
                    'decimals' => 0,
                    'factor' => 1
                ),
                "Automobilista 2" => array(
                    'min' => 20,
                    'max' => 160,
                    'decimals' => 0,
                    'factor' => 1
                ),
                "BeamNG.drive" => array(
                    'min' => 10,
                    'max' => 120,
                    'decimals' => 1,
                    'factor' => 1
                ),
                "European & American Truck Simulator" => array(
                    'min' => 35,
                    'max' => 180,
                    'decimals' => 0,
                    'factor' => 1
                ),
                "RaceRoom Racing Experience" => array(
                    'min' => 35,
                    'max' => 180,
                    'decimals' => 1,
                    'factor' => 1,
                ),
            ),
            'hfovrad' => array(
                "Richard Burns Rally" => array(
                    'min' => 10,
                    'max' => 180,
                    'decimals' => 6,
                    'factor' => 1
                )
            ),
            'vfov' => array(
                "vFov" => array(
                    'decimals' => 0,
                    'factor' => 1
                ),
                "Assetto Corsa, Assetto Corsa Competizione" => array(
                    'min' => 10,
                    'max' => 120,
                    'decimals' => 1,
                    'factor' => 1
                ),
                "rFactor 1 & 2, GSC, GSCE, SCE, AMS" => array(
                    'min' => 10,
                    'max' => 100,
                    'decimals' => 0,
                    'factor' => 1
                ),
                "Le Mans Ultimate" => array(
                    'min' => 10,
                    'max' => 100,
                    'decimals' => 0,
                    'factor' => 1
                ),
                "F1 23, F1 24 (divide result by 2)" => array(
                    'min' => 10,
                    'max' => 115,
                    'decimals' => 0,
                    'factor' => 2
                ),
                "DiRT Rally 1/2, GRID Autosport (divide result by 2)" => array(
                    'min' => 10,
                    'max' => 115,
                    'decimals' => 0,
                    'factor' => 2
                ),
                "EA Sports WRC (divide result by 2)" => array(
                    'min' => 10,
                    'max' => 115,
                    'decimals' => 0,
                    'factor' => 2
                )
            ),
            'tangle' => array(
                "Triple Screen Angle" => array(
                    'min' => 10,
                    'max' => 180,
                    'decimals' => 2,
                    'factor' => 1
                )
            )
        );
    }
}
