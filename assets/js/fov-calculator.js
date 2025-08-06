/**
 * FOV Calculator for Sim Racing - JavaScript (No jQuery)
 * Version: 1.0.1
 * Copyright (c) 2024. All rights reserved.
 */

console.log('FOV Calculator Script Loaded - No jQuery Version');

(function() {
    'use strict';
    
    var FOVCalculator = {
        
        // Initialize the calculator
        init: function() {
            this.bindEvents();
            this.initSliders();
            this.calculate();
        },
        
        // Helper function to get element by ID
        $: function(id) {
            return document.getElementById(id);
        },
        
        // Bind all event handlers
        bindEvents: function() {
            var self = this;
            
            // Dropdowns
            this.$('ratio').addEventListener('change', function() {
                self.calculate();
            });
            
            this.$('screens').addEventListener('change', function() {
                self.calculate();
            });
            
            // Checkbox
            this.$('curved').addEventListener('change', function() {
                self.toggleCurved();
            });
            
            // Sliders with debouncing
            var calculateDebounced = this.debounce(this.calculate.bind(this), 100);
            
            ['screensize', 'distance', 'bezel', 'radius'].forEach(function(id) {
                self.$(id).addEventListener('input', calculateDebounced);
            });
        },
        
        // Initialize slider displays
        initSliders: function() {
            var self = this;
            var sliders = ['screensize', 'distance', 'bezel', 'radius'];
            
            sliders.forEach(function(id) {
                var slider = self.$(id);
                var valueDisplay = self.$(id + 'Value');
                
                // Set initial value
                valueDisplay.textContent = slider.value;
                
                slider.addEventListener('input', function() {
                    valueDisplay.textContent = this.value;
                });
            });
        },
        
        // Toggle curved monitor options
        toggleCurved: function() {
            var isChecked = this.$('curved').checked;
            this.$('radiusContainer').style.display = isChecked ? 'block' : 'none';
            this.calculate();
        },
        
        // Main calculation function - sends to server
        calculate: function() {
            console.log('Starting calculation...');
            
            var data = {
                action: 'fov_calculate',
                nonce: fov_ajax.nonce,
                ratio: this.$('ratio').value,
                screens: this.$('screens').value,
                screensize: this.$('screensize').value,
                distance: this.$('distance').value,
                bezel: this.$('bezel').value,
                curved: this.$('curved').checked,
                radius: this.$('radius').value
            };
            
            console.log('Sending data:', data);
            this.$('fov').innerHTML = '<div class="fov-loading">Calculating...</div>';
            
            // Create form data
            var formData = new FormData();
            for (var key in data) {
                formData.append(key, data[key]);
            }
            
            // Send AJAX request
            fetch(fov_ajax.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(response) {
                if (response.success) {
                    FOVCalculator.displayResults(response.data);
                } else {
                    FOVCalculator.$('fov').innerHTML = '<div class="fov-error">Error: ' + (response.data || 'Calculation failed') + '</div>';
                    console.error('FOV Calculation Error:', response);
                }
            })
            .catch(function(error) {
                FOVCalculator.$('fov').innerHTML = '<div class="fov-error">Error: ' + error.message + '</div>';
                console.error('AJAX Error:', error);
            });
        },
        
        // Display calculation results
        displayResults: function(results) {
            var groups = {
                'Horizontal FOV': [],
                'Vertical FOV': [],
                'Special Cases': []
            };
            
            // Group results by type
            results.forEach(function(item) {
                if (item.type === 'hfov') {
                    groups['Horizontal FOV'].push(item);
                } else if (item.type === 'vfov') {
                    groups['Vertical FOV'].push(item);
                } else {
                    groups['Special Cases'].push(item);
                }
            });
            
            // Build HTML
            var html = '<div class="results-grid">';
            
            for (var groupName in groups) {
                if (groups[groupName].length > 0) {
                    html += '<div class="result-group">';
                    html += '<h4 class="group-title">' + groupName + '</h4>';
                    html += '<div class="results-list">';
                    
                    groups[groupName].forEach(function(item) {
                        var specialClass = (item.game.indexOf('divide') !== -1 || item.type === 'hfovrad' || item.type === 'tangle') ? ' special' : '';
                        html += '<div class="result-item' + specialClass + '">';
                        html += '<span class="game-name">' + FOVCalculator.escapeHtml(item.game) + '</span>';
                        html += '<span class="game-value">' + item.value + item.unit + '</span>';
                        html += '</div>';
                    });
                    
                    html += '</div></div>';
                }
            }
            
            html += '</div>';
            this.$('fov').innerHTML = html;
        },
        
        // Escape HTML for security
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },
        
        // Debounce function for performance
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('fov-calculator')) {
                FOVCalculator.init();
            }
        });
    } else {
        // DOM is already ready
        if (document.getElementById('fov-calculator')) {
            FOVCalculator.init();
        }
    }
    
})();