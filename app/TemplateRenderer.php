<?php
class TemplateRenderer {
    private $templatePath;
    private $cachePath;

    public function __construct() {
        $this->templatePath = dirname(__DIR__) . '/templates/';
        $this->cachePath = dirname(__DIR__) . '/storage/cache/templates/';

        if (!file_exists($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Render email template
     */
    public function renderEmail($template, $data) {
        $content = $this->loadTemplate('emails/' . $template);
        return $this->replaceVariables($content, $data);
    }

    /**
     * Render PDF template
     */
    public function renderPdf($template, $data) {
        $content = $this->loadTemplate('pdf/' . $template);
        return $this->replaceVariables($content, $data);
    }

    /**
     * Load template file
     */
    private function loadTemplate($name) {
        $file = $this->templatePath . $name . '.html';
        
        if (!file_exists($file)) {
            throw new Exception("Template not found: $name");
        }

        return file_get_contents($file);
    }

    /**
     * Replace template variables
     */
    private function replaceVariables($content, $data) {
        // Replace simple variables
        $content = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($data) {
            $key = trim($matches[1]);
            return $this->getValue($data, $key);
        }, $content);

        // Handle conditional blocks
        $content = preg_replace_callback('/\{\{#if ([^}]+)\}\}(.*?)\{\{\/if\}\}/s', function($matches) use ($data) {
            $condition = trim($matches[1]);
            $innerContent = $matches[2];
            
            if ($this->evaluateCondition($condition, $data)) {
                return $innerContent;
            }
            return '';
        }, $content);

        // Handle loops
        $content = preg_replace_callback('/\{\{#each ([^}]+)\}\}(.*?)\{\{\/each\}\}/s', function($matches) use ($data) {
            $arrayKey = trim($matches[1]);
            $template = $matches[2];
            $result = '';
            
            $array = $this->getValue($data, $arrayKey);
            if (is_array($array)) {
                foreach ($array as $item) {
                    $result .= $this->replaceVariables($template, $item);
                }
            }
            
            return $result;
        }, $content);

        return $content;
    }

    /**
     * Get nested array value using dot notation
     */
    private function getValue($data, $key) {
        $keys = explode('.', $key);
        $value = $data;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return '';
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Evaluate template condition
     */
    private function evaluateCondition($condition, $data) {
        // Handle simple existence check
        if (strpos($condition, ' ') === false) {
            return !empty($this->getValue($data, $condition));
        }

        // Handle comparisons
        if (preg_match('/([^ ]+) (==|!=|>|<|>=|<=) ([^ ]+)/', $condition, $matches)) {
            $left = $this->getValue($data, $matches[1]);
            $operator = $matches[2];
            $right = $matches[3];

            // Remove quotes from string literals
            if (preg_match('/^["\'](.+)["\']$/', $right, $m)) {
                $right = $m[1];
            } else if (is_numeric($right)) {
                $right = $right + 0; // Convert to number
            } else {
                $right = $this->getValue($data, $right);
            }

            switch ($operator) {
                case '==': return $left == $right;
                case '!=': return $left != $right;
                case '>': return $left > $right;
                case '<': return $left < $right;
                case '>=': return $left >= $right;
                case '<=': return $left <= $right;
            }
        }

        return false;
    }

    /**
     * Format currency amount
     */
    private function formatCurrency($amount) {
        return number_format($amount, 2);
    }

    /**
     * Format date
     */
    private function formatDate($date, $format = 'F j, Y') {
        return date($format, strtotime($date));
    }

    /**
     * Generate QR code data URL
     */
    private function generateQrCode($data) {
        // In production, use a QR code library
        // For now, return placeholder
        return 'data:image/png;base64,placeholder';
    }

    /**
     * Cache rendered template
     */
    private function cacheTemplate($key, $content) {
        $file = $this->cachePath . md5($key) . '.html';
        file_put_contents($file, $content);
    }

    /**
     * Get cached template
     */
    private function getCachedTemplate($key) {
        $file = $this->cachePath . md5($key) . '.html';
        
        if (file_exists($file)) {
            return file_get_contents($file);
        }

        return null;
    }

    /**
     * Clear template cache
     */
    public function clearCache() {
        $files = glob($this->cachePath . '*.html');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Add custom helper function
     */
    public function addHelper($name, $callback) {
        if (!is_callable($callback)) {
            throw new Exception("Helper must be callable");
        }

        $this->helpers[$name] = $callback;
    }

    /**
     * Call custom helper function
     */
    private function callHelper($name, $args) {
        if (!isset($this->helpers[$name])) {
            throw new Exception("Helper not found: $name");
        }

        return call_user_func_array($this->helpers[$name], $args);
    }

    /**
     * Escape HTML special characters
     */
    private function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Convert HTML to PDF
     */
    public function convertToPdf($html, $options = []) {
        // In production, use a PDF library like TCPDF or Dompdf
        // For now, save HTML
        $file = tempnam(sys_get_temp_dir(), 'pdf_') . '.html';
        file_put_contents($file, $html);
        return $file;
    }

    /**
     * Send email with template
     */
    public function sendEmail($to, $subject, $template, $data) {
        $html = $this->renderEmail($template, $data);
        
        // In production, use configured email service
        // For now, save to file
        $file = dirname(__DIR__) . '/storage/emails/' . time() . '_' . md5($to) . '.html';
        file_put_contents($file, $html);
        
        return basename($file);
    }
}
