<?php
/**
 * Build Script for ISP Management System
 * Minifies CSS and JavaScript files for production
 */

class AssetBuilder {
    private $cssDir = 'public/assets/css';
    private $jsDir = 'public/assets/js';
    private $buildDir = 'public/assets/build';
    
    public function __construct() {
        // Create build directory if it doesn't exist
        if (!file_exists($this->buildDir)) {
            mkdir($this->buildDir, 0755, true);
        }
    }
    
    /**
     * Minify CSS content
     */
    private function minifyCSS($content) {
        // Remove comments
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        
        // Remove unnecessary whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/;\s*/', ';', $content);
        $content = preg_replace('/:\s*/', ':', $content);
        $content = preg_replace('/\s*{\s*/', '{', $content);
        $content = preg_replace('/\s*}\s*/', '}', $content);
        $content = preg_replace('/\s*,\s*/', ',', $content);
        $content = preg_replace('/\s*>\s*/', '>', $content);
        $content = preg_replace('/\s*\+\s*/', '+', $content);
        $content = preg_replace('/\s*~\s*/', '~', $content);
        
        // Remove trailing semicolons before closing braces
        $content = preg_replace('/;}/', '}', $content);
        
        // Remove leading/trailing whitespace
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Minify JavaScript content
     */
    private function minifyJS($content) {
        // Remove single-line comments (but preserve URLs)
        $content = preg_replace('/(?<!:)\/\/.*$/m', '', $content);
        
        // Remove multi-line comments
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
        
        // Remove unnecessary whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/;\s*/', ';', $content);
        $content = preg_replace('/{\s*/', '{', $content);
        $content = preg_replace('/\s*}/', '}', $content);
        $content = preg_replace('/\s*,\s*/', ',', $content);
        $content = preg_replace('/\s*=\s*/', '=', $content);
        $content = preg_replace('/\s*\+\s*/', '+', $content);
        $content = preg_replace('/\s*-\s*/', '-', $content);
        $content = preg_replace('/\s*\*\s*/', '*', $content);
        $content = preg_replace('/\s*\/\s*/', '/', $content);
        $content = preg_replace('/\s*\(\s*/', '(', $content);
        $content = preg_replace('/\s*\)\s*/', ')', $content);
        $content = preg_replace('/\s*\[\s*/', '[', $content);
        $content = preg_replace('/\s*\]\s*/', ']', $content);
        
        // Remove trailing semicolons before closing braces
        $content = preg_replace('/;}/', '}', $content);
        
        // Remove leading/trailing whitespace
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Build CSS bundle
     */
    public function buildCSS() {
        echo "Building CSS bundle...\n";
        
        $cssFiles = [
            $this->cssDir . '/main.css',
            $this->cssDir . '/dashboard.css',
            $this->cssDir . '/optimized.css'
        ];
        
        $bundle = '';
        $fileSizes = [];
        
        foreach ($cssFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $originalSize = strlen($content);
                $minified = $this->minifyCSS($content);
                $minifiedSize = strlen($minified);
                
                $bundle .= "/* " . basename($file) . " */\n" . $minified . "\n";
                
                $fileSizes[basename($file)] = [
                    'original' => $originalSize,
                    'minified' => $minifiedSize,
                    'savings' => round((($originalSize - $minifiedSize) / $originalSize) * 100, 2)
                ];
                
                echo "  - " . basename($file) . ": {$originalSize} -> {$minifiedSize} bytes (" . $fileSizes[basename($file)]['savings'] . "% reduction)\n";
            }
        }
        
        // Write bundle
        $bundleFile = $this->buildDir . '/bundle.min.css';
        file_put_contents($bundleFile, $bundle);
        
        $totalOriginal = array_sum(array_column($fileSizes, 'original'));
        $totalMinified = strlen($bundle);
        $totalSavings = round((($totalOriginal - $totalMinified) / $totalOriginal) * 100, 2);
        
        echo "CSS bundle created: {$bundleFile}\n";
        echo "Total size: {$totalOriginal} -> {$totalMinified} bytes ({$totalSavings}% reduction)\n\n";
        
        return $bundleFile;
    }
    
    /**
     * Build JavaScript bundle
     */
    public function buildJS() {
        echo "Building JavaScript bundle...\n";
        
        $jsFiles = [
            $this->jsDir . '/optimized.js',
            $this->jsDir . '/main.js',
            $this->jsDir . '/dashboard.js',
            $this->jsDir . '/sidebar.js'
        ];
        
        $bundle = '';
        $fileSizes = [];
        
        foreach ($jsFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $originalSize = strlen($content);
                $minified = $this->minifyJS($content);
                $minifiedSize = strlen($minified);
                
                $bundle .= "/* " . basename($file) . " */\n" . $minified . "\n";
                
                $fileSizes[basename($file)] = [
                    'original' => $originalSize,
                    'minified' => $minifiedSize,
                    'savings' => round((($originalSize - $minifiedSize) / $originalSize) * 100, 2)
                ];
                
                echo "  - " . basename($file) . ": {$originalSize} -> {$minifiedSize} bytes (" . $fileSizes[basename($file)]['savings'] . "% reduction)\n";
            }
        }
        
        // Write bundle
        $bundleFile = $this->buildDir . '/bundle.min.js';
        file_put_contents($bundleFile, $bundle);
        
        $totalOriginal = array_sum(array_column($fileSizes, 'original'));
        $totalMinified = strlen($bundle);
        $totalSavings = round((($totalOriginal - $totalMinified) / $totalOriginal) * 100, 2);
        
        echo "JavaScript bundle created: {$bundleFile}\n";
        echo "Total size: {$totalOriginal} -> {$totalMinified} bytes ({$totalSavings}% reduction)\n\n";
        
        return $bundleFile;
    }
    
    /**
     * Generate asset manifest
     */
    public function generateManifest() {
        $manifest = [
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'css' => [
                'bundle' => '/assets/build/bundle.min.css',
                'size' => file_exists($this->buildDir . '/bundle.min.css') ? filesize($this->buildDir . '/bundle.min.css') : 0
            ],
            'js' => [
                'bundle' => '/assets/build/bundle.min.js',
                'size' => file_exists($this->buildDir . '/bundle.min.js') ? filesize($this->buildDir . '/bundle.min.js') : 0
            ]
        ];
        
        $manifestFile = $this->buildDir . '/manifest.json';
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
        
        echo "Asset manifest created: {$manifestFile}\n";
        return $manifestFile;
    }
    
    /**
     * Build all assets
     */
    public function build() {
        echo "Starting asset build process...\n\n";
        
        $cssBundle = $this->buildCSS();
        $jsBundle = $this->buildJS();
        $manifest = $this->generateManifest();
        
        echo "Build completed successfully!\n";
        echo "Generated files:\n";
        echo "  - {$cssBundle}\n";
        echo "  - {$jsBundle}\n";
        echo "  - {$manifest}\n";
        
        return [
            'css' => $cssBundle,
            'js' => $jsBundle,
            'manifest' => $manifest
        ];
    }
    
    /**
     * Clean build directory
     */
    public function clean() {
        if (file_exists($this->buildDir)) {
            $files = glob($this->buildDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            echo "Build directory cleaned.\n";
        }
    }
}

// Run build if script is executed directly
if (php_sapi_name() === 'cli' || isset($_GET['build'])) {
    $builder = new AssetBuilder();
    
    if (isset($_GET['clean'])) {
        $builder->clean();
    } else {
        $builder->build();
    }
}
?>